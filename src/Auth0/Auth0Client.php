<?php

declare(strict_types=1);

namespace App\Auth0;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Schlanker OIDC-Client fuer den Authorization-Code-Flow (mit PKCE) gegen Auth0.
 *
 * Der ID-Token kommt direkt vom Token-Endpunkt ueber TLS, nicht ueber den Browser. OIDC Core
 * (3.1.3.7, Punkt 6) erlaubt es in diesem Fall, auf die Pruefung der Signatur zu verzichten;
 * Issuer, Audience, Ablauf und Nonce werden trotzdem geprueft. Deshalb braucht es hier keine
 * JWT-Bibliothek.
 *
 * Domain, Client-ID und -Secret stehen in config/parameters.yml (nicht im Git).
 */
class Auth0Client
{
    private const LEEWAY = 60;

    private readonly string $domain;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        string $domain,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {
        // Toleriert "https://domain/" in der Konfiguration.
        $this->domain = rtrim((string) preg_replace('#^https?://#i', '', trim($domain)), '/');
    }

    public function isConfigured(): bool
    {
        return '' !== $this->domain && '' !== $this->clientId && '' !== $this->clientSecret;
    }

    public function buildAuthorizeUrl(string $redirectUri, string $state, string $nonce, string $codeVerifier, bool $signup): string
    {
        $query = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'openid profile email',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ];

        if ($signup) {
            $query['screen_hint'] = 'signup';
        }

        return 'https://'.$this->domain.'/authorize?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function buildLogoutUrl(string $returnTo): string
    {
        return 'https://'.$this->domain.'/v2/logout?'.http_build_query([
            'client_id' => $this->clientId,
            'returnTo' => $returnTo,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Tauscht den Code gegen Tokens und liefert die geprueften Claims des ID-Tokens.
     *
     * @return array<string, mixed>
     *
     * @throws Auth0Exception
     */
    public function fetchClaims(string $code, string $redirectUri, string $codeVerifier, string $nonce): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://'.$this->domain.'/oauth/token', [
                'timeout' => 8,
                'body' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'code_verifier' => $codeVerifier,
                ],
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new Auth0Exception('Token request failed: '.$e->getMessage(), 0, $e);
        }

        if ($status >= 400 || !\is_string($data['id_token'] ?? null)) {
            throw new Auth0Exception(\sprintf(
                'Token endpoint answered %d: %s',
                $status,
                mb_substr((string) ($data['error_description'] ?? $data['error'] ?? 'no id_token'), 0, 300),
            ));
        }

        return $this->validate($data['id_token'], $nonce);
    }

    /**
     * @return array<string, mixed>
     */
    private function validate(string $idToken, string $nonce): array
    {
        $parts = explode('.', $idToken);

        if (3 !== \count($parts)) {
            throw new Auth0Exception('Malformed ID token.');
        }

        $claims = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/'), true), true);

        if (!\is_array($claims)) {
            throw new Auth0Exception('Unreadable ID token payload.');
        }

        if (($claims['iss'] ?? null) !== 'https://'.$this->domain.'/') {
            throw new Auth0Exception('Unexpected issuer: '.json_encode($claims['iss'] ?? null));
        }

        $aud = (array) ($claims['aud'] ?? []);

        if (!\in_array($this->clientId, $aud, true) || (\count($aud) > 1 && ($claims['azp'] ?? null) !== $this->clientId)) {
            throw new Auth0Exception('ID token was not issued for this client.');
        }

        if (!\is_int($claims['exp'] ?? null) || $claims['exp'] + self::LEEWAY < time()) {
            throw new Auth0Exception('ID token expired.');
        }

        if (!\is_string($claims['nonce'] ?? null) || !hash_equals($nonce, $claims['nonce'])) {
            throw new Auth0Exception('Nonce mismatch.');
        }

        if (!\is_string($claims['sub'] ?? null) || '' === $claims['sub']) {
            throw new Auth0Exception('ID token has no subject.');
        }

        return $claims;
    }
}
