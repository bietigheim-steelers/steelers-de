<?php

namespace App\Seats;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Hält Sitzplätze befristet und bucht sie endgültig.
 *
 * Bis hierher fiel ein Konflikt erst beim Absenden des gesamten Formulars
 * auf: Platz wählen, Bezahlung, Adresse, Zustimmungen -- und dann
 * "seat_already_booked". Jetzt wird der Platz schon bei der Auswahl
 * gehalten.
 *
 * Ein Hold ist bewusst KEIN eigener seat_status, sondern steht in
 * reserved_until und hold_token. Sonst würde das Freigeben eines Platzes,
 * der vorher "reserved" war (Vorbehalt für den bisherigen Inhaber), diesen
 * Vorbehalt löschen.
 *
 * Der Ablauf ist ein Query-Prädikat und kein Cronjob: abgelaufen zählt
 * einfach als frei. Damit stimmt der Zustand auch dann, wenn Contaos
 * request-getriebener Cron gerade nicht läuft.
 */
class SeatHoldService
{
    public const TABLE = 'tl_tilastot_seats';

    /** Wie lange ein Platz gehalten wird. */
    public const HOLD_SECONDS = 15 * 60;

    public function __construct(
        private readonly Connection $connection,
        private readonly SeatInventory $inventory,
    ) {
    }

