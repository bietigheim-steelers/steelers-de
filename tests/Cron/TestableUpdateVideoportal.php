<?php

declare(strict_types=1);

namespace App\Tests\Cron;

use App\Cron\updateVideoportal;

/**
 * Replaces the database access of the cron job with in-memory data, so the
 * selection and categorisation logic can be tested without Contao.
 */
final class TestableUpdateVideoportal extends updateVideoportal
{
    /** @var array<string, string> feed URL => URL/file that is actually loaded */
    public array $feedMap = [];

    /** @var array<string, array> Y-m-d of the game => game */
    public array $games = [];

    /** When set, every video is assigned this game (used for the live feeds). */
    public ?array $fallbackGame = null;

    /** @var list<array> */
    public array $newsEntries = [];

    /** @var list<string> */
    public array $logMessages = [];

    public function fetchVideos(string $feedUrl): array
    {
        return $this->getLatestVideos($feedUrl);
    }

    protected function getLatestVideos($feedUrl)
    {
        return parent::getLatestVideos($this->feedMap[$feedUrl] ?? $feedUrl);
    }

    protected function determineGame($video)
    {
        // like the real lookup: game on the publishing day, otherwise on the day before
        $published = strtotime($video['published']);

        return $this->games[date('Y-m-d', $published)]
            ?? $this->games[date('Y-m-d', strtotime('-1 day', $published))]
            ?? $this->fallbackGame;
    }

    protected function addNewsEntry($data)
    {
        $this->newsEntries[] = $data;

        return true;
    }

    protected function log($message)
    {
        $this->logMessages[] = $message;
    }
}
