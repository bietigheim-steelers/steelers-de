<?php

namespace App\EventListener\FormField;

use App\Model\Camps;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

#[AsHook('loadFormField')]
class CampSelectListener
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (get_class($widget) !== 'Contao\FormSelect' || $widget->options[0]['value'] !== 'porschecamps') {
            return $widget;
        }

        $camps = Camps::findAll(array(
            'order'  => ' startdate ASC',
            'column' => array('published=?', 'startdate>?'),
            'value'  => array(1, time()),
        ));

        if (!$camps) {
            $widget->options = array(array('value' => 'no-camp-found', 'label' => "Kein Camps geplant."));

            return $widget;
        }

        $widget->options = array_map(function ($camp) {
            $timezone = new \DateTimeZone('Europe/Berlin');
            $date1 = new \DateTime('@' . $camp['startdate']);
            $date1->setTimezone($timezone);
            $date2 = new \DateTime('@' . $camp['enddate']);
            $date2->setTimezone($timezone);
            $text = $date1->format('d') . '. bis ' . $date2->format('d.m.Y');
            if ($camp['full']) {
                $text .= ' - ausgebucht (Warteliste)';
            }

            return array('value' => $camp['name'], 'label' => $text);
        }, $camps->fetchAll());

        return $widget;
    }
}
