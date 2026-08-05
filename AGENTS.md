# Steelers.de – AI Agent Instructions

Custom Contao 5.7 CMS application for the **Bietigheim Steelers** ice hockey team website.

## Stack

| Layer | Technology |
|-------|-----------|
| CMS | Contao 5.7 (Symfony-based) |
| PHP | 8.4 |
| Namespace | `App\` → `src/` |
| Frontend build | Vite 3 + Tailwind CSS 4.3 (in `assets/`) |
| Vue | 3.2 |
| Deployment | Deployer (`deploy.php`) |

## Build Commands

**Frontend** – run from `assets/`:
```bash
npm run build          # Full production build (CSS + JS)
npm run css-build      # Tailwind CSS only
npm run js-build       # Vite JS only (main + form configs)
npm run css-dev        # Watch CSS
npm run js-dev         # Watch JS
```

**Backend** – run from project root:
```bash
composer install
vendor/bin/contao-setup   # Run after composer install/update
```

## Project Structure

```
src/
  Controller/          # Symfony controllers (season tickets, seats API, content elements, frontend modules)
  Cron/                # Scheduled cron jobs
  Dca/                 # Data Container Array classes (Contao backend config)
  EventListener/       # Contao hook listeners (tagged contao.hook in services.yml)
  Migration/           # Database migrations
  Model/               # Contao models (Games, Players, Seats, SeasonTicket, …)
  Page/                # Custom page type controllers
  Twig/                # AppExtension with custom Twig filters/functions
  Utils/               # External API adapters (DEL, Hockeydata, Holema, Tilastot)

assets/                # Frontend source (JS, CSS, Vue components)
  js/main.js           # Main JS entrypoint → files/js/index.js
  css/                 # Tailwind source
  vite.config.js       # Main Vite config
  vite-form.config.js  # Separate form/Vueform build config
  tailwind.config.cjs  # Custom brand colors/fonts

contao/
  dca/                 # DCA overrides (table configuration)
  languages/           # Translation files
  templates/           # Contao legacy templates
  config/              # Contao-specific config

config/
  services.yml         # Service definitions, tags, public Utils
  config.yml           # Contao image sizes, backend badge
  routes.yml

files/
  css/steelers.css     # Compiled CSS output
  js/index.js          # Compiled JS output

templates/             # Twig template overrides
  steelers/            # template overrides for steelers theme
  business/            # template overrides for business theme

