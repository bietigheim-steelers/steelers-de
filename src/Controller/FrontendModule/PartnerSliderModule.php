<?php

declare(strict_types=1);

namespace App\Controller\FrontendModule;

use App\Model\Partners;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Partner-Logos als endlos laufendes Band ("Logo Slider" des Business-Themes).
 *
 * Die Laufanimation kommt aus files/business/js/main.js und haengt an der
 * Klasse ".marquee-slider". Das Skript dupliziert den Inhalt genau einmal und
 * setzt zurueck, sobald die halbe Scrollbreite erreicht ist. Damit dabei keine
 * Luecke sichtbar wird, muss ein Durchlauf breiter als der Viewport sein –
 * deshalb wird die Logo-Liste hier so oft wiederholt, bis MIN_ITEMS erreicht
 * ist.
 */
#[AsFrontendModule(category: 'tilastot')]
class PartnerSliderModule extends AbstractFrontendModuleController
{
	/**
	 * Mindestanzahl an Logos pro Durchlauf.
	 */
	private const MIN_ITEMS = 12;

	public function __construct(private readonly ContentUrlGenerator $urlGenerator)
	{
	}

	protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
	{
		$categories = StringUtil::deserialize($model->tilastot_partners_category, true);
		$allowedBranchen = Partners::filterValidBranchen(StringUtil::deserialize($model->tilastot_partners_branche, true));

		$partners = Partners::findPublished($categories, $allowedBranchen);

		$withLink = (bool) $model->tilastot_partner_slider_link;
		$readerPage = $withLink ? $this->getReaderPage($model) : null;

		$logos = array();

		foreach ($partners?->fetchAll() ?? array() as $partner) {
			$logo = $partner['logo'] ? FilesModel::findByUuid($partner['logo']) : null;

			// Ohne Logo gibt es im Logo-Slider nichts anzuzeigen
			if (null === $logo) {
				continue;
			}

			$detailUrl = $withLink ? $this->getDetailUrl($readerPage, $partner['alias']) : '';
			$href = $detailUrl ?: ($withLink ? (string) $partner['url'] : '');

			$logos[] = array(
				'uuid' => $logo->uuid,
				'title' => $partner['displayname'] ?: $partner['name'],
				'href' => $href,
				'external' => '' !== $href && '' === $detailUrl,
			);
		}

		$template->logos = $logos;
		$template->items = $this->repeatToMinimum($logos);
		$template->empty = empty($logos);

		$headline = StringUtil::deserialize($model->headline, true);
		$cssID = StringUtil::deserialize($model->cssID, true);
		$template->headline = $headline['value'] ?? '';
		$template->headlineUnit = $headline['unit'] ?? 'h2';
		$template->cssId = $cssID[0] ?? '';
		$template->cssClass = $cssID[1] ?? '';

		return $template->getResponse();
	}

	/**
	 * Logo-Liste so oft wiederholen, bis das Band breit genug ist.
	 *
	 * @param list<array<string,mixed>> $logos
	 *
	 * @return list<array<string,mixed>>
	 */
	private function repeatToMinimum(array $logos): array
	{
		if (empty($logos)) {
			return array();
		}

		$items = $logos;

		while (\count($items) < self::MIN_ITEMS) {
			$items = array_merge($items, $logos);
		}

		return $items;
	}

	private function getReaderPage(ModuleModel $model): ?PageModel
	{
		if (!$model->jumpTo) {
			return null;
		}

		return PageModel::findPublishedById((int) $model->jumpTo);
	}

	private function getDetailUrl(?PageModel $page, string $alias): string
	{
		if (null === $page || '' === $alias) {
			return '';
		}

		try {
			return $this->urlGenerator->generate($page, array('parameters' => '/' . $alias));
		} catch (\Throwable) {
			return '';
		}
	}
}
