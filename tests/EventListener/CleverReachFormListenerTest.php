<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\CleverReachFormListener;
use Contao\Form;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CleverReachFormListenerTest extends TestCase
{
    private const URL = 'https://flow.cleverreach.test/fl/abc/confirm';

    /**
     * Der Body muss byte-identisch zu dem sein, was der Browser aus dem
     * Original-Formular von CleverReach geschickt haette.
     */
    public function testBuildsBrowserIdenticalBody(): void
    {
        $body = $this->buildBody([
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'email' => 'max@example.com',
            'tags' => ['on'],
        ]);

        $this->assertSame(
            'email_confirm=&global.firstname=Max&global.lastname=Mustermann&email=max%40example.com&tags%5B%5D=on',
            $body,
        );
    }

    public function testKeepsDotsAndEncodesUmlauts(): void
    {
        $this->assertSame(
            'email_confirm=&global.firstname=J%C3%B6rg&email=a%2Bb%40example.com',
            $this->buildBody(['firstname' => 'Jörg', 'email' => 'a+b@example.com']),
        );
    }

    public function testDoesNothingWithoutUrl(): void
    {
        $client = new MockHttpClient();
        $form = $this->form('');

        $this->invoke($client, $form);

        $this->assertSame(0, $client->getRequestsCount());
        $this->assertFalse($form->hasErrors());
    }

    public function testSubmitsToTheConfiguredUrl(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 302]));
        $form = $this->form(self::URL);

        $this->invoke($client, $form);

        $this->assertSame(1, $client->getRequestsCount());
        $this->assertFalse($form->hasErrors(), 'Eine Weiterleitung ist der Normalfall und kein Fehler.');
    }

    /**
     * Lehnt CleverReach ab, muss das Formular einen Fehler anzeigen – Contao bricht
     * dahinter ab, speichert nichts und leitet nicht weiter.
     */
    public function testAddsFormErrorWhenCleverReachRejects(): void
    {
        $client = new MockHttpClient(new MockResponse('invalid email', ['http_code' => 422]));
        $form = $this->form(self::URL);

        $this->invoke($client, $form);

        $this->assertTrue($form->hasErrors());
        $this->assertNotSame('', trim($form->getErrors()[0] ?? ''));
    }

    public function testAddsFormErrorWhenCleverReachIsUnreachable(): void
    {
        $client = new MockHttpClient(static fn (): never => throw new TransportException('Connection refused'));
        $form = $this->form(self::URL);

        $this->invoke($client, $form);

        $this->assertTrue($form->hasErrors());
    }

    public function testServerErrorIsAFormError(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 500]));
        $form = $this->form(self::URL);

        $this->invoke($client, $form);

        $this->assertTrue($form->hasErrors());
    }

    private function invoke(HttpClientInterface $client, Form $form): void
    {
        $listener = new CleverReachFormListener($client, new NullLogger());
        $listener([], [], [], $form, null);
    }

    /**
     * @param array<string, mixed> $submitted
     */
    private function buildBody(array $submitted): string
    {
        $rc = new \ReflectionClass(CleverReachFormListener::class);
        $method = $rc->getMethod('buildBody');
        $method->setAccessible(true);

        return $method->invoke(
            new CleverReachFormListener(new MockHttpClient(), new NullLogger()),
            $submitted,
        );
    }

    /**
     * Form ohne Konstruktor: der braucht ein FormModel, hier zaehlt nur arrData
     * (Contao\Hybrid::__set) und die Fehlerliste.
     */
    private function form(string $url): Form
    {
        $form = (new \ReflectionClass(Form::class))->newInstanceWithoutConstructor();
        $form->cleverreachUrl = $url;
        $form->title = 'Newsletter';

        return $form;
    }
}
