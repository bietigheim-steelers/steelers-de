<?php
namespace App\Controller\FrontendModule;

use App\Model\BusTours;
use App\Model\Games;
use App\Model\Standings;
use App\Model\Rounds;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BusToursModule extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $bustours = BusTours::findAll(array(
            'column'  => array(
                'published=1',
            ),
            'order' => 'tourdate ASC'
        ));

        if (!$bustours) {
            return new Response();
        }

        $bustourslist = $bustours->fetchAll();
        foreach ($bustourslist as $key => $tour) {
            $game = Games::findByPk($tour['game_id']);
            if ($game) {
                $bustourslist[$key]['home'] = Standings::findByIdAndRound($game->hometeam, $game->round, true);
                $bustourslist[$key]['away'] = Standings::findByIdAndRound($game->awayteam, $game->round, true);
                $bustourslist[$key]['season'] = Rounds::findByPkFiltered($game->round, true);

                unset($bustourslist[$key]['game_id']);
                unset($bustourslist[$key]['id']);
                unset($bustourslist[$key]['tstamp']);
                unset($bustourslist[$key]['round']);
                unset($bustourslist[$key]['gamestatus']);
                unset($bustourslist[$key]['hometeam']);
                unset($bustourslist[$key]['awayteam']);
                unset($bustourslist[$key]['spectators']);
                unset($bustourslist[$key]['periodscore']);
            }
        }

        $template->bustours = $bustourslist;
        $template->cssId = $model->cssID[0];
        $template->cssClass = $model->cssID[1];

        return $template->getResponse();
    }
}