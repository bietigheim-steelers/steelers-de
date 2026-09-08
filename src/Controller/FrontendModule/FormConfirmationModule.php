<?php

declare(strict_types=1);

namespace App\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Environment;
use Contao\Form;
use Contao\FormModel;
use Contao\Input;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gibt den Bestätigungstext (tl_form.confirmation) des Formulars aus, dessen Alias
 * im auto_item der URL steht (z. B. /danke/mein-formular).
 *
 * Das Modul wird einmalig in den Artikel der Bestätigungsseite eingebunden; alle
 * Formulare teilen sich diese eine Seite. Ein neues Formular braucht dadurch weder
 * eine eigene Seite noch einen eigenen Artikel.
 *
 * Den Alias hängt App\EventListener\FormConfirmationRedirectListener an die
 * Weiterleitung an; er erkennt an diesem Modul, dass die Weiterleitungsseite den
 * Alias verarbeiten kann.
 */
#[AsFrontendModule(category: 'application')]
class FormConfirmationModule extends AbstractFrontendModuleController
{
    public const TYPE = 'form_confirmation_module';

    public function __construct(
        private readonly InsertTagParser $insertTagParser,
        private readonly SimpleTokenParser $simpleTokenParser,
        private readonly ResponseContextAccessor $responseContextAccessor,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $alias = (string) Input::get('auto_item');

        // Ohne Alias in der URL gibt es nichts anzuzeigen; der Rest der Seite
        // (Überschrift, Zurück-Link …) wird trotzdem ausgegeben.
        if ('' === $alias) {
            return new Response();
        }

        $form = FormModel::findOneByAlias($alias);

        if (!$form) {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        $message = $this->getConfirmationMessage($form, $request);

        if ('' === $message) {
            return new Response();
        }

        // Bestätigungen sind Sitzungsdaten: weder cachen noch indexieren
        $this->responseContextAccessor->getResponseContext()?->get(HtmlHeadBag::class)?->setMetaRobots('noindex,nofollow');

        $template->message = $message;
        $template->form = $form->row();

        $response = $template->getResponse();
        $response->headers->set('Cache-Control', 'no-cache, no-store');

        return $response->setPrivate();
    }

    /**
     * Contao legt den bereits mit den übermittelten Werten befüllten Text vor der
     * Weiterleitung in der Flash-Bag ab. Fehlt er (Direktaufruf oder Reload), wird
     * der Bestätigungstext ohne die Formulardaten ausgegeben.
     */
    private function getConfirmationMessage(FormModel $form, Request $request): string
    {
        if ($request->hasPreviousSession()) {
            $flashBag = $request->getSession()->getFlashBag();
            $data = $flashBag->peek(Form::SESSION_CONFIRMATION_KEY);

            if (isset($data['id']) && (int) $data['id'] === (int) $form->id) {
                $flashBag->get(Form::SESSION_CONFIRMATION_KEY);

                return (string) ($data['message'] ?? '');
            }
        }

        return $this->parseConfirmation((string) $form->confirmation);
    }

    private function parseConfirmation(string $message): string
    {
        if ('' === $message) {
            return '';
        }

        try {
            $message = $this->simpleTokenParser->parse($message, array());
        } catch (\Throwable) {
            // Bedingungen mit unbekannten Tokens lassen sich ohne Formulardaten nicht auswerten
        }

        // Nicht ersetzte Platzhalter entfernen
        $message = preg_replace('/##[^#\s]+##/', '', $message);

        return $this->insertTagParser->replaceInline($message);
    }
}
