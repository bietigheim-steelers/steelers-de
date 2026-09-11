<?php

namespace App\EventListener;

use App\Form\GamedayBirthdayLabel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Doctrine\DBAL\Connection;

class BirthdayGreetingsFormListener
{
    private const FORM_ALIAS = 'geburtstagsgrusse';

    /**
     * Spiel-ID aus prepareFormData, gemerkt für processFormData: dort steht im
     * abgeschickten Datensatz nur noch der lesbare Text.
     */
    private ?int $gameId = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Ersetzt die Spiel-ID durch "Fr 23.10.2026 - Ravensburg Towerstars".
     *
     * Der Hook läuft in Form::processFormData() vor dem Mailversand, die Ersetzung
     * wirkt daher auch in den Sessiondaten der Bestätigungsseite.
     *
     * @param array<string, mixed> $arrSubmitted
     * @param array<string, string> $arrLabels
     * @param array<mixed> $arrFields
     * @param array<mixed> $arrFiles
     */
    #[AsHook('prepareFormData')]
    public function onPrepareFormData(array &$arrSubmitted, array $arrLabels, array $arrFields, Form $objForm, array &$arrFiles): void
    {
        $this->gameId = null;

        if ($objForm->alias !== self::FORM_ALIAS) {
            return;
        }

        $fieldName = $this->findGamedayField((int) $objForm->id);

        if (null === $fieldName) {
            return;
        }

        $gameId = (int) ($arrSubmitted[$fieldName] ?? 0);

        if ($gameId <= 0) {
            return;
        }

        // Für processFormData merken, die ID ist danach nicht mehr im Datensatz
        $this->gameId = $gameId;

        if (null !== $label = GamedayBirthdayLabel::fromGameId($gameId)) {
            $arrSubmitted[$fieldName] = $label;
        }
    }

    /**
     * @param array<string, mixed> $arrSubmitted
     * @param array<string, mixed> $arrData
     * @param array<mixed>|null $arrFiles
     * @param array<string, string> $arrLabels
     */
    #[AsHook('processFormData')]
    public function onProcessFormData(array $arrSubmitted, array $arrData, ?array $arrFiles, array $arrLabels, Form $objForm): void
    {
        if (($arrData['alias'] ?? '') !== self::FORM_ALIAS) {
            return;
        }

        $gameId = $this->gameId;
        $this->gameId = null;

        if (null === $gameId) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE tl_tilastot_client_games SET birthdayGreetingsCount = birthdayGreetingsCount + 1 WHERE id = ?',
            [$gameId]
        );
    }

    /**
     * Findet das Formularfeld, das im Backend über den Platzhalter "gamedays_birthday"
     * befüllt wird (siehe App\EventListener\FormField\GamedayBirthdayListener).
     */
    private function findGamedayField(int $formId): ?string
    {
        $name = $this->connection->fetchOne(
            'SELECT name FROM tl_form_field WHERE pid = ? AND options LIKE ? AND invisible = 0',
            [$formId, '%gamedays_birthday%']
        );

        return \is_string($name) && '' !== $name ? $name : null;
    }
}
