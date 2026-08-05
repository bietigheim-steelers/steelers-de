<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Teamuebersicht des Business-Themes ("expert-guidness" aus about-us.html).
 *
 * Das Theme kennt genau vier Netzwerke (Symbole #team-card-*), deshalb sind
 * die Social-Links feste Spalten im MultiColumnWizard.
 */
#[AsContentElement(type: 'business_team', category: 'business_elements')]
class BusinessTeamController extends AbstractContentElementController
{
    /**
     * Spaltenname im MCW => Icon-Name in templates/business/icon.html.twig.
     */
    private const NETWORKS = [
        'facebook' => 'team_facebook',
        'instagram' => 'team_instagram',
        'twitter' => 'team_twitter',
        'linkedin' => 'team_linkedin',
    ];

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('text', $model->text ?: '');
        $template->set('label', trim((string) $model->businessLabel));
        $template->set('members', $this->parseMembers($model->teamMembers));

        return $template->getResponse();
    }

    /**
     * Das Theme baut die Karte um das Foto herum (aspect-410/520), ohne Bild
     * gaebe es keine Kartenhoehe. Zeilen ohne Foto werden daher uebersprungen.
     *
     * @return list<array{uuid: string, name: string, position: string, socials: list<array{icon: string, href: string}>}>
     */
    private function parseMembers(mixed $value): array
    {
        $members = [];

        foreach (StringUtil::deserialize($value, true) as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $file = !empty($row['image']) ? FilesModel::findByUuid($row['image']) : null;

            if (null === $file) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));

            $socials = [];

            foreach (self::NETWORKS as $column => $icon) {
                $href = trim((string) ($row[$column] ?? ''));

                if ('' !== $href) {
                    $socials[] = ['icon' => $icon, 'href' => $href];
                }
            }

            $members[] = [
                'uuid' => $file->uuid,
                'name' => $name,
                'position' => trim((string) ($row['position'] ?? '')),
                'socials' => $socials,
            ];
        }

        return $members;
    }
}
