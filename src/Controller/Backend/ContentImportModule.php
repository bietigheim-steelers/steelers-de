<?php

namespace App\Controller\Backend;

use App\Utils\ContentImporter;
use Contao\BackendModule;
use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Input;

/**
 * Backend module "Inhalte -> Content-Import": lets an admin paste or upload a
 * JSON file (see .claude/skills/contao-content-import) that describes
 * articles and content elements, and creates them via ContentImporter.
 *
 * Restricted to admins regardless of the module's user-group permissions,
 * because it writes arbitrary tl_article/tl_content rows.
 */
class ContentImportModule extends BackendModule
{
    protected $strTemplate = 'be_content_import';

    protected function compile(): void
    {
        $user = BackendUser::getInstance();

        if (!$user->isAdmin) {
            throw new AccessDeniedException('Der Content-Import ist nur für Administratoren verfügbar.');
        }

        $this->Template->href = $this->getReferer(true);
        $this->Template->title = htmlspecialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $this->Template->button = $GLOBALS['TL_LANG']['MSC']['backBT'];
        $this->Template->formSubmit = 'contao?do=content_import';
        $this->Template->result = null;
        $this->Template->error = null;
        $this->Template->json = '';

        if ('tl_content_import' !== Input::post('FORM_SUBMIT')) {
            return;
        }

        $json = $this->readJsonInput();
        $this->Template->json = $json;

        if ('' === $json) {
            $this->Template->error = 'Bitte eine JSON-Datei hochladen oder den JSON-Text einfügen.';

            return;
        }

        try {
            $this->Template->result = (new ContentImporter())->import($json, (int) $user->id);
            $this->Template->json = '';
        } catch (\Throwable $e) {
            $this->Template->error = $e->getMessage();
        }
    }

    private function readJsonInput(): string
    {
        if (!empty($_FILES['jsonFile']['tmp_name']) && is_uploaded_file($_FILES['jsonFile']['tmp_name'])) {
            $content = file_get_contents($_FILES['jsonFile']['tmp_name']);

            if (false !== $content && '' !== trim($content)) {
                return $content;
            }
        }

        return trim((string) Input::postUnsafeRaw('json'));
    }
}
