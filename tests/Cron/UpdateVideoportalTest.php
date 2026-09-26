<?php

declare(strict_types=1);

namespace App\Tests\Cron;

use App\Cron\updateVideoportal;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestableUpdateVideoportal.php';

/**
 * Runs the cron job against the dummy feeds in Fixtures/.
 */
final class UpdateVideoportalTest extends TestCase
{
    private TestableUpdateVideoportal $cron;

    protected function setUp(): void
    {
        $this->cron = new TestableUpdateVideoportal(self::createStub(ContaoFramework::class));
        $this->cron->feedMap = [
            updateVideoportal::STEELERS_FEED => __DIR__ . '/Fixtures/steelers.xml',
            updateVideoportal::SETV_FEED => __DIR__ . '/Fixtures/setv.xml',
            updateVideoportal::DEL2_FEED => __DIR__ . '/Fixtures/del2.xml',
        ];
        $this->cron->games = [
            '2026-01-09' => $this->game(101, '2026-01-09 19:30', 'Bietigheim Steelers', 'EC Bad Nauheim'),
            '2026-01-11' => $this->game(102, '2026-01-11 18:30', 'Bietigheim Steelers', 'Eisbären Regensburg'),
            '2026-01-12' => $this->game(103, '2026-01-12 19:30', 'Kassel Huskies', 'Bietigheim Steelers'),
            '2026-09-20' => $this->game(104, '2026-09-20 18:30', 'Bietigheim Steelers', 'Lausitzer Füchse'),
            '2026-09-25' => $this->game(105, '2026-09-25 19:30', 'Bietigheim Steelers', 'Blue Devils Weiden'),
        ];
    }

    public function testParsesYoutubeFeed(): void
    {
        $videos = $this->cron->fetchVideos(updateVideoportal::STEELERS_FEED);

        self::assertCount(7, $videos);
        self::assertSame([
            'title' => 'Razorsharp | Folge 12',
            'desc' => 'Hinter den Kulissen',
            'published' => '2026-01-10T12:00:00+01:00',
            'link' => 'https://www.youtube.com/watch?v=steel001',
        ], $videos[0]);
    }

    public function testReturnsEmptyListForUnreadableFeed(): void
    {
        self::assertSame([], $this->cron->fetchVideos(__DIR__ . '/Fixtures/does-not-exist.xml'));
    }

    public function testCreatesNewsEntriesForSteelersFeed(): void
    {
        ($this->cron)();

        $steelers = $this->entriesByLink('steel');

        self::assertSame([
            'https://www.youtube.com/watch?v=steel001' => [
                'headline' => '09.01.2026 - Razorsharp - Bietigheim Steelers vs. EC Bad Nauheim',
                'categories' => [35],
                'game_id' => 101,
            ],
            'https://www.youtube.com/watch?v=steel002' => [
                'headline' => '11.01.2026 - Impressionen - Bietigheim Steelers vs. Eisbären Regensburg',
                'categories' => [33],
                'game_id' => 102,
            ],
            'https://www.youtube.com/watch?v=steel003' => [
                'headline' => '12.01.2026 - Highlights - Kassel Huskies vs. Bietigheim Steelers',
                'categories' => [29],
                'game_id' => 103,
            ],
            // keyword only in the title, empty description, published the day after the game
            'https://www.youtube.com/watch?v=steel006' => [
                'headline' => '25.09.2026 - Highlights - Bietigheim Steelers vs. Blue Devils Weiden',
                'categories' => [29],
                'game_id' => 105,
            ],
            'https://www.youtube.com/watch?v=steel007' => [
                'headline' => '20.09.2026 - Impressionen - Bietigheim Steelers vs. Lausitzer Füchse',
                'categories' => [33],
                'game_id' => 104,
            ],
        ], $steelers);
    }

    public function testSkipsSteelersVideosWithoutCategoryOrGame(): void
    {
        ($this->cron)();

        $links = array_column($this->cron->newsEntries, 'link');

        // no matching keyword → has to be added manually
        self::assertNotContains('https://www.youtube.com/watch?v=steel004', $links);
        self::assertContains('Video with link https://www.youtube.com/watch?v=steel004 skipped. No category found', $this->cron->logMessages);
        // Razorsharp, but no game on that day
        self::assertNotContains('https://www.youtube.com/watch?v=steel005', $links);
        self::assertContains('Video with link https://www.youtube.com/watch?v=steel005 skipped. No game found', $this->cron->logMessages);
    }

    public function testCreatesNewsEntriesForSportEuropeAndDel2Feeds(): void
    {
        ($this->cron)();

        self::assertSame([
            'https://www.youtube.com/watch?v=setv0001' => [
                'headline' => '12.01.2026 - Highlights - Kassel Huskies vs. Bietigheim Steelers',
                'categories' => [36, 29],
                'game_id' => 103,
            ],
            'https://www.youtube.com/watch?v=setv0002' => [
                'headline' => '12.01.2026 - Pressekonferenz - Kassel Huskies vs. Bietigheim Steelers',
                'categories' => [36, 30],
                'game_id' => 103,
            ],
            // title starts with "Bietigheim" (strpos() === 0)
            'https://www.youtube.com/watch?v=setv0005' => [
                'headline' => '12.01.2026 - Highlights - Kassel Huskies vs. Bietigheim Steelers',
                'categories' => [36, 29],
                'game_id' => 103,
            ],
        ], $this->entriesByLink('setv'));

        self::assertSame([
            'https://www.youtube.com/watch?v=del20001' => [
                'headline' => '11.01.2026 - Highlights - Bietigheim Steelers vs. Eisbären Regensburg',
                'categories' => [36, 29],
                'game_id' => 102,
            ],
        ], $this->entriesByLink('del2'));
    }

    public function testDoesNothingWhenAllFeedsFail(): void
    {
        $this->cron->feedMap = array_fill_keys(array_keys($this->cron->feedMap), __DIR__ . '/Fixtures/does-not-exist.xml');

        ($this->cron)();

        self::assertSame([], $this->cron->newsEntries);
    }

    private function game(int $id, string $date, string $home, string $away): array
    {
        return [
            'id' => $id,
            'homeTeam' => ['name' => $home],
            'awayTeam' => ['name' => $away],
            'date' => strtotime($date),
        ];
    }

    private function entriesByLink(string $videoIdPrefix): array
    {
        $result = [];

        foreach ($this->cron->newsEntries as $entry) {
            if (str_starts_with($entry['link'], 'https://www.youtube.com/watch?v=' . $videoIdPrefix)) {
                $result[$entry['link']] = [
                    'headline' => $entry['headline'],
                    'categories' => $entry['categories'],
                    'game_id' => $entry['game']['id'],
                ];
            }
        }

        return $result;
    }
}
