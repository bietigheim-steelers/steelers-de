# Contao-Muster für Business-Elemente

Nachschlagewerk für Schritt 5 der Skill. Alle Beispiele existieren so im Repo.

## Inhalt

- [Controller-Gerüst](#controller-gerüst)
- [Was das Template automatisch bekommt — und was nicht](#was-das-template-automatisch-bekommt--und-was-nicht)
- [Der wiederkehrende Abschnittskopf](#der-wiederkehrende-abschnittskopf)
- [MultiColumnWizard für Wiederholungen](#multicolumnwizard-für-wiederholungen)
- [Links und Insert-Tags](#links-und-insert-tags)
- [Bilder](#bilder)
- [Icons](#icons)
- [Animations-Hooks des Themes](#animations-hooks-des-themes)
- [Frontend-Modul statt Inhaltselement](#frontend-modul-statt-inhaltselement)

## Controller-Gerüst

```php
#[AsContentElement(type: 'business_timeline', category: 'business_elements')]
class BusinessTimelineController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('text', $model->text ?: '');
        $template->set('label', trim((string) $model->businessLabel));
        $template->set('groups', array_chunk($this->parseEntries($model->timelineEntries), 4));

        return $template->getResponse();
    }
}
```

Die Kategorie `business_elements` gruppiert die Elemente im Backend-Auswahldialog; ihr Label
steht in `contao/languages/de/tl_content.php` als `$GLOBALS['TL_LANG']['CTE']['business_elements']`.

Die Controller sind absichtlich dünn: parsen, normalisieren, ins Template geben. Alles
Layout-Bezogene gehört ins Template, damit der Theme-Abgleich aus Schritt 6 aussagekräftig
bleibt.

## Was das Template automatisch bekommt — und was nicht

`AbstractContentElementController` befüllt das Template mit `type`, `data` (die rohe
Model-Zeile), `headline` (`{text, tag_name}`), `element_html_id`, `element_css_classes`,
`nested_fragments`, `section`, `properties`.

**`text` ist nicht dabei**, obwohl das Feld in der Palette steht — Contaos eigene Elemente
setzen es jeweils selbst. Ohne `$template->set('text', $model->text ?: '')` bleibt der
Einleitungstext im Frontend unsichtbar, ohne dass irgendetwas fehlschlägt. Gleiches gilt für
jedes andere eigene Feld.

## Der wiederkehrende Abschnittskopf

Fast jeder Theme-Abschnitt beginnt mit demselben Kopf: rotierendes Icon + kleine
Großbuchstaben-Zeile links, `<h2>` darunter, Einleitungstext rechts. Das Feld dafür heißt
projektweit **`businessLabel`** (nicht pro Element ein eigenes anlegen).

```twig
{% if label or headline.text|default or text|default %}
    <div class="flex items-start justify-between gap-4 md:gap-10 mb-12 sm:mb-14 md:mb-16 lg:mb-20 flex-col md:flex-row max-w-125 md:max-w-full" data-section-title>
        <div class="md:max-w-170 w-full">
            {% if label %}
                <div class="flex items-center gap-2.5">
                    <img class="rotate" src="/files/business/img/title-icon.svg" alt="" width="18" height="18">
                    <span class="text-base lg:text-lg font-semibold leading-[1.1]! text-secondary uppercase block">{{ label }}</span>
                </div>
            {% endif %}

            {% if headline.text|default %}
                {% with {headline: {
                    text: headline.text,
                    tag_name: headline.tag_name|default('h2'),
                    attributes: attrs(headline.attributes|default)
                        .addClass('text-3xl md:text-4xl lg:text-[40px] xl:text-5xl font-bold leading-tight text-title_black' ~ (label ? ' mt-4' : ''))
                        .set('data-content')
                }} %}
                    {{ block('headline_component') }}
                {% endwith %}
            {% endif %}
        </div>

        {% if text|default %}
            {% with {text, attributes: attrs()
                .addClass('md:max-w-115 w-full text-base sm:text-lg text-paragraph_black')
                .set('data-content')
            } %}
                {{ block('rich_text_component') }}
            {% endwith %}
        {% endif %}
    </div>
{% endif %}
```

Auf dunklem Hintergrund tauscht das Theme drei Dinge: `title-icon-primary.svg` statt
`title-icon.svg`, `text-primary` statt `text-secondary` (und ohne `block`), sowie
`text-title_white` / `text-paragraph_white`. Siehe `business_team`.

`.set('data-content')` mit nur einem Argument erzeugt ein Attribut ohne Wert — genau wie im
Theme. Ohne `data-content` bzw. `data-section-title` läuft die Einblendung aus
`animation.js` nicht.

## MultiColumnWizard für Wiederholungen

Für alles, wovon der Redakteur beliebig viele braucht (Pakete, Meilensteine, Personen,
Links). Registrierung in `contao/dca/tl_content.php`:

```php
$GLOBALS['TL_DCA']['tl_content']['fields']['timelineEntries'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['timelineEntries'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => array(
        'tl_class'     => 'clr',
        'columnFields' => array(
            'year' => array(
                'label'     => &$GLOBALS['TL_LANG']['tl_content']['timelineEntry_year'],
                'inputType' => 'text',
                'eval'      => array('mandatory' => true, 'maxlength' => 32, 'style' => 'width:120px'),
            ),
        ),
    ),
    'sql'       => "blob NULL",
);
```

Auslesen immer über `StringUtil::deserialize($value, true)`, jede Zeile auf `is_array`
prüfen und leere Zeilen überspringen — der Wizard hinterlässt beim Löschen gern leere
Datensätze.

Spaltenbreiten über `'style' => 'width:…px'` steuern; der Wizard scrollt horizontal, sieben
Spalten sind noch handhabbar (siehe `teamMembers`).

Nützliche Spaltentypen:

| Zweck | `inputType` / `eval` |
|---|---|
| Mehrzeilige Liste (eine Zeile = ein Punkt) | `textarea`, im Controller mit `preg_split('/\R/', …)` zerlegen |
| Interner oder externer Link | `text` mit `'dcaPicker' => true, 'rgxp' => 'url'` |
| Bild | `fileTree` mit `'fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%'` |
| Ja/Nein | `checkbox` mit `'style' => 'width:30px'` |

Feste Wertelisten (z. B. Social-Netzwerke) besser als **feste Spalten** modellieren als über
eine frei wählbare Auswahl: das Theme liefert nur für bestimmte Netzwerke ein Symbol, und
feste Spalten machen genau das im Backend sichtbar.

## Links und Insert-Tags

`dcaPicker` speichert interne Ziele als `{{link_url::12}}`. Im Controller auflösen:

```php
$href = trim($this->insertTagParser->replaceInline((string) $url));

if ('' !== $href && Validator::isRelativeUrl($href)) {
    $href = $request->getBasePath().'/'.$href;
}
```

`InsertTagParser` per Konstruktor injizieren. `AbstractFooterElementController` hat dieselbe
Methode für die Footer-Elemente — für neue Elemente außerhalb des Footers die paar Zeilen
lieber wiederholen, als die Footer-Basisklasse umzubauen.

Im Template dann `attrs()` statt handgeschriebener Attribute, damit `target`/`rel` sauber
zusammenpassen:

```twig
<a{{ attrs()
    .set('href', link.href)
    .addClass('… Theme-Klassen …')
    .set('target', '_blank', link.target)
    .set('rel', 'noreferrer noopener', link.target)
}}>{{ link.label }}</a>
```

## Bilder

`picture_config` **ohne** Media-Queries erzeugt ein reines `<img>` mit `srcset`/`sizes` —
kein `<picture>`-Wrapper. Das ist wichtig, weil das Theme-CSS direkt auf dem `<img>` arbeitet
(`aspect-410/520`, `group-hover:transform-…`) und ein zusätzlicher Inline-Wrapper das Layout
brechen würde.

```twig
{% use '@Contao/component/_picture.html.twig' %}

{% set team_photo = picture_config({
    width: 410, height: 520, resizeMode: 'crop',
    sizes: '(max-width: 640px) 92vw, (max-width: 1024px) 46vw, 410px',
    densities: '1x, 2x'
}) %}
{% set team_photo_attributes = attrs().addClass('… exakte Theme-Klassen des <img> …') %}

{% with {figure: figure(member.uuid, team_photo, {metadata: {alt: member.name}}), img_attributes: team_photo_attributes} %}
    {{ block('picture_component') }}
{% endwith %}
```

Die Bildmaße aus der Theme-Klasse ableiten: `aspect-410/520` → `width: 410, height: 520`.

Trägt die Karte im Theme ihre Höhe über das Bild (`aspect-…` auf dem `<img>`), dann
kollabiert sie ohne Bild komplett. In dem Fall das Bildfeld `mandatory` setzen **und** im
Controller Zeilen ohne auflösbare Datei überspringen — und beides in der Feldbeschreibung
erwähnen, damit das Verschwinden nicht rätselhaft wirkt.

## Icons

Das Theme arbeitet mit einem `<symbol>`-Sprite (`<use href="#questionMark">`), das Contao
nicht ausliefert. Die gebrauchten Pfade liegen deshalb inline in
`templates/business/icon.html.twig`:

```twig
{% include 'business/icon.html.twig' with {icon: 'check_badge', icon_class: 'w-5 h-5 fill-current'} only %}
```

Neues Icon übernehmen: das `<symbol id="…">` in der Theme-HTML suchen, Inhalt und `viewBox`
unverändert in einen neuen `{%- elseif icon == '…' -%}`-Zweig kopieren, `class="{{ icon_class }}"`
und `aria-hidden="true" focusable="false"` ergänzen.

Farben nicht als Hex aus dem Theme übernehmen — `--color-primary` und `--color-secondary`
sind im Build auf die Steelers-Farben umgestellt (`#009cde`, `#046a38`). Stattdessen
`fill="currentColor"` plus eine Farbklasse über `icon_class`. Braucht ein Icon zwei Farben,
die zweite über eine vorhandene Utility-Klasse setzen (`class="fill-white"` am zweiten Pfad,
siehe `check_badge`).

## Animations-Hooks des Themes

`files/business/js/animation.js` sucht diese Attribute. Ohne sie bleibt der Abschnitt
statisch, was optisch aus dem Rahmen fällt:

| Attribut | Wirkung |
|---|---|
| `data-section-title` + `data-content` | Abschnittskopf blendet zeilenweise ein |
| `data-sttr-wrapper` + `data-sttr-card` | Karten blenden gestaffelt ein |
| `data-journey-section` mit `data-sttr-line` / `data-sttr-dots` / `data-sttr-card` | Zeitstrahl |
| `.marquee-slider` (in `main.js`) | Endlos-Band |

Bei sehr vielen Karten wird die generische Timeline zäh — die Partnerliste hat dafür eine
eigene Einblendung in `files/business/js/partner-list.js`. Ab ~50 Karten daran denken.

## Frontend-Modul statt Inhaltselement

Gleiche Bauweise, andere Ablage und andere DCA-Tabelle:

```php
#[AsFrontendModule(category: 'tilastot')]
class PartnerSliderModule extends AbstractFrontendModuleController
```

Palette und Felder in `contao/dca/tl_module.php`, Labels in
`contao/languages/de/modules.php` (`FMD`-Eintrag nicht vergessen). Bestehende Felder
wiederverwenden, wo es passt — `tilastot_partners_category`, `tilastot_partners_branche`
und `jumpTo` sind schon da.

Überschrift und CSS-ID kommen bei Modulen serialisiert an:

```php
$headline = StringUtil::deserialize($model->headline, true);
$cssID = StringUtil::deserialize($model->cssID, true);
$template->headline = $headline['value'] ?? '';
$template->cssId = $cssID[0] ?? '';
$template->cssClass = $cssID[1] ?? '';
```

Bei einem Kernmodul mit eigenem Template (FAQ) das Kern-Template als Vorlage nehmen
(`vendor/contao/*/contao/templates/twig/mod_*.html.twig`) und dieselbe Basis erweitern,
damit `cssID`, Suchindex-Markierungen und schema.org-Daten erhalten bleiben:

```twig
{% extends '@Contao/block_unsearchable.html.twig' %}
{% block headline %}{% endblock %}   {# Überschrift wandert in den Abschnittskopf #}
{% block content %} … {% endblock %}
{% do add_schema_org(getSchemaOrgData.invoke()|default(null)) %}
```
