<?php

namespace App\Controller\Api;

use App\Api\ApiResponder;
use App\Api\ContentFormatter;
use App\Api\ImageUrlBuilder;
use Codefog\NewsCategoriesBundle\Model\NewsCategoryModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsModel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsController extends AbstractApiController
{
    /** Archiv des Videoportals (Cron updateVideoportal); fuellt sonst die News-Liste mit Videos. */
    private const VIDEO_ARCHIVE = 7;

    public function __construct(
        ContaoFramework $framework,
        ApiResponder $responder,
        ImageUrlBuilder $images,
        ContentFormatter $format,
        #[Autowire(service: 'contao.routing.content_url_generator')]
        private readonly ContentUrlGenerator $urls,
    ) {
        parent::__construct($framework, $responder, $images, $format);
    }

    public function list(Request $request): Response
    {
        $this->boot();

        $page = max(1, $this->intParam($request, 'page') ?? 1);
        $perPage = min(50, max(1, $this->intParam($request, 'perPage') ?? 20));
        $time = time();
        $t = NewsModel::getTable();

        $columns = [$this->publishedCondition($t, $time)];
        $values = [];

        $archives = array_filter(array_map('intval', explode(',', (string) $request->query->get('archive', ''))));
        if ($archives !== []) {
            $columns[] = "$t.pid IN(" . implode(',', $archives) . ')';
        } else {
            $columns[] = "$t.pid != " . self::VIDEO_ARCHIVE;
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
            ["$t.id = ?", $this->publishedCondition($t, time())],
            [$id],
        );

        if ($news === null) {
            return $this->responder->error(404, 'not_found', 'Eintrag nicht gefunden.');
        }

        return $this->responder->json($request, $this->serialize($news), [], 120);
    }

    private function serialize(NewsModel $news): array
    {
        $image = null;
        if ($news->addImage && $news->singleSRC) {
            $image = $this->images->fromUuid($news->singleSRC, 750, null, $this->format->plain($news->alt ?: $news->imageTitle), $this->format->plain($news->caption));
        }

        $categories = [];
        foreach (NewsCategoryModel::findPublishedByNews((int) $news->id) ?? [] as $category) {
            $categories[] = [
                'id' => (int) $category->id,
                'title' => $category->getTitle(),
                'alias' => $category->getAlias(),
            ];
        }

        $video = null;
        if ($news->playerType === 'playerUrl' && $news->playerUrl) {
            $video = ['url' => $news->playerUrl];
        }

        return [
            'id' => (int) $news->id,
            'title' => $this->format->plain($news->headline) ?? '',
            'date' => $this->format->date($news->time ?: $news->date),
            'updatedAt' => $this->format->date($news->tstamp),
            'teaser' => $this->format->plain($news->teaser),
            'image' => $image,
            'categories' => $categories,
            'featured' => (bool) $news->featured,
            'webUrl' => $this->webUrl($news),
            'gameId' => $news->game_id ? (int) $news->game_id : null,
            'video' => $video,
        ];
    }

    private function webUrl(NewsModel $news): ?string
    {
        try {
            return $this->urls->generate($news, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (\Throwable) {
            return null;
        }
    }
}
