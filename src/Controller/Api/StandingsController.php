<?php

namespace App\Controller\Api;

use App\Api\ApiResponder;
use App\Api\ContentFormatter;
use App\Api\GameSerializer;
use App\Api\ImageUrlBuilder;
use App\Api\SeasonResolver;
use App\Model\Standings;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StandingsController extends AbstractApiController
{
    public function __construct(
        ContaoFramework $framework,
        ApiResponder $responder,
        ImageUrlBuilder $images,
        ContentFormatter $format,
        private readonly GameSerializer $games,
        private readonly SeasonResolver $season,
    ) {
        parent::__construct($framework, $responder, $images, $format);
    }

    public function list(Request $request): Response
    {
        $this->boot();

        $round = $this->intParam($request, 'round') ?? $this->season->currentRound();
        if ($round === null) {
            return $this->responder->json($request, [], [], 60);
        }

        // Reihenfolge wie im Frontend-Modul (StandingsModule)
        $rows = Standings::findBy(['round=?'], [$round], [
            'order' => 'points DESC, goalsfor-goalsagainst DESC, goalsfor DESC, goalsagainst ASC',
        ]);

        $items = [];
        $rank = 0;
        foreach ($rows ?? [] as $row) {
            $items[] = [
                'rank' => ++$rank,
                'team' => $this->games->teamFromModel($row),
                'games' => (int) $row->games,
                'regulationWins' => (int) $row->rw,
                'overtimeWins' => (int) $row->ow,
                'penaltyWins' => (int) $row->pw,
                'penaltyLosses' => (int) $row->pl,
                'overtimeLosses' => (int) $row->ol,
                'regulationLosses' => (int) $row->rl,
                'goalsFor' => (int) $row->goalsfor,
                'goalsAgainst' => (int) $row->goalsagainst,
                'goalDifference' => (int) $row->goalsfor - (int) $row->goalsagainst,
                'penalties' => $row->penalties !== null ? (int) $row->penalties : null,
                'points' => (int) $row->points,
            ];
        }

        return $this->responder->json($request, $items, [], 120);
    }
}
