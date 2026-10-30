<?php

namespace App\Controller\Api;

use App\Model\Partners;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnersController extends AbstractApiController
{
    public function list(Request $request): Response
    {
        $this->boot();

        $category = $request->query->get('category');
        $categories = \is_string($category) && $category !== '' ? [$category] : [];

        $items = [];
        foreach (Partners::findPublished($categories) ?? [] as $partner) {
            $items[] = [
                'id' => (int) $partner->id,
                'name' => $this->format->text($partner->displayname ?: $partner->name) ?? '',
                'categories' => $this->options($partner->category, Partners::getCategoryOptions()),
                'branches' => $this->options($partner->branche, Partners::getBrancheOptions()),
                'url' => $partner->url ?: null,
                'teaser' => $this->format->plain($partner->teaser),
                'description' => $this->format->html($partner->description),
                'logo' => $this->images->fromUuid($partner->logo, 400, null, $this->format->text($partner->displayname ?: $partner->name)),
                'photo' => $this->images->fromUuid($partner->photo, 800, null, $this->format->text($partner->displayname ?: $partner->name)),
            ];
        }

        return $this->responder->json($request, $items, [], 600);
    }

    /**
     * Gespeicherte Schluessel (serialisiertes Array) als [{key, label}].
     *
     * @param array<string,string> $labels
     */
    private function options(mixed $stored, array $labels): array
    {
        $result = [];
        foreach (StringUtil::deserialize($stored, true) as $key) {
            $result[] = ['key' => (string) $key, 'label' => $labels[$key] ?? (string) $key];
        }

        return $result;
    }
}
