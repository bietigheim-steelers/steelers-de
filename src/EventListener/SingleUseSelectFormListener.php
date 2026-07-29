<?php

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

#[AsHook('processFormData')]
class SingleUseSelectFormListener
{
    private const FIELD_MARKER = '-singleuse-';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(array $arrSubmitted, array $arrData, ?array $arrFiles, array $arrLabels, Form $objForm): void
    {
        foreach ($arrSubmitted as $fieldName => $submittedValue) {
            if (!str_contains((string) $fieldName, self::FIELD_MARKER)) {
                continue;
            }

            $submittedValues = array_map('strval', array_filter((array) $submittedValue, static fn ($value): bool => '' !== (string) $value));

            if (empty($submittedValues)) {
                continue;
            }

            // Only handle Contao\FormSelect fields (type "select") belonging to this form
            $field = $this->connection->fetchAssociative(
                "SELECT id, options FROM tl_form_field WHERE pid = ? AND name = ? AND type = 'select'",
                [(int) $arrData['id'], (string) $fieldName]
            );

            if (!$field) {
                continue;
            }

            $options = StringUtil::deserialize($field['options'], true);

            $filtered = array_values(array_filter(
                $options,
                static fn (array $option): bool => !\in_array((string) ($option['value'] ?? ''), $submittedValues, true)
            ));

            // Nothing was removed, leave the field untouched
            if (\count($filtered) === \count($options)) {
                continue;
            }

            $this->connection->update(
                'tl_form_field',
                ['options' => serialize($filtered)],
                ['id' => (int) $field['id']]
            );
        }
    }
}
