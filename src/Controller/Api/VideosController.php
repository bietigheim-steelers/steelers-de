<?php

namespace App\Controller\Api;

use App\Api\ApiResponder;
use App\Api\ContentFormatter;
use App\Api\GameSerializer;
use App\Api\ImageUrlBuilder;
use App\Model\Games;
use Codefog\NewsCategoriesBundle\Model\NewsCategoryModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\NewsModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Videoportal (News-Archiv 7, befuellt vom Cron updateVideoportal) mit zugewiesenem Spiel.
 */
class VideosController extends AbstractApiController
{
    /** Archiv des Videoportals. */
    private const VIDEO_ARCHIVE = 7;

    /** @var array<int, ?array> game id => serialisiertes Spiel */
    private array $gameCache = [];

    public function __construct(
        ContaoFramework $framework,
        ApiResponder $responder,
        ImageUrlBuilder $images,
        ContentFormatter $format,
        private readonly GameSerializer $games,
    ) {
        parent::__construct($framework, $responder, $images, $format);
    }

    public function list(Request $request): Response
    {
        $this->boot();

        $page = max(1, $this->intParam($request, 'page') ?? 1);
        $perPage = min(50, max(1, $this->intParam($request, 'perPage') ?? 20));
        $t = NewsModel::getTable();

        $columns = [
            "$t.pid = " . self::VIDEO_ARCHIVE,
            $this->publishedCondition($t, time()),
            "$t.playerType = 'playerUrl'",
            "$t.playerUrl != ''",
        ];
        $values = [];

        // Suche nach Spiel: /videos?game=123 (Spiel-ID wie bei /games)
        if (($game = $this->intParam($request, 'game')) !== null) {
            $columns[] = "$t.game_id = ?";
            $values[] = $game;
        }

        $category = $request->query->get('category');
        if (\is_string($category) && $category !== '') {
            $columns[] = "$t.id IN (SELECT nc.news_id FROM tl_news_categories nc JOIN tl_news_category c ON c.id = nc.category_id WHERE c.alias = ?)";
            $values[] = $category;
        }

        if (($since = $this->dateParam($request, 'since')) !== null) {
            $columns[] = "$t.tstamp > ?";
            $values[] = $since;
        }

        $total = NewsModel::countBy($columns, $values);
        $items = [];

        $news = NewsModel::findBy($columns, $values, [
            'order' => "$t.date DESC, $t.time DESC",
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]);

        foreach ($news ?? [] as $item) {
            $items[] = $this->serialize($item);
        }

        return $this->responder->json($request, $items, ['page' => $page, 'perPage' => $perPage, 'total' => $total], 120);
    }

    public function detail(Request $request, int $id): Response
    {
        $this->boot();

        $t = NewsModel::getTable();
        $news = NewsModel::findOneBy(
            ["$t.id = ?", "$t.pid = " . self::VIDEO_ARCHIVE, $this->publishedCondition($t, time())],
            [$id],
        );

        if ($news === null || !$news->playerUrl) {
            return $this->responder->error(404, 'not_found', 'Video nicht gefunden.');
        }

        return $this->responder->json($request, $this->serialize($news), [], 120);
    }

    private function serialize(NewsModel $news): array
    {
        $categories = [];
        foreach (NewsCategoryModel::findPublishedByNews((int) $news->id) ?? [] as $category) {
            $categories[] = [
                'id' => (int) $category->id,
                'title' => $category->getTitle(),
                'alias' => $category->getAlias(),
            ];
        }

        $url = (string) $news->playerUrl;
        $youtubeId = $this->youtubeId($url);
        $gameId = $news->game_id ? (int) $news->game_id : null;

        return [
            'id' => (int) $news->id,
            'title' => $this->format->plain($news->headline) ?? '',
            'date' => $this->format->date($news->time ?: $news->date),
            'url' => $url,
            'provider' => $youtubeId !== null ? 'youtube' : null,
            'videoId' => $youtubeId,
            'thumbnail' => $youtubeId !== null ? "https://i.ytimg.com/vi/$youtubeId/hqdefault.jpg" : null,
            'categories' => $categories,
            'gameId' => $gameId,
            'game' => $gameId !== null ? $this->game($gameId) : null,
        ];
    }

    private function game(int $id): ?array
    {
        if (!\array_key_exists($id, $this->gameCache)) {
            $game = Games::findByPk($id);
            $this->gameCache[$id] = $game !== null ? $this->games->serialize($game) : null;
        }

        return $this->gameCache[$id];
    }

    private function youtubeId(string $url): ?string
    {
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([\w-]{11})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
