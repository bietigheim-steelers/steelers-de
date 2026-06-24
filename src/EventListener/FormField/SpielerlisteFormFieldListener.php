<?php

namespace App\EventListener\FormField;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

use App\Model\Players;

#[AsHook('loadFormField')]
class SpielerlisteFormFieldListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (get_class($widget) !== 'Contao\FormSelect' || ($widget->options[0]['value'] ?? null) !== 'spielerliste') {
            return $widget;
        }

        $spieler = Players::findAll([
            'order'  => ' jersey ASC',
            'column' => ['published=? AND position != ?'],
            'value'  => [1, 'Staff'],
        ]);

        if (!$spieler) {
            $widget->options = [['value' => 'no-player-found', 'label' => 'Keine Spieler gefunden.']];
            return $widget;
        }

        $widget->options = array_map(function ($s) {
            return ['value' => $s['alias'], 'label' => '#' . $s['jersey'] . ' - ' . $s['lastname'] . ', ' . $s['firstname']];
        }, $spieler->fetchAll());

        return $widget;
    }
}
