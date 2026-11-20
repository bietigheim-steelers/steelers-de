<?php

declare(strict_types=1);

namespace App\Auth0;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Legt zu einem Auth0-Login das Contao-Mitglied an bzw. gleicht es ab.
 *
 * - Schluessel ist die Auth0-"sub" (tl_member.auth0Sub), nicht die E-Mail-Adresse.
 * - Ohne bestaetigte E-Mail-Adresse wird weder angelegt noch verknuepft: sonst koennte sich
 *   jemand mit einer fremden, unbestaetigten Adresse an ein bestehendes Mitglied haengen.
 * - Gruppen folgen den Rollen aus Auth0. Welche Rolle zu welcher Gruppe gehoert, steht am
 *   Gruppendatensatz (tl_member_group.auth0Role). Gruppen ohne Rolle fasst der Abgleich nicht an.
 */
class Auth0MemberProvisioner
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $rolesClaim,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $claims
     *
     * @return string Benutzername des Mitglieds
     *
     * @throws Auth0Exception
     */
    public function provision(array $claims): string
    {
        $sub = (string) $claims['sub'];
        $email = trim((string) ($claims['email'] ?? ''));

        if ('' === $email || true !== ($claims['email_verified'] ?? false)) {
            throw new Auth0Exception('E-mail address missing or not verified.', 1);
        }

        $member = $this->connection->fetchAssociative(
            'SELECT id, username, `groups` FROM tl_member WHERE auth0Sub = ?',
            [$sub],
        );

        if (false === $member) {
            // Bestehendes Mitglied ohne Auth0-Verknuepfung anhand der (bestaetigten) Adresse uebernehmen.
            $member = $this->connection->fetchAssociative(
                "SELECT id, username, `groups` FROM tl_member WHERE auth0Sub = '' AND email = ? ORDER BY id LIMIT 1",
                [$email],
            );
        }

        // Das Produktivlog zeigt erst ab Level error etwas; fehlende Rollen sollen sichtbar sein.
        $this->logger->log([] === (array) ($claims[$this->rolesClaim] ?? []) ? 'error' : 'info', 'Auth0 login without roles in token', [
            'sub' => $sub,
            'roles' => $claims[$this->rolesClaim] ?? null,
            'claims' => array_keys($claims),
        ]);

        $groups = $this->resolveGroups($claims, $member ? StringUtil::deserialize($member['groups'], true) : []);

        $fields = [
            'tstamp' => time(),
            'email' => $email,
            'auth0Sub' => $sub,
            'groups' => serialize($groups),
        ];

        // Namen nur uebernehmen, wenn Auth0 sie kennt. Vorrang haben die Custom Claims aus der Action
        // (<Namespace>firstname / <Namespace>lastname, gespeist aus user_metadata); die Standard-Claims
        // given_name / family_name sind der Rueckfall (Social-Logins).
        $namespace = substr($this->rolesClaim, 0, (int) strrpos($this->rolesClaim, '/') + 1);

        foreach (['firstname' => 'given_name', 'lastname' => 'family_name'] as $column => $fallback) {
            $value = trim((string) ($claims[$namespace.$column] ?? $claims[$fallback] ?? ''));

            if ('' !== $value) {
                $fields[$column] = mb_substr($value, 0, 255);
            }
        }

        if ($member) {
            $this->connection->update('tl_member', $fields, ['id' => $member['id']]);

            return (string) $member['username'];
        }

        $username = 'auth0_'.substr(sha1($sub), 0, 20);

        $this->connection->insert('tl_member', $fields + [
            'dateAdded' => time(),
            'username' => $username,
            'firstname' => '',
            'lastname' => '',
            'language' => 'de',
            'login' => 1,
            'disable' => 0,
            // Kein Passwort-Login: Anmeldung laeuft nur ueber Auth0.
            'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
        ]);

        return $username;
    }

    /**
     * @param array<string, mixed> $claims
     * @param list<mixed>          $current
     *
     * @return list<string>
     */
    private function resolveGroups(array $claims, array $current): array
    {
        $roles = array_map('strval', array_filter((array) ($claims[$this->rolesClaim] ?? []), 'is_scalar'));

        $managed = $this->connection->fetchAllKeyValue("SELECT id, auth0Role FROM tl_member_group WHERE auth0Role != ''");

        $groups = array_map('strval', $current);

        foreach ($managed as $id => $role) {
            $groups = array_diff($groups, [(string) $id]);

            if (\in_array($role, $roles, true)) {
                $groups[] = (string) $id;
            }
        }

        return array_values(array_unique($groups));
    }
}
