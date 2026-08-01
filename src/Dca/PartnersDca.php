<?php

namespace App\Dca;

use App\Model\Partners;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * DCA-Callbacks für tl_tilastot_partners.
 *
 * Erzeugt beim Speichern automatisch einen eindeutigen Alias, damit Partner
 * über sprechende URLs auf einer Detailseite aufgerufen werden können.
 */
class PartnersDca
{
    public function __construct(
        private readonly Slug $slug,
        private readonly Connection $connection,
    ) {
    }

    /**
     * save_callback für das Feld "alias".
     */
    public function generateAlias(mixed $value, DataContainer $dc): string
    {
        $aliasExists = function (string $alias) use ($dc): bool {
            $count = $this->connection->fetchOne(
                'SELECT COUNT(*) FROM tl_tilastot_partners WHERE alias = ? AND id != ?',
                [$alias, (int) $dc->id]
            );

            return $count > 0;
        };

        // Alias leer -> aus dem (angezeigten) Namen erzeugen
        if (!$value) {
            $source = $dc->activeRecord->displayname ?: $dc->activeRecord->name;
            $value = $this->slug->generate(StringUtil::decodeEntities((string) $source), [], $aliasExists);
        } elseif (preg_match('/^[1-9]\d*$/', (string) $value)) {
            throw new \Exception(sprintf('Ungültiger Alias "%s": Ein Alias darf keine reine Zahl sein.', $value));
        } elseif ($aliasExists((string) $value)) {
            throw new \Exception(sprintf('Der Alias "%s" ist bereits vergeben.', $value));
        }

        return (string) $value;
    }

    /**
     * label_callback: Branchen in der Listenansicht als lesbare Labels ausgeben.
     */
    public function formatListLabel(array $row, string $label, DataContainer $dc, array $args): array
    {
        $branchen = StringUtil::deserialize($row['branche'] ?? null, true);

        $args[2] = empty($branchen)
            ? ''
            : implode(', ', array_map(static fn ($key) => Partners::getBrancheLabel((string) $key), $branchen));

        return $args;
    }
}
