<?php

namespace App\EventListener\FormField;

use App\Model\Players;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

#[AsHook('loadFormField')]
class PlayerSelectListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (get_class($widget) !== 'Contao\FormSelect' || $widget->options[0]['value'] !== 'spielerliste') {
            return $widget;
        }

        $spieler = Players::findAll(array(
            'order'  => ' jersey ASC',
            'column' => array('published=? AND position != ?'),
            'value'  => array(1, 'Staff'),
        ));

        if (!$spieler) {
            $widget->options = array(array('value' => 'no-player-found', 'label' => "Keine Spieler gefunden."));

            return $widget;
        }

        $widget->options = array_map(function ($s) {
            return array('value' => $s['alias'], 'label' => '#' . $s['jersey'] . ' - ' . $s['lastname'] . ', ' . $s['firstname']);
        }, $spieler->fetchAll());

        return $widget;
    }
}
