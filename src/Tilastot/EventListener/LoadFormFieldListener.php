<?php

namespace App\Tilastot\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use Contao\Widget;

use App\Tilastot\Model\Games;
use App\Tilastot\Model\Standings;
use App\Tilastot\Model\Camps;
use App\Tilastot\Model\BusTours;
use App\Tilastot\Model\Players;

/**
 * @Hook("loadFormField")
 */
class LoadFormFieldListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (is_array($widget->options) && $widget->options[0]['value'] == 'gamedays_group') {

            $column = array('gamedate >= ? AND hometeam = ? AND id != ? AND optional != ?');
            $displayTeam = 'awayteam';

            $games = Games::findAll(array(
                'order'   => ' gamedate ASC',
                'column'  => $column,
                'value'   => array(time() + (10 * 60 * 60), 36, 2668692721, true)
            ));

            if (!$games) {
                $widget->options = array('value' => 'no-game-found', 'label' => "Kein Spiel gefunden.");
            } else {
                $gameArray = $games->fetchAll();
                $options = array_map(function ($game) use ($displayTeam, $widget) {

                    $gameConfig = unserialize($game['gameConfig']);
                    $away = Standings::findByIdAndRound($game[$displayTeam], $game['round'], true);
                    $date = \Contao\Date::parse('D d.m.Y', $game['gamedate']);
                    $text = $date . ' - '  . $away['name'];
                    if(in_array($widget->options[0]['label'], $gameConfig)) {
                        $disabled = 'disabled="disabled"';
                        $text .= ' (ausverkauft)';
                    } else {
                        $disabled = '';
                    }
                    return array('value' => $text, 'label' => $text, 'disabled' => $disabled);
                }, $gameArray);
                array_unshift($options, array('value' => '', 'label' => 'Bitte Spiel wählen...'));
                $widget->options = $options;
            }
        } else if (is_array($widget->options) && ($widget->options[0]['value'] == 'gamedays' || $widget->options[0]['value'] == 'gamedays_away')) {

            $column = array('gamedate >= ? AND hometeam = ? AND id != ? AND optional != ?');
            $displayTeam = 'awayteam';

            if ($widget->options[0]['value'] == 'gamedays_away') {
                $column = array('gamedate >= ? AND awayteam = ? AND id != ?');
                $displayTeam = 'hometeam';
            }
            $games = Games::findAll(array(
                'order'   => ' gamedate ASC',
                'column'  => $column,
                'value'   => array(time() + (10 * 60 * 60), 36, 2668692721, true)
            ));
            if (!$games) {
                $widget->options = array('value' => 'no-game-found', 'label' => "Kein Spiel gefunden.");
            } else {
                $gameArray = $games->fetchAll();
                $widget->options = array_map(function ($game) use ($displayTeam) {
                    $away = Standings::findByIdAndRound($game[$displayTeam], $game['round'], true);
                    $date = \Contao\Date::parse('D d.m.Y', $game['gamedate']);
                    $text = $date . ' - '  . $away['name'];
                    return array('value' => $text, 'label' => $text);
                }, $gameArray);
            }
        } else if (is_array($widget->options) && $widget->options[0]['value'] == 'spielerliste') {
            $spieler = Players::findAll(array(
                'order'   => ' jersey ASC',
                'column'  => array('published=? AND position != ?'),
                'value'   => array(1, 'Staff')
            ));
            if (!$spieler) {
                $widget->options = array('value' => 'no-player-found', 'label' => "Keine Spieler gefunden.");
            } else {
                $spielerArray = $spieler->fetchAll();
                $widget->options = array_map(function ($s) {
                    return array('value' => $s['alias'], 'label' => '#' . $s['jersey'] . ' - ' . $s['lastname'] . ', ' . $s['firstname']);
                }, $spielerArray);
            }
        } else if (is_array($widget->options) && $widget->options[0]['value'] == 'porschecamps') {
            $column = array('gamedate >= ? AND hometeam = ?');
            $camps = Camps::findAll(array(
                'order'   => ' startdate ASC',
                'column'  => array('published=?', 'startdate>?'),
                'value'   => array(1, time())
            ));
            if (!$camps) {
                $widget->options = array('value' => 'no-camp-found', 'label' => "Kein Camps geplant.");
            } else {
                $campsArray = $camps->fetchAll();
                $widget->options = array_map(function ($camp) {
                    $timezone = new \DateTimeZone('Europe/Berlin');
                    $timestamp1 = $camp['startdate'];
                    $timestamp2 = $camp['enddate'];
                    $date1 = new \DateTime("@$timestamp1");
                    $date1->setTimezone($timezone);
                    $date2 = new \DateTime("@$timestamp2");
                    $date2->setTimezone($timezone);
                    $formattedDate1 = $date1->format('d');
                    $formattedDate2 = $date2->format('d.m.Y');
                    $text = $formattedDate1 . '. bis ' . $formattedDate2;
                    if ($camp['full']) {
                        $text .= ' - ausgebucht (Warteliste)';
                    }
                    return array('value' => $camp['name'], 'label' => $text);
                }, $campsArray);
            }
        } else if (is_array($widget->options) && $widget->options[0]['value'] == 'bustours') {
            $bustours = BusTours::findAll(array(
                'order'   => ' tourdate ASC',
                'column'  => array('published=?', 'tourdate>?'),
                'value'   => array(1, time())
            ));
            if (!$bustours) {
                $widget->options = array('value' => 'no-tour-found', 'label' => "Keine Bus Touren geplant.");
            } else {
                $bustoursArray = $bustours->fetchAll();
                $widget->options = array_map(function ($tour) {
                    $timezone = new \DateTimeZone('Europe/Berlin');
                    $timestamp = $tour['tourdate'];
                    $date = new \DateTime("@$timestamp");
                    $date->setTimezone($timezone);
                    $formattedDate = $date->format('d.m.Y');
                    $text = $formattedDate . ' - ' . $tour['hometeam'] . ' - ' . $tour['price'] . '€';
                    if ($tour['full']) {
                        $text .= ' - ausgebucht (Warteliste)';
                    }
                    return array('value' => $tour[$tour['tourdate'] . ' - ' . $tour['hometeam']], 'label' => $text);
                }, $bustoursArray);
            }
        }
        return $widget;
    }
}
