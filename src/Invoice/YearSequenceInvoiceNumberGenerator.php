<?php

declare(strict_types=1);

namespace App\Invoice;

use Doctrine\DBAL\Connection;

/**
 * Format JJJJ-NNNN (z. B. 2026-0001), pro Jahr neu beginnend.
 *
 * Der Zähler liegt in tl_invoice_counter. LAST_INSERT_ID(expr) macht das Hochzählen und
 * Auslesen zu einer einzigen atomaren Anweisung.
 */
final class YearSequenceInvoiceNumberGenerator implements InvoiceNumberGeneratorInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function next(\DateTimeImmutable $date): string
    {
        $year = (int) $date->format('Y');

        $this->connection->executeStatement(
            'INSERT INTO tl_invoice_counter (year, last_number) VALUES (?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)',
            [$year],
        );

        $number = (int) $this->connection->lastInsertId();

        return \sprintf('%d-%04d', $year, $number);
    }
}
