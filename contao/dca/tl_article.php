<?php

/*
 * "Content-Import"-Icon neben jedem Artikel im Artikelbaum einer Seite
 * (contao?do=article&id=<pageId>). Handler: App\Controller\Backend\ContentImportAction
 * (registriert in contao/config/config.php unter BE_MOD.content.article.content_import).
 *
 * Bewusst eine Zeilen-Operation (list.operations), keine globale Operation:
 * Zeilen-Operationen haengen automatisch "&id=<dieser Artikel>" an den Link an
 * (siehe Contao-Kern: Backend::addToUrl in DataContainerOperationsBuilder),
 * genau wie beim "Artikel"-Icon einer Seite in tl_page.php. Eine globale
 * Operation auf tl_content mit key= haette dagegen zum Absturz gefuehrt:
 * Contaos DcaUrlAnalyzer::findTableAndId() behandelt bei vorhandenem "key"-
 * Parameter die "id" als Datensatz-ID der aktuellen Tabelle (nicht als
 * Parent-Referenz) - fuer tl_content (dynamicPtable) waere die Artikel-ID
 * dann faelschlich als Content-Element-ID interpretiert worden
 * ("Parent record of tl_content.<id> not found" beim Rendern des
 * Backend-Headers/Breadcrumbs).
 */
$GLOBALS['TL_DCA']['tl_article']['list']['operations']['content_import'] = array(
	'href'  => 'key=content_import',
	'icon'  => 'theme_import.svg',
	'label' => &$GLOBALS['TL_LANG']['tl_article']['content_import'],
);
