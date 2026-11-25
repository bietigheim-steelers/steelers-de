<?php

declare(strict_types=1);

namespace App\Invoice;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Die drei Mails rund um eine Rechnung: Eingangsbestätigung an den Kunden, Info mit
 * Link an die Mitarbeiter und der Versand des fertigen PDFs. Läuft über den
 * Standard-Transport wie die Dauerkarten-Mails (Absender webseite@steelers.de).
 */
class InvoiceMailer
{
    private const FROM = ['webseite@steelers.de', 'Bietigheim Steelers'];

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $internalRecipient,
    ) {
    }

    public function getDefaultRecipient(): string
    {
        return $this->internalRecipient;
    }

    public function editUrl(int $invoiceId): string
    {
        return $this->urlGenerator->generate('backend_invoice_edit', ['id' => $invoiceId], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param array<string, mixed> $invoice
     */
    public function sendCustomerConfirmation(array $invoice): void
    {
        $order = $invoice['order_data'];
        $name = trim(($order['vorname'] ?? '').' '.($order['nachname'] ?? ''));

        $lines = [
            '' !== $name ? 'Hallo '.$name.',' : 'Hallo,',
            '',
            'vielen Dank für Deine Bestellung von Gruppentickets. Sie ist bei uns eingegangen,',
            'wir melden uns in Kürze mit der Rechnung bei Dir.',
            '',
            'Deine Bestellung:',
        ];

        foreach ($invoice['items'] as $item) {
            $lines[] = \sprintf('  %d × %s', $item['quantity'], $item['description']);
        }

        $lines[] = '';
        $lines[] = 'Bei Fragen antworte einfach auf diese E-Mail.';
        $lines[] = '';
        $lines[] = 'Deine Bietigheim Steelers';

        $email = (new Email())
            ->from(new Address(...self::FROM))
            ->replyTo($this->internalRecipient)
            ->to((string) $invoice['customer_email'])
            ->subject('Bietigheim Steelers – Deine Gruppenbestellung ist eingegangen')
            ->text(implode("\n", $lines));

        $this->mailer->send($email);
    }

    /**
     * @param array<string, mixed> $invoice
     */
    public function sendInternalNotice(array $invoice): void
    {
        $order = $invoice['order_data'];
        $url = $this->editUrl((int) $invoice['id']);

        $lines = [
            'Neue Gruppenbestellung – die Rechnung wartet auf Prüfung.',
            '',
            'Rechnung bearbeiten und versenden (nur mit Contao-Login):',
            $url,
            '',
            'Besteller: '.trim(($order['vorname'] ?? '').' '.($order['nachname'] ?? '')),
        ];

        foreach (['schule' => 'Schule/Verein', 'Spieltag' => 'Spieltag', 'email' => 'E-Mail', 'telefon' => 'Telefon', 'bezahlung' => 'Bezahlung', 'bemerkung' => 'Bemerkung'] as $key => $label) {
            if (!empty($order[$key])) {
                $lines[] = $label.': '.$order[$key];
            }
        }

        $email = (new Email())
            ->from(new Address(...self::FROM))
            ->to($this->internalRecipient)
            ->subject('Gruppenbestellung – Rechnung prüfen')
            ->text(implode("\n", $lines));

        if (!empty($invoice['customer_email'])) {
            $email->replyTo((string) $invoice['customer_email']);
        }

        $this->mailer->send($email);
    }

    /**
     * @param array<string, mixed> $invoice
     */
    public function sendInvoicePdf(array $invoice, string $pdf, string $to): void
    {
        $number = (string) $invoice['invoice_number'];

        $email = (new Email())
            ->from(new Address(...self::FROM))
            ->to($to)
            ->subject('Rechnung '.$number)
            ->text("Rechnung {$number} im Anhang.\n\nBearbeiten: ".$this->editUrl((int) $invoice['id']))
            ->attach($pdf, 'Rechnung-'.$number.'.pdf', 'application/pdf');

        $this->mailer->send($email);
    }
}
