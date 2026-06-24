<?php

namespace App\EventListener\FormField;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

use App\Model\Camps;

#[AsHook('loadFormField')]
class PorscheCampsFormFieldListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (get_class($widget) !== 'Contao\FormSelect' || ($widget->options[0]['value'] ?? null) !== 'porschecamps') {
            return $widget;
        }

        $camps = Camps::findAll([
            'order'  => ' startdate ASC',
            'column' => ['published=?', 'startdate>?'],
            'value'  => [1, time()],
        ]);

        if (!$camps) {
            $widget->options = [['value' => 'no-camp-found', 'label' => 'Kein Camps geplant.']];
            return $widget;
        }

        $timezone = new \DateTimeZone('Europe/Berlin');

        $widget->options = array_map(function ($camp) use ($timezone) {
            $date1 = (new \DateTime('@' . $camp['startdate']))->setTimezone($timezone);
            $date2 = (new \DateTime('@' . $camp['enddate']))->setTimezone($timezone);
            $text = $date1->format('d') . '. bis ' . $date2->format('d.m.Y');
            if ($camp['full']) {
                $text .= ' - ausgebucht (Warteliste)';
            }
            return ['value' => $camp['name'], 'label' => $text];
        }, $camps->fetchAll());

        return $widget;
    }
}
