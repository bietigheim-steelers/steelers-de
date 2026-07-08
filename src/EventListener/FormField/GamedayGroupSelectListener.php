<?php

namespace App\EventListener\FormField;

use App\Model\Games;
use App\Model\Standings;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Date;
use Contao\Form;
use Contao\Widget;

#[AsHook('loadFormField')]
class GamedayGroupSelectListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (get_class($widget) !== 'Contao\FormSelect' || $widget->options[0]['value'] !== 'gamedays_group') {
            return $widget;
        }

        $games = Games::findAll(array(
            'order'  => ' gamedate ASC',
            'column' => array('gamedate >= ? AND hometeam = ? AND id != ? AND optional != ?'),
            'value'  => array(time() + (10 * 60 * 60), 36, 2668692721, true),
        ));

        if (!$games) {
            $widget->options = array(array('value' => 'no-game-found', 'label' => "Kein Spiel gefunden."));

            return $widget;
        }

        $options = array_map(function ($game) use ($widget) {
            $gameConfig = unserialize($game['gameConfig']);
            $away = Standings::findByIdAndRound($game['awayteam'], $game['round'], true);
            $date = Date::parse('D d.m.Y', $game['gamedate']);
            $text = $date . ' - ' . $away['name'];
            if (in_array($widget->options[0]['label'], $gameConfig)) {
                $disabled = 'disabled="disabled"';
                $text .= ' (ausverkauft)';
            } else {
                $disabled = '';
            }

            return array('value' => $text, 'label' => $text, 'disabled' => $disabled);
        }, $games->fetchAll());

        array_unshift($options, array('value' => '', 'label' => 'Bitte Spiel wählen...'));
        $widget->options = $options;

        return $widget;
    }
}
