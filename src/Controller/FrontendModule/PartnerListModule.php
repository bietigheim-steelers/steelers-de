<?php

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
 * Partnerliste mit Branchenfilter.
 *
 * Der aktive Filter wird über den Query-Parameter "branche" gesteuert, damit
 * gefilterte Ansichten verlinkbar und ohne JavaScript nutzbar sind.
 */
#[AsFrontendModule(category: 'tilastot')]
class PartnerListModule extends AbstractFrontendModuleController
{
	public function __construct(private readonly ContentUrlGenerator $urlGenerator)
	{
	}

	protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
	{
		$categories = StringUtil::deserialize($model->tilastot_partners_category, true);
		$allowedBranchen = Partners::filterValidBranchen(StringUtil::deserialize($model->tilastot_partners_branche, true));

		$partners = Partners::findPublished($categories, $allowedBranchen);

		if (!$partners) {
			$template->partners = array();
			$template->branchen = array();
			$template->activeBranche = '';
			$template->empty = true;

			return $template->getResponse();
		}

		$readerPage = $this->getReaderPage($model);
		$brancheLabels = Partners::getBrancheOptions();

		$all = array();
		$availableBranchen = array();

		foreach ($partners->fetchAll() as $partner) {
			$partner['branche'] = Partners::filterValidBranchen(StringUtil::deserialize($partner['branche'], true));
			$partner['brancheLabels'] = array_map(static fn ($key) => $brancheLabels[$key], $partner['branche']);
			$partner['categories'] = StringUtil::deserialize($partner['category'], true);
			$partner['title'] = $partner['displayname'] ?: $partner['name'];
			$partner['logo'] = $partner['logo'] ? FilesModel::findByUuid($partner['logo']) : null;
			$partner['photo'] = $partner['photo'] ? FilesModel::findByUuid($partner['photo']) : null;
			$partner['detailUrl'] = $this->getDetailUrl($readerPage, $partner['alias']);

			foreach ($partner['branche'] as $key) {
				$availableBranchen[$key] = $brancheLabels[$key];
			}

			$all[] = $partner;
		}

		// Nur Branchen anbieten, zu denen es tatsächlich Partner gibt
		$availableBranchen = array_intersect_key($brancheLabels, $availableBranchen);
		$activeBranche = $this->getActiveBranche($request, array_keys($availableBranchen));

		$filtered = $activeBranche
			? array_values(array_filter($all, static fn ($partner) => \in_array($activeBranche, $partner['branche'], true)))
			: $all;

		$template->partners = $filtered;
		$template->total = \count($all);
		$template->branchen = $availableBranchen;
		$template->activeBranche = $activeBranche;
		$template->activeBrancheLabel = $activeBranche ? $brancheLabels[$activeBranche] : '';
		$template->showFilter = (bool) $model->tilastot_partner_filter && \count($availableBranchen) > 1;
		$template->filterBaseUrl = $request->getPathInfo();
		$template->empty = empty($filtered);

		$headline = StringUtil::deserialize($model->headline, true);
		$cssID = StringUtil::deserialize($model->cssID, true);
		$template->headline = $headline['value'] ?? '';
		$template->headlineUnit = $headline['unit'] ?? 'h2';
		$template->cssId = $cssID[0] ?? '';
		$template->cssClass = $cssID[1] ?? '';

		return $template->getResponse();
	}

	/**
	 * Gültigen Branchenfilter aus dem Request lesen.
	 *
	 * @param array<int,string> $available
	 */
	private function getActiveBranche(Request $request, array $available): string
	{
		$branche = (string) $request->query->get('branche', '');

		return \in_array($branche, $available, true) ? $branche : '';
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
