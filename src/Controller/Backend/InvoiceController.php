<?php

declare(strict_types=1);

namespace App\Controller\Backend;

use App\Invoice\InvoiceCalculator;
use App\Invoice\InvoiceMailer;
use App\Invoice\InvoicePdfRenderer;
use App\Invoice\InvoiceRepository;
use Contao\BackendUser;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Rechnungsseite für Gruppenbestellungen. Liegt unter /contao/…, ist also nur mit
 * Backend-Login erreichbar (Contao-Firewall, _scope backend). Der Link steht in der
 * Info-Mail an die Mitarbeiter.
 *
 * @internal
 */
#[Route(defaults: ['_scope' => 'backend'])]
class InvoiceController
{
    public function __construct(
        private readonly InvoiceRepository $invoices,
        private readonly InvoicePdfRenderer $renderer,
        private readonly InvoiceMailer $mailer,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $router,
        private readonly ContaoCsrfTokenManager $csrf,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/contao/rechnungen/{id}', name: 'backend_invoice_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $invoice = $this->invoices->find($id);

        if (null === $invoice) {
            return new Response('Rechnung nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        if ($request->isMethod('POST')) {
            return $this->handlePost($id, $invoice, $request);
        }

        $session = $request->hasSession() ? $request->getSession() : null;
        $flashes = ($session && method_exists($session, 'getFlashBag')) ? $session->getFlashBag()->get('invoice') : [];

        return new Response($this->twig->render('@Contao_App/invoice_edit.html.twig', [
            'invoice' => $invoice,
            'items' => array_map(static fn (array $i): array => $i + ['price' => InvoiceCalculator::formatMoney((int) $i['unit_price_cents'])], $invoice['items']),
            'totals' => InvoiceCalculator::totals($invoice['items'], (float) $invoice['vat_rate']),
            'flashes' => $flashes,
            'sent' => InvoiceRepository::STATUS_SENT === $invoice['status'],
            'has_letterhead' => $this->renderer->hasLetterhead(),
            'request_token' => $this->csrf->getDefaultTokenValue(),
            'pdf_url' => $this->router->generate('backend_invoice_pdf', ['id' => $id, 'v' => $invoice['tstamp']]),
            'list_url' => $this->router->generate('contao_backend', ['do' => 'invoices']),
        ]));
    }

    #[Route('/contao/rechnungen/{id}/pdf', name: 'backend_invoice_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function pdf(int $id): Response
    {
        $invoice = $this->invoices->find($id);

        if (null === $invoice) {
            return new Response('Rechnung nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        $name = $invoice['invoice_number'] ? 'Rechnung-'.$invoice['invoice_number'] : 'Rechnung-Entwurf-'.$id;

        return new Response($this->renderer->render($invoice), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * @param array<string, mixed> $invoice
     */
    private function handlePost(int $id, array $invoice, Request $request): Response
    {
        $input = $request->request;
        $fields = [
            'invoice_date' => $this->parseDate((string) $input->get('invoice_date')) ?? (int) $invoice['invoice_date'],
            'recipient' => trim((string) $input->get('recipient')),
            'subject' => trim((string) $input->get('subject')),
            'intro_text' => trim((string) $input->get('intro_text')),
            'outro_text' => trim((string) $input->get('outro_text')),
            'vat_rate' => max(0.0, min(100.0, (float) str_replace(',', '.', (string) $input->get('vat_rate', '0')))),
            'send_to' => trim((string) $input->get('send_to')),
        ];

        $items = [];

        foreach ((array) $input->all('items') as $row) {
            $description = trim((string) ($row['description'] ?? ''));
            $quantity = max(0, (int) ($row['quantity'] ?? 0));
            $price = InvoiceCalculator::parseMoney((string) ($row['price'] ?? ''));

            if ('' === $description && 0 === $quantity && 0 === $price) {
                continue;
            }

            $items[] = ['description' => $description, 'quantity' => $quantity, 'unit_price_cents' => $price];
        }

        $this->invoices->saveEdits($id, $fields, $items);

        if ('send' !== $input->get('action')) {
            $this->flash($request, 'ok', InvoiceRepository::STATUS_SENT === $invoice['status']
                ? 'Gespeichert. Achtung: Diese Rechnung wurde bereits verschickt, der Versand enthält diese Änderung nicht.'
                : 'Gespeichert.');

            return $this->back($id);
        }

        if (!filter_var($fields['send_to'], FILTER_VALIDATE_EMAIL)) {
            $this->flash($request, 'error', 'Nicht verschickt: Die Empfängeradresse ist keine gültige E-Mail-Adresse.');

            return $this->back($id);
        }

        if ([] === $items) {
            $this->flash($request, 'error', 'Nicht verschickt: Die Rechnung hat keine Positionen.');

            return $this->back($id);
        }

        try {
            $this->invoices->ensureNumber($id);
            $invoice = $this->invoices->find($id);
            $this->mailer->sendInvoicePdf($invoice, $this->renderer->render($invoice), $fields['send_to']);
        } catch (\Throwable $exception) {
            $this->logger->error('Rechnung {id} konnte nicht verschickt werden', ['id' => $id, 'exception' => $exception]);
            $this->flash($request, 'error', 'Nicht verschickt: '.$exception->getMessage());

            return $this->back($id);
        }

        $user = $this->security->getUser();
        $by = $user instanceof BackendUser ? ((string) $user->name ?: (string) $user->username) : ($user?->getUserIdentifier() ?? '');

        $this->invoices->markSent($id, $fields['send_to'], $by);
        $this->flash($request, 'ok', 'Rechnung '.$invoice['invoice_number'].' wurde an '.$fields['send_to'].' verschickt.');

        return $this->back($id);
    }

    private function parseDate(string $value): ?int
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date ? $date->setTime(12, 0)->getTimestamp() : null;
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session && method_exists($session, 'getFlashBag')) {
            $session->getFlashBag()->add('invoice', [$type, $message]);
        }
    }

    private function back(int $id): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('backend_invoice_edit', ['id' => $id]));
    }
}