deploy.php             # Deployer deployment recipe
```

## Key Conventions

- **Service tags**: Frontend modules → `contao.frontend_module`; hook listeners → `contao.hook` (see `config/services.yml`)
- **Utils are public services**: API adapters in `src/Utils/` are declared `public: true` so Contao can resolve them outside the DI container
- **Frontend build output** lands in `files/css/` and `files/js/`, not in `assets/` — do not edit compiled files
- **Tailwind brand tokens**: `steelgreen` (#046a38 nav), `steelpogreen` (#00994c), `steelblue` (#009cde) — use these instead of generic colors
- **Vueform** is configured in `assets/vueform.config.js` and built with the separate form config
- **No automated PHP test suite** — `tests/` only contains Twig template tests; test logic manually via the dev site
- **Shared deployment files** (`config/config.yml`, `files/steelers/`) persist across releases and are never overwritten by Deployer

## Deployment

Deployment happens ONLY in github actions. Never from the local development system!
In deployment npm build happens in the action, and results get deployed.

```bash
dep deploy prod    # Deploy to steelers.de (keeps 5 releases)
dep deploy dev     # Deploy to dev.steelers.de (keeps 3 releases)
```

Only these paths are uploaded per release: `config/`, `contao/`, `files/steelers/`, `files/js/`, `files/css/`, `templates/`, `src/`, `composer.json/lock`.  
OPcache is cleared automatically via Cachetool after each deployment.

## External Integrations

- `ApiDEL.php` – Importer for League Data (Scores and statistics) from DEL (German ice hockey league)
- `ApiHockeydata.php` – Importer for League Data (Scores and statistics) from Oberliga (third German ice hockey league)
- `ApiHolema.php` – Importer for League Data (Scores and statistics) from DEL2 (second German ice hockey league)
- `TilastotApi.php` – Player statistics
- `Mixpanel` – Analytics (triggered in `GeneratePageListener`)

## Environment

- all prompts and the agents run on a local development system. there is no access to the database or log files on this machine.
- node is not available on the production machine

## Theming


### Contao Twig Template Structure (short)

- Base folder: `/templates`
- Structure maps to context:
  - e.g. `/templates/content_element/text.html.twig`
- Naming defines binding:
  - `<type>/<element>.html.twig`

#### Variants
- Stored in subfolder:
  - `/templates/content_element/text/highlight.html.twig`
- Folder = base element, file = variant name
- Selectable in backend

#### Themes
- Theme override:
  - `/templates/<theme>/content_element/text.html.twig`
- Same structure as global

#### Resolution
1. Theme template  
2. Global template  
3. Core fallback

## Contao Content Elements (short)

### Text
- text, headline, list, table, html, code, description_list

### Link
- hyperlink, toplink

### File
- download, downloads

### Media
- image, gallery, video_audio, vimeo, youtube

### Misc (nested)
- accordion, element_group, content_slider

### Include
- article, content_element, form, module, comments, custom_template, article_teaser

### Legacy
- wrapper_start, wrapper_stop, separator

## Notes
- Each element maps to: `/templates/content_element/<name>.html.twig`
- Variants: `/templates/content_element/<name>/<variant>.html.twig`

## Contao Navigation Modules (short)

### Modules
- navigation        → hierarchical menu from page tree
- custom_navigation → manual page selection (no hierarchy)
- breadcrumb        → current page path
- quick_navigation  → dropdown to jump to pages (tree-based)
- quick_link        → dropdown with manual page selection
- book_navigation   → prev / next / up navigation

---

## Key Fields (common patterns)

### navigation
- start level
- stop level
- hard limit
- reference page
- show hidden / protected
- navigation template
- module template (`mod_navigation`)

### custom_navigation
- selected pages
- show protected
- navigation template
- module template (`mod_customnav`)

### breadcrumb
- show hidden
- module template (`mod_breadcrumb`)

### quick_navigation
- label
- start/stop level
- hard limit
- reference page
- module template (`mod_quicknav`)

### quick_link
- selected pages
- label
- module template (`mod_quicklink`)

### book_navigation
- reference page
- show hidden / protected
- module template (`mod_booknav`)

---

## Template Structure

```/templates/<theme>/mod_<module>.html.twig```

## Navigation Templates (Important)

Contao navigation rendering is split into 2 templates and both must stay CMS-driven:

1. Wrapper template: `/templates/<theme>/mod_navigation.html.twig`
- Responsibility: outer `<nav>`, skip links, wrapper classes/attributes, placement of `{{ items|raw }}`.
- Data comes from Contao module context (`class`, `cssID`, `style`, `request`, `skipId`, `skipNavigation`, `ariaLabel`, `items`).
- Do not hardcode navigation URLs in wrapper chrome. If a logo/home link is needed, resolve it dynamically (e.g. insert tags or module data).

2. Item template: `/templates/<theme>/nav_default.html.twig`
- Responsibility: recursive list markup for each page item.
- Use Contao item fields exactly (`item.href`, `item.link`, `item.class`, `item.isActive`, `item.subitems`, `item.target`, `item.rel`, `item.accesskey`).
- Keep recursive output via `{{ item.subitems|default|raw }}` so submenu trees render correctly.

Reference behavior in Contao core:
- `contao/core-bundle/contao/templates/twig/mod_navigation.html.twig`
- `contao/core-bundle/contao/templates/twig/nav_default.html.twig`

## Business Footer (business.steelers.de)

The footer is fully CMS-driven. Only the `<footer>` chrome (background, decorative shapes,
grid-reveal attributes, `.container`) and the logo live in `templates/fe_page_business.html.twig`.

Content flow: hidden resource page → article (template `mod_article_business_footer`) →
content elements → rendered into the layout footer section via `{{insert_article::<alias>}}`
inside an HTML module.

Element structure inside the article:

```
footer_cta                                  # row 1: headline, text, buttons
element_group  (variant footer_main)        # row 2 wrapper, renders dividers between children
├── footer_about                            # logo (hardcoded), text, optional form
└── element_group (variant footer_columns)  # right-hand grid
    ├── footer_linklist                     # one link column (repeatable)
    └── footer_contact                      # address / mail / phone column
