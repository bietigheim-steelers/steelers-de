<?php

declare(strict_types=1);

namespace App\Invoice;

/**
 * Vergibt Rechnungsnummern. Das Format ist bewusst austauschbar: Eine andere
 * Implementierung als Service-Alias genügt, wenn der Nummernkreis später zu den
 * übrigen Rechnungen der Firma passen muss.
 */
interface InvoiceNumberGeneratorInterface
{
    /**
     * Muss atomar sein: zwei gleichzeitige Aufrufe dürfen nie dieselbe Nummer liefern.
     * Der Aufrufer ruft das innerhalb einer Transaktion auf, damit eine Nummer nicht
     * verbraucht wird, wenn das Speichern danach scheitert.
     */
    public function next(\DateTimeImmutable $date): string;
}
