<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AppExtensionTest extends TestCase
{
    public function testPageTitleReturnsOriginalValueWhenArticleIdIsZero(): void
    {
        $extension = new AppExtension(new RequestStack());

        self::assertSame('Default Title', $extension->pageTitle('Default Title', 0));
    }

    public function testTruncateTextReturnsOriginalValueWhenShortEnough(): void
    {
        $extension = new AppExtension(new RequestStack());

        self::assertSame('Short text', $extension->truncateText('Short text', 20));
    }

    public function testTruncateTextTruncatesOnWordBoundaryAndAddsEllipsis(): void
    {
        $extension = new AppExtension(new RequestStack());

        $result = $extension->truncateText('Hello world from Steelers', 11);

        self::assertSame('Hello world…', $result);
    }

    public function testTruncateTextHandlesLongMultilineHtmlAndSpecialCharacters(): void
    {
        $extension = new AppExtension(new RequestStack());

        $value = "First line <strong>Steelers & Co.</strong>\nSecond line with \"quotes\" and 100% effort\nThird line";
        $result = $extension->truncateText($value, 65);

        self::assertSame("First line Steelers & Co.\nSecond line with \"quotes\" and 100%…", $result);
    }

    public function testTruncateTextWithExactProvidedHtmlSnippet(): void
    {
        $extension = new AppExtension(new RequestStack());

        $value = <<<'HTML'

<div class="x11i5rnm xat24cr x1mh8g0r x1vvkbs xtlvy1s x126k92a">
<div dir="auto" style="text-align: start;">Heute um 16:00 Uhr startet der Ticketverkauf für die Stehplatzblöcke D (Steelers-Fans) und D Gast (Hannover-Fans) für die beiden Heimspiele im Oberliga-Finale gegen die Scorpions!</div>
<div dir="auto" style="text-align: start;">&nbsp;</div>
</div>
HTML;

    $result = $extension->truncateText($value, 120);

    self::assertStringNotContainsString('<div', $result);
    self::assertStringContainsString('Heute um 16:00 Uhr startet der Ticketverkauf', $result);
    self::assertStringEndsWith('…', $result);
    }

    public function testAddDomainUsesCurrentRequestSchemeAndHost(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://steelers.de/news'));

        $extension = new AppExtension($requestStack);

        self::assertSame('https://steelers.de/path', $extension->addDomain('/path'));
    }

    public function testAddDomainReturnsInputWhenNoCurrentRequestExists(): void
    {
        $extension = new AppExtension(new RequestStack());

        self::assertSame('/path', $extension->addDomain('/path'));
    }

    public function testGetUrlParamsParsesQueryString(): void
    {
        $extension = new AppExtension(new RequestStack());

        self::assertSame(
            ['v' => 'abc123', 't' => '10'],
            $extension->getUrlParams('https://youtube.com/watch?v=abc123&t=10')
        );
    }

    public function testDecodeEntitiesDecodesMultipleTimes(): void
    {
        $extension = new AppExtension(new RequestStack());

        self::assertSame('Tom & Jerry', $extension->decodeEntities('Tom &amp;amp; Jerry'));
    }

    public function testAddRootAppendsPathToProjectRootReference(): void
    {
        $extension = new AppExtension(new RequestStack());

        $result = str_replace('\\', '/', $extension->addRoot('/var/tmp'));

        self::assertStringEndsWith('/var/tmp', $result);
    }
}
