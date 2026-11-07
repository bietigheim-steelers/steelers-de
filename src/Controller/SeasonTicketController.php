<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

use Contao\Database;
use Symfony\Component\Mime\Address;
use App\Model\SeasonTicket;
use App\Model\Seats;
use App\Seats\SeatHoldService;
use App\Seats\SeatInventory;
use App\Seats\SeatUnavailableException;
use App\SeasonTicket\PriceTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class SeasonTicketController
{
  /**
   * Felder, die das Formular setzen darf und die als Spalte in
   * tl_tilastot_season_ticket existieren.
   */
  private const ORDER_FIELDS = [
    'ticket_type',
    'ticket_area',
    'ticket_category',
    'seat_block',
    'seat_row',
    'seat_seat',
    'ticket_form',
    'ticket_payment',
    'customer_firstname',
    'customer_name',
    'customer_street',
    'customer_plz',
    'customer_city',
    'customer_phone',
    'customer_email',
    'customer_birthday',
    'customer_last_season',
    'eventim',
    'eventim_email',
    'eventim_account',
    'bemerkung',
    'terms',
    'data_privacy',
  ];

  /**
   * Felder, die das Formular mitschickt, fuer die es aber keine Spalte gibt.
   * Sie werden fuer die Platzaufloesung und die Bestellmail gebraucht und
   * landen bewusst nicht im Datensatz.
   */
  private const TRANSIENT_FIELDS = [
    'seat_rollstuhl_block',
    'customer_member',
  ];

  public function __construct(
    private readonly ContaoFramework $framework,
    private readonly MailerInterface $mailer,
    private readonly SeatInventory $inventory,
    private readonly SeatHoldService $holds,
    private readonly LoggerInterface $logger,
    private readonly RateLimiterFactory $orderLimiter,
  ) {
  }

  public function order(Request $request): Response
  {
    $this->framework->initialize();

    // Bestelldaten gehoeren nicht im Klartext uebers Netz. Lokale
    // Entwicklung laeuft ueber http und bleibt ausgenommen.
    if (!$request->isSecure() && !in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true)) {
      return new Response('https_required', 400);
    }

    // Ohne Limit kann ein Skript die Halle leerbuchen. Geschluesselt wird
    // ueber den Request-Token, nicht ueber die IP: Trusted Proxies sind nicht
    // konfiguriert, getClientIp() liefert hinter einem Proxy also fuer alle
    // dieselbe Adresse -- ein IP-Limit wuerde am Verkaufstag alle gemeinsam
    // ausbremsen. Der Token ist pro Browser stabil und wurde vom
    // RequestTokenListener bereits geprueft.
    $requestToken = (string) $request->request->get('REQUEST_TOKEN', '');
    $limiterKey = $requestToken !== ''
      ? 'token:' . hash('sha256', $requestToken)
      : 'ip:' . ($request->getClientIp() ?? 'unknown');

    if (!$this->orderLimiter->create($limiterKey)->consume()->isAccepted()) {
      return new Response('too_many_requests', 429);
    }

    // Nur bekannte Felder uebernehmen. Vorher landete request->all() in einer
    // Schleife im Model, womit ein praepariertes POST auch paid, pay_date oder
    // order_number setzen konnte. Fehlende Felder bleiben wie bisher ganz weg,
    // damit Spalten mit NOT NULL nicht auf null laufen.
    $data = [];

    foreach ([...self::ORDER_FIELDS, ...self::TRANSIENT_FIELDS] as $field) {
      if (!$request->request->has($field)) {
        continue;
      }

      $value = $request->request->get($field);
      $data[$field] = is_string($value) ? trim($value) : $value;
    }

    $area = $data['ticket_area'] ?? null;

    if (!in_array($area, PriceTable::AREAS, true)) {
      return new Response('invalid_area', 400);
    }

    if (!in_array($data['ticket_type'] ?? null, PriceTable::TYPES, true)) {
      return new Response('invalid_type', 400);
    }

    if (!array_key_exists($data['ticket_category'] ?? '', self::getCategoryMap())) {
      return new Response('invalid_category', 400);
    }

    // Minimalpruefung der Felder, auf die der Code danach zwingend angewiesen
    // ist. Ohne sie lief ein unvollstaendiges POST in einen 500 (etwa
    // $email->to(null)). Die inhaltliche Pflichtfeldpruefung macht weiter das
    // Formular -- hier geht es nur darum, sauber abzulehnen.
    foreach (['customer_email', 'customer_name', 'terms', 'data_privacy'] as $required) {
      if (($data[$required] ?? '') === '') {
        return new Response('missing_field:' . $required, 400);
      }
    }

    if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
      return new Response('invalid_email', 400);
    }

    if ($area === 'stehplatz') {
      // Das Formular blendet die Platzfelder beim Wechsel auf Stehplatz nur
      // aus, schickt veraltete Werte aber weiter mit. In den Datensatz
      // gehoeren sie dann nicht.
      unset($data['seat_block'], $data['seat_row'], $data['seat_seat']);
    }

    // Wer den Platz waehrend der Bestellung gehalten hat, steht im Cookie.
    // Nur dieser Besucher darf ihn jetzt buchen.
    $holdToken = (string) $request->cookies->get(SeatHoldController::COOKIE, '');

    if (in_array($area, ['sitzplatz', 'rollstuhl'], true)) {
      $seatBookingData = $this->resolveSeatBookingData($data, $holdToken);

      // resolveSeatBookingData() kann null liefern: unbekannter Rollstuhlblock,
      // kein freier Rollstuhlplatz oder fehlende seat_*-Felder. Vorher lief der
      // Code dann auf null['baseSeat'], $seatsToBlock wurde null, die Schleife
      // machte nichts -- und die Bestellung ging durch, ohne dass ein Platz
      // blockiert wurde.
      if ($seatBookingData === null) {
        return new Response('seat_unavailable', 400);
      }

      $block = (string) $seatBookingData['block'];
      $row = (string) $seatBookingData['row'];
      $baseSeat = (int) $seatBookingData['baseSeat'];
      $seatsToBlock = (int) $seatBookingData['seatsToBlock'];

      // Gegen den echten Hallenbestand pruefen. Vorher waren Block, Reihe und
      // Platz ungepruefte POST-Werte: einen Platz, den es nicht gibt, hat der
      // Server einfach als gebucht angelegt.
      $knownSeat = $area === 'rollstuhl'
        ? $baseSeat >= 1 && $baseSeat <= ($this->inventory->wheelchairCapacity($block) ?? 0)
        : $this->inventory->seatRangeExists($block, $row, $baseSeat, $seatsToBlock);

      if (!$knownSeat) {
        return new Response('seat_non_existent', 400);
      }

      try {
        $this->holds->book($block, $row, $baseSeat, $seatsToBlock, $holdToken);
      } catch (SeatUnavailableException $exception) {
        return new Response($exception->getMessage(), 400);
      }

      // Den aufgeloesten Platz festhalten. Bei Rollstuhlbestellungen stand im
      // Datensatz bisher kein Platz, weil seat_rollstuhl_block keine Spalte
      // hat und Contao unbekannte Felder beim Speichern verwirft.
      $data['seat_block'] = $block;
      $data['seat_row'] = $row;
      $data['seat_seat'] = (string) $baseSeat;
    }

    $data['price'] = $this->getTicketPrice(
      $area,
      $data['ticket_category'] ?? null,
      $data['seat_block'] ?? null,
      $data['ticket_type'] ?? null
    );

    // Save to database using SeasonTicket model
    $seasonTicket = new SeasonTicket();
    if (($data['customer_birthday'] ?? '') === '') {
      unset($data['customer_birthday']);
    }

    foreach (self::ORDER_FIELDS as $field) {
      if (array_key_exists($field, $data)) {
        $seasonTicket->$field = $data[$field];
      }
    }

    $seasonTicket->price = $data['price'];

    // Generate unique random order number (e.g., 100000-999999)
    do {
      $randomNumber = random_int(100000, 999999);

      $objResult = Database::getInstance()
        ->prepare("SELECT id FROM tl_tilastot_season_ticket WHERE order_number = ?")
        ->execute($randomNumber);
    } while ($objResult->numRows > 0); // Keep generating until unique

    $data['order_number'] = $randomNumber;
    $seasonTicket->order_number = $randomNumber;
    $seasonTicket->tstamp = time();
    $seasonTicket->order_date = time();

    $email = new TemplatedEmail();
    $email->subject('Steelers Dauerkarte - Bestellung ' . $randomNumber);
    $email->from(new Address('webseite@steelers.de', 'Bietigheim Steelers'));
    $email->replyTo('ticketing@steelers.de');

    $email->to($data['customer_email']);
    $email->htmlTemplate('@Contao_App/email_season_ticket_confirmation.html.twig');
    $email->context($data);

    $email2 = new TemplatedEmail();
    // customer_firstname ist im Formular optional, wird hier aber benutzt.
    $email2->subject('Dauerkarte Bestellung - ' . trim(($data['customer_firstname'] ?? '') . ' ' . $data['customer_name']));
    $email2->from('webseite@steelers.de');
    $email2->replyTo($data['customer_email']);

    $email2->to('ticketing@steelers.de');
    $email2->htmlTemplate('@Contao_App/email_season_ticket_order.html.twig');
    // Hier stand bisher $data['ticket_block'] -- ein Feld, das das Formular
    // nie geschickt hat. Dadurch blieb die Preisklasse (PK 1/2/3) bei allen
    // Sitzplatzbestellungen leer. Das Formularfeld heisst seat_block.
    $eventimCategory = $this->getEventimCategory(
      $data['ticket_area'],
      $data['ticket_category'] ?? null,
      $data['seat_block'] ?? null,
      null
    );

    $email2->context(array_merge($data, [
      'raw_data' => json_encode($data, JSON_PRETTY_PRINT),
      'eventim_category' => $eventimCategory
    ]));

    // Die Bestellung ist der verbindliche Datensatz: erst speichern, dann
    // mailen. Vorher gingen die Mails zuerst raus ("sometimes the microsoft
    // mail server is not reachable") -- scheiterte save() danach, hatte der
    // Kunde eine Bestaetigung und es gab keine Bestellung.
    $seasonTicket->save();

    // Scheitert der Mailversand, bleibt die Bestellung bestehen und wird nur
    // protokolliert. Ein 500 wuerde den Besucher erneut absenden lassen -- und
    // weil der Platz jetzt gebucht ist, bekaeme er beim zweiten Versuch
    // "seat_already_booked" auf seine eigene Bestellung.
    // Absichtlich Throwable und nicht nur TransportExceptionInterface: auch
    // eine fehlende Mailer-Konfiguration oder ein Fehler im Mail-Template darf
    // eine bereits gespeicherte Bestellung nicht in einen 500 verwandeln.
    foreach (['Kundenbestaetigung' => $email, 'Ticketing' => $email2] as $label => $message) {
      try {
        $this->sendWithRetry($this->mailer, $message);
      } catch (\Throwable $exception) {
        $this->logger->error(
          'Dauerkarte {order} ist gespeichert, aber die Mail "{label}" ging nicht raus',
          ['order' => $randomNumber, 'label' => $label, 'exception' => $exception]
        );
      }
    }

    return new Response('order successful');
  }
  private function resolveSeatBookingData(array $data, string $holdToken): ?array
  {
    if (($data['ticket_area'] ?? null) === 'rollstuhl') {
      $rollstuhlBlock = $this->normalizeBlock($data['seat_rollstuhl_block'] ?? null);

      if ($rollstuhlBlock === null || !$this->inventory->isWheelchairBlock($rollstuhlBlock)) {
        return null;
      }

      $nextSeat = $this->holds->nextFreeWheelchairSeat($rollstuhlBlock, $holdToken);

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

    return [
      'baseSeat' => (int) $data['seat_seat'],
      'block' => (string) $data['seat_block'],
      'row' => $data['seat_row'],
      // Familienkarten belegen mehrere Plaetze; die Zahl steht in PriceTable.
      'seatsToBlock' => PriceTable::seatsFor($data['ticket_category'] ?? null),
    ];
  }

  private function normalizeBlock(?string $block): ?string
  {
    if ($block === null) {
      return null;
    }

    return preg_replace('/^Block\s+/', '', trim($block));
  }

  private function sendWithRetry(MailerInterface $mailer, TemplatedEmail $email): void
  {
    $maxAttempts = 3;
    $delaysInMicroseconds = [200000, 500000];

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
      try {
        $mailer->send($email);

        return;
      } catch (TransportExceptionInterface $exception) {
        if ($attempt === $maxAttempts) {
          throw $exception;
        }

        usleep($delaysInMicroseconds[$attempt - 1] ?? 500000);
      }
    }
  }

  /**
   * Die Preistabelle liegt in App\SeasonTicket\PriceTable -- frueher stand
   * sie hier noch einmal komplett und musste bei jeder Preisrunde doppelt
   * gepflegt werden (die zweite Kopie war assets/js/TicketPriceCalculator.js).
   *
   * Rueckgabe jetzt float statt int: die BASIC-Stehplatzpreise fuer
   * jugendlich/kind sind 129,74 und wurden vom alten ": int" auf 129
   * abgeschnitten, waehrend das Formular 130 anzeigte.
   */
  private function getTicketPrice($area, $category, $block, $type): float
  {
    return PriceTable::priceFor($type, $category, $area, $block);
  }

  public static function getCategoryMap(): array
  {
    return PriceTable::categoryLabels();
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
