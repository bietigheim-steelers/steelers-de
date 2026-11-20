<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth0\Auth0Client;
use App\Auth0\Auth0Exception;
use App\Auth0\Auth0MemberProvisioner;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Frontend-Login ueber Auth0 (Authorization-Code-Flow mit PKCE).
 *
 * Routen: config/routes.yml (auth0_login, auth0_callback, auth0_logout). Die Routen laufen im
 * Frontend-Scope, damit die Contao-Frontend-Firewall greift und das Mitglied ganz normal
 * ueber die Contao-Session eingeloggt wird.
 */
class Auth0Controller
{
    private const SESSION_KEY = 'auth0_login';

    public function __construct(
        private readonly Auth0Client $client,
        private readonly Auth0MemberProvisioner $provisioner,
        private readonly Security $security,
        #[Autowire(service: 'contao.security.frontend_user_provider')]
        private readonly UserProviderInterface $userProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Link: /auth0/login?target=/mitglieder  (optional signup=1 oeffnet gleich die Registrierung).
     */
    public function login(Request $request): Response
    {
        if (!$this->client->isConfigured()) {
            return $this->error('Der Login ist nicht konfiguriert.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $verifier = bin2hex(random_bytes(32));

        $request->getSession()->set(self::SESSION_KEY, [
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'target' => $this->safeTarget($request->query->get('target')),
        ]);

        return $this->noStore(new RedirectResponse($this->client->buildAuthorizeUrl(
            $this->callbackUrl(),
            $state,
            $nonce,
            $verifier,
            $request->query->getBoolean('signup'),
        )));
    }

    public function callback(Request $request): Response
    {
        if (!$this->client->isConfigured()) {
            return $this->error('Der Login ist nicht konfiguriert.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $session = $request->getSession();
        $stored = $session->get(self::SESSION_KEY);
        // Einmalig: ein erneuter Aufruf der Callback-URL (Reload, Zurueck-Button) scheitert.
        $session->remove(self::SESSION_KEY);

        $state = $request->query->get('state');
        $code = $request->query->get('code');

        if (!\is_array($stored) || !\is_string($state) || !hash_equals($stored['state'], $state)) {
            return $this->error('Die Anmeldung ist abgelaufen. Bitte erneut versuchen.', Response::HTTP_BAD_REQUEST);
        }

        if ($request->query->has('error')) {
            $this->logger->warning('Auth0 login denied', [
                'error' => (string) $request->query->get('error'),
                'description' => mb_substr((string) $request->query->get('error_description'), 0, 300),
            ]);

            return $this->error('Die Anmeldung wurde abgebrochen oder abgelehnt.', Response::HTTP_FORBIDDEN);
        }

        if (!\is_string($code) || '' === $code) {
            return $this->error('Die Anmeldung ist fehlgeschlagen.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $claims = $this->client->fetchClaims($code, $this->callbackUrl(), $stored['verifier'], $stored['nonce']);
            $username = $this->provisioner->provision($claims);
            $user = $this->userProvider->loadUserByIdentifier($username);
        } catch (Auth0Exception $e) {
            $this->logger->error('Auth0 login failed: '.$e->getMessage());

            return $this->error(
                1 === $e->getCode()
                    ? 'Bitte bestätige zuerst deine E-Mail-Adresse und melde dich danach erneut an.'
                    : 'Die Anmeldung ist fehlgeschlagen. Bitte erneut versuchen.',
                Response::HTTP_FORBIDDEN,
            );
        } catch (\Throwable $e) {
            // z. B. gesperrtes oder deaktiviertes Mitglied (UserChecker)
            $this->logger->error('Auth0 login failed: '.$e->getMessage());

            return $this->error('Die Anmeldung ist fehlgeschlagen.', Response::HTTP_FORBIDDEN);
        }

        try {
            // Contaos Erfolgs-Handler verlangt das Formularfeld _target_path (Base64), auch ohne Formular.
            $request->request->set('_target_path', base64_encode($stored['target'] ?? '/'));

            // Gleicher Weg wie das Contao-Loginformular: setzt Token, Session und lastLogin.
            $response = $this->security->login($user, 'contao.security.login_authenticator.contao_frontend', 'contao_frontend');
        } catch (\Throwable $e) {
            $this->logger->error('Auth0 login failed: '.$e->getMessage());

            return $this->error('Das Konto ist nicht für die Anmeldung freigeschaltet.', Response::HTTP_FORBIDDEN);
        }

        // Ohne eigenes Ziel gilt Contaos Logik (Weiterleitungsseite der Mitgliedergruppe).
        if (null !== $stored['target'] || !$response instanceof RedirectResponse) {
            $response = new RedirectResponse($stored['target'] ?? '/');
        }

        return $this->noStore($response);
    }

    public function logout(Request $request): Response
    {
        if ($this->security->getUser()) {
            $this->security->logout(false);
        }

        // Nur die Sitzung dieser Anwendung beenden und alle Session-Daten verwerfen. Die
        // Auth0-Sitzung (SSO) bleibt bewusst bestehen: ein neuer Klick auf "Anmelden" loggt
        // dann ohne erneute Eingabe wieder ein.
        if ($request->hasSession()) {
            $request->getSession()->invalidate();
        }

        // Die Route "contao_root" gibt es nicht (Contao-Seitenrouten sind dynamisch).
        return $this->noStore(new RedirectResponse($request->getUriForPath('/')));
    }

    private function callbackUrl(): string
    {
        return $this->urlGenerator->generate('auth0_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Nur Pfade auf dieser Seite zulassen (kein Open Redirect).
     */
    private function safeTarget(mixed $target): ?string
    {
        if (!\is_string($target) || !preg_match('#^/(?![/\\\\])#', $target) || preg_match('/[\x00-\x1f]/', $target)) {
            return null;
        }

        return $target;
    }

    private function noStore(Response $response): Response
    {
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    private function error(string $message, int $status): Response
    {
        $html = '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><meta name="robots" content="noindex">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1"><title>Anmeldung</title></head>'
            .'<body style="font-family:sans-serif;max-width:32rem;margin:4rem auto;padding:0 1rem">'
            .'<h1>Anmeldung</h1><p>'.htmlspecialchars($message, ENT_QUOTES).'</p>'
            .'<p><a href="/auth0/login">Erneut anmelden</a> · <a href="/">Zur Startseite</a></p></body></html>';

        return $this->noStore(new Response($html, $status));
    }
}
