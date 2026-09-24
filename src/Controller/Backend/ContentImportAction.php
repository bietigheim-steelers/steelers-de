<?php

namespace App\Controller\Backend;

use App\Utils\ContentImporter;
use Contao\ArticleModel;
use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Input;
use Contao\Message;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Handler for the "Content-Import" row operation next to each article in a
 * page's article tree (tl_article, key=content_import) — see
 * contao/dca/tl_article.php. Invoked by Contao's legacy module dispatch
 * (Backend::getBackendModule()), which resolves this class from the service
 * container and calls run().
 *
 * The parent is always the article whose row was clicked (Input::get('id'),
 * automatically set to that article's own id by Contao's row-operation
 * link builder), so the JSON only ever describes content elements, never an
 * article or page id.
 *
 * This must stay a row operation on tl_article, not a global operation on
 * tl_content: with a "key" GET parameter present, Contao's
 * DcaUrlAnalyzer::findTableAndId() treats "id" as a record id of the
 * current table instead of a parent reference. tl_content has a dynamic
 * ptable, so an article id passed as "id" there gets misread as a content
 * element id, and the backend header/breadcrumb then throws "Parent record
 * of tl_content.<id> not found". tl_article has no dynamic ptable, so the
 * same mechanism resolves correctly here.
 *
 * Restricted to admins regardless of the module's user-group permissions,
 * because it writes arbitrary tl_content rows.
 */
class ContentImportAction
{
    public function run(): string|RedirectResponse
    {
        $user = BackendUser::getInstance();

        if (!$user->isAdmin) {
            throw new AccessDeniedException('Der Content-Import ist nur für Administratoren verfügbar.');
        }

        $articleId = (int) Input::get('id');

        if (!ArticleModel::findById($articleId)) {
            throw new \RuntimeException('Content-Import: keine gültige Artikel-ID im Aufruf (id=' . $articleId . ').');
        }

        $backHref = 'contao?do=article&table=tl_content&id=' . $articleId;

        if ('tl_content_import' !== Input::post('FORM_SUBMIT')) {
            return $this->renderForm($backHref, '', null);
        }

        $json = $this->readJsonInput();

        if ('' === $json) {
            return $this->renderForm($backHref, '', 'Bitte eine JSON-Datei hochladen oder den JSON-Text einfügen.');
        }

        try {
            $created = (new ContentImporter())->import($json, $articleId);
        } catch (\Throwable $e) {
            return $this->renderForm($backHref, $json, $e->getMessage());
        }

        Message::addConfirmation(\sprintf('%d Inhaltselement(e) wurden angelegt.', count($created)));

        return new RedirectResponse($backHref);
    }

    private function renderForm(string $backHref, string $json, string|null $error): string
    {
        $template = new BackendTemplate('be_content_import');
        $template->href = $backHref;
        $template->title = htmlspecialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $template->button = $GLOBALS['TL_LANG']['MSC']['backBT'];
        $template->json = $json;
        $template->error = $error;

        return $template->parse();
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
