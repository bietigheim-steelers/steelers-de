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

class ApiHolema
{
    const API_URL = 'https://del2.holema.eu/api/v1/';
    const TEAM_ID = 36;
    const IGNORED_GAMES = [7446, 7445];

    private static function call($path, $apiKey)
    {
        $url = self::API_URL . $path;
        return TilastotApi::call($url, array('apikey: ' . $apiKey));
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
        $obj->id = $team->{'@id'};
        $obj->shortcut = $team->shortname;
        $obj->name = $team->name;
        return TilastotApi::updateTeam($obj, $round);
    }

    public static function refreshStandings($round)
    {
        if (!$round) {
            return "round missing!";
        }
        $r = Rounds::findById($round);

        $data = json_decode(self::call('teamstats.json/' . $r->standingsid, $r->apikey));

        foreach ($data->teamstats->teams->team as $team) {

            $t = Standings::findAll(array(
                'limit'   => 1,
                'column'  => array('tilastotid=?', 'round=?'),
                'value'   => array($team->{'@id'}, $round)
            ));

            if (!$t) {
                $t = self::updateTeam($team, $round);
            }

            $t->name = $team->name;
            $t->tilastotid = $team->{'@id'};
            $t->round = $round;
            $t->shortname = $team->shortname;
            $t->games = $team->games;
            $t->rw = $team->rw;
            $t->ow = $team->ow;
            $t->pw = $team->pw;
            $t->pl = $team->pl;
            $t->ol = $team->ol;
            $t->rl = $team->rl;
            $t->points = $team->points;
            $t->goalsfor = $team->goalsfor;
            $t->goalsagainst = $team->goalsagainst;
            $t->save();
        }
    }

    public static function refreshPlayers($round)
    {
        if (!$round) {
            return "round missing!";
        }
        $r = Rounds::findById($round);

        $rosterData = json_decode(self::call('roster.json/' . $r->standingsid . '/' . self::TEAM_ID, $r->apikey));
        $statsData = json_decode(self::call('playerstats.json/' . $r->standingsid . '/' . self::TEAM_ID, $r->apikey));
        if ($rosterData->teamroster->players == "") {
            return null;
        }

        foreach ($rosterData->teamroster->players->player as $player) {

            $p = Players::findAll(array(
                'limit'   => 1,
                'column'  => array('tilastotid=?'),
                'value'   => array($player->{'@id'})
            ));
            if (!$p) {
                $p = new Players();
                $p->tilastotid = $player->{'@id'};
            }
            $birthday = date_parse_from_format("d.m.Y", $player->birthday);
            if ($player->{'@eliteprospectsid'} > 0) {
                $p->eliteprospectsid = $player->{'@eliteprospectsid'};
            }
            $p->firstname = $player->firstname;
            $p->lastname = $player->lastname;
            $p->jersey = $player->jersey;
            $p->position = $player->position;
            $p->nationality = $player->nationality;
            $p->shoots = $player->shoots;
            $p->birthday = mktime(0, 0, 0, $birthday['month'], $birthday['day'], $birthday['year']);
            $p->birthplace = $player->birthplace ? $player->birthplace : '';
            $p->height = $player->height;
            $p->weight = $player->weight;

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

            if ($statsData->playerstats->players != '') {
                foreach ($statsData->playerstats->players->player as $stat) {
                    if ($player->{'@id'} == $stat->{'@id'}) {
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
                        $stats->games = $stat->games;
                        $stats->goals = $stat->goals;
                        $stats->assists = $stat->assists;
                        $stats->points = $stat->points;
                        $stats->penalties = $stat->penalties;
                        $stats->plusminus = $stat->plus - $stat->minus;
                        $stats->faceoffswon = $stat->faceoffswon;
                        $stats->faceoffslost = $stat->faceoffslost;
                        $stats->shots = $stat->shots;
                        $stats->tstamp = time();
                        $stats->save();
                        break;
                    }
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

        $data = json_decode(self::call('games.json/' . $r->standingsid . '/' . self::TEAM_ID, $r->apikey));

        foreach ($data->schedule->games->game as $game) {
            if(in_array($game->{'@id'}, self::IGNORED_GAMES)) {
                // skip games from the ignored list
                continue;
            }
            $date = date_parse_from_format("d.m.Y", $game->gamedate);
            $time = explode(':', $game->gametime);
            $g = Games::findById($game->{'@id'});
            if (!$g) {
                $g = new Games();
                $g->id = $game->{'@id'};
            }
            self::updateTeam($game->hometeam, $round);
            self::updateTeam($game->awayteam, $round);
            $g->hometeam = $game->hometeam->{'@id'};
            $g->awayteam = $game->awayteam->{'@id'};
            $g->gamedate = mktime($time[0], $time[1], 0, $date['month'], $date['day'], $date['year']);
            $g->gametime = $game->gametime;
            $g->round = $round;
            $g->periodscore = $game->periodscore;
            $g->gameday = $game->gameday;
            $g->gamestatus = $game->gamestatus;
            $g->resulttype = $game->resulttype;
            $g->homescore = $game->homescore;
            $g->awayscore = $game->awayscore;
            $g->ended = ($game->ended == 'False') ? 0 : 1;
            $g->tstamp = time();
            $g->save();
        }
    }
}