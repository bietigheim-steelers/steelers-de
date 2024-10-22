<?php

/*
 * This file is part of the TilastotBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tilastot\Utils;

use Contao\StringUtil;
use App\Tilastot\Model\Rounds;
use App\Tilastot\Model\Games;
use App\Tilastot\Model\Standings;
use App\Tilastot\Model\Players;
use App\Tilastot\Model\PlayerStats;

class ApiHockeydata
{
    const API_URL = 'https://api.hockeydata.net/data/ebel/';
    const TEAM_ID = 54744;
    const IGNORED_GAMES = [];

    private static function call($endpoint, $standingsid, $apiKey, $teamid = null)
    {
        $url = self::API_URL . $endpoint;
        $params = [
            'apiKey' => $apiKey,
            'referer' => 'deb-online.live', // needs to be changed after we got our own API key
            'divisionId' => $standingsid
        ];

        if ($teamid) {
            $params['teamId'] = $teamid;
        }

        return TilastotApi::call($url, array(), $params);
    }

    public static function refreshAll($round)
    {
        self::refreshStandings($round);
        self::refreshGames($round);
        self::refreshPlayers($round);
    }

    private static function updateTeam($team, $round)
    {
        $obj = new \stdClass();
        $obj->id = $team->id;
        $obj->shortcut = $team->shortcut;
        $obj->name = $team->name;
        return TilastotApi::updateTeam($obj, $round);
    }

    private static function uuidToInt($uuid)
    {
        // Remove hyphens from the UUID
        $hex = explode('-', $uuid);

        // Convert the hexadecimal string to a decimal integer
        $int = base_convert($hex[0], 16, 10);

        return $int;
    }

    public static function refreshStandings($round)
    {
        if (!$round) {
            return "round missing!";
        }
        $r = Rounds::findById($round);

        $data = json_decode(self::call('Standings', $r->standingsid, $r->apikey));
        foreach ($data->data->rows as $team) {
            if (!property_exists($team, 'id') || !$team->id) {
                continue;
            }

            $t = Standings::findAll(array(
                'limit'   => 1,
                'column'  => array('tilastotid=?', 'round=?'),
                'value'   => array($team->id, $round)
            ));

            if (!$t) {
                $t = self::updateTeam($team, $round);
            }

            $t->name = $team->teamLongname;
            $t->tilastotid = $team->id;
            $t->round = $round;
            $t->shortname = $team->teamShortname;
            $t->games = $team->gamesPlayed;
            $t->rw = $team->gamesWon;
            $t->ow = $team->gamesWonInOt;
            //          $t->pw = $team->pw;
            //          $t->pl = $team->pl;
            $t->ol = $team->gamesLostInOt;
            $t->rl = $team->gamesLost;
            $t->points = $team->points;
            $t->goalsfor = $team->goalsFor;
            $t->goalsagainst = $team->goalsAgainst;
            $t->save();
        }
    }

    public static function refreshPlayers($round)
    {
        if (!$round) {
            return "round missing!";
        }
        $r = Rounds::findById($round);

        $rosterData = json_decode(self::call('GetTeamDetails', $r->standingsid, $r->apikey, self::TEAM_ID));
        foreach ($rosterData->data->teamRoster as $player) {

            if (!$player->id) {
                continue;
            }

            $p = Players::findAll(array(
                'limit'   => 1,
                'column'  => array('tilastotid=?'),
                'value'   => array($player->id)
            ));
            if (!$p) {
                $p = new Players();
                $p->tilastotid = $player->id;
            }

            $birthday = date_parse_from_format("d.m.Y", $player->birthdate->formattedShort);
            $p->firstname = $player->playerFirstname;
            $p->lastname = ucwords(mb_strtolower($player->playerLastname, 'UTF-8'), '-');
            $p->jersey = $player->playerJerseyNr;
            $p->position = $player->position;
            $p->nationality = $player->nation;
            $p->shoots = $player->shootsCatches == 1 ? 'R' : 'L';
            $p->birthday = mktime(0, 0, 0, $birthday['month'], $birthday['day'], $birthday['year']);
            $p->height = $player->playerHeight;
            $p->weight = $player->playerWeight;

            $p->alias = StringUtil::generateAlias($p->firstname . " " . $p->lastname);

            if ($p->eliteprospectsid) {
                $rss = new \SimpleXMLElement('http://eliteprospects.com/rss_player_stats2.php?player=' . $p->eliteprospectsid, 0, true);
                foreach ($rss->xpath('channel/item') as $item) {
                    $p->epstats = mb_convert_encoding($item->description, 'ISO-8859-1', 'UTF-8');
                    break;
                }
            }

            $p->tstamp = time();
            $savedPlayer = $p->save();

            foreach ($rosterData->data->playerStats as $stat) {
                if ($player->id == $stat->id) {
                    $stats = PlayerStats::findAll(array(
                        'limit'   => 1,
                        'column'  => array('pid=?', 'round=?'),
                        'value'   => array($savedPlayer->id, $round)
                    ));
                    if (!$stats) {
                        $stats = new PlayerStats();
                        $stats->pid = $savedPlayer->id;
                    }
                    $stats->round = $round;
                    $stats->games = $stat->gamesPlayed;
                    $stats->goals = $stat->goals;
                    $stats->assists = $stat->assists;
                    $stats->points = $stat->points;
                    $stats->penalties = $stat->penaltyMinutes;
                    $stats->plusminus = $stat->plusMinus;
                    $faceoffs = explode('/', $stat->faceoffs);
                    $stats->faceoffswon = $faceoffs[0];
                    $stats->faceoffslost = $faceoffs[1];
                    $stats->shots = $stat->shotsOnGoal;
                    $stats->tstamp = time();
                    $stats->save();
                    break;
                }
            }
        }
    }

    public static function refreshGames($round)
    {
        if (!$round) {
            return "round missing!";
        }
        $r = Rounds::findById($round);

        $data = json_decode(self::call('Schedule', $r->standingsid, $r->apikey));

        foreach ($data->data->rows as $game) {
            if (in_array($game->id, self::IGNORED_GAMES)) {
                // skip games from the ignored list
                continue;
            }
            if ($game->awayTeamId != self::TEAM_ID && $game->homeTeamId != self::TEAM_ID) {
                // skip non steelers games
                continue;
            }


            $date = date_parse_from_format("d.m.Y", $game->scheduledDate->value);
            $time = explode(':', $game->scheduledTime);

            $game->id = self::uuidToInt($game->id);

            $g = Games::findById($game->id);
            if (!$g) {
                $g = new Games();
                $g->id = $game->id;
            }

            $homeTeam = (object)[
                'id' => $game->homeTeamId,
                'shortcut' => $game->homeTeamShortName,
                'name' => $game->homeTeamLongName
            ];
            $awayTeam = (object)[
                'id' => $game->awayTeamId,
                'shortcut' => $game->awayTeamShortName,
                'name' => $game->awayTeamLongName
            ];

            self::updateTeam($homeTeam, $round);
            self::updateTeam($awayTeam, $round);
            $g->hometeam = $game->homeTeamId;
            $g->awayteam = $game->awayTeamId;
            $g->gamedate = mktime($time[0], $time[1], 0, $date['month'], $date['day'], $date['year']);
            $g->gametime = $game->scheduledTime;
            $g->round = $round;
            $g->periodscore = $game->periodResults;
            $g->gameday = $game->gameDay;
            $g->gamestatus = $game->gameStatus; // 4 = ended
            if ($game->isOvertime) {
                $g->resulttype = 'OT';
            } else if ($game->isShootOut) {
                $g->resulttype = 'SO';
            }
            $g->homescore = $game->homeTeamScore;
            $g->awayscore = $game->awayTeamScore;
            $g->ended = ($game->gameHasEnded) ? 1 : 0;
            $g->tstamp = time();
            $g->save();
        }
    }
}
