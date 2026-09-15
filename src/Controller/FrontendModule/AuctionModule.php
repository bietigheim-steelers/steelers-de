<?php

namespace App\Controller\FrontendModule;

use App\Migration\AuctionMigration;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Höchstgebote einer Auktion.
 *
 * Die Artikel kommen aus dem Auswahlfeld "player" des im Modul gewählten Formulars, die Gebote
 * aus steelers_auktion (dorthin speichert das Formular). Artikel ohne Gebot werden mit
 * leerem Höchstgebot ausgegeben.
 */
class AuctionModule extends AbstractFrontendModuleController
{
    private const PLAYER_FIELD = 'player';

    public function __construct(private readonly Connection $connection)
    {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $options = StringUtil::deserialize(
            $this->connection->fetchOne(
                "SELECT options FROM tl_form_field WHERE pid = ? AND name = ? AND type = 'select'",
                [(int) $model->form, self::PLAYER_FIELD]
            ),
            true
        );

        $highestBids = $this->connection->fetchAllKeyValue(
            'SELECT player, MAX(gebot) FROM '.AuctionMigration::TABLE.' GROUP BY player'
        );

        $bids = [];

        foreach ($options as $option) {
            // Gruppen-Überschriften des Auswahlfelds sind keine Artikel
            if (!empty($option['group'])) {
                continue;
            }

            $value = (string) ($option['value'] ?? '');

            $bids[] = [
                'label' => (string) ($option['label'] ?? $value),
                'highestBid' => isset($highestBids[$value]) ? (float) $highestBids[$value] : null,
            ];
        }

        $template->bids = $bids;

        return $template->getResponse();
    }
}
