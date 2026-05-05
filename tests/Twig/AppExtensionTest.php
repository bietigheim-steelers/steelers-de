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

        $value = <<<'HTML'
Die neue DEL2-Saison 2025/2026 steht in den Startl🔥chern und sie wird hoffentlich etwas ganz Besonderes werden. Insgesamt 26 Hauptrundenspiele warten auf Euch und bei 7 Heimspielen setzen wir ein echtes Highlight: Unsere Mottospieltage!
&nbsp;


Warum? Weil wir zeigen wollen, dass die Steelers weit mehr sind als „nur“ 60 Minuten der schnellsten Mannschaftssportart der Welt. Und genau deshalb gibt es wieder exklusive Aktionen und starke Ticketing-Deals, perfekt für Gruppen, Vereine und alle die Lust haben unsere Steelers-Familie kennenzulernen.
&nbsp;


Freut euch auf traditionelle Klassiker wie den Tag der Firmen und den Tag der Vereine oder auf den PORSCHE KIDS-DAY, bei dem die Kinderaugen heller strahlen als das Flutlicht in der EgeTrans Arena.
&nbsp;


Und wir setzen noch einen drauf:
- Blaulicht-Tag: Ein Dankeschön an alle Heldinnen und Helden, die täglich für uns da sind.
- Student-Day: Weil Eishockey die coolste Pause vom Lernen ist.
- Ladies-Night: Unser beliebtes Highlight zum Saisonende: Glamour, Action und Gänsehaut garantiert!
&nbsp;


Das ist Eure Chance, die Steelers live zu erleben. Bringt Eure Freunde, Familie oder Kollegen mit und begeistert sie von Eurer Leidenschaft.
&nbsp;


Einen Überblick über unsere besonderen Spieltage findet ihr hier: https://steelers.de/tickets/mottospieltage

HTML;
        $result = $extension->truncateText($value, 65);

        self::assertSame("Die neue DEL2-Saison 2025/2026 steht in den Startl🔥chern und sie…", $result);
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
