<?php

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baut die JSON-Antworten der App-API (siehe docs/openapi.yaml): Envelope {data, meta},
 * ETag mit 304-Antwort und Cache-Control.
 */
class ApiResponder
{
    public function json(Request $request, mixed $data, array $meta = [], int $maxAge = 120): Response
    {
        $payload = ['data' => $data];
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        $response = new JsonResponse();
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $response->setData($payload);

        $response->setPublic();
        $response->setMaxAge($maxAge);
        $response->setEtag(md5((string) $response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    public function error(int $status, string $code, string $message): Response
    {
        $response = new JsonResponse(['error' => ['code' => $code, 'message' => $message]], $status);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
