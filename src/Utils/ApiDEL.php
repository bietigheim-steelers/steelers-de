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

class ApiDEL
{
    const API_URL = 'https://s3-eu-west-1.amazonaws.com/de.hokejovyzapis.cz/';

    private static function call($path, $round) {
        $url = self::API_URL . $path;
        return TilastotApi::call($url, $round);
    }

    public static function refreshAll($round) {
        self::refreshStandings($round);
        self::refreshGames($round);
        self::refreshPlayers($round);
    }

    public static function refreshStandings($round) {
        if (!$round) {
            return "round missing!";
        }
        $r = Rounds::findById($round);

        $data = json_decode(self::call('tables/' . $r->standingsid . '.json', $round));

        foreach ($data->table as $team) {

            $t = Standings::findAll(array(
                    'limit'   => 1,
                    'column'  => array('tilastotid=?', 'round=?'),
                    'value'   => array($team->id, $round)
                ));

            if (!$t) {
                $t = TilastotApi::updateTeam($team, $round, true);
            }

            $t->name = $team->name;
            $t->tilastotid = $team->id;
            $t->round = $round;
            $t->shortname = $team->shortcut;
            $t->games = $team->games;
            $t->rw = $team->wins;
            $t->ow = $team->onlyOTwins;
            $t->pw = $team->onlySOwins;
            $t->pl = $team->onlySOlosts;
            $t->ol = $team->onlyOTlosts;
            $t->rl = $team->losses;
            $t->points = $team->points;
            $t->goalsfor = $team->score1;
            $t->goalsagainst = $team->score2;
            $t->save();
        }
    }

    public static function refreshPlayers($round) {
        if (!$round) {
            return "round missing!";
        }
        $r = Rounds::findById($round);

        $playerData = json_decode(self::call('league-team-stats/' . $r->year . '/' . $r->league . '/22.json', $round));

        if (count($playerData) == 0) {
            return null;
        }

        foreach ($playerData as $player) {

            $p = Players::findAll(array(
                'limit'   => 1,
                'column'  => array('tilastotid=?'),
                'value'   => array($player->id)
            ));
            if (!$p) {
                $p = new Players();
                $p->tilastotid = $player->id;
            }
            $birthday = date_parse_from_format("Y-m-d", $player->dateOfBirth);
            // $p->eliteprospectsid = $player->{'@eliteprospectsid'};
            $p->birthday = mktime(0, 0, 0, $birthday['month'], $birthday['day'], $birthday['year']);
            $p->firstname = $player->firstname;
            $p->jersey = $player->jersey;
            $p->lastname = $player->surname;
            $p->position = $player->position;
            $p->nationality = $player->nationality;
            $p->shoots = $player->stick;
            $p->birthplace = $player->birthCountry;
            $p->height = $player->height;
            $p->weight = $player->weight;

            $p->alias = StringUtil::generateAlias($p->firstname . " " . $p->lastname);

            if ($p->eliteprospectsid > 0) {
                $rss = new \SimpleXMLElement('http://eliteprospects.com/rss_player_stats2.php?player=' . $p->eliteprospectsid, 0, true);
                foreach ($rss->xpath('channel/item') as $item) {
                    $p->epstats = mb_convert_encoding($item->description, 'ISO-8859-1', 'UTF-8');
                    break;
                }
            }

            $p->tstamp = time();
            $savedPlayer = $p->save();


            if ($player->statistics) {
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
                $stats->jersey = $player->jersey;
                $stats->games = $player->statistics->games;
                $stats->goals = $player->statistics->goals->home + $player->statistics->goals->away;
                $stats->assists = $player->statistics->assists->home + $player->statistics->assists->away;
                $stats->points = $player->statistics->points->home + $player->statistics->points->away;
                $stats->penalties = $player->statistics->penaltyMinutes;
                $stats->plusminus = $player->statistics->positive -  $player->statistics->negative;
                $stats->faceoffswon = $player->statistics->faceoffsWin;
                $stats->faceoffslost = $player->statistics->faceoffsLosses;
                $stats->shots = $player->statistics->shotsOnGoal->home + $player->statistics->shotsOnGoal->away;
                $stats->tstamp = time();
                $stats->save();
            }
        }
    }

    public static function refreshGames($round)
    {
        if (!$round) {
            return "round missing!";
        }
        $r = Rounds::findById($round);

        $data = json_decode(self::call('league-team-matches/' . $r->year . '/' . $r->league . '/22.json', $round));

        foreach ($data->matches as $game) {
            $date = date_parse_from_format("Y-m-d H:i:s", $game->start_date);
            $g = Games::findById($game->id);
            if (!$g) {
                $g = new Games();
                $g->id = $game->id;
            }
            TilastotApi::updateTeam($game->home, $round);
            TilastotApi::updateTeam($game->guest, $round);
            if ($game->results->extra_time && !$game->results->shooting) {
                $g->resulttype = 'OT';
            } elseif ($game->results->extra_time && $game->results->shooting) {
                $g->resulttype = 'SO';
            }
            $g->hometeam = $game->home->id;
            $g->awayteam = $game->guest->id;
            $g->gamedate = mktime($date['hour'], $date['minute'], 0, $date['month'], $date['day'], $date['year']);
            $g->gametime = $date['hour'] . ":" . sprintf('%02d', $date['minute']);
            $g->round = $round;
            $g->gameday = $game->round;
            $g->periodscore = $game->periodscore;
            $g->gamestatus = $game->status;
            $g->homescore = $game->results->score->final->score_home;
            $g->awayscore = $game->results->score->final->score_guest;
            $g->ended = ($game->status === 'AFTER_MATCH') ? 1 : 0;
            $g->tstamp = time();
            $g->save();
        }
    }
}