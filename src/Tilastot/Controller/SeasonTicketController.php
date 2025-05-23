<?php

namespace App\Tilastot\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Contao\Email;

class SeasonTicketController
{
  public function order(Request $request, MailerInterface $mailer, ContaoFramework $framework): Response
  {

    $framework->initialize();
    $data = $request->request->all();

    $data['price'] = self::getTicketPrice($data['ticket_area'], $data['ticket_category'], substr($data['seat_block'], -1), $data['ticket_type']);

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

    $email2->to('ticketing@steelers.de');
    $email2->htmlTemplate('@Contao_App/email_season_ticket_order.html.twig');
    $eventimCategory = $this->getEventimCategory(
      $data['ticket_area'],
      $data['ticket_category'],
      substr($data['seat_block'], -1),
      $data['ff_new_dk']
    );

    $email2->context(array_merge($data, [
      'raw_data' => json_encode($data, JSON_PRETTY_PRINT),
      'eventim_category' => $eventimCategory
    ]));

    $mailer->send($email2);

    // Add the selected ticket to the booked seats file
    $filePath = '/usr/www/users/steelg/2022/files/steelers/tools/seatingPlan/booked_seats.json';

    if (file_exists($filePath)) {
      $bookedSeats = json_decode(file_get_contents($filePath), true);
      $baseSeat = (int)$data['seat_seat'];
      $block = $data['seat_block'];
      $row = $data['seat_row'];
      $seatsToBlock = 1;

      if (in_array($data['ticket_category'], ['familie1', 'familie2'])) {
        $seatsToBlock = 3;
      } elseif ($data['ticket_category'] === 'familie3') {
        $seatsToBlock = 4;
      }

      for ($i = 0; $i < $seatsToBlock; $i++) {
        $seatNum = $baseSeat + $i;
        $newSeat = $block . '_' . $row . '_' . $seatNum;
        if (!in_array($newSeat, $bookedSeats['booked'])) {
          $bookedSeats['booked'][] = $newSeat;
        }
      }
      file_put_contents($filePath, json_encode($bookedSeats, JSON_PRETTY_PRINT));
    }


    return new Response('order successful');
  }

  private function getPrices($type, $block, $category): int
  {
    $prices = [
      "plus" => [
        "A,G" => [
          "vollzahler" => 785,
          "ermaessigt" => 677,
          "jugendlich" => 465,
          "kind" => 392,
          "behinderung" => 392,
        ],
        "B,F,H,L" => [
          "vollzahler" => 680,
          "ermaessigt" => 572,
          "jugendlich" => 418,
          "kind" => 334,
          "behinderung" => 334,
        ],
        "C,I,K" => [
          "vollzahler" => 575,
          "ermaessigt" => 502,
          "jugendlich" => 337,
          "kind" => 287,
          "behinderung" => 287,
          "familie1" => 855,
          "familie2" => 1133,
          "familie3" => 1296,
        ],
        J => [
          "vollzahler" => 418,
          "ermaessigt" => 370,
          "jugendlich" => 263,
          "kind" => 203,
          "behinderung" => 203,
        ],
        "R1,R3,R4" => [
          "rollstuhl" => 360,
        ],
      ],
      "basic" => [
        "A,G" => [
          "vollzahler" => 686,
          "ermaessigt" => 592,
          "jugendlich" => 405,
          "kind" => 343,
          "behinderung" => 343,
        ],
        "B,F,H,L" => [
          "vollzahler" => 592,
          "ermaessigt" => 499,
          "jugendlich" => 364,
          "kind" => 291,
          "behinderung" => 291,
        ],
        "C,I,K" => [
          "vollzahler" => 499,
          "ermaessigt" => 436,
          "jugendlich" => 291,
          "kind" => 249,
          "behinderung" => 249,
          "familie1" => 748,
          "familie2" => 998,
          "familie3" => 1144,
        ],
        J => [
          "vollzahler" => 364,
          "ermaessigt" => 322,
          "jugendlich" => 129.74,
          "kind" => 129.74,
          "behinderung" => 176,
        ],
        "R1,R3,R4" => [
          "rollstuhl" => 312,
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