footer_bottom                               # row 3: copyright, legal links, social icons
```

- Controllers: `src/Controller/ContentElement/Footer*Controller.php`, all extending
  `AbstractFooterElementController` (MCW row parsing + insert-tag/URL resolution).
- Templates live in `templates/content_element/`, **not** in `templates/business/`. A template
  under a theme folder only resolves when a theme context is active — the backend renders
  content elements without one and throws *"Could neither find template … nor the legacy
  fallback template …"*. Theme folders are only safe for templates that are never rendered
  in the backend.
- Fields and palettes: `contao/dca/tl_content.php`, labels in `contao/languages/de/tl_content.php`
  (element category `business_footer`).
- Repeatable lists use `multiColumnWizard`; the URL columns support the Contao page picker.
- The theme's `<symbol>` sprite is not shipped — icons are inlined via
  `templates/business/icon.html.twig` (`{% include ... with {icon: 'facebook'} only %}`).
- Newsletter form templates: `form_wrapper_business_newsletter`, `form_text_business_newsletter`,
  `form_submit_business_newsletter`. Do not reuse the theme's `footer-newsletter-form` id —
  `files/business/js/main.js` would intercept the Contao submit.
- `files/business/css/style.css` is the theme's precompiled Tailwind build and is **not** rebuilt
  by `npm run build`. Only use classes that already exist in that file.

## Weitere Business-Theme-Elemente

Alle drei stammen aus `services.html` des SecureVest-Themes.

**Logo-Slider** (Frontend-Modul `partner_slider_module`)
- `src/Controller/FrontendModule/PartnerSliderModule.php`, Template
  `templates/business/frontend_module/partner_slider_module.html.twig`.
- Zeigt nur Partner mit hinterlegtem Logo. Die Laufanimation kommt aus
  `files/business/js/main.js` (Klasse `.marquee-slider`): das Skript dupliziert den Inhalt
  genau **einmal** und springt bei halber Scrollbreite zurück. Ist ein Durchlauf schmaler
  als der Viewport, entsteht eine Lücke – deshalb wiederholt der Controller die Logo-Liste
  bis `MIN_ITEMS` (12) erreicht ist.
- Palette/Felder: `contao/dca/tl_module.php`, Labels in `contao/languages/de/modules.php`.

**FAQ** (Contao-FAQ-Bundle)
- Kein eigenes Modul: das Kernmodul „FAQ-Seite“ (`faqpage`) bekommt unter
  *Template* `mod_faqpage_business` zugewiesen → `templates/mod_faqpage_business.html.twig`.
- Die Modul-Überschrift bildet den Abschnittstitel. Sind mehrere FAQ-Kategorien gewählt,
  wird deren `headline` als Zwischentitel ausgegeben.

**Preisliste** (Inhaltselement `business_pricing`, Kategorie `business_elements`)
- `src/Controller/ContentElement/BusinessPricingController.php`, Template
  `templates/content_element/business_pricing.html.twig` (global, **nicht** im Theme-Ordner –
  siehe Hinweis bei den Footer-Elementen).
- Bewusst ohne den Monatlich/Jährlich-Umschalter des Themes; der Zeitraum ist je Paket ein
  freies Textfeld. Pakete werden per `multiColumnWizard` gepflegt (`pricingPlans`),
  Leistungen zeilenweise im Textarea `features`.
- Bringt keinen eigenen `<section>`/`.container`-Rahmen mit – den liefert das Artikel-Template
  (`mod_article_business_container`).

**Zeitstrahl** (Inhaltselement `business_timeline`, aus `about-us.html`)
- `src/Controller/ContentElement/BusinessTimelineController.php`, Template
  `templates/content_element/business_timeline.html.twig`.
- Das Theme-Markup ist auf **genau vier Eintraege pro Block** ausgelegt: Desktop vier Spalten
  mit waagerechter Linie, mobil zwei Spalten mit senkrechter Linie. Der Controller zerlegt die
  Eintraege deshalb in Vierergruppen; jede Gruppe ist ein eigener `[data-journey-section]`-Block
  mit eigener Linie (`animation.js` verarbeitet beliebig viele davon).
- Position innerhalb der Gruppe steuert die Varianten: gerader Index → Karte unter der Linie
  (`md:mt-32`, Pfeil oben), ungerader Index → Karte darueber (`md:-mt-8`, Pfeil unten).
  Die mobile Zeile (Index 0/1 vs. 2/3) steuert `pt-0`/`pt-10` und `top-14`/`top-23.5`.
- Kein eigener `<section>`/`.container` – gehoert in einen Container-Artikel.

**Team** (Inhaltselement `business_team`, aus `about-us.html`)
- `src/Controller/ContentElement/BusinessTeamController.php`, Template
  `templates/content_element/business_team.html.twig`.
- **Bringt als einziges Business-Inhaltselement seinen eigenen `<section>` mit**, weil der
  dunkle Hintergrund (`bg-secondary`) ueber die volle Breite laufen muss. Gehoert deshalb in
  einen Artikel **ohne** Container-Template.
- Zeilen ohne Foto werden uebersprungen: die Karte baut ueber `aspect-410/520` auf dem Bild auf
  und haette sonst keine Hoehe.
- Social-Links sind feste Spalten (Facebook, Instagram, X, LinkedIn) – das Theme liefert nur
  fuer diese vier ein Symbol.

Die kleine Ueberschrift ueber der H2 nutzen alle drei Elemente ueber das gemeinsame Feld
`businessLabel`.

Neue Icons (`question_mark`, `check_badge`, `team_facebook`, `team_instagram`, `team_twitter`,
`team_linkedin`) liegen in `templates/business/icon.html.twig`.

### Theme-Markup nicht anfassen

CSS und HTML des SecureVest-Themes sind aufeinander abgestimmt und `files/business/css/style.css`
ist ein fertiger Tailwind-Build. Beim Uebernehmen eines Theme-Abschnitts die Klassen und die
Verschachtelung **unveraendert** aus der HTML-Datei kopieren – auch scheinbar redundante Klassen.
Zum Pruefen alle `class`-Attribute des gerenderten Abschnitts mit denen der Theme-Datei
vergleichen (Whitespace normalisiert).

## Lokale Docker-Umgebung

- Console-Befehle **immer als `www-data`** ausführen, sonst gehört `var/cache/prod` danach
  root und die Seite antwortet mit einem leeren HTTP 500 (ohne Log-Eintrag):
  `docker compose exec -u www-data web php vendor/bin/contao-console cache:clear`
- `contao:migrate` würde auf diesem Datenbestand viele Alt-Spalten entfernter Extensions
  löschen. Für neue DCA-Felder stattdessen gezielt das ausgeben lassen, was fehlt
  (`contao:migrate --dry-run`) und nur das entsprechende `ALTER TABLE ... ADD` ausführen.
- Der DB-Client im Container heißt `mariadb`, nicht `mysql`.
- Partner-Logos und andere Dateien unter `files/steelers/content/` fehlen lokal; `figure()`
  rendert dann `<img src alt>` ohne Quelle. Das ist ein Datenproblem, kein Template-Fehler.
- **`contao:filesync` niemals ohne Pfad aufrufen.** Weil die meisten Dateien lokal fehlen,
  loescht ein voller Lauf ~3000 `tl_files`-Eintraege und macht damit alle UUID-Referenzen
  (Partner-Logos, News-Bilder, Downloads) kaputt. Immer nur den betroffenen Teilbaum syncen:
  `contao:filesync files/business`. Zum Wiederherstellen von `tl_files` reicht der Dump unter
  `dev-docker/db/dumps/` – nur den `tl_files`-Abschnitt einspielen.

