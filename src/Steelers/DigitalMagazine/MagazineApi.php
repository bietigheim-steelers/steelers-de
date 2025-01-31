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

class MagazineApi
{
    const API_URL = 'https://api.hockeydata.net/data/ebel/';
    const TEAM_ID = 54744;

    const ROUND = 15881;
    const API_KEY = '';
    const IGNORED_GAMES = [];

    private static function call($endpoint, $standingsid, $teamid = null, $gameId = null)
    {
        $url = self::API_URL . $endpoint;
        $params = [
            'apiKey' => self::API_KEY,
            'referer' => 'deb-online.live',
            'divisionId' => $standingsid
        ];

        if ($teamid) {
            $params['teamId'] = $teamid;
        }
        if ($gameId) {
            $params['gameId'] = $gameId;
        }

        $curl = curl_init();
        $queryString = http_build_query($params);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url . '?' . $queryString,
            CURLOPT_USERAGENT => 'starting6media powered website',
            CURLOPT_SSL_VERIFYPEER => false, // Disable SSL certificate verification
            CURLOPT_HTTPHEADER => array()
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new \Exception(curl_error($curl));
        } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) {
            throw new \Exception('API response http code: ' . curl_getinfo($curl, CURLINFO_HTTP_CODE) . ' (' . $url . '?' . $queryString . ')');
        }
        curl_close($curl);

        return $response;
    }


    public static function getStandings()
    {
        $data = json_decode(self::call('Standings', self::ROUND));
        return $data->data;
    }

    public static function getPlayers()
    {
        $rosterData = json_decode(self::call('GetTeamDetails', self::ROUND, self::TEAM_ID));
        return $rosterData->data->teamRoster;
           
    }

    public static function getGames()
    {
        $data = json_decode(self::call('Schedule', self::ROUND));
        return $data->data->rows;
    }
    public static function getGame($id)
    {
        $data = json_decode(self::call('GetGameReport', self::ROUND, null, $id));
        return $data->data;
    }

    public static function getNextHomeGame() {
        $games = self::getGames();
        $nextGame = null;
        foreach ($games as $game) {
            if ($game->homeTeamId == self::TEAM_ID && $game->scheduledDate->sortValue > time()*1000 && !in_array($game->id, self::IGNORED_GAMES)) {
                $nextGame = $game;
                break;
            }
        }
        return self::getGame($nextGame->id);
    }

}
