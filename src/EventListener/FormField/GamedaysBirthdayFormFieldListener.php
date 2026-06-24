<?php

namespace App\EventListener\FormField;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

use App\Model\Games;
use App\Model\Standings;

#[AsHook('loadFormField')]
class GamedaysBirthdayFormFieldListener
{
    private const BIRTHDAY_GREETINGS_MAX = 5;

    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (get_class($widget) !== 'Contao\FormSelect' || ($widget->options[0]['value'] ?? null) !== 'gamedays_birthday') {
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

        $options = [['value' => '', 'label' => 'Bitte Spiel wählen...']];

        foreach ($games->fetchAll() as $game) {
            if ((int) $game['birthdayGreetingsCount'] >= self::BIRTHDAY_GREETINGS_MAX) {
                continue;
            }
            $away = Standings::findByIdAndRound($game['awayteam'], $game['round'], true);
            $date = \Contao\Date::parse('D d.m.Y', $game['gamedate']);
            $text = $date . ' - ' . $away['name'];
            $options[] = ['value' => $text, 'label' => $text];
        }

        if (count($options) <= 1) {
            $widget->options = [['value' => 'no-game-found', 'label' => 'Kein Spiel verfügbar.']];
        } else {
            $widget->options = $options;
        }

        return $widget;
    }
}
