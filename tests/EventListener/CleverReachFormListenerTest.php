<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\CleverReachFormListener;
use CleverReach\SDK\CleverReachClient;
use Contao\Form;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CleverReachFormListenerTest extends TestCase
{
    /**
     * @var list<array{method: string, url: string, body: array<string, mixed>, headers: string}>
     */
    private array $requests = [];

    protected function setUp(): void
    {
        $this->requests = [];
    }

    public function testDoesNothingWithoutGroupOrForm(): void
    {
        foreach ([['', ''], ['12', ''], ['', '34']] as [$group, $formId]) {
            $form = $this->form($group, $formId);

            $this->invoke([], $this->submitted(), $form);

            $this->assertFalse($form->hasErrors());
        }

        $this->assertCount(0, $this->requests);
    }

    /**
     * Zuerst inaktiv anlegen, dann ueber das Formular die Bestaetigungsmail ausloesen.
     */
    public function testCreatesInactiveReceiverAndSendsActivationMail(): void
    {
        $form = $this->form();

        $this->invoke([$this->json(['id' => 1]), $this->json(true)], $this->submitted(), $form);

        $this->assertFalse($form->hasErrors());
        $this->assertCount(2, $this->requests);

        [$create, $send] = $this->requests;

        $this->assertSame('POST', $create['method']);
        $this->assertSame('https://rest.cleverreach.com/v3/groups/12/receivers', $create['url']);
        $this->assertStringContainsString('Bearer secret-token', $create['headers']);
        $this->assertSame('max@example.com', $create['body']['email']);
        $this->assertArrayNotHasKey('activated', $create['body'], 'Ohne Bestaetigung darf der Empfaenger nicht aktiv sein.');
        $this->assertIsInt($create['body']['registered']);
        $this->assertSame(['firstname' => 'Max', 'lastname' => 'Mustermann'], $create['body']['global_attributes']);

        $this->assertSame('https://rest.cleverreach.com/v3/forms/34/send/activate', $send['url']);
        $this->assertSame('max@example.com', $send['body']['email']);
        $this->assertSame([
            'user_ip' => '203.0.113.7',
            'referer' => 'https://steelers.de/newsletter',
            'user_agent' => 'PHPUnit',
        ], $send['body']['doidata']);
    }

    /**
     * Die Checkbox ohne Optionswert schickt "on" – das ist die Einwilligung, kein Tag.
     */
    public function testConsentCheckboxIsNotATag(): void
    {
        $this->invoke([$this->json([]), $this->json(true)], $this->submitted(['tags' => 'on']), $this->form());
        $this->assertArrayNotHasKey('tags', $this->requests[0]['body']);

        $this->requests = [];

        $this->invoke([$this->json([]), $this->json(true)], $this->submitted(['tags' => ['on', 'fans', '']]), $this->form());
        $this->assertSame(['fans'], $this->requests[0]['body']['tags']);
    }

    public function testEmptyAttributesAreOmitted(): void
    {
        $this->invoke([$this->json([]), $this->json(true)], $this->submitted(['lastname' => '']), $this->form());

        $this->assertSame(['firstname' => 'Max'], $this->requests[0]['body']['global_attributes']);
    }

    /**
     * Kennt CleverReach die Adresse schon, wird nur die Bestaetigungsmail ausgeloest.
     */
    public function testExistingReceiverStillGetsActivationMail(): void
    {
        $form = $this->form();

        $this->invoke([
            $this->json(['error' => ['code' => 400, 'message' => 'duplicate']], 400),
            $this->json(true),
        ], $this->submitted(), $form);

        $this->assertFalse($form->hasErrors());
        $this->assertCount(2, $this->requests);
    }

    /**
     * Ein bereits aktiver Empfaenger bekommt keine Mail; der Besucher soll davon nichts
     * erfahren, also kein Formularfehler.
     */
    public function testAlreadyActiveReceiverIsNotAnError(): void
    {
        $form = $this->form();

        $this->invoke([
            $this->json(['error' => ['code' => 400, 'message' => 'duplicate']], 400),
            $this->json(['error' => ['code' => 400, 'message' => 'receiver already active']], 400),
        ], $this->submitted(), $form);

        $this->assertFalse($form->hasErrors());
    }

    public function testRejectedReceiverIsAFormError(): void
    {
        $form = $this->form();

        $this->invoke([$this->json(['error' => ['code' => 400, 'message' => 'invalid email']], 400)], $this->submitted(), $form);

        $this->assertTrue($form->hasErrors());
        $this->assertNotSame('', trim($form->getErrors()[0] ?? ''));
        $this->assertCount(1, $this->requests, 'Nach dem Fehler darf keine Bestaetigungsmail ausgeloest werden.');
    }

    public function testFailedActivationMailForNewReceiverIsAFormError(): void
    {
        $form = $this->form();

        $this->invoke([
            $this->json(['id' => 1]),
            $this->json(['error' => ['code' => 400, 'message' => 'form not found']], 400),
        ], $this->submitted(), $form);

        $this->assertTrue($form->hasErrors());
    }

    public function testInvalidTokenIsAFormError(): void
    {
        $form = $this->form();

        $this->invoke([$this->json(['error' => ['code' => 401, 'message' => 'Unauthorized']], 401)], $this->submitted(), $form);

        $this->assertTrue($form->hasErrors());
    }

    public function testMissingTokenIsAFormErrorWithoutRequest(): void
    {
        $form = $this->form();

        $this->invoke([], $this->submitted(), $form, '');

        $this->assertTrue($form->hasErrors());
        $this->assertCount(0, $this->requests);
    }

    public function testServerErrorIsAFormError(): void
    {
        $form = $this->form();

        $this->invoke([$this->json([], 500)], $this->submitted(), $form);

        $this->assertTrue($form->hasErrors());
    }

    public function testMissingEmailIsAFormErrorWithoutRequest(): void
    {
        $form = $this->form();

        $this->invoke([], ['firstname' => 'Max'], $form);

        $this->assertTrue($form->hasErrors());
        $this->assertCount(0, $this->requests);
    }

    public function testRetriesAfterTransientTransportError(): void
    {
        $form = $this->form();

        $this->invoke([
            new TransportException('Could not resolve host'),
            $this->json(['id' => 1]),
            $this->json(true),
        ], $this->submitted(), $form);

        $this->assertFalse($form->hasErrors());
        $this->assertCount(3, $this->requests);
    }

    public function testPersistentTransportErrorIsAFormError(): void
    {
        $form = $this->form();

        $this->invoke([
            new TransportException('Connection refused'),
            new TransportException('Connection refused'),
        ], $this->submitted(), $form);

        $this->assertTrue($form->hasErrors());
    }

    /**
     * @param list<MockResponse|\Throwable> $responses Eine Antwort je erwarteter Anfrage
     * @param array<string, mixed>          $submitted
     */
    private function invoke(array $responses, array $submitted, Form $form, string $token = 'secret-token'): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $this->requests[] = [
                'method' => $method,
                'url' => $url,
                'body' => json_decode((string) ($options['body'] ?? ''), true) ?? [],
                'headers' => implode("\n", $options['headers'] ?? []),
            ];

            $next = array_shift($responses);

            if ($next instanceof \Throwable) {
                throw $next;
            }

            return $next ?? new MockResponse('{}');
        });

        $factory = new Psr17Factory();
        $client = new CleverReachClient($token, 'https://rest.cleverreach.com/v3/', new Psr18Client($http, $factory, $factory), $factory, $factory);

        $stack = new RequestStack();
        $stack->push(Request::create('https://steelers.de/newsletter?utm=x', 'POST', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]));

        (new CleverReachFormListener($client, $stack, new NullLogger()))($submitted, [], [], $form, null);
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function submitted(array $override = []): array
    {
        return $override + [
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'email' => 'max@example.com',
        ];
    }

    private function json(mixed $data, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($data, JSON_THROW_ON_ERROR), [
            'http_code' => $status,
            'response_headers' => ['Content-Type: application/json'],
        ]);
    }

    /**
     * Form ohne Konstruktor: der braucht ein FormModel, hier zaehlt nur arrData
     * (Contao\Hybrid::__set) und die Fehlerliste.
     */
    private function form(string $groupId = '12', string $formId = '34'): Form
    {
        $form = (new \ReflectionClass(Form::class))->newInstanceWithoutConstructor();
        $form->cleverreachGroupId = $groupId;
        $form->cleverreachFormId = $formId;
        $form->title = 'Newsletter';

        return $form;
    }
}
