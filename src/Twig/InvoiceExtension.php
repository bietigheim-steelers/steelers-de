<?php

declare(strict_types=1);

namespace App\Twig;

use App\Invoice\InvoiceCalculator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig-Filter für die Rechnung: Cent => "1.234,50".
 */
class InvoiceExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('money', static fn (int|string|null $cents): string => InvoiceCalculator::formatMoney((int) $cents)),
        ];
    }
}
