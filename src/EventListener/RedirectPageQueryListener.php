<?php

declare(strict_types=1);

namespace App\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Hängt bei Seiten vom Typ "Umleitung" mit gesetztem Häkchen "Query-Parameter weitergeben"
 * die Parameter des Aufrufs an die Ziel-URL an:
 * /ticketshop?utm_source=meta_ad → https://ziel.example/shop?utm_source=meta_ad
 *
 * Parameter, die bereits in der Ziel-URL stehen, haben Vorrang. Der Typ "Weiterleitung"
 * (forward) reicht die Query-Parameter in Contao selbst schon durch.
 */
#[AsEventListener]
class RedirectPageQueryListener
{
    public function __construct(private readonly ScopeMatcher $scopeMatcher)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof RedirectResponse || !$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();
        $page = $request->attributes->get('pageModel');

        if (!$page instanceof PageModel || 'redirect' !== $page->type || !$page->forwardQuery) {
            return;
        }

        $incoming = $request->query->all();

        if ([] === $incoming) {
            return;
        }

        $target = $response->getTargetUrl();
        $fragment = '';

        if (false !== ($pos = strpos($target, '#'))) {
            $fragment = substr($target, $pos);
            $target = substr($target, 0, $pos);
        }

        $base = $target;
        $existing = [];

        if (false !== ($pos = strpos($target, '?'))) {
            $base = substr($target, 0, $pos);
            parse_str(substr($target, $pos + 1), $existing);
        }

        $query = http_build_query($existing + $incoming, '', '&', PHP_QUERY_RFC3986);

        $response->setTargetUrl($base.'?'.$query.$fragment);
    }
}
