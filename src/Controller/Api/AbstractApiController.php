<?php

namespace App\Controller\Api;

use App\Api\ApiResponder;
use App\Api\ContentFormatter;
use App\Api\ImageUrlBuilder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\Request;

/**
 * Basis der read-only App-API unter /api/v1 (Vertrag: docs/openapi.yaml).
 *
 * Die Controller laufen ausserhalb einer Contao-Seite, das Framework wird deshalb wie im
 * SeatsApiController selbst initialisiert.
 */
abstract class AbstractApiController
{
    public function __construct(
        protected readonly ContaoFramework $framework,
        protected readonly ApiResponder $responder,
        protected readonly ImageUrlBuilder $images,
        protected readonly ContentFormatter $format,
    ) {
    }

    protected function boot(): void
    {
        $this->framework->initialize();
    }

    /**
     * Veroeffentlichungs-Bedingung (Muster aus den Contao-News-Listen).
     */
    protected function publishedCondition(string $table, int $time): string
    {
        return "$table.published='1' AND ($table.start='' OR $table.start<=$time) AND ($table.stop='' OR $table.stop>$time)";
    }

    protected function intParam(Request $request, string $name): ?int
    {
        $value = $request->query->get($name);

        return $value !== null && ctype_digit((string) $value) ? (int) $value : null;
    }

    protected function dateParam(Request $request, string $name): ?int
    {
        $value = $request->query->get($name);
        if (!\is_string($value) || $value === '') {
            return null;
        }

        $ts = strtotime($value);

        return $ts === false ? null : $ts;
    }
}
