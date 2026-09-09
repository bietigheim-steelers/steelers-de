<?php

declare(strict_types=1);

namespace App\InsertTag;

use App\Sponsors\SponsorsEventResolver;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;

/**
 * Insert-Tags für den Bestätigungstext eines Sponsor-Events.
 *
 * Da sich mehrere Sponsor-Events ein Formular teilen, kann der Bestätigungstext nicht
 * mehr im Formular selbst stehen. Stattdessen wird in tl_form.confirmation das
 * Insert-Tag {{sponsor_event_confirmation}} eingesetzt; Contao ersetzt es beim
 * Absenden (also noch mit dem Token in der URL) und legt das Ergebnis wie gewohnt für
 * die dynamische Bestätigungsseite in der Flash-Bag ab. Dadurch bleiben
 * App\Controller\FrontendModule\FormConfirmationModule und
 * App\EventListener\FormConfirmationRedirectListener unverändert.
 *
 * Weil Contao erst die ##Formular-Tokens## und danach die Insert-Tags ersetzt, kann
 * der Event-Text selbst keine ##Formularfelder## enthalten – die gehören in den
 * Bestätigungstext des Formulars drumherum.
 */
#[AsInsertTag(self::TAG_CONFIRMATION)]
#[AsInsertTag(self::TAG_TITLE)]
#[AsInsertTag(self::TAG_DATE)]
#[AsInsertTag(self::TAG_TIME)]
class SponsorsEventInsertTag
{
    public const TAG_CONFIRMATION = 'sponsor_event_confirmation';

    public const TAG_TITLE = 'sponsor_event_title';

    public const TAG_DATE = 'sponsor_event_date';

    public const TAG_TIME = 'sponsor_event_time';

    public function __construct(private readonly SponsorsEventResolver $resolver)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $event = $this->resolver->findForCurrentRequest();

        // Ohne gültiges Token (Direktaufruf, Reload der Bestätigungsseite) bleibt der
        // Platzhalter leer, statt unersetzt im Text zu landen.
        if (null === $event) {
            return $this->result('');
        }

        return match ($insertTag->getName()) {
            self::TAG_CONFIRMATION => $this->result((string) ($event['confirmationText'] ?? ''), OutputType::html),
            self::TAG_TITLE => $this->result(SponsorsEventResolver::formatTitle($event)),
            self::TAG_DATE => $this->result(SponsorsEventResolver::formatDate($event)),
            self::TAG_TIME => $this->result(SponsorsEventResolver::formatTime($event)),
            default => $this->result(''),
        };
    }

    /**
     * Der Wert hängt am Zugriffstoken der Anfrage und darf nie im Seitencache landen.
     */
    private function result(string $value, OutputType $outputType = OutputType::text): InsertTagResult
    {
        return new InsertTagResult($value, $outputType, new \DateTimeImmutable());
    }
}
