<?php

namespace App\Migration;

use App\EventListener\AmountFormFieldListener;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\DecimalType;

/**
 * Bringt die Auktionen (Formulare mit Zieltabelle steelers_auktion) auf den aktuellen Stand:
 *
 * - steelers_auktion.gebot von FLOAT auf DECIMAL(10,2), damit Gebote centgenau gespeichert werden
 * - steelers_auktion.tstamp, damit der Zeitpunkt eines Gebots nachvollziehbar ist
 * - steelers_auktion.trikotsatz für das optionale versteckte Formularfeld "trikotsatz"
 * - Gebotsfelder der Auktionsformulare von "digit" auf "amount" umstellen
 *   (siehe App\EventListener\AmountFormFieldListener)
 * - Auktionsmodule ohne Formular auf das bisher fest verdrahtete Formular setzen
 *
 * Wie bei den übrigen Migrationen als Migration und nicht über den Schema-Abgleich
 * (siehe AGENTS.md). Das DCA contao/dca/steelers_auktion.php beschreibt denselben Zielzustand.
 */
class AuctionMigration extends AbstractMigration
{
    public const TABLE = 'steelers_auktion';

    /**
     * Das Auswahlfeld "player" des Formulars "Trikotauktion". AuctionModule hat die Spieler
     * früher fest aus diesem Feld gelesen – bestehende Module behalten so ihr Formular.
     */
    private const LEGACY_PLAYER_FIELD_ID = 55;

    /**
     * Spaltenname => DDL.
     */
    private const COLUMNS = [
        'tstamp' => 'ADD tstamp INT UNSIGNED DEFAULT 0 NOT NULL AFTER id',
        'trikotsatz' => "ADD trikotsatz VARCHAR(255) DEFAULT '' NOT NULL AFTER player",
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        if (!$this->connection->createSchemaManager()->tablesExist([self::TABLE])) {
            return false;
        }

        return $this->needsDecimalColumn()
            || [] !== $this->getMissingColumns()
            || $this->countDigitBidFields() > 0
            || $this->countModulesWithoutForm() > 0;
    }

    public function run(): MigrationResult
    {
        $messages = [];

        if ($this->needsDecimalColumn()) {
            $this->connection->executeStatement(
                'ALTER TABLE '.self::TABLE." CHANGE gebot gebot DECIMAL(10,2) DEFAULT '0.00' NOT NULL"
            );
            $messages[] = 'Changed '.self::TABLE.'.gebot to DECIMAL(10,2).';
        }

        if ([] !== $missing = $this->getMissingColumns()) {
            $this->connection->executeStatement(
                'ALTER TABLE '.self::TABLE.' '.implode(', ', array_values($missing))
            );
            $messages[] = 'Added '.implode(', ', array_keys($missing)).' to '.self::TABLE.'.';
        }

        if ($this->countDigitBidFields() > 0) {
            $count = $this->connection->executeStatement(
                "UPDATE tl_form_field SET rgxp = ? WHERE type = 'text' AND name = 'gebot' AND rgxp = 'digit' AND pid IN (SELECT id FROM tl_form WHERE targetTable = ?)",
                [AmountFormFieldListener::RGXP_NAME, self::TABLE]
            );
            $messages[] = "Switched $count bid field(s) to rgxp \"".AmountFormFieldListener::RGXP_NAME.'".';
        }

        if ($this->countModulesWithoutForm() > 0) {
            $formId = (int) $this->connection->fetchOne(
                "SELECT pid FROM tl_form_field WHERE id = ? AND name = 'player'",
                [self::LEGACY_PLAYER_FIELD_ID]
            );

            $count = $this->connection->executeStatement(
                "UPDATE tl_module SET form = ? WHERE type = 'auction' AND form = 0",
                [$formId]
            );
            $messages[] = "Assigned form $formId to $count auction module(s).";
        }

        return $this->createResult(true, implode(' ', $messages));
    }

    private function needsDecimalColumn(): bool
    {
        // listTableColumns() liefert die Spalten in Kleinschreibung
        $columns = $this->connection->createSchemaManager()->listTableColumns(self::TABLE);

        if (!isset($columns['gebot'])) {
            return false;
        }

        return !$columns['gebot']->getType() instanceof DecimalType || 2 !== $columns['gebot']->getScale();
    }

    /**
     * @return array<string, string>
     */
    private function getMissingColumns(): array
    {
        $existing = $this->connection->createSchemaManager()->listTableColumns(self::TABLE);

        return array_filter(
            self::COLUMNS,
            static fn (string $column): bool => !isset($existing[strtolower($column)]),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function countDigitBidFields(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_form_field WHERE type = 'text' AND name = 'gebot' AND rgxp = 'digit' AND pid IN (SELECT id FROM tl_form WHERE targetTable = ?)",
            [self::TABLE]
        );
    }

    private function countModulesWithoutForm(): int
    {
        // Nur umstellen, wenn es das frühere Spielerfeld überhaupt noch gibt
        $hasLegacyField = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_form_field WHERE id = ? AND name = 'player'",
            [self::LEGACY_PLAYER_FIELD_ID]
        );

        if (!$hasLegacyField) {
            return 0;
        }

        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM tl_module WHERE type = 'auction' AND form = 0");
    }
}
