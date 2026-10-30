<?php

namespace App\Controller\Api;

use App\Model\Players;
use Contao\ArrayUtil;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlayersController extends AbstractApiController
{
    public function list(Request $request): Response
    {
        $this->boot();

        $columns = ['published=1'];
        $values = [];

        $position = $request->query->get('position');
        if (\is_string($position) && $position !== '') {
            $columns[] = 'position=?';
            $values[] = $position;
        }

        $items = [];
        foreach (Players::findBy($columns, $values, ['order' => 'jersey ASC']) ?? [] as $player) {
            $items[] = $this->serialize($player);
        }

        return $this->responder->json($request, $items, [], 600);
    }

    public function detail(Request $request, int $id): Response
    {
        $this->boot();

        $player = Players::findOneBy(['id=?', 'published=1'], [$id]);
        if ($player === null) {
            return $this->responder->error(404, 'not_found', 'Spieler nicht gefunden.');
        }

        return $this->responder->json($request, $this->serialize($player), [], 600);
    }

    private function serialize(Players $player): array
    {
        $name = trim($player->firstname . ' ' . $player->lastname);

        $uuids = StringUtil::deserialize($player->pictures, true);
        if ($uuids !== []) {
            $uuids = ArrayUtil::sortByOrderField($uuids, StringUtil::deserialize($player->orderPictures, true));
        }

        $pictures = [];
        foreach ($uuids as $uuid) {
            if (($image = $this->images->fromUuid($uuid, 800, null, $name)) !== null) {
                $pictures[] = $image;
            }
        }

        return [
            'id' => (int) $player->id,
            'alias' => $player->alias ?: null,
            'jersey' => $player->jersey !== null && $player->jersey !== '' ? (int) $player->jersey : null,
            'firstname' => $this->format->text($player->firstname) ?? '',
            'lastname' => $this->format->text($player->lastname) ?? '',
            'position' => $player->position ?: null,
            'nationality' => $player->nationality ?: null,
            'shoots' => $player->shoots ?: null,
            'birthplace' => $this->format->text($player->birthplace),
            'birthday' => $player->birthday ? (new \DateTimeImmutable('@' . (int) $player->birthday))
                ->setTimezone(new \DateTimeZone('Europe/Berlin'))->format('Y-m-d') : null,
            'height' => $player->height ? (int) $player->height : null,
            'weight' => $player->weight ? (int) $player->weight : null,
            'steelersSince' => $player->steelersince ?: null,
            'about' => $this->format->html($player->about),
            'pictures' => $pictures,
        ];
    }
}
