<?php

namespace App\EventListener\FormField;

use App\Form\GamedayBirthdayLabel;
use App\Model\Games;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

#[AsHook('loadFormField')]
class GamedayBirthdayListener
{
  private const BIRTHDAY_GREETINGS_MAX = 5;

  public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
  {
      if (get_class($widget) !== 'Contao\FormSelect') {
          return $widget;
      }

      $sentinel = $widget->options[0]['value'];
      if ($sentinel !== 'gamedays_birthday') {
          return $widget;
      }

      $games = Games::findAll(array(
        'order'   => ' gamedate ASC',
        'column'  => array('gamedate >= ? AND hometeam = ? AND id != ? AND optional != ?'),
        'value'   => array(time() + (10 * 60 * 60), 36, 2668692721, true)
      ));
      if (!$games) {
          $widget->options = array(array('value' => 'no-game-found', 'label' => "Kein Spiel gefunden."));
      } else {
          $options = array(array('value' => '', 'label' => 'Bitte Spiel wählen...'));
          foreach ($games->fetchAll() as $game) {
              $count = (int) $game['birthdayGreetingsCount'];
              if ($count >= self::BIRTHDAY_GREETINGS_MAX) {
                  continue;
              }
              $options[] = array('value' => (string) $game['id'], 'label' => GamedayBirthdayLabel::fromRow($game));
          }
          if (count($options) <= 1) {
              $widget->options = array(array('value' => 'no-game-found', 'label' => 'Kein Spiel verfügbar.'));
          } else {
              $widget->options = $options;
          }
      }
      
      return $widget;

  }
}
