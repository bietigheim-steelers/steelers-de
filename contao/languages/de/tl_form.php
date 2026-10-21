<?php

$GLOBALS['TL_LANG']['tl_form']['cleverreachGroupId'] = array(
    'CleverReach-Gruppen-ID',
    'Die Empfängerliste, in die die Anmeldung eingetragen wird (CleverReach: Empfänger → Listen, die ID steht in der Adresszeile bzw. in den Listen-Einstellungen). Der Empfänger bleibt inaktiv, bis er die Bestätigungsmail bestätigt hat.',
);

$GLOBALS['TL_LANG']['tl_form']['cleverreachFormId'] = array(
    'CleverReach-Formular-ID',
    'Das CleverReach-Formular der Gruppe, über das die Double-Opt-In-Mail verschickt wird (ID über die API unter GET /v3/groups/{id}/forms). Beide Felder müssen gefüllt sein, sonst wird nichts an CleverReach übergeben. Die Formularfelder heißen wie die CleverReach-Attribute: „email“ ist die Adresse, „tags“ nimmt Tags auf, alle anderen (z. B. „firstname“, „lastname“) werden als globales Attribut gleichen Namens angelegt.',
);

$GLOBALS['TL_LANG']['tl_form']['cleverreach_legend'] = 'CleverReach';
