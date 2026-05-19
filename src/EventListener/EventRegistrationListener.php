<?php

namespace App\EventListener;

use Contao\DataContainer;
use Contao\Date;

class EventRegistrationListener
{
    /**
     * Save callback for regToken: auto-generates a unique token on first save.
     */
    public function generateToken(mixed $value, DataContainer $dc): string
    {
        if (!empty($value)) {
            return $value;
        }

        return bin2hex(random_bytes(16));
    }

    /**
     * Label callback for tl_event_registration list view.
     *
     * @param array         $row    The current record
     * @param string        $label  The pre-formatted label
     * @param DataContainer $dc     The DataContainer instance
     * @param array         $args   The substitution values matching the format string
     */
    public function listRegistration(array $row, string $label, DataContainer $dc, array $args): array
    {
        $args[2] = Date::parse('d.m.Y H:i', (int) $row['dateAdded']);

        return $args;
    }
}
