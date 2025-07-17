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
use App\Tilastot\Model\Standings;
use App\Tilastot\Utils\ApiDEL;
use App\Tilastot\Utils\ApiHolema;
use App\Tilastot\Utils\ApiHockeydata;

class TilastotApi
{

	public static function call($uri, $additional_headers = array(), $queryParameters = array())
	{
		$curl = curl_init();
		$queryString = http_build_query($queryParameters);
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $uri . '?' . $queryString,
			CURLOPT_USERAGENT => 'starting6media powered website',
			CURLOPT_HTTPHEADER => $additional_headers

		));

		$response = curl_exec($curl);
		if (curl_errno($curl) > 0) {
			throw new \Exception(curl_error($curl));
		} elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) {
			throw new \Exception('API response http code: ' . curl_getinfo($curl, CURLINFO_HTTP_CODE) . ' (' . $uri . '?' . $queryString . ')');
		}
		curl_close($curl);

		return $response;
	}

	public static function updateTeam($tilastotTeam, $round)
	{
		$t = Standings::findAll(array(
			'limit'   => 1,
			'column'  => array('tilastotid=?', 'round=?'),
			'value'   => array($tilastotTeam->id, $round)
		));
		if (!$t) {
			$t = new Standings();
			$t->tilastotid = $tilastotTeam->id;
		}

		$t->shortname = $tilastotTeam->shortcut;
		$t->tstamp = time();
		$t->name = $tilastotTeam->name;
		$t->round = $round;
		$t->alias = StringUtil::generateAlias($t->name);
		$t->save();

		return $t;
	}

	public static function refreshAll()
	{
		$r = Rounds::findAll(array(
			'column'  => array('autorefresh=?'),
			'value'   => array(1)
		));

		foreach ($r as $round) {
			switch ($round->api) {
				case 'del':
					ApiDEL::refreshAll($round->id);
					break;
				case 'holema':
					ApiHolema::refreshAll($round->id);
					break;
				case 'deb':
					ApiHockeydata::refreshAll($round->id);
					break;
				default:
					throw new \Exception('unknown api "' . $round->api . '"');
			}
		}
	}
}
