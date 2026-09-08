<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Controller\FrontendModule\FormConfirmationModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Form;
use Contao\FormModel;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Hängt bei der Weiterleitung nach dem Absenden eines Formulars den Formular-Alias
 * an die URL an: "/danke" wird zu "/danke/mein-formular".
 *
 * Contao leitet auf die in tl_form.jumpTo hinterlegte Seite weiter, ohne Parameter.
 * Statt in processFormData einzugreifen (und damit die Reihenfolge der übrigen Hooks
 * zu stören), wird hier nur die fertige Weiterleitung umgeschrieben.
 *
 * Umgeschrieben wird ausschließlich, wenn die Zielseite das Frontend-Modul
 * "Formular-Bestätigung" enthält – sonst würde der zusätzliche Pfadteil auf allen
 * anderen Weiterleitungsseiten zu einem 404 führen (unbenutzter Routen-Parameter).
 */
#[AsEventListener]
class FormConfirmationRedirectListener
{
    /**
     * Verschachtelungstiefe, bis zu der ein Inhaltselement (z. B. in einer
     * Elementgruppe) auf seinen Artikel zurückgeführt wird.
     */
    private const MAX_NESTING = 5;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof RedirectResponse || !$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->hasSession() || !$request->getSession()->isStarted()) {
            return;
        }

        $data = $request->getSession()->getFlashBag()->peek(Form::SESSION_CONFIRMATION_KEY);

        if (!isset($data['id'])) {
            return;
        }

        $this->framework->initialize();

        $form = $this->framework->getAdapter(FormModel::class)->findById((int) $data['id']);

        if (null === $form || !$form->alias || !$form->jumpTo) {
            return;
        }

        $jumpTo = $this->framework->getAdapter(PageModel::class)->findById((int) $form->jumpTo);

        if (null === $jumpTo) {
            return;
        }

        try {
            // Nur die Weiterleitung auf die Zielseite des Formulars umschreiben
            if ($response->getTargetUrl() !== $this->urlGenerator->generate($jumpTo, array(), UrlGeneratorInterface::ABSOLUTE_URL)) {
                return;
            }

            if (!$this->showsConfirmation($jumpTo)) {
                return;
            }

            $response->setTargetUrl($this->urlGenerator->generate(
                $jumpTo,
                array('parameters' => '/' . $form->alias),
                UrlGeneratorInterface::ABSOLUTE_URL,
            ));
        } catch (ExceptionInterface) {
            // Lässt sich keine URL erzeugen, bleibt es bei der Weiterleitung von Contao
        }
    }

    /**
     * Prüft, ob auf der Seite das Modul "Formular-Bestätigung" ausgegeben wird –
     * entweder über das Layout oder über ein Inhaltselement in einem Artikel.
     */
    private function showsConfirmation(PageModel $page): bool
    {
        $moduleIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_module WHERE type = ?',
            array(FormConfirmationModule::TYPE),
        ));

        if (!$moduleIds) {
            return false;
        }

        return $this->layoutHasModule($page, $moduleIds) || $this->articlesHaveModule($page, $moduleIds);
    }

    /**
     * @param array<int> $moduleIds
     */
    private function layoutHasModule(PageModel $page, array $moduleIds): bool
    {
        $page->loadDetails();

        if (!$page->layout) {
            return false;
        }

        $layout = $this->framework->getAdapter(LayoutModel::class)->findById((int) $page->layout);

        if (null === $layout) {
            return false;
        }

        foreach (StringUtil::deserialize($layout->modules, true) as $module) {
            if (!empty($module['enable']) && \in_array((int) ($module['mod'] ?? 0), $moduleIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int> $moduleIds
     */
    private function articlesHaveModule(PageModel $page, array $moduleIds): bool
    {
        $articleIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_article WHERE pid = ? AND published = 1',
            array((int) $page->id),
        ));

        if (!$articleIds) {
            return false;
        }

        $elements = $this->connection->fetchAllAssociative(
            "SELECT pid, ptable FROM tl_content WHERE type = 'module' AND module IN (?) AND invisible = 0",
            array($moduleIds),
            array(ArrayParameterType::INTEGER),
        );

        foreach ($elements as $element) {
            if (\in_array($this->findArticle($element), $articleIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Löst ein verschachteltes Inhaltselement (z. B. in einer Elementgruppe) auf
     * den Artikel auf, in dem es steckt.
     *
     * @param array{pid: int|string, ptable: string} $element
     */
    private function findArticle(array $element): int
    {
        for ($i = 0; $i < self::MAX_NESTING && 'tl_content' === $element['ptable']; ++$i) {
            $parent = $this->connection->fetchAssociative(
                'SELECT pid, ptable FROM tl_content WHERE id = ?',
                array((int) $element['pid']),
            );

            if (!$parent) {
                return 0;
            }

            $element = $parent;
        }

        return 'tl_article' === $element['ptable'] ? (int) $element['pid'] : 0;
    }
}
