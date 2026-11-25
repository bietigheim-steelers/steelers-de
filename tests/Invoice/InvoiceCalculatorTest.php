<?php

declare(strict_types=1);

namespace App\Tests\Invoice;

use App\Invoice\InvoiceCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    public function testTotalsExtractNetAndVatFromGross(): void
    {
        $items = [
            ['description' => 'Vollzahler', 'quantity' => 10, 'unit_price_cents' => 1500],
            ['description' => 'Kinder', 'quantity' => 5, 'unit_price_cents' => 1000],
            ['description' => 'Versand', 'quantity' => 1, 'unit_price_cents' => 395],
        ];

        $totals = InvoiceCalculator::totals($items, 19.0);

        $this->assertSame(20395, $totals['gross_cents']);
        $this->assertSame(17139, $totals['net_cents']);
        $this->assertSame(3256, $totals['vat_cents']);
    }

    public function testNetAndVatAlwaysAddUpToGross(): void
    {
        foreach ([7.0, 19.0, 0.0] as $rate) {
            $totals = InvoiceCalculator::totals([['quantity' => 3, 'unit_price_cents' => 333]], $rate);

            $this->assertSame($totals['gross_cents'], $totals['net_cents'] + $totals['vat_cents']);
        }
    }

    public function testEmptyInvoiceIsZero(): void
    {
        $this->assertSame(['net_cents' => 0, 'vat_cents' => 0, 'gross_cents' => 0], InvoiceCalculator::totals([], 19.0));
    }

    #[DataProvider('moneyProvider')]
    public function testParseMoney(string $input, int $expected): void
    {
        $this->assertSame($expected, InvoiceCalculator::parseMoney($input));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function moneyProvider(): iterable
    {
        yield 'comma' => ['15,50', 1550];
        yield 'dot' => ['15.5', 1550];
        yield 'integer' => ['15', 1500];
        yield 'thousands german' => ['1.234,50', 123450];
        yield 'thousands english' => ['1,234.50', 123450];
        yield 'thousands only' => ['1.234', 123400];
        yield 'euro sign' => ['3,95 €', 395];
        yield 'empty' => ['', 0];
        yield 'garbage' => ['abc', 0];
    }

    public function testFormatMoney(): void
    {
        $this->assertSame('1.234,50', InvoiceCalculator::formatMoney(123450));
        $this->assertSame('0,05', InvoiceCalculator::formatMoney(5));
    }
}
