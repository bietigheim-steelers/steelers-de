<?php

namespace App\Controller;

use App\Seats\SeatHoldService;
use App\Seats\SeatUnavailableException;
use App\SeasonTicket\PriceTable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Hält einen Platz während der Bestellung und gibt ihn wieder frei.
 *
 * Wer den Platz hält, steht in einem HttpOnly-Cookie. Der Browser muss den
 * Wert also weder kennen noch mitschicken, und die Belegungsabfrage kann die
 * eigenen Holds von fremden unterscheiden.
 */
class SeatHoldController
{
    public const COOKIE = 'dk_hold';

    private const COOKIE_LIFETIME = 2 * 60 * 60;

    public function __construct(
        private readonly SeatHoldService $holds,
        private readonly RateLimiterFactory $orderLimiter,
    ) {
    }

    public function hold(Request $request): Response
    {
        $token = $this->token($request);

        // Halten ist für einen Angreifer attraktiver als Bestellen: es kostet
        // nichts und blockiert Plätze. Zwei Dinge begrenzen das -- dieses
        // Limit, und dass ein Token immer nur einen Platzblock gleichzeitig
        // hält (SeatHoldService::hold gibt vorher alles andere frei).
        if (!$this->orderLimiter->create('hold:' . $token)->consume()->isAccepted()) {
            return $this->json($request, ['error' => 'too_many_requests'], 429, $token);
        }

        $block = trim((string) $request->request->get('seat_block', ''));
        $row = trim((string) $request->request->get('seat_row', ''));
        $seat = (int) $request->request->get('seat_seat', 0);
        $category = (string) $request->request->get('ticket_category', '');

        if ($block === '' || $row === '' || $seat < 1) {
            return $this->json($request, ['error' => 'invalid_seat'], 400, $token);
        }

        $seats = PriceTable::seatsFor($category);

        try {
            $until = $this->holds->hold($block, $row, $seat, $seats, $token);
        } catch (SeatUnavailableException $exception) {
            return $this->json(
                $request,
                ['error' => $exception->getMessage(), 'availability' => $this->holds->availability($token)],
                409,
                $token
            );
        }

        return $this->json($request, [
            'until' => $until,
            'seconds' => max(0, $until - time()),
            'seats' => $seats,
            'availability' => $this->holds->availability($token),
        ], 200, $token);
    }

    public function release(Request $request): Response
    {
        $token = $this->token($request);
        $this->holds->release($token);

        return $this->json($request, ['released' => true, 'availability' => $this->holds->availability($token)], 200, $token);
    }

    private function token(Request $request): string
    {
        $existing = (string) $request->cookies->get(self::COOKIE, '');

        // Nur unser eigenes Format akzeptieren, damit ein manipuliertes
        // Cookie nicht zu seltsamen Datenbankwerten führt.
        return preg_match('/^[a-f0-9]{32}$/', $existing) ? $existing : bin2hex(random_bytes(16));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function json(Request $request, array $data, int $status, string $token): JsonResponse
    {
        $response = new JsonResponse($data, $status);

        $response->headers->setCookie(
            Cookie::create(self::COOKIE, $token)
                ->withExpires(time() + self::COOKIE_LIFETIME)
                ->withPath('/')
                ->withHttpOnly(true)
                // Nicht fest auf true: über http (lokale Entwicklung) würde
                // der Browser ein Secure-Cookie verwerfen.
                ->withSecure($request->isSecure())
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
