<?php

declare(strict_types=1);

namespace App\Invoice;

use Mpdf\Mpdf;
use Twig\Environment;

/**
 * Erzeugt das Rechnungs-PDF. Der Text liegt als Overlay über dem Briefpapier-PDF
 * (mPDF SetDocTemplate, FPDI). Fehlt die Datei, entsteht das PDF ohne Briefpapier,
 * damit Vorschau und Versand in der Entwicklung trotzdem funktionieren.
 */
class InvoicePdfRenderer
{
    /**
     * @param string $letterheadPath Briefpapier-PDF (Parameter app.invoice.letterhead)
     * @param string $tempDir        Arbeitsverzeichnis von mPDF
     * @param array{top: int, right: int, bottom: int, left: int} $margins Schreibbereich in mm
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly string $letterheadPath,
        private readonly string $tempDir,
        private readonly array $margins,
    ) {
    }

    public function hasLetterhead(): bool
    {
        return is_file($this->letterheadPath);
    }

    /**
     * @param array<string, mixed> $invoice Datensatz aus InvoiceRepository::find()
     */
    public function render(array $invoice): string
    {
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $this->tempDir,
            'margin_top' => $this->margins['top'],
            'margin_right' => $this->margins['right'],
            'margin_bottom' => $this->margins['bottom'],
            'margin_left' => $this->margins['left'],
            'default_font' => 'dejavusans',
            'default_font_size' => 10,
        ]);

        $mpdf->SetTitle($invoice['invoice_number'] ? 'Rechnung '.$invoice['invoice_number'] : 'Rechnung (Entwurf)');
        $mpdf->SetAuthor('Bietigheim Steelers');

        if ($this->hasLetterhead()) {
            // true: das Briefpapier gilt für alle Seiten, nicht nur für die erste
            $mpdf->SetDocTemplate($this->letterheadPath, true);
        }

        $items = [];

        foreach ($invoice['items'] as $item) {
            $items[] = $item + ['line_cents' => InvoiceCalculator::lineTotal($item)];
        }

        $mpdf->WriteHTML($this->twig->render('@Contao_App/invoice_pdf.html.twig', [
            'invoice' => $invoice,
            'items' => $items,
            'totals' => InvoiceCalculator::totals($invoice['items'], (float) $invoice['vat_rate']),
        ]));

        return $mpdf->Output('', 'S');
    }
}
