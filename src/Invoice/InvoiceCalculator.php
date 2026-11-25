<?php

declare(strict_types=1);

namespace App\Invoice;

/**
 * Rechnet in Cent (Integer), damit sich keine Rundungsfehler aufsummieren.
 *
 * Einzelpreise sind Bruttopreise (so stehen sie im Formular: 15,00 € pro Person).
 * Netto und MwSt. werden aus der Bruttosumme herausgerechnet.
 */
final class InvoiceCalculator
{
    /**
     * @param list<array{description?: string, quantity?: int|string, unit_price_cents?: int|string}> $items
     *
     * @return array{net_cents: int, vat_cents: int, gross_cents: int}
     */
    public static function totals(array $items, float $vatRate): array
    {
        $gross = 0;

        foreach ($items as $item) {
            $gross += self::lineTotal($item);
        }

        $net = (int) round($gross / (1 + $vatRate / 100));

        return ['net_cents' => $net, 'vat_cents' => $gross - $net, 'gross_cents' => $gross];
    }

    /**
     * @param array{quantity?: int|string, unit_price_cents?: int|string} $item
     */
    public static function lineTotal(array $item): int
    {
        return (int) ($item['quantity'] ?? 0) * (int) ($item['unit_price_cents'] ?? 0);
    }

    /**
     * "1.234,50", "15,5", "15.50" und "15" werden zu Cent. Ungültiges ergibt 0.
     */
    public static function parseMoney(string $value): int
    {
        $value = trim(str_replace(['€', ' ', "\u{a0}"], '', $value));

        if ('' === $value) {
            return 0;
        }

        // Das letzte Trennzeichen ist das Dezimalzeichen, alle anderen sind Tausendertrenner
        if (preg_match('/^(-?)([\d.,]+)$/', $value, $m)) {
            $digits = $m[2];
            $pos = max((int) strrpos($digits, ','), (int) strrpos($digits, '.'));

            if (0 < $pos && \strlen($digits) - $pos - 1 <= 2) {
                $int = preg_replace('/\D/', '', substr($digits, 0, $pos));
                $dec = str_pad(substr($digits, $pos + 1), 2, '0');
            } else {
                $int = preg_replace('/\D/', '', $digits);
                $dec = '00';
            }

            return (int) ($m[1].((int) $int * 100 + (int) $dec));
        }

        return 0;
    }

    public static function formatMoney(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }
}
