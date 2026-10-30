<?php

namespace App\Controller\Api;

use App\Api\ApiResponder;
use App\Api\ContentFormatter;
use App\Api\GameSerializer;
use App\Api\ImageUrlBuilder;
use App\Api\SeasonResolver;
use App\Model\Games;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GamesController extends AbstractApiController
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

        $columns = ['round=?'];
        $values = [$round];

        if (($team = $this->intParam($request, 'team')) !== null) {
            $columns[] = '(hometeam=? OR awayteam=?)';
            array_push($values, $team, $team);
        }
        if (($from = $this->dateParam($request, 'from')) !== null) {
            $columns[] = 'gamedate >= ?';
            $values[] = $from;
        }
        if (($to = $this->dateParam($request, 'to')) !== null) {
            // "to" gilt einschliesslich des angegebenen Tages
            $columns[] = 'gamedate < ?';
            $values[] = $to + 86400;
        }

        $status = $request->query->get('status');
        $items = [];
        foreach (Games::findBy($columns, $values, ['order' => 'gamedate ASC']) ?? [] as $game) {
            $item = $this->games->serialize($game);
            if (\is_string($status) && $status !== '' && $item['status'] !== $status) {
                continue;
            }
            $items[] = $item;
        }

        // Spielstaende aendern sich waehrend eines Spiels – kurz cachen
        return $this->responder->json($request, $items, [], 60);
    }

    public function detail(Request $request, int $id): Response
    {
        $this->boot();

        $game = Games::findByPk($id);
        if ($game === null) {
            return $this->responder->error(404, 'not_found', 'Spiel nicht gefunden.');
        }

        return $this->responder->json($request, $this->games->serialize($game), [], 60);
    }
}
