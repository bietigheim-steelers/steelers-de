<?php

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Doctrine\DBAL\Connection;

use App\Model\Games;
use App\Model\Standings;

#[AsHook('processFormData')]
class BirthdayGreetingsFormListener
{
    private const FORM_ALIAS = 'geburtstagsgrusse';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(array $arrSubmitted, array $arrData, ?array $arrFiles, array $arrLabels, Form $objForm): void
    {
        if (($arrData['alias'] ?? '') !== self::FORM_ALIAS) {
            return;
        }

        // Find the form field that uses the gamedays_birthday sentinel value
        $field = $this->connection->fetchAssociative(
            "SELECT name FROM tl_form_field WHERE pid = ? AND options LIKE ? AND invisible = ''",
            [(int) $arrData['id'], '%gamedays_birthday%']
        );

        if (!$field || empty($arrSubmitted[$field['name']])) {
            return;
        }

        $gameId = (int) $arrSubmitted[$field['name']];
        if ($gameId <= 0) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE tl_tilastot_client_games SET birthdayGreetingsCount = birthdayGreetingsCount + 1 WHERE id = ?',
            [$gameId]
        );

        $game = Games::findByPk($gameId);
        if ($game !== null) {
            $away = Standings::findByIdAndRound($game->awayteam, $game->round, true);
            $date = \Contao\Date::parse('D d.m.Y', $game->gamedate);
            $arrLabels[$field['name']] = $date . ' - ' . $away['name'];
        }
    }
}
