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

