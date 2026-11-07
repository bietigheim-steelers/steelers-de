<?php

namespace App\Controller;

use App\Seats\SeatHoldService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Die aktuelle Belegung für den Saalplan.
 *
 * Liefert jetzt zusätzlich "held": Plätze, die gerade ein anderer
 * Bestellvorgang hält. Die eigenen Holds bleiben wählbar -- welche das sind,
 * sagt das dk_hold-Cookie.
 */
class SeatsApiController
{
  public function __construct(private readonly SeatHoldService $holds)
  {
  }

  public function getSeats(Request $request): JsonResponse
  {
    $token = (string) $request->cookies->get(SeatHoldController::COOKIE, '');

    $response = new JsonResponse($this->holds->availability($token));

    // Die Antwort haengt am Cookie und veraltet in Sekunden -- nichts davon
    // darf in einen Cache.
    $response->headers->set('Cache-Control', 'no-store, private');

    return $response;
  }
}
