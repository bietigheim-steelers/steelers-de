<?php

declare(strict_types=1);

namespace App\Dca;

use App\Invoice\InvoiceCalculator;
use App\Invoice\InvoiceRepository;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;

/**
 * Callbacks für die Rechnungsliste im Backend (tl_invoice).
 */
class InvoiceDca
{
    /**
     * @param array<string, mixed> $row
     */
    public function formatLabel(array $row, string $label, DataContainer $dc, array $args): array
    {
        $items = json_decode((string) $row['items'], true) ?: [];
        $gross = InvoiceCalculator::totals($items, (float) $row['vat_rate'])['gross_cents'];
        $recipient = trim(strtok((string) $row['recipient'], "\n") ?: '');

        if (InvoiceRepository::STATUS_SENT === $row['status']) {
            $status = '<span style="color:#2e7d32;font-weight:600">Versendet '.date('d.m.Y H:i', (int) $row['sent_at']).'</span>';

            if ((int) $row['modified_after_sent'] > 0) {
                $status .= ' <span style="color:#c62828;font-weight:600">· nach Versand geändert</span>';
            }
        } else {
            $status = '<span style="color:#b26a00;font-weight:600">Entwurf</span>';
        }

        return [
            $row['invoice_number'] ?: '–',
            date('d.m.Y', (int) $row['created']),
            StringUtil::specialchars($recipient),
            InvoiceCalculator::formatMoney($gross).' €',
            $status,
        ];
    }

    /**
     * Versendete Rechnungen dürfen nicht gelöscht werden: die Nummer wäre sonst eine Lücke.
     *
     * @param array<string, mixed> $row
     */
    public function deleteButton(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes): string
    {
        if (InvoiceRepository::STATUS_SENT === $row['status'] || !empty($row['invoice_number'])) {
            return Image::getHtml(str_replace('.svg', '--disabled.svg', (string) $icon), $label);
        }

        return '<a href="'.\Contao\Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml((string) $icon, $label).'</a> ';
    }
}
