<?php

declare(strict_types=1);

namespace App\Invoice;

/**
 * Preise der Gruppenbestellung. Im Formular stehen sie nur als Text in den
 * Feldbeschriftungen ("= 15,00 € pro Person"), für die Rechnung brauchen wir sie als Zahl.
 * Ändert sich ein Preis, muss er hier und in der Feldbeschriftung angepasst werden.
 */
final class GroupOrderPricing
{
    /** Formular-Alias (tl_form.alias), unabhängig von der ID auf dem jeweiligen System. */
    public const FORM_ALIAS = 'gruppenangebote';

    public const ADULT_CENTS = 1500;
    public const CHILD_CENTS = 1000;
    public const POST_SHIPPING_CENTS = 395;

    /** Vorbelegung, je Rechnung änderbar. */
    public const DEFAULT_VAT_RATE = 19.0;
}
