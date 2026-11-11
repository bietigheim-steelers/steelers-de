<?php

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\Input;
use Contao\Widget;

/**
 * Backend-gesteuerte Regeln und Meldungen für den Schrittassistenten
 * (Template form_wrapper_steps, siehe AGENTS.md).
 *
 * Zwei Aufgaben:
 *
 * 1. loadFormField: reicht die im Backend gepflegten Texte und Regeln als data-Attribute ans
 *    Eingabefeld weiter. Das Skript templates/custom/form_steps.html.twig liest nur diese
 *    Attribute und enthält selbst keine formularspezifischen Texte.
 * 2. validateFormField: setzt dieselben Texte serverseitig ein und erzwingt die Summenregel
 *    ("Summengruppe" + "Mindestsumme"). Das gilt auch ohne JavaScript.
 *
 * Alle Felder sind optional; ohne Eintrag bleibt das Contao-Standardverhalten.
 */
class FormStepsRulesListener
{
    public const DEFAULT_SUM_MESSAGE = 'Bitte insgesamt mindestens {min} {einheit} angeben – es fehlen noch {fehlt}.';

    #[AsHook('loadFormField')]
    public function addClientHints(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        $hints = [
            'data-sf-msg-required' => $widget->sfMsgRequired,
            'data-sf-msg-invalid' => $widget->sfMsgInvalid ?: $widget->errorMsg,
            'data-sf-rgxp' => $widget->rgxp,
            'data-sf-pattern' => 'custom' === $widget->rgxp ? $widget->customRgxp : null,
        ];

        if ('' !== (string) $widget->sfSumGroup) {
            $hints += [
                'data-sf-sum-group' => $widget->sfSumGroup,
                'data-sf-sum-min' => $widget->sfSumMin,
                'data-sf-sum-unit' => $widget->sfSumUnit,
                'data-sf-sum-message' => $widget->sfSumMessage,
            ];
        }

        foreach ($hints as $name => $value) {
            if (null !== $value && '' !== (string) $value) {
                $widget->addAttribute($name, (string) $value);
            }
        }

        return $widget;
    }

    /**
     * Zuletzt geprüfte Werte je Formular und Feldname. Contao leert den POST-Wert eines Feldes,
     * sobald es fehlerfrei geprüft ist (Form::processFormData, "see #5474"); beim letzten Feld
     * einer Summengruppe wären die vorherigen sonst nicht mehr lesbar.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $seen = [];

    #[AsHook('validateFormField')]
    public function applyRules(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        $this->seen[$formId][(string) $widget->name] = $widget->value;

        $this->replaceMessages($widget);
        $this->checkSum($widget, $formId);

        return $widget;
    }

    /**
     * Ersetzt die Contao-Standardmeldung durch den Backend-Text: "Pflichtangabe fehlt" bei leerem
     * Feld, sonst "ungültige Angabe".
     */
    private function replaceMessages(Widget $widget): void
    {
        if (!$widget->hasErrors()) {
            return;
        }

        $posted = Input::post($widget->name, true);
        $empty = \is_array($posted) ? [] === array_filter($posted) : '' === trim((string) $posted);
        $message = $empty ? (string) $widget->sfMsgRequired : (string) $widget->sfMsgInvalid;

        if ('' === $message) {
            return;
        }

        // Widget bietet kein Zurücksetzen der Fehler an
        $errors = new \ReflectionProperty(Widget::class, 'arrErrors');
        $errors->setValue($widget, []);

        $widget->addError($message);
    }

    /**
     * Summe aller Felder einer Gruppe prüfen. Der Fehler hängt am letzten Feld der Gruppe, dann
     * sind alle Werte bekannt.
     */
    private function checkSum(Widget $widget, string $formId): void
    {
        $group = (string) $widget->sfSumGroup;

        if ('' === $group || $widget->hasErrors()) {
            return;
        }

        $field = FormFieldModel::findById($widget->id);

        if (null === $field) {
            return;
        }

        $members = FormFieldModel::findBy(
            ['pid=?', 'sfSumGroup=?', "invisible=''"],
            [$field->pid, $group],
            ['order' => 'sorting']
        );

        if (null === $members) {
            return;
        }

        $members = $members->getModels();

        if ((int) end($members)->id !== (int) $field->id) {
            return;
        }

        $sum = 0;
        $min = 0;
        $template = '';

        foreach ($members as $member) {
            $value = trim((string) ($this->seen[$formId][$member->name] ?? ''));

            // Fehlende Pflichtangaben meldet das jeweilige Feld selbst
            if ('' === $value && $member->mandatory) {
                return;
            }

            $sum += (int) $value;
            $min = max($min, (int) $member->sfSumMin);
            $template = '' !== $template ? $template : (string) $member->sfSumMessage;
        }

        if ($sum >= $min) {
            return;
        }

        $unit = (string) ($field->sfSumUnit ?: array_reduce($members, static fn ($c, $m) => $c ?: (string) $m->sfSumUnit, ''));

        $widget->addError(trim(preg_replace('/\s+/', ' ', strtr('' !== $template ? $template : self::DEFAULT_SUM_MESSAGE, [
            '{min}' => (string) $min,
            '{summe}' => (string) $sum,
            '{fehlt}' => (string) ($min - $sum),
            '{einheit}' => $unit,
        ]))));
    }
}
