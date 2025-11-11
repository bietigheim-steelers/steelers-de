<?php

namespace App\Cron;

use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use App\Tilastot\Model\Standings;
use App\Tilastot\Model\Games;
use Contao\NewsArchiveModel;
use Contao\System;
use Contao\Database;

// 

/**
 * @CronJob("*\/10 * * * *")
 */
class updateVideoportal
{
    private $framework;
    private $pid = 7;
    private $currentSeasonCategory = 36;
    private $logFile = 'debug_cronjob.log';
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(): void
    {
        $this->framework->initialize();

        // Clear the log file
        file_put_contents($this->logFile, '');

        $this->log("Starting update process");

        $steelersFeed = 'https://www.youtube.com/feeds/videos.xml?channel_id=UCaVaIAlCziRfT9A4Yw5cb5Q';
        $setvFeed1 = 'https://www.youtube.com/feeds/videos.xml?channel_id=UCy7nHxKl2ZQ9ZkxFlvt8lWQ';
        $del2Feed1 = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLLj7IG0GXbwpeJEamF3R2Iy2LDasYFDZR';
        // $spradeFeed1 = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLnuQ1LaZpIteElx6nKfKsytUHq0g6xbsj';
        // $spradeFeed2 = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLnuQ1LaZpItdGEy0y2NDwVvXxJvn8LXU6';

        $steelersVideos = $this->getLatestVideos($steelersFeed);
        $setvVideos1 = $this->getLatestVideos($setvFeed1);
        $del2Videos1 = $this->getLatestVideos($del2Feed1);
        // $spradeVideos1 = $this->getLatestVideos($spradeFeed1);
        // $spradeVideos2 = $this->getLatestVideos($spradeFeed2);
        $setvVideos = array_merge($setvVideos1, $del2Videos1);

        // Steelers Latest Videos
        foreach ($steelersVideos as $video) {
            $this->log("Check video: " . $video['title'] . " - " . $video['desc']);
            if (strpos($video['title'], 'Razorsharp') !== false) {
                $display_category = 'Razorsharp';
                $category = 35;
            } elseif (strpos($video['desc'], 'Impressionen') !== false) {
                $display_category = 'Impressionen';
                $category = 33;
            } else {
                // not a game needs to be added manually
                continue;
            }

            $game = $this->determineGame($video);
            $headline = date('d.m.Y', $game['date']) . " - " . $display_category . " - " . $game['homeTeam']['name'] . " vs. " . $game['awayTeam']['name'];
            if ($game['date'] > 0) {
                $this->addNewsEntry([
                    'headline' => $headline,
                    'link' => $video['link'],
                    'game' => $game,
                    'categories' => [$category]
                ]);
            } else {
                $this->log("News entry with headline '$headline' skipped. No game found");
            }
        }

        // SportEurope.TV Latest Videos
        foreach ($setvVideos as $video) {
            if (strpos($video['title'], 'Bietigheim') == false) {
                continue;
            }
            $game = $this->determineGame($video);

            $category = 0;


            if (strpos($video['title'], 'Highlights') !== false) {
                $display_category = 'Highlights';
                $category = 29;
            } elseif (strpos($video['title'], 'Pressekonferenz') !== false) {
                $display_category = 'Pressekonferenz';
                $category = 30;
            }

            var_dump($video['title'], $display_category, $category);


            if ($category == 0) {
                continue;
            }

            $headline = date('d.m.Y', $game['date']) . " - " . $display_category . " - " . $game['homeTeam']['name'] . " vs. " . $game['awayTeam']['name'];

            $this->addNewsEntry([
                'headline' => $headline,
                'link' => $video['link'],
                'game' => $game,
                'categories' => [$this->currentSeasonCategory, $category]
            ]);
        }
    }
    private function addNewsEntry($data)
    {
        $this->log("Adding news entry: " . print_r($data, true));

        $aliasExists = function (string $alias): bool {
            return false;
        };

        // Generate alias
        $alias = System::getContainer()->get('contao.slug')->generate($data['headline'], NewsArchiveModel::findByPk($this->pid)->jumpTo, $aliasExists);

        // Check if an entry with the same video URL already exists
        $existingEntry = \Database::getInstance()->prepare("SELECT * FROM tl_news WHERE playerUrl = ?")
            ->execute($data['link']);

        if ($existingEntry->numRows > 0) {
            $this->log("News entry with alias '$alias' already exists. Skipping.");
            return false;
        }

        if (!$data['game']['date']) {
            $this->log("News entry with alias '$alias' skipped. No game found");
            return false;
        }

        // Set default values for optional fields
        $sqlData = [
            'headline' => $data['headline'],
            'date' => $data['game']['date'],
            'time' => $data['game']['date'],
            'alias' => $alias,
            'pid' => $this->pid,
            'published' => '1',
            'author' => 1,
            'addMedia' => '1',
            'playerType' => 'playerUrl',
            'playerUrl' => $data['link'],
            'game_id' => $data['game']['id'],
            'tstamp' => time()
        ];

        $this->log("insert news: " . print_r($sqlData, true));

        // Insert the new record
        $result = \Database::getInstance()->prepare("INSERT INTO tl_news %s")
            ->set($sqlData)
            ->execute();

        $insertId = $result->insertId;

        // categories after ceating the news entry
        // Insert categories after creating the news entry
        foreach ($data['categories'] as $category) {
            \Database::getInstance()->prepare("INSERT INTO tl_news_categories (news_id, category_id) VALUES (?, ?)")
                ->execute($insertId, $category);
        }


        return $result->affectedRows > 0;
    }

    private function determineGame($video)
    {
        $this->log("determing game: " . print_r($video['published'], true));

        $videoDate = strtotime($video['published']);
        $gameDateStart = strtotime(date('Y-m-d 00:00:00', $videoDate));
        $gameDateEnd = strtotime(date('Y-m-d 23:59:59', $videoDate));
        $previousDateStart = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day', $videoDate)));
        $previousDateEnd = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day', $videoDate)));

        // Find game on the same date
        $game = Games::findOneBy(['gamedate >= ? AND gamedate <= ?'], [$gameDateStart, $gameDateEnd]);

        // If no game found, check the previous day
        if (!$game) {
            $game = Games::findOneBy(['gamedate >= ? AND gamedate <= ?'], [$previousDateStart, $previousDateEnd]);
        }

        if ($game) {

            $homeTeam = Standings::findByIdAndRound($game->hometeam, $game->round);
            $awayTeam = Standings::findByIdAndRound($game->awayteam, $game->round);

            return [
                'id' => $game->id,
                'homeTeam' => $homeTeam,
                'awayTeam' => $awayTeam,
                'date' => $game->gamedate
            ];
        }

        return $game;
    }


    private function getLatestVideos($feedUrl)
    {
        $xml = simplexml_load_file($feedUrl);
        if ($xml === false) {
            return "Failed to load XML";
        }

        $videos = [];
        foreach ($xml->entry as $entry) {
            $namespaces = $entry->getNamespaces(true);
            $media = $entry->children($namespaces['media']);

            $video = [
                'title' => (string) $entry->title,
                'desc' => (string) $media->{'group'}->{'description'},
                'published' => (string) $entry->published,
                'link' => (string) $entry->link['href']
            ];
            $videos[] = $video;
        }

        return $videos;
    }

    private function log($message)
    {
        file_put_contents($this->logFile, $message . PHP_EOL, FILE_APPEND);
    }
}
