<?php

namespace App\EventListener\FormField;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

use App\Model\Games;
use App\Model\Standings;

#[AsHook('loadFormField')]
class GamedaysGroupFormFieldListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (get_class($widget) !== 'Contao\FormSelect' || ($widget->options[0]['value'] ?? null) !== 'gamedays_group') {
            return $widget;
        }

        $games = Games::findAll([
            'order'  => ' gamedate ASC',
            'column' => ['gamedate >= ? AND hometeam = ? AND id != ? AND optional != ?'],
            'value'  => [time() + (10 * 60 * 60), 36, 2668692721, true],
        ]);

        if (!$games) {
            $widget->options = [['value' => 'no-game-found', 'label' => 'Kein Spiel gefunden.']];
            return $widget;
        }

        $groupLabel = $widget->options[0]['label'];
        $options = array_map(function ($game) use ($groupLabel) {
            $gameConfig = unserialize($game['gameConfig']);
            $away = Standings::findByIdAndRound($game['awayteam'], $game['round'], true);
            $date = \Contao\Date::parse('D d.m.Y', $game['gamedate']);
            $text = $date . ' - ' . $away['name'];
            if (in_array($groupLabel, $gameConfig)) {
                $disabled = 'disabled="disabled"';
                $text .= ' (ausverkauft)';
            } else {
                $disabled = '';
            }
            return ['value' => $text, 'label' => $text, 'disabled' => $disabled];
        }, $games->fetchAll());

        array_unshift($options, ['value' => '', 'label' => 'Bitte Spiel wählen...']);
        $widget->options = $options;

        return $widget;
    }
}
