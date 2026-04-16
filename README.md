# steelers-de

[![Contao 5.7](https://img.shields.io/badge/Contao-5.7-orange)](https://contao.org)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Node.js](https://img.shields.io/badge/Node.js-24-339933?logo=node.js&logoColor=white)](https://nodejs.org)
[![Composer](https://img.shields.io/badge/Composer-2-885630?logo=composer&logoColor=white)](https://getcomposer.org)
[![Vite](https://img.shields.io/badge/Vite-3-646CFF?logo=vite&logoColor=white)](https://vitejs.dev)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Deploy Prod](https://github.com/bietigheim-steelers/steelers-de/actions/workflows/deploy.yml/badge.svg)](https://github.com/bietigheim-steelers/steelers-de/actions/workflows/deploy.yml)
[![Deploy Dev](https://github.com/bietigheim-steelers/steelers-de/actions/workflows/deploy-dev.yml/badge.svg)](https://github.com/bietigheim-steelers/steelers-de/actions/workflows/deploy-dev.yml)

Internal Contao project for steelers.de.

This repository is intentionally documented for a small team and for people who are not working with Git or Composer every day.

## What this project is

- CMS: Contao (Composer-based project)
- Backend/Server code: PHP in `src/`
- Frontend source code: `assets/`
- Twig templates (Contao overrides and custom templates): `templates/`
- Deployment: GitHub Actions + Deployer (`deploy.php`)

## Important Notes (Please read first)

- The npm project is in `assets/`, not in the repository root.
- CSS/JS build output is written to `files/css/` and `files/js/`.
- Do not add random files to `contao/` unless you know exactly why.
- Keep `.env`, secrets, passwords and server credentials out of Git.

## Local Setup

### 1) PHP / Composer install

Run in repository root:

```bash
composer install
```

This installs Contao, Symfony packages, and all PHP dependencies.

### 2) Frontend dependencies

Run in `assets/`:

```bash
cd assets
npm ci
```

### 3) Build or watch frontend

Run in `assets/`:

```bash
# Watch Tailwind while developing
npm run css-dev

# Watch JS via Vite
npm run js-dev

# Production build (css + js)
npm run build
```

## Composer Explained (short version)

File: `composer.json`

- `require`: packages needed in production (Contao bundles, app dependencies)
- `require-dev`: development-only packages (tools/plugins not needed at runtime)
- `autoload.psr-4`: maps `App\\` namespace to `src/`
- `scripts.post-install-cmd` and `post-update-cmd`: automatically run `vendor/bin/contao-setup`
- `extra.public-dir`: defines `public/` as web root
- `extra.contao-component-dir`: installs frontend components into `assets/`
- `config.allow-plugins`: explicitly allows Composer plugins used by this project

### Composer commands you will use most

In repository root:

```bash
composer install
composer update
composer dump-autoload
```

Tip: Avoid `composer update` on random days. Prefer targeted updates and test first.

## Parameters file explained (`config/parameters.yml.example`)

File: `config/parameters.yml.example`

This file is a template for local/server runtime settings.

Typical workflow:

1. Copy `config/parameters.yml.example` to `config/parameters.yml`.
2. Fill in real values for the target environment (dev/prod).
3. Never commit real secrets/passwords to Git.

### Parameter keys

- `database_host`: database server host name or IP.
- `database_port`: database server port (usually `3306` for MySQL/MariaDB).
- `database_user`: database username.
- `database_password`: database password.
- `database_name`: database/schema name.
- `database_version`: DB platform/version hint for Doctrine (for example MariaDB/MySQL version string).
- `secret`: application secret used for cryptographic operations.
- `mailer_transport`: mail transport type.
- `mailer_host`: SMTP host.
- `mailer_user`: SMTP username.
- `mailer_password`: SMTP password.
- `mailer_port`: SMTP port.
- `mailer_encryption`: encryption mode (commonly `tls` or `ssl`, depending on provider).
- `mixpanel_project_token`: token for Mixpanel tracking integration.

### Practical notes

- If the website cannot connect to DB, first verify `database_*` values.
- If emails are not sent, verify `mailer_*` values.
- If analytics events fail, verify `mixpanel_project_token`.
- Keep one secure value set per environment (dev and prod are usually different).

## Where to put new JavaScript / Vue code

### Folder structure

- Main JS/Vue source files: `assets/js/`
- Primary JS entry: `assets/js/main.js`
- Form-specific Vue entry: `assets/js/form.js`
- Vite build config: `assets/vite.config.js` and `assets/vite-form.config.js`

### Typical workflow for new feature JS

1. Create or update a file in `assets/js/`.
2. Import it from the correct entry file:
   - `main.js` for site-wide or regular pages
   - `form.js` for form app (`#formApp`)
3. Build in `assets/`:

```bash
npm run build
```

### Adding a new Vue component

1. Create component in `assets/js/`, for example `MyWidget.vue`.
2. Import and mount it in the correct entry file:

```js
import { createApp } from "vue";
import MyWidget from "./MyWidget.vue";

createApp(MyWidget).mount("#my-widget");
```

3. Add matching container element in a Twig template:

```html
<div id="my-widget"></div>
```

4. Build assets (`npm run build`).

If you need an additional output bundle, add a new `input` entry in a Vite config and define the output filename.

## Templates: where and how

Main folder: `templates/`

- `templates/*.html.twig`: generic page/layout/module overrides
- `templates/content_element/`: custom or overridden content elements
- `templates/frontend_module/`: custom or overridden frontend modules
- `templates/steelers/`: project-specific grouped templates

### Rules for templates

- Keep overrides named exactly like the Contao template you replace.
- Keep reusable snippets small and focused.
- Prefer Twig logic that is simple to understand; move complex logic to PHP in `src/`.

## GitHub Actions

Workflow files:

- `.github/workflows/deploy.yml`: deploys on push to `main` (production)
- `.github/workflows/deploy-dev.yml`: deploys on push to non-`main` branches (dev)
- `.github/workflows/sync-database.yml`: manual database sync prod -> dev

### How deployments work

- Actions build frontend assets (`npm run build`)
- Deployment is done via `deployphp/action` and `deploy.php`
- Stages are `prod` and `dev`

### Important workflow caveat

`deploy-dev.yml` runs npm commands inside `assets/` (correct for this repository).

`deploy.yml` currently runs `npm ci` and `npm run build` from repository root, but the npm project is in `assets/`. If production deploy fails with npm errors, align `deploy.yml` with `deploy-dev.yml` (`cd assets && ...`).

## Deployment files and stages

Main deployment config: `deploy.php`

- Uploads selected project folders/files (`config`, `templates`, `src`, `files/js`, `files/css`, etc.)
- Defines two hosts/stages:
  - `prod` -> `steelers.de`
  - `dev` -> `dev.steelers.de`
- Runs OPcache clear on successful deploy
