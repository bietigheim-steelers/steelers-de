<?php

namespace App\Tilastot\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

use Contao\Database;
use Symfony\Component\Mime\Address;
use App\Tilastot\Model\SeasonTicket;
use App\Tilastot\Model\Seats;

class SeasonTicketController
{
  public function order(Request $request, MailerInterface $mailer, ContaoFramework $framework): Response
  {


    $framework->initialize();
    $data = $request->request->all();

    $seatBookingData = $this->resolveSeatBookingData($data);

    if ($seatBookingData === null) {
      return new Response('seat_non_existent', 400);
    }

    $baseSeat = $seatBookingData['baseSeat'];
    $block = $seatBookingData['block'];
    $row = $seatBookingData['row'];
    $seatsToBlock = $seatBookingData['seatsToBlock'];

    $data['price'] = self::getTicketPrice(
      $data['ticket_area'],
      $data['ticket_category'],
      substr($block, -1),
      $data['ticket_type']
    );

    for ($i = 0; $i < $seatsToBlock; $i++) {
      $seatNum = $baseSeat + $i;

      // Check if seat exists in database
      $seat = Seats::findOneBy(['seat_block=?', 'seat_row=?', 'seat_seat=?'], [$block, $row, $seatNum]);

      if ($seat) {
        // Seat exists - check status
        if ($seat->seat_status === 'booked') {
          return new Response('seat_already_booked', 400);
        } elseif ($seat->seat_status === 'non-existent') {
          return new Response('seat_non_existent', 400);
        } else {
          // Seat is available or reserved - mark as booked
          $seat->seat_status = 'booked';
          $seat->tstamp = time();
          $seat->save();
        }
      } else {
        // Seat doesn't exist in database - create it as booked
        $newSeat = new Seats();
        $newSeat->seat_block = $block;
        $newSeat->seat_row = $row;
        $newSeat->seat_seat = $seatNum;
        $newSeat->seat_status = 'booked';
        $newSeat->tstamp = time();
        $newSeat->save();
      }
    }
    // Save to database using SeasonTicket model
    $seasonTicket = new SeasonTicket();
    foreach ($data as $key => $value) {
      $seasonTicket->$key = $value;
    }

    // Generate unique random order number (e.g., 100000-999999)
    do {
      $randomNumber = random_int(100000, 999999);

      $objResult = Database::getInstance()
        ->prepare("SELECT id FROM tl_tilastot_season_ticket WHERE order_number = ?")
        ->execute($randomNumber);
    } while ($objResult->numRows > 0); // Keep generating until unique

    $seasonTicket->order_number = $randomNumber;
    $seasonTicket->tstamp = time();
    $seasonTicket->save();

    $email = new TemplatedEmail();
    $email->subject('Steelers Dauerkarte - Bestellung');
    $email->from(new Address('webseite@steelers.de', 'Bietigheim Steelers'));
    $email->replyTo('ticketing@steelers.de');

    $email->to($data['customer_email']);
    $email->htmlTemplate('@Contao_App/email_season_ticket_confirmation.html.twig');
    $email->context($data);

    $mailer->send($email);


    $email2 = new TemplatedEmail();
    $email2->subject('Dauerkarte Bestellung - ' . $data['customer_firstname'] . ' ' . $data['customer_name']);
    $email2->from('webseite@steelers.de');
    $email2->replyTo($data['customer_email']);

    $email2->to('dominik.sander@steelers.de');
    $email2->htmlTemplate('@Contao_App/email_season_ticket_order.html.twig');
    $eventimCategory = $this->getEventimCategory(
      $data['ticket_area'],
      $data['ticket_category'],
      substr($block, -1),
      $data['ff_new_dk']
    );

    $email2->context(array_merge($data, [
      'raw_data' => json_encode($data, JSON_PRETTY_PRINT),
      'eventim_category' => $eventimCategory
    ]));

    $mailer->send($email2);

    return new Response('order successful');
  }

  private function resolveSeatBookingData(array $data): ?array
  {
    if (($data['ticket_area'] ?? null) === 'rollstuhl') {
      $rollstuhlBlock = $this->normalizeBlock($data['seat_rollstuhl_block'] ?? null);

      if (!in_array($rollstuhlBlock, ['R1', 'R4'], true)) {
        return null;
      }

      $maxSeats = ($rollstuhlBlock === 'R1') ? 2 : 3;
      $nextSeat = $this->findNextAvailableSeat($rollstuhlBlock, 1, $maxSeats);

      if ($nextSeat === null) {
        return null; // No seats available
      }

      return [
        'baseSeat' => $nextSeat,
        'block' => $rollstuhlBlock,
        'row' => 1,
        'seatsToBlock' => 1,
      ];
    }

    if (!isset($data['seat_block'], $data['seat_row'], $data['seat_seat'])) {
      return null;
    }

    $seatsToBlock = 1;

    if (in_array($data['ticket_category'], ['familie1', 'familie2'], true)) {
      $seatsToBlock = 3;
    } elseif (($data['ticket_category'] ?? null) === 'familie3') {
      $seatsToBlock = 4;
    }

    return [
      'baseSeat' => (int) $data['seat_seat'],
      'block' => (string) $data['seat_block'],
      'row' => $data['seat_row'],
      'seatsToBlock' => $seatsToBlock,
    ];
  }

  private function findNextAvailableSeat(string $block, int $row, int $maxSeats): ?int
  {
    for ($seat = 1; $seat <= $maxSeats; $seat++) {
      $existingSeat = Seats::findOneBy(
        ['seat_block=?', 'seat_row=?', 'seat_seat=?'],
        [$block, $row, $seat]
      );

      // If seat doesn't exist OR exists but not booked, it's available
      if (!$existingSeat || $existingSeat->seat_status !== 'booked') {
        return $seat;
      }
    }

    return null; // All seats booked
  }

