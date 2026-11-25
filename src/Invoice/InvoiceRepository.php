<?php

declare(strict_types=1);

namespace App\Invoice;

use Doctrine\DBAL\Connection;

/**
 * Zugriff auf tl_invoice. Positionen liegen als JSON in der Spalte "items",
 * jede Position als {description, quantity, unit_price_cents}.
 */
class InvoiceRepository
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';

    /** Felder, die auf der Rechnungsseite bearbeitet werden. */
    private const EDITABLE = ['invoice_date', 'recipient', 'subject', 'intro_text', 'outro_text', 'vat_rate', 'send_to'];

    public function __construct(
        private readonly Connection $connection,
        private readonly InvoiceNumberGeneratorInterface $numberGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $now = time();

        $this->connection->insert('tl_invoice', array_merge([
            'tstamp' => $now,
            'created' => $now,
            'status' => self::STATUS_DRAFT,
            'invoice_date' => $now,
        ], $data));

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM tl_invoice WHERE id = ?', [$id]);

        if (false === $row) {
            return null;
        }

        $row['items'] = $this->decodeItems((string) $row['items']);
        $row['order_data'] = json_decode((string) $row['order_data'], true) ?: [];

        return $row;
    }

    /**
     * Speichert die bearbeitbaren Felder und die Positionen. Wurde die Rechnung schon
     * verschickt, wird der Zeitpunkt der Änderung vermerkt.
     *
     * @param array<string, mixed>                                                   $fields
     * @param list<array{description: string, quantity: int, unit_price_cents: int}> $items
     */
    public function saveEdits(int $id, array $fields, array $items): void
    {
        $values = array_intersect_key($fields, array_flip(self::EDITABLE));
        $values['items'] = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $values['tstamp'] = time();

        $this->connection->update('tl_invoice', $values, ['id' => $id]);

        $this->connection->executeStatement(
            'UPDATE tl_invoice SET modified_after_sent = ? WHERE id = ? AND status = ?',
            [time(), $id, self::STATUS_SENT],
        );
    }

    /**
     * Vergibt die Rechnungsnummer, falls noch keine existiert, und gibt sie zurück.
     * Eine einmal vergebene Nummer bleibt auch bei erneutem Versand erhalten.
     */
    public function ensureNumber(int $id): string
    {
        return $this->connection->transactional(function () use ($id): string {
            // FOR UPDATE: zwei gleichzeitige Versände derselben Rechnung vergeben nur eine Nummer
            $row = $this->connection->fetchAssociative(
                'SELECT invoice_number, invoice_date FROM tl_invoice WHERE id = ? FOR UPDATE',
                [$id],
            );

            if (false === $row) {
                throw new \RuntimeException(\sprintf('Rechnung %d existiert nicht.', $id));
            }

            if (!empty($row['invoice_number'])) {
                return (string) $row['invoice_number'];
            }

            $date = (new \DateTimeImmutable())->setTimestamp((int) $row['invoice_date'] ?: time());
            $number = $this->numberGenerator->next($date);

            $this->connection->update('tl_invoice', ['invoice_number' => $number], ['id' => $id]);

            return $number;
        });
    }

    public function markSent(int $id, string $to, string $by): void
    {
        $this->connection->executeStatement(
            'UPDATE tl_invoice SET status = ?, sent_at = ?, sent_to = ?, sent_by = ?, sent_count = sent_count + 1,
                modified_after_sent = 0, tstamp = ? WHERE id = ?',
            [self::STATUS_SENT, time(), $to, $by, time(), $id],
        );
    }

    /**
     * @return list<array{description: string, quantity: int, unit_price_cents: int}>
     */
    private function decodeItems(string $json): array
    {
        $items = json_decode($json, true);

        return \is_array($items) ? array_values($items) : [];
    }
}
