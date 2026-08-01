<?php

namespace App\Controller\FrontendModule;

use App\Model\Partners;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Detailansicht eines Partners.
 *
 * Der Partner wird über den Alias im auto_item der URL ermittelt
 * (z. B. /business/partner/muster-gmbh).
 */
#[AsFrontendModule(category: 'tilastot')]
class PartnerReaderModule extends AbstractFrontendModuleController
{
	public function __construct(
		private readonly ContentUrlGenerator $urlGenerator,
		private readonly ResponseContextAccessor $responseContextAccessor,
		private readonly HtmlDecoder $htmlDecoder,
	) {
	}

	protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
	{
		$alias = (string) Input::get('auto_item');

		// Ohne Alias in der URL gibt es nichts anzuzeigen. Auf einer Detailseite
		// mit "Element erforderlich" kann dieser Fall gar nicht auftreten, auf
		// einer kombinierten Übersichtsseite bleibt das Modul dadurch stumm.
		if ('' === $alias) {
			return new Response();
		}

		$partner = Partners::findOneBy(array('alias = ? AND published = 1'), array($alias));

		if (!$partner) {
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		$brancheLabels = Partners::getBrancheOptions();
		$categoryLabels = Partners::getCategoryOptions();

		$p = $partner->row();
		$p['branche'] = Partners::filterValidBranchen(StringUtil::deserialize($p['branche'], true));
		$p['brancheLabels'] = array_map(static fn ($key) => $brancheLabels[$key], $p['branche']);
		$p['categories'] = StringUtil::deserialize($p['category'], true);
		$p['categoryLabels'] = array_values(array_intersect_key($categoryLabels, array_flip($p['categories'])));
		$p['title'] = $p['displayname'] ?: $p['name'];
		$p['logo'] = $p['logo'] ? FilesModel::findByUuid($p['logo']) : null;
		$p['photo'] = $p['photo'] ? FilesModel::findByUuid($p['photo']) : null;

		$cssID = StringUtil::deserialize($model->cssID, true);

		$template->partner = $p;
		$template->backUrl = $this->getBackUrl($model, $p['branche']);
		$template->cssId = $cssID[0] ?? '';
		$template->cssClass = $cssID[1] ?? '';

		$this->updatePageMetadata($p);

		return $template->getResponse();
	}

	/**
	 * Seitentitel und Meta-Description auf den Partner setzen.
	 *
	 * @param array<string,mixed> $partner
	 */
	private function updatePageMetadata(array $partner): void
	{
		$responseContext = $this->responseContextAccessor->getResponseContext();

		if (!$responseContext?->has(HtmlHeadBag::class)) {
			return;
		}

		$htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
		$htmlHeadBag->setTitle($this->htmlDecoder->inputEncodedToPlainText((string) $partner['title']));

		if ($partner['teaser']) {
			$htmlHeadBag->setMetaDescription($this->htmlDecoder->inputEncodedToPlainText((string) $partner['teaser']));
		} elseif ($partner['description']) {
			$htmlHeadBag->setMetaDescription($this->htmlDecoder->htmlToPlainText((string) $partner['description']));
		}
	}

	/**
	 * Link zurück zur Partnerübersicht – wenn möglich mit vorausgewählter Branche.
	 *
	 * @param array<int,string> $branchen
	 */
	private function getBackUrl(ModuleModel $model, array $branchen): string
	{
		if (!$model->jumpTo) {
			return '';
		}

		$page = PageModel::findPublishedById((int) $model->jumpTo);

		if (null === $page) {
			return '';
		}

		try {
			$url = $this->urlGenerator->generate($page);
		} catch (\Throwable) {
			return '';
		}

		if (!empty($branchen)) {
			$url .= '?branche=' . urlencode($branchen[0]);
		}

		return $url;
	}
}
