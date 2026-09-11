<?php

namespace App\Controller\FrontendModule;

use App\Model\SponsorsEvent;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend-Modul: Sponsor-Event Formular
 *
 * Validiert den URL-Parameter "token" gegen tl_sponsors_event.access_token.
 * Nur mit einem gültigen Token wird das zugehörige Contao-Formular angezeigt.
 *
 * Einrichtung:
 * 1. Contao-Seite anlegen (z. B. "Sponsor Anmeldung")
 * 2. Dieses Modul auf der Seite einbinden
 * 3. Im Sponsor-Event die Zielseite auswählen → Zugriffslink wird automatisch generiert
 */
#[AsFrontendModule(type: 'sponsors_event_form', category: 'tilastot', template: 'frontend_module/sponsors_event_form')]
class SponsorsEventFormModule extends AbstractFrontendModuleController
{
    /**
     * Hinweis, wenn am Event kein eigener Text für ein ausgebuchtes Event hinterlegt ist.
     */
    private const BOOKED_OUT_FALLBACK = '<p>Für dieses Event sind alle Plätze belegt. Eine Anmeldung ist leider nicht mehr möglich.</p>';

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $token = Input::get('token');

        if (empty($token)) {
            $template->error        = true;
            $template->errorMsg     = 'Kein Zugriffstoken angegeben.';
            $template->formHtml     = null;
            $template->event        = null;
            $template->bookedOut    = false;
            $template->bookedOutMsg = null;
            $template->participants = null;

            return $template->getResponse();
        }

        // Token gegen Datenbank prüfen
        $sponsorEvent = SponsorsEvent::findOneBy('access_token', $token);

        if ($sponsorEvent === null || !$sponsorEvent->published) {
            $template->error        = true;
            $template->errorMsg     = 'Ungültiger oder abgelaufener Zugriffslink.';
            $template->formHtml     = null;
            $template->event        = null;
            $template->bookedOut    = false;
            $template->bookedOutMsg = null;
            $template->participants = null;

            return $template->getResponse();
        }

        // Optionale Teilnehmerbegrenzung: maxParticipants wird vom
        // App\EventListener\SponsorsEventParticipantListener bei jeder Anmeldung
        // gegen participantCount hochgezählt.
        $maxParticipants  = $sponsorEvent->limitParticipants ? (int) $sponsorEvent->maxParticipants : 0;
        $participantCount = max(0, (int) $sponsorEvent->participantCount);
        $bookedOut        = $maxParticipants > 0 && $participantCount >= $maxParticipants;

        // Ausgebucht: das Formular wird gar nicht erst gerendert, damit auch eine
        // abgeschickte Anmeldung nicht mehr verarbeitet wird.
        $formId   = (int) $sponsorEvent->form_id;
        $formHtml = '';

        if ($formId > 0 && !$bookedOut) {
            $framework = System::getContainer()->get('contao.framework');
            $framework->initialize();

            $formHtml = System::getContainer()
                ->get('contao.insert_tag.parser')
                ->replace('{{insert_form::' . $formId . '}}');
        }

        $template->error        = false;
        $template->errorMsg     = null;
        $template->formHtml     = $formHtml;
        $template->bookedOut    = $bookedOut;
        $template->bookedOutMsg = $bookedOut
            ? ((string) $sponsorEvent->bookedOutText ?: self::BOOKED_OUT_FALLBACK)
            : null;
        $template->participants = $maxParticipants > 0 && $sponsorEvent->showParticipantCount
            ? ['count' => $participantCount, 'max' => $maxParticipants]
            : null;
        $template->event    = [
            'title'     => $sponsorEvent->title,
            'startDate' => $sponsorEvent->startDate,
            'startTime' => $sponsorEvent->startTime,
            'teaser'    => $sponsorEvent->teaser,
            'addImage'  => (bool) $sponsorEvent->addImage,
            'singleSRC' => $sponsorEvent->singleSRC ? StringUtil::binToUuid($sponsorEvent->singleSRC) : null,
        ];

        return $template->getResponse();
    }
}
