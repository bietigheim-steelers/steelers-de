<?php

namespace App\EventListener\FormField;

use App\Model\Games;
use App\Model\Standings;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Date;
use Contao\Form;
use Contao\Widget;

#[AsHook('loadFormField')]
class GamedaySelectListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (get_class($widget) !== 'Contao\FormSelect') {
            return $widget;
        }

        $sentinel = $widget->options[0]['value'];
        if ($sentinel !== 'gamedays' && $sentinel !== 'gamedays_away') {
            return $widget;
        }

        if ($sentinel === 'gamedays_away') {
            $column = array('gamedate >= ? AND awayteam = ? AND id != ?');
            $displayTeam = 'hometeam';
        } else {
            $column = array('gamedate >= ? AND hometeam = ? AND id != ? AND optional != ?');
            $displayTeam = 'awayteam';
        }

        $games = Games::findAll(array(
            'order'  => ' gamedate ASC',
            'column' => $column,
            'value'  => array(time() + (10 * 60 * 60), 36, 2668692721, true),
        ));

        if (!$games) {
            $widget->options = array(array('value' => 'no-game-found', 'label' => "Kein Spiel gefunden."));

            return $widget;
        }

        $widget->options = array_map(function ($game) use ($displayTeam) {
            $away = Standings::findByIdAndRound($game[$displayTeam], $game['round'], true);
            $date = Date::parse('D d.m.Y', $game['gamedate']);
            $text = $date . ' - ' . $away['name'];

            return array('value' => $text, 'label' => $text);
        }, $games->fetchAll());

        return $widget;
    }
}
