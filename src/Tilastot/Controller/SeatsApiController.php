<?php

namespace App\Tilastot\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Contao\CoreBundle\Framework\ContaoFramework;
use App\Tilastot\Model\Seats;

class SeatsApiController
{
  public function getSeats(ContaoFramework $framework): JsonResponse
  {
    $framework->initialize();

    // Fetch all seats from database
    $allSeats = Seats::findAll();

    $booked = [];
    $reserved = [];

    if ($allSeats !== null) {
      foreach ($allSeats as $seat) {
        $seatId = $seat->seat_block . '_' . $seat->seat_row . '_' . $seat->seat_seat;
        
        if ($seat->seat_status === 'booked') {
          $booked[] = $seatId;
        } elseif ($seat->seat_status === 'reserved') {
          $reserved[] = $seatId;
        }
      }
    }

    return new JsonResponse([
      'booked' => $booked,
      'reserved' => $reserved
    ]);
  }
}
