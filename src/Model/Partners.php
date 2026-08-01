<?php

/*
 * This file is part of the TilastotBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use Contao\Model;
use Contao\Model\Collection;

/**
 * @property int    $id
 * @property int    $tstamp
 * @property string $name
 * @property string $displayname
 * @property string $alias
 * @property string $url
 * @property string $category
 * @property string $branche
 * @property string $logo
 * @property string $photo
 * @property string $teaser
 * @property string $description
 * @property int    $published
 */
class Partners extends Model
{

    /**
     * Name of the table
     * @var string
     */
    protected static $strTable = 'tl_tilastot_partners';

    public static function getCategoryOptions($dc = null)
    {
        return array(
            'platin' => 'Platinpartner',
            'premium' => 'Premiumpartner',
            'gold' => 'Goldpartner',
            'silber' => 'Silberpartner',
            'bronze' => 'Bronzepartner',
            'business' => 'Businesspartner',
            'lounge' => 'Businesslounge',
            'medien' => 'Medienpartner',
            'video' => 'Videopartner',
            'carpool' => 'Carpool Partner',
            'team' => 'Teampartner',
            'supporter' => 'Supporter',
            'nachwuchs_haupt' => 'Nachwuchs - Hauptsponsor',
            'nachwuchs' => 'Nachwuchssponsor',
            'nachwuchsspieler' => 'Nachwuchs Spielerpatenschaften',
        );
    }

    /**
     * Branchen (Wirtschaftszweige) eines Partners.
     *
     * Wird sowohl im Backend (tl_tilastot_partners.branche, tl_module) als auch
     * im Frontend für die Filterung der Partnerliste verwendet.
     */
    public static function getBrancheOptions($dc = null)
    {
        return array(
            'auto' => 'Automobil & Mobilität',
            'bau' => 'Bau & Handwerk',
            'immobilien' => 'Immobilien & Wohnen',
            'einrichtung' => 'Einrichtung & Wohnaccessoires',
            'finanzen' => 'Banken & Finanzen',
            'versicherung' => 'Versicherungen',
            'beratung' => 'Beratung, Recht & Steuern',
            'it' => 'IT, Software & Telekommunikation',
            'industrie' => 'Industrie & Produktion',
            'handel' => 'Handel & Einzelhandel',
            'gastronomie' => 'Gastronomie & Hotellerie',
            'lebensmittel' => 'Lebensmittel & Getränke',
            'gesundheit' => 'Gesundheit & Medizin',
            'beauty' => 'Beauty & Wellness',
            'sport' => 'Sport & Freizeit',
            'energie' => 'Energie & Umwelt',
            'logistik' => 'Transport & Logistik',
            'marketing' => 'Marketing, Werbung & Medien',
            'bildung' => 'Bildung & Weiterbildung',
            'personal' => 'Personal & Recruiting',
            'sicherheit' => 'Sicherheit & Schutz',
            'reise' => 'Reise & Tourismus',
            'dienstleistung' => 'Dienstleistungen',
            'oeffentlich' => 'Öffentliche Hand, Vereine & Verbände',
            'sonstiges' => 'Sonstiges',
        );
    }

    /**
     * Lesbares Label zu einem Branchenschlüssel.
     */
    public static function getBrancheLabel(string $key): string
    {
        $options = self::getBrancheOptions();

        return $options[$key] ?? $key;
    }

    /**
     * Nur gültige Branchenschlüssel zurückgeben (Schutz vor manipulierten Requests).
     *
     * @param array<int,string> $keys
     *
     * @return array<int,string>
     */
    public static function filterValidBranchen(array $keys): array
    {
        $options = self::getBrancheOptions();

        return array_values(array_filter($keys, static fn ($key) => isset($options[$key])));
    }

    /**
     * Veröffentlichte Partner suchen – optional nach Kategorien und/oder Branchen gefiltert.
     *
     * @param array<int,string> $categories
     * @param array<int,string> $branchen
     */
    public static function findPublished(array $categories = array(), array $branchen = array(), string $order = 'name ASC'): ?Collection
    {
        $t = static::$strTable;
        $columns = array("$t.published = 1");
        $values = array();

        if (!empty($categories)) {
            $or = array();
            foreach ($categories as $category) {
                $or[] = "$t.category LIKE ?";
                $values[] = '%"' . $category . '"%';
            }
            $columns[] = '(' . implode(' OR ', $or) . ')';
        }

        if (!empty($branchen)) {
            $or = array();
            foreach ($branchen as $branche) {
                $or[] = "$t.branche LIKE ?";
                $values[] = '%"' . $branche . '"%';
            }
            $columns[] = '(' . implode(' OR ', $or) . ')';
        }

        return self::findBy($columns, $values, array('order' => $order));
    }
}
