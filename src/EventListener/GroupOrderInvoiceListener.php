<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Invoice\GroupOrderPricing;
use App\Invoice\InvoiceMailer;
use App\Invoice\InvoiceRepository;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Psr\Log\LoggerInterface;

/**
 * Legt zu jeder Gruppenbestellung (Formular-Alias "gruppenangebote") einen Rechnungsentwurf an
 * und verschickt die Mails: Eingangsbestätigung an den Kunden und Info mit Link an die
 * Mitarbeiter. Die Rechnung bearbeiten und versenden sie danach im Backend.
 *
 * Der Datensatz ist die verbindliche Bestellung: erst speichern, dann mailen. Scheitert eine
 * Mail, wird nur protokolliert – der Besucher soll nicht erneut absenden.
 */
#[AsHook('processFormData')]
class GroupOrderInvoiceListener
{
    public function __construct(
        private readonly InvoiceRepository $invoices,
        private readonly InvoiceMailer $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed>      $arrSubmitted
     * @param array<string, mixed>      $arrData
     * @param array<string, mixed>|null $arrFiles
     * @param array<string, mixed>      $arrLabels
     */
    public function __invoke(array $arrSubmitted, array $arrData, array|null $arrFiles, array $arrLabels, Form $objForm): void
    {
        if (GroupOrderPricing::FORM_ALIAS !== ($arrData['alias'] ?? null)) {
            return;
        }

        $order = array_map(static fn ($v) => \is_array($v) ? implode(', ', $v) : (string) $v, $arrSubmitted);

        $id = $this->invoices->create([
            'form_id' => (int) ($arrData['id'] ?? 0),
            'recipient' => $this->buildAddress($order),
            'subject' => 'Rechnung Gruppentickets'.(!empty($order['Spieltag']) ? ' – '.$order['Spieltag'] : ''),
            'vat_rate' => GroupOrderPricing::DEFAULT_VAT_RATE,
            'items' => json_encode($this->buildItems($order), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'customer_email' => $order['email'] ?? '',
            'send_to' => $this->mailer->getDefaultRecipient(),
            'order_data' => json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $invoice = $this->invoices->find($id);

        $mails = [
            'Mitarbeiter-Info' => fn () => $this->mailer->sendInternalNotice($invoice),
        ];

        if (!empty($invoice['customer_email'])) {
            $mails = ['Kundenbestätigung' => fn () => $this->mailer->sendCustomerConfirmation($invoice)] + $mails;
        }

        foreach ($mails as $label => $send) {
            try {
                $send();
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'Rechnung {id} ist gespeichert, aber die Mail "{label}" ging nicht raus',
                    ['id' => $id, 'label' => $label, 'exception' => $exception],
                );
            }
        }
    }

    /**
     * @param array<string, string> $order
     */
    private function buildAddress(array $order): string
    {
        $anrede = ['herr' => 'Herr', 'frau' => 'Frau'][strtolower($order['anrede'] ?? '')] ?? '';
        $lines = [
            $order['schule'] ?? '',
            trim($anrede.' '.($order['vorname'] ?? '').' '.($order['nachname'] ?? '')),
            $order['strasse'] ?? '',
            trim(($order['plz'] ?? '').' '.($order['ort'] ?? '')),
        ];

        return implode("\n", array_filter(array_map('trim', $lines), static fn (string $l): bool => '' !== $l));
    }

    /**
     * @param array<string, string> $order
     *
     * @return list<array{description: string, quantity: int, unit_price_cents: int}>
     */
    private function buildItems(array $order): array
    {
        $game = !empty($order['Spieltag']) ? ' ('.$order['Spieltag'].')' : '';
        $items = [];

        if (($adults = (int) ($order['anzahl_tickets'] ?? 0)) > 0) {
            $items[] = ['description' => 'Gruppenticket Vollzahler'.$game, 'quantity' => $adults, 'unit_price_cents' => GroupOrderPricing::ADULT_CENTS];
        }

        if (($kids = (int) ($order['anzahl_tickets_kids'] ?? 0)) > 0) {
            $items[] = ['description' => 'Gruppenticket Kinder/Jugendliche'.$game, 'quantity' => $kids, 'unit_price_cents' => GroupOrderPricing::CHILD_CENTS];
        }

        // Das Versand-Feld ist ein Select, der Wert für Postversand ist "post"
        if (str_contains(strtolower($order['versand'] ?? ''), 'post')) {
            $items[] = ['description' => 'Versand per Post', 'quantity' => 1, 'unit_price_cents' => GroupOrderPricing::POST_SHIPPING_CENTS];
        }

        return $items;
    }
}
