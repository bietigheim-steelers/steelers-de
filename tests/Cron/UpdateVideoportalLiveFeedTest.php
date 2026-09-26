<?php

declare(strict_types=1);

namespace App\Tests\Cron;

use App\Cron\updateVideoportal;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestableUpdateVideoportal.php';

/**
 * Loads the real YouTube feeds. Needs internet access and is therefore
 * excluded by default: vendor/bin/phpunit --group network
 *
 * The database is still replaced: every video is assigned a dummy game.
 */
#[Group('network')]
final class UpdateVideoportalLiveFeedTest extends TestCase
{
    public static function feedProvider(): iterable
    {
        yield 'Steelers' => [updateVideoportal::STEELERS_FEED];
        yield 'SportEurope.TV' => [updateVideoportal::SETV_FEED];
        yield 'DEL2 Playlist' => [updateVideoportal::DEL2_FEED];
    }

    #[DataProvider('feedProvider')]
    public function testFeedIsReachableAndHasExpectedStructure(string $feedUrl): void
    {
        $videos = $this->createCron()->fetchVideos($feedUrl);

        if ([] === $videos) {
            self::markTestSkipped('Feed not reachable or empty: ' . $feedUrl);
        }

        foreach ($videos as $video) {
            self::assertNotSame('', $video['title'], 'Video without title');
            self::assertMatchesRegularExpression('#^https://www\.youtube\.com/(watch\?v=|shorts/)[\w-]+#', $video['link']);
            self::assertNotFalse(strtotime($video['published']), 'Invalid published date: ' . $video['published']);
        }
    }

    public function testRunWithLiveFeedsCreatesValidNewsEntries(): void
    {
        $cron = $this->createCron();
        $cron->fallbackGame = [
            'id' => 999,
            'homeTeam' => ['name' => 'Bietigheim Steelers'],
            'awayTeam' => ['name' => 'Testgegner'],
            'date' => strtotime('2026-01-01 19:30'),
        ];

        $cron();

        $feedLinks = [];
        foreach ([updateVideoportal::STEELERS_FEED, updateVideoportal::SETV_FEED, updateVideoportal::DEL2_FEED] as $feedUrl) {
            $feedLinks = [...$feedLinks, ...array_column($cron->fetchVideos($feedUrl), 'link')];
        }

        if ([] === $feedLinks) {
            self::markTestSkipped('No feed reachable');
        }

        foreach ($cron->newsEntries as $entry) {
            self::assertContains($entry['link'], $feedLinks);
            self::assertMatchesRegularExpression('/^01\.01\.2026 - (Razorsharp|Impressionen|Highlights|Pressekonferenz) - Bietigheim Steelers vs\. Testgegner$/', $entry['headline']);
            self::assertNotEmpty($entry['categories']);
            self::assertEmpty(array_diff($entry['categories'], [29, 30, 33, 35, 36]));
        }

        // Output for manual review: which videos would be imported right now
        fwrite(STDERR, sprintf("\n%d of %d feed videos would be imported:\n", \count($cron->newsEntries), \count($feedLinks)));
        foreach ($cron->newsEntries as $entry) {
            fwrite(STDERR, sprintf("  [%s] %s\n", implode(',', $entry['categories']), $entry['link']));
        }
    }

    private function createCron(): TestableUpdateVideoportal
    {
        return new TestableUpdateVideoportal(self::createStub(ContaoFramework::class));
    }
}