    /**
     * Belegung für das Frontend.
     *
     * @return array{booked: list<string>, reserved: list<string>, held: list<string>}
     */
    public function availability(string $ownToken = ''): array
    {
        $now = time();

        $rows = $this->connection->fetchAllAssociative(
            'SELECT seat_block, seat_row, seat_seat, seat_status, reserved_until, hold_token
               FROM ' . self::TABLE . '
              WHERE seat_status IN (\'booked\', \'reserved\', \'non-existent\')
                 OR reserved_until > :now',
            ['now' => $now]
        );

        $out = ['booked' => [], 'reserved' => [], 'held' => []];

        foreach ($rows as $row) {
            $id = $row['seat_block'] . '_' . $row['seat_row'] . '_' . $row['seat_seat'];

            // Nicht existente Plätze sind für das Frontend wie verkauft:
            // nicht wählbar.
            if ($row['seat_status'] === 'booked' || $row['seat_status'] === 'non-existent') {
                $out['booked'][] = $id;
                continue;
            }

            $heldByOther = (int) $row['reserved_until'] > $now
                && $row['hold_token'] !== ''
                && $row['hold_token'] !== $ownToken;

            if ($heldByOther) {
                $out['held'][] = $id;
                continue;
            }

            if ($row['seat_status'] === 'reserved') {
                $out['reserved'][] = $id;
            }
        }

        return $out;
    }

    /**
     * Hält einen oder mehrere benachbarte Plätze für diesen Besucher.
     *
     * @throws SeatUnavailableException
     *
     * @return int Zeitpunkt, bis zu dem gehalten wird
     */
    public function hold(string $block, string $row, int $firstSeat, int $seats, string $token): int
    {
        $this->assertSeatsExist($block, $row, $firstSeat, $seats);

        $now = time();
        $until = $now + self::HOLD_SECONDS;

        $this->connection->transactional(function (Connection $conn) use (
            $block, $row, $firstSeat, $seats, $token, $now, $until
        ): void {
            // Zuerst die bisherigen Plätze dieses Besuchers freigeben, sonst
            // sammelt jemand beim Umwählen die halbe Reihe ein.
            $this->releaseWith($conn, $token, $now);

            for ($i = 0; $i < $seats; $i++) {
                $seat = (string) ($firstSeat + $i);
                $this->claim($conn, $block, $row, $seat, $token, $now, $until);
            }
        });

        return $until;
    }

    /**
     * Bucht die gehaltenen Plätze endgültig.
     *
     * @throws SeatUnavailableException
     */
    public function book(string $block, string $row, int $firstSeat, int $seats, string $token): void
    {
        $now = time();

        $this->connection->transactional(function (Connection $conn) use (
            $block, $row, $firstSeat, $seats, $token, $now
        ): void {
            for ($i = 0; $i < $seats; $i++) {
                $seat = (string) ($firstSeat + $i);

                // Belegbar ist der Platz, wenn er frei ist oder wenn dieser
                // Besucher ihn selbst hält. Ein fremder, noch laufender Hold
                // blockiert.
                $updated = $conn->executeStatement(
                    'UPDATE ' . self::TABLE . "
                        SET seat_status = 'booked', reserved_until = 0, hold_token = '', tstamp = :now
                      WHERE seat_block = :block AND seat_row = :row AND seat_seat = :seat
                        AND seat_status IN ('available', 'reserved')
                        AND (reserved_until <= :now OR hold_token = :token)",
                    compact('block', 'row', 'seat', 'token', 'now')
                );

                if ($updated === 1) {
                    continue;
                }

                // Beim Buchen gibt es kein "gehört mir schon": wäre der Platz
                // frei und von uns gehalten, hätte das UPDATE gegriffen.
                $this->inspectAfterMiss($conn, $block, $row, $seat, $token, $now);

                // Zeile fehlt noch: anlegen. Der Unique-Key über
                // (seat_block, seat_row, seat_seat) lässt nur einen Gewinner zu.
                try {
                    $conn->insert(self::TABLE, [
                        'tstamp' => $now,
                        'seat_block' => $block,
                        'seat_row' => $row,
                        'seat_seat' => $seat,
                        'seat_status' => 'booked',
                        'reserved_until' => 0,
                        'hold_token' => '',
                    ]);
                } catch (UniqueConstraintViolationException) {
                    throw new SeatUnavailableException('seat_already_booked');
                }
            }
        });
    }

    /**
     * Der erste freie Rollstuhlplatz eines Blocks, oder null.
     *
     * Berücksichtigt jetzt auch fremde Holds -- vorher galt nur "booked" als
     * belegt, zwei gleichzeitige Bestellungen bekamen denselben Platz.
     */
    public function nextFreeWheelchairSeat(string $block, string $token = ''): ?int
    {
        $capacity = $this->inventory->wheelchairCapacity($block);

        if ($capacity === null) {
            return null;
        }

        $now = time();

        $taken = $this->connection->fetchAllAssociative(
            'SELECT seat_seat, seat_status, reserved_until, hold_token
               FROM ' . self::TABLE . '
              WHERE seat_block = :block AND seat_row = \'1\'',
            ['block' => $block]
        );

        $blocked = [];

        foreach ($taken as $row) {
            $heldByOther = (int) $row['reserved_until'] > $now
                && $row['hold_token'] !== ''
                && $row['hold_token'] !== $token;

            if (in_array($row['seat_status'], ['booked', 'non-existent'], true) || $heldByOther) {
                $blocked[(int) $row['seat_seat']] = true;
            }
        }

        for ($seat = 1; $seat <= $capacity; $seat++) {
            if (!isset($blocked[$seat])) {
                return $seat;
            }
        }

        return null;
    }

    /** Gibt alle Plätze frei, die dieser Besucher hält. */
    public function release(string $token): int
    {
        if ($token === '') {
            return 0;
        }

        return $this->releaseWith($this->connection, $token, time());
    }

    private function releaseWith(Connection $conn, string $token, int $now): int
    {
        return (int) $conn->executeStatement(
            'UPDATE ' . self::TABLE . "
                SET reserved_until = 0, hold_token = '', tstamp = :now
              WHERE hold_token = :token AND seat_status != 'booked'",
            ['token' => $token, 'now' => $now]
        );
    }

    /**
     * @throws SeatUnavailableException
     */
    private function claim(
        Connection $conn,
        string $block,
        string $row,
        string $seat,
        string $token,
        int $now,
        int $until,
    ): void {
        $updated = $conn->executeStatement(
            'UPDATE ' . self::TABLE . "
                SET reserved_until = :until, hold_token = :token, tstamp = :now
              WHERE seat_block = :block AND seat_row = :row AND seat_seat = :seat
                AND seat_status IN ('available', 'reserved')
                AND (reserved_until <= :now OR hold_token = :token)",
            compact('block', 'row', 'seat', 'token', 'now', 'until')
        );

        if ($updated === 1) {
            return;
        }

        // Halten wir den Platz schon mit genau diesem Ablauf, zählt MySQL
        // keine geänderte Zeile -- das ist Erfolg, kein Konflikt.
        if ($this->inspectAfterMiss($conn, $block, $row, $seat, $token, $now) === 'ours') {
            return;
        }

        try {
            $conn->insert(self::TABLE, [
                'tstamp' => $now,
                'seat_block' => $block,
                'seat_row' => $row,
                'seat_seat' => $seat,
                'seat_status' => 'available',
                'reserved_until' => $until,
                'hold_token' => $token,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new SeatUnavailableException('seat_already_booked');
        }
    }

    /**
     * Das UPDATE hat keine Zeile geändert. Warum?
     *
     * @throws SeatUnavailableException wenn der Platz belegt ist
     *
     * @return string "missing" (Zeile anlegen) oder "ours" (hält dieser
     *                Besucher bereits, nichts zu tun)
     */
    private function inspectAfterMiss(
        Connection $conn,
        string $block,
        string $row,
        string $seat,
        string $token,
        int $now,
    ): string {
        $existing = $conn->fetchAssociative(
            'SELECT seat_status, reserved_until, hold_token FROM ' . self::TABLE . '
              WHERE seat_block = :block AND seat_row = :row AND seat_seat = :seat',
            compact('block', 'row', 'seat')
        );

        if ($existing === false) {
            return 'missing';
        }

        if ($existing['seat_status'] === 'non-existent') {
            throw new SeatUnavailableException('seat_non_existent');
        }

        if ($existing['seat_status'] === 'booked') {
            throw new SeatUnavailableException('seat_already_booked');
        }

        if ($token !== '' && $existing['hold_token'] === $token) {
            return 'ours';
        }

        if ((int) $existing['reserved_until'] > $now) {
            throw new SeatUnavailableException('seat_on_hold');
        }

        throw new SeatUnavailableException('seat_already_booked');
    }

    /**
     * @throws SeatUnavailableException
     */
    private function assertSeatsExist(string $block, string $row, int $firstSeat, int $seats): void
    {
        $ok = $this->inventory->isWheelchairBlock($block)
            ? $firstSeat >= 1 && $firstSeat <= ($this->inventory->wheelchairCapacity($block) ?? 0)
            : $this->inventory->seatRangeExists($block, $row, $firstSeat, $seats);

        if (!$ok) {
            throw new SeatUnavailableException('seat_non_existent');
        }
    }
}
