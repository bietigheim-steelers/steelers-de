<?php

namespace App\EventListener\FormField;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

use App\Model\Games;
use App\Model\Standings;

#[AsHook('loadFormField')]
class GamedaysFormFieldListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        $firstValue = $widget->options[0]['value'] ?? null;

        if (get_class($widget) !== 'Contao\FormSelect' || !in_array($firstValue, ['gamedays', 'gamedays_away'], true)) {
            return $widget;
        }

        if ($firstValue === 'gamedays_away') {
            $column = ['gamedate >= ? AND awayteam = ? AND id != ?'];
            $displayTeam = 'hometeam';
            $value = [time() + (10 * 60 * 60), 36, 2668692721];
        } else {
            $column = ['gamedate >= ? AND hometeam = ? AND id != ? AND optional != ?'];
            $displayTeam = 'awayteam';
            $value = [time() + (10 * 60 * 60), 36, 2668692721, true];
        }

        $games = Games::findAll([
            'order'  => ' gamedate ASC',
            'column' => $column,
            'value'  => $value,
        ]);

        if (!$games) {
            $widget->options = [['value' => 'no-game-found', 'label' => 'Kein Spiel gefunden.']];
            return $widget;
        }

        $widget->options = array_map(function ($game) use ($displayTeam) {
            $away = Standings::findByIdAndRound($game[$displayTeam], $game['round'], true);
            $date = \Contao\Date::parse('D d.m.Y', $game['gamedate']);
            $text = $date . ' - ' . $away['name'];
            return ['value' => $text, 'label' => $text];
        }, $games->fetchAll());

        return $widget;
    }
}
