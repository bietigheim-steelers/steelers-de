<?php

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\Widget;

/**
 * Eingabeprüfung "Betrag" (rgxp "amount") für Textfelder in Formularen, z. B. das Gebot
 * einer Auktion.
 *
 * Erlaubt sind Zahlen mit höchstens zwei Nachkommastellen, als Trennzeichen Komma oder Punkt
 * ("75", "75,5", "75.50"). Tausendertrennzeichen sind nicht erlaubt – "1.000" wird abgelehnt,
 * statt als 1 € gewertet zu werden. Weitergegeben (Datenbank, E-Mail, Hooks) wird der Wert
 * immer mit Punkt.
 *
 * Anders als bei "digit" rendert Contao das Feld als type="text": ein type="number" lehnt je
 * nach Browser-Sprache das Komma ab und lässt ohne step-Attribut nur ganze Zahlen zu.
 */
#[AsHook('validateFormField')]
class AmountFormFieldListener
{
    public const RGXP_NAME = 'amount';

    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (self::RGXP_NAME !== $widget->rgxp || $widget->hasErrors()) {
            return $widget;
        }

        $value = trim((string) $widget->value);

        // Leere Pflichtfelder hat Contao bereits abgelehnt
        if ('' === $value) {
            return $widget;
        }

        if (!preg_match('/^\d+(?:[.,]\d{1,2})?$/', $value)) {
            // Die Meldung gibt es nur auf Deutsch, sonst die allgemeine Zahlen-Meldung
            $widget->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['amount'] ?? $GLOBALS['TL_LANG']['ERR']['digit'], $widget->label));

            return $widget;
        }

        $value = str_replace(',', '.', $value);

        // FormText verwirft minval/maxval bei allen Eingabeprüfungen außer "digit"
        $field = FormFieldModel::findById($widget->id);

        if (null !== $field && is_numeric($field->minval) && (float) $value < (float) $field->minval) {
            $widget->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['minval'], $widget->label, $this->format($field->minval)));

            return $widget;
        }

        if (null !== $field && is_numeric($field->maxval) && (float) $value > (float) $field->maxval) {
            $widget->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['maxval'], $widget->label, $this->format($field->maxval)));

            return $widget;
        }

        $widget->value = $value;

        return $widget;
    }

    private function format(string $amount): string
    {
        return number_format((float) $amount, 2, ',', '.');
    }
}