  private function normalizeBlock(?string $block): ?string
  {
    if ($block === null) {
      return null;
    }

    return preg_replace('/^Block\s+/', '', trim($block));
  }

  private function getPrices($type, $block, $category): int
  {
    $prices = [
      "plus" => [
        "A,G" => [
          "vollzahler" => 855,
          "ermaessigt" => 735,
          "jugendlich" => 510,
          "kind" => 426,
          "behinderung" => 426,
        ],
        "B,F,H,L" => [
          "vollzahler" => 737,
          "ermaessigt" => 618,
          "jugendlich" => 453,
          "kind" => 369,
          "behinderung" => 369,
        ],
        "C,I,K" => [
          "vollzahler" => 621,
          "ermaessigt" => 537,
          "jugendlich" => 360,
          "kind" => 310,
          "behinderung" => 310,
          "familie1" => 948,
          "familie2" => 1249,
          "familie3" => 1435,
        ],
        "J" => [
          "vollzahler" => 430,
          "ermaessigt" => 381,
          "jugendlich" => 275,
          "kind" => 215,
          "behinderung" => 215,
        ],
        "R1,R3,R4" => [
          "rollstuhl" => 383,
        ],
      ],
      "basic" => [
        "A,G" => [
          "vollzahler" => 749,
          "ermaessigt" => 645,
          "jugendlich" => 447,
          "kind" => 375,
          "behinderung" => 375,
        ],
        "B,F,H,L" => [
          "vollzahler" => 645,
          "ermaessigt" => 540,
          "jugendlich" => 395,
          "kind" => 322,
          "behinderung" => 322,
        ],
        "C,I,K" => [
          "vollzahler" => 540,
          "ermaessigt" => 468,
          "jugendlich" => 312,
          "kind" => 270,
          "behinderung" => 270,
          "familie1" => 832,
          "familie2" => 1102,
          "familie3" => 1268,
        ],
        "J" => [
          "vollzahler" => 374,
          "ermaessigt" => 333,
          "jugendlich" => 129.74,
          "kind" => 129.74,
          "behinderung" => 187,
        ],
        "R1,R3,R4" => [
          "rollstuhl" => 333,
        ],
      ],
    ];

    return $prices[$type][$block][$category];
  }
  private function getTicketPrice($area, $category, $block, $type): int
  {
    $reducedCategories = ['rentner', 'student', 'azubi', 'schueler', 'mitglied'];
    $category = in_array($category, $reducedCategories) ? 'ermaessigt' : $category;

    if ($area === 'stehplatz') {
      return $this->getPrices($type, 'J', $category);
    }
    if ($area === 'rollstuhl') {
      return $this->getPrices($type, 'R1,R3,R4', 'rollstuhl');
    }

    $blockMap = [
      'A' => 'A,G',
      'G' => 'A,G',
      'B' => 'B,F,H,L',
      'F' => 'B,F,H,L',
      'H' => 'B,F,H,L',
      'L' => 'B,F,H,L',
      'C' => 'C,I,K',
      'I' => 'C,I,K',
      'K' => 'C,I,K',
    ];

    $mappedBlock = $blockMap[$block] ?? null;

    return $mappedBlock ? $this->getPrices($type, $mappedBlock, $category) : 0;
  }

  public static function getCategoryMap(): array
  {
    return [
      'vollzahler' => 'Vollzahler',
      'familie1' => 'Familienkarte 1',
      'familie2' => 'Familienkarte 2',
      'familie3' => 'Familienkarte 3',
      'rentner' => 'Rentner',
      'student' => 'Student',
      'azubi' => 'Auszubildender',
      'schueler' => 'Schüler über 18 Jahre',
      'mitglied' => 'SC Mitglied',
      'jugendlich' => 'Jugendlicher (13-17 Jahre)',
      'kind' => 'Kind (8-12 Jahre)',
      'behinderung' => 'Fan mit Behinderung ab 50%',
      'rollstuhl' => 'Rollstuhlfahrer inkl. Begleitperson',
    ];
  }

  private function getEventimCategory($area, $category, $block, $ff_new_dk): string
  {
    $blockMap = [
      'A' => 'PK 1',
      'G' => 'PK 1',
      'B' => 'PK 2',
      'F' => 'PK 2',
      'H' => 'PK 2',
      'L' => 'PK 2',
      'C' => 'PK 3',
      'I' => 'PK 3',
      'K' => 'PK 3',
      'J' => 'PK Stehplatz',
    ];

    $categoryMap = [
      'vollzahler' => ' Sitzplatz',
      'ermaessigt' => ' Ermäßigt',
      'jugendlich' => ' Jugendlich',
      'kind' => ' Kind',
      'behinderung' => ' Fan mit Behinderung ab 50 %',
      'familie1' => ' Familienkarte 1',
      'familie2' => ' Familienkarte 2',
      'familie3' => ' Familienkarte 3',
      'rollstuhl' => ' Rollstuhlfahrer + Begleitperson',
    ];

    $mappedBlock = $blockMap[$block] ?? null;
    $mappedCategory = $categoryMap[$category] ?? '';
    $mappedFF = '';

    if ($area === 'stehplatz') {
      $mappedBlock = 'PK Stehplatz';
    } elseif ($area === 'rollstuhl') {
      $mappedBlock = 'PK Rollstuhlfahrer + Begleitperson';
    }

    if ($ff_new_dk) {
      if ($category === 'vollzahler') {
        $mappedCategory = ' Vollzahler';
      }
      $mappedFF .= '_Family_Friends';
    }

    return $mappedBlock . $mappedCategory . $mappedFF;
  }
}
