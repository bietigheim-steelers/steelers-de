<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Sponsors\SponsorsEventResolver;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Zählt die belegten Plätze eines Sponsor-Events hoch.
 *
 * Gezählt werden Personen: die Anmeldung selbst belegt einen Platz, eine ausgefüllte
 * Begleitperson einen zweiten. Gezählt wird immer, auch ohne aktive Begrenzung: wird
 * "Teilnehmerzahl begrenzen" später eingeschaltet, stimmt der Stand dann bereits.
 *
 * Der Zähler liegt als Spalte am Event und nicht in den Leads, weil sich mehrere
 * Events ein Formular teilen und die Leads keine belastbare Event-Zuordnung haben.
 * Er bleibt im Backend korrigierbar (tl_sponsors_event.participantCount).
 */
#[AsHook('processFormData')]
class SponsorsEventParticipantListener
{
    /**
     * Feldnamen, die diesen Teil enthalten, gehören zur Begleitperson – wie der
     * "-singleuse-"-Marker im SingleUseSelectFormListener. Bewusst ohne Trennzeichen,
     * weil das Formular sowohl "vorname_begleitperson" als auch "email-begleitperson"
     * verwendet.
     */
    private const COMPANION_MARKER = 'begleitperson';

    /**
     * Nur Eingabefelder belegen eine Begleitperson. Das Auswahlfeld "Anrede
     * Begleitperson" hat keine Leeroption und liefert deshalb immer einen Wert – es
     * würde sonst bei jeder Anmeldung eine Begleitperson vortäuschen.
     */
    private const COMPANION_FIELD_TYPES = ['text', 'textarea'];

    public function __construct(
        private readonly SponsorsEventResolver $resolver,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param array<string, mixed>      $arrSubmitted
     * @param array<string, mixed>      $arrData
     * @param array<string, mixed>|null $arrFiles
     * @param array<string, mixed>      $arrLabels
     */
    public function __invoke(array $arrSubmitted, array $arrData, array|null $arrFiles, array $arrLabels, Form $objForm): void
    {
        $sponsorEvent = $this->resolver->findForCurrentRequest();
        $formId = (int) ($arrData['id'] ?? 0);

        // Nur wenn das Event tatsächlich auf das abgeschickte Formular zeigt – sonst
        // würde ein zufällig vorhandener "token"-Parameter fremde Zähler verändern.
        if (null === $sponsorEvent || (int) ($sponsorEvent['form_id'] ?? 0) !== $formId) {
            return;
        }

        $seats = $this->hasCompanion($arrSubmitted, $formId) ? 2 : 1;

        // Atomar hochzählen, damit parallele Anmeldungen sich nicht überschreiben
        $this->connection->executeStatement(
            'UPDATE tl_sponsors_event SET participantCount = participantCount + ? WHERE id = ?',
            array($seats, (int) $sponsorEvent['id']),
        );
    }

    /**
     * @param array<string, mixed> $arrSubmitted
     */
    private function hasCompanion(array $arrSubmitted, int $formId): bool
    {
        $filled = array();

        foreach ($arrSubmitted as $name => $value) {
            if (!str_contains(strtolower((string) $name), self::COMPANION_MARKER)) {
                continue;
            }

            if ('' === trim(implode('', array_map('strval', (array) $value)))) {
                continue;
            }

            $filled[] = (string) $name;
        }

        if (!$filled) {
            return false;
        }

        return false !== $this->connection->fetchOne(
            'SELECT id FROM tl_form_field WHERE pid = ? AND name IN (?) AND type IN (?) LIMIT 1',
            array($formId, $filled, self::COMPANION_FIELD_TYPES),
            array(ParameterType::INTEGER, ArrayParameterType::STRING, ArrayParameterType::STRING),
        );
    }
}
