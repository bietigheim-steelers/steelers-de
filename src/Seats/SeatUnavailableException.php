<?php

namespace App\Seats;

/**
 * Ein Platz liess sich nicht belegen. Die Meldung ist der Schluessel, den das
 * Formular kennt (seat_already_booked, seat_non_existent).
 */
class SeatUnavailableException extends \RuntimeException
{
}
