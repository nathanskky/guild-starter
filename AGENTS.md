# AGENTS.md

Guidance for AI coding agents (and new humans) working in this repository.

## What this is

`guild/starter` — the runnable example application for the Guild framework, and the reference for how a
consuming app is wired. Namespace `Guild\Starter\`, autoloaded from `src/`. Unlike its siblings this is a
`project`, not a `library`: it is meant to be run, and to be copied as the starting point for new apps.
To scaffold a new app, use the `composer create-project` command in
[README.md](README.md#creating-a-new-app); its `post-create-project-cmd` swaps in the `replacements/`
templates, copies the `.env` examples, deletes this file, and runs `git init`.

- **PHP:** `~8.5.0`
- **Remote:** `git@github.com:nathanskky/guild-starter.git` (SSH), or
  `https://github.com/nathanskky/guild-starter.git` if you don't have SSH keys set up
- **Default branch:** `develop`. **Work targets `develop`** — see
  [Branching and pull requests](#branching-and-pull-requests).
- **`composer.lock` is gitignored here**, as in every Guild package. This repo is a template meant to
  always resolve current dependency versions for whoever copies it — a project built from this starter
  should begin tracking its own lock once it exists as a real, deployed app.

## Sibling packages

These repos are developed side by side but are **independent git repos**. There is no root
`composer.json` and no root git repository, so each is cloned and installed on its own. Do not invent
root-level tooling or a shared root autoloader.

| Package | Namespace | Role |
|---|---|---|
| `guild/starter` *(this one)* | `Guild\Starter\` | Runnable example app |
| `guild/framework` | `Guild\Framework\` | Application kernel / DI container. Required as `^2.1.2` (tag constraint) |
| `guild/dev-tools` | `Guild\DevTools\` | Persona sign-in and the feature-test kit. In `require-dev` only, so production builds don't contain it (see [Personas](#personas)) |
| `guild/access` | `Guild\Access\` | IU Login (OIDC) authentication. Reached transitively through the framework; not required directly |
| `guild/grouper` | `Guild\Grouper\` | IU Grouper group-membership lookup, used by the framework's authorization layer. Reached transitively; not required directly, but its VCS repository is declared here (see Landmines) |
| `iu/notifications` | `IU\Notifications\` | IU Notifications API client. Fully independent; not used here |
| `guild/rivet` | `Guild\Rivet\` | IU Rivet Design System components, reached through the framework. Also required **directly** as `^1.0`, because `config/rivet.php` and the templates use it themselves |

`framework/AGENTS.md` documents the `ApplicationBuilder` API this app configures.
`access/README.md` is the authoritative reference for OIDC config fields and redirect behavior.

## Verify your change

**Run `composer check` before you change anything and keep that output as your baseline.** The working tree
may carry in-progress work that is not yours. Your obligation is **no new failures** against that baseline.

**Run composer through the container, not a host install** — see
[Local environment (Docker)](#local-environment-docker) for why. From `docker/`:

```bash
docker compose exec app composer test          # phpunit
docker compose exec app composer analyse       # PHPStan, level 10 over src/ and tests/
docker compose exec app composer format:check  # pint, PSR-12 style check (writes nothing)
docker compose exec app composer check         # style, PHPStan, then tests; stops at the first failure
```

Tests live in `tests/`, under `Guild\Starter\Test\`. `tests/Feature/` holds feature tests that sign in as
personas and visit the app through its real routes, Gate and grants; see [Personas](#personas).
`phpunit.xml.dist` sets `failOnEmptyTestSuite="true"`, so a run that finds no tests fails rather than
falsely passing.

CI (`.github/workflows/ci.yml`) runs `composer check`, and checks that a `composer install --no-dev` build
has no persona sign-in (`/_dev/personas` is 404 even with the flag on), on every push to `develop` and
`main` and every pull request.

Static analysis levels across the workspace, for reference: `access` `max`, `framework` `10`,
`notification` `5`, `starter` `10`. Do not assume one bar.

## Architecture

### Boot flow

```
public/index.php  →  bootstrap/app.php  →  config/app.php  →  Application::configure($basePath)…->run()
```

- `public/index.php` requires `bootstrap/app.php` and calls `->run()`.
- `bootstrap/app.php` sets PHP error display and the default timezone from `$_ENV['DISPLAY_ERRORS_ENABLED']`
  and `$_ENV['DEFAULT_TIMEZONE']`, then requires `vendor/autoload.php`, then returns `config/app.php`. The
  defaults come first so they cover every file autoloading loads (see
  [Local environment](#local-environment-docker) on vendor deprecations).
- `config/app.php` builds the application through the framework's fluent builder. As committed:
  `->addRouting()->addIlluminateDatabase()->addAuthentication()->addAuthorization(IdentitySource::Oidc, AppPermission::class)->addTemplateEngine(TemplateEngine::Twig)->addRivet(…)->enableAutoWiring()->create()`.
  The same chain runs in production, locally and in tests; installed extensions (`guild/dev-tools`) are
  discovered by the framework, not named here.
- `public/.htaccess` rewrites all requests to `index.php` (front-controller pattern).

### Directory layout

| Path | Holds |
|---|---|
| `bootstrap/app.php` | PHP error/timezone defaults, autoload, then `require config/app.php` |
| `config/` | executable config files (table below) |
| `routes/routes.php` | routes and route middleware |
| `src/` | application code, `Guild\Starter\` — `Example/` (the example controller) and `Authorization/AppPermission.php` (the permission catalog) |
| `tests/` | PHPUnit tests, `Guild\Starter\Test\`; `Feature/ExamplePageTest.php` visits the example page as each persona |
| `templates/` | Twig templates; `example/index.html.twig` is the example page |
| `public/` | docroot: `index.php` and `.htaccess` |
| `docker/` | compose file (which lists every app setting), Dockerfile, entrypoint, Apache config templates, `.env.example` for host ports, the 1Password Environment ID and personal overrides |
| `docker/up-personas.sh` | starts the stack with persona sign-in: no IU Login, no Grouper, no secrets |
| `docker/up-iu-services.sh` | starts the stack with real IU Login and Grouper under `op run`, loading a 1Password Environment (see [Environment and secrets](#environment-and-secrets)) |
| `tmp/` | gitignored runtime data: MySQL data (`tmp/mysql`) and Xdebug output (`tmp/xdebug`) |

**Config is executable PHP that returns objects, arrays, or closures** — not config arrays across the board.
This is the single most important convention to internalize here:

| File | Must return |
|---|---|
| `config/app.php` | the built `Application` |
| `config/authentication.php` | an `OidcConfiguration` **object** (checked with `instanceof`), read by `addAuthentication()` unless persona sign-in is on |
| `config/authorization.php` | an `AuthorizationConfiguration` **object**, read by `addAuthorization()` |
| `config/grouper.php` | a `GrouperDirectoryConfiguration` **object**, read by `addAuthorization()` unless persona sign-in is on |
| `config/personas.php` | a `Guild\DevTools\Persona\PersonaRoster` **object**, read only while persona sign-in is on |
| `config/database.php` | a plain Eloquent connection **array** |
| `config/rivet.php` | a `Guild\Rivet\Page\PageDefaults` **object**, passed to `addRivet()` |
| `routes/routes.php` | a **closure**: `return static function (Router $router) { … };` |

Any `ApplicationBuilder::add*` failure reading or validating one of these throws
`Guild\Framework\Exception\ConfigurationException` rather than letting the raw parse/type error propagate.

### Environment and secrets

**There is no `.env` file parsing anywhere in the code.** PHP reads `$_ENV` directly, so running outside
Docker means no environment at all. **The `app` service's `environment:` list in
`docker/docker-compose.yml` is the complete list of settings the application reads**, each written as
`- NAME=${NAME:-default}`. Compose fills each one from the first source that sets it:

1. The start script, which sets `PERSONA_SIGN_IN_ENABLED` (and, for personas, blanks both secrets).
2. The app's 1Password Environment, which `docker/up-iu-services.sh` loads with
   `op run --environment "$OP_ENVIRONMENT_ID" -- docker compose up`. It holds shared settings as visible
   variables and secrets as hidden ones.
3. `docker/.env`, which is gitignored and holds personal, non-secret overrides and the Environment ID.
4. The committed default.

The `database` service takes its credentials from the same `MYSQL_*` names, so the two always agree.
`up-iu-services.sh` stops with a message when `OP_ENVIRONMENT_ID` is unset or `op` is missing; it never
falls back to plain compose, which would start IU Login with no secrets. `up-personas.sh` loads no
Environment, so a setting persona mode needs must have a compose default or live in `docker/.env`. The
Environment ID is not a secret; it grants nothing without access to the Environment.

`PERSONA_SIGN_IN_ENABLED` belongs to the start scripts: never put it in the 1Password Environment or
`docker/.env`. Its compose default is `true`, so a plain `docker compose up` starts in persona mode.

**Secrets never live in a file, and agents must not try to read them.** `OIDC_CLIENT_SECRET` and
`GROUPER_PASSWORD` come only from the 1Password Environment.

- Never give a secret a default in `docker-compose.yml`, or a value in `docker/.env` or any other file.
- Never run commands that print a secret: `op` in any form, `env`/`printenv` in the container, `docker inspect`, `docker compose config`. `.claude/settings.json` denies these for Claude Code.
- When a setting or secret is missing, tell the developer which variable to add to the 1Password Environment, then stop.

Adding a setting takes two edits: a line in the `environment:` list and its `$_ENV['NAME']` read in
`config/`. A secret also needs its value added to the 1Password Environment by the developer, and its
line must use a blank default: `- NAME=${NAME:-}`.

### Authentication: two supported approaches

Both are legitimate. Which one fits depends on how the app is built and where it is deployed.

**1. IU Login (OIDC) via the front controller** — `guild/access` through the framework's
`addAuthentication()`. The committed default, for apps that route everything through `public/index.php`.
It needs:

- `->addAuthentication()` in the builder chain in `config/app.php` (committed)
- `config/authentication.php` returning a valid `OidcConfiguration` (committed)
- real values for the `OIDC_*` env vars it reads, in the 1Password Environment: `OIDC_CLIENT_ID`, the
  secret `OIDC_CLIENT_SECRET`, and `OIDC_REDIRECT_URI` where it differs from the local default (see
  [Environment and secrets](#environment-and-secrets))

Locally and in tests, persona sign-in stands in for IU Login and none of these values is read; see
[Personas](#personas).

Routes that need a signed-in user take `Guild\Framework\Middleware\RequireSignIn`, attached in
`routes/routes.php` per route or per route group. It asks the sign-in method in use: IU Login redirects to
IU Login, persona sign-in to the persona picker. Templates get `sign_out_form()`, a CSRF-protected
**Sign out** button posting to `/framework/sign-out`; the example page shows it.

**2. Apache `mod_auth_cas` + `.htaccess`** — authentication handled at the web-server layer, before PHP
runs. Appropriate for apps simple enough to skip a front controller, or otherwise architected so that
Apache-level auth fits. This matters especially for **AppKube** (IU's Kubernetes environment): the PHP
container image used there ships with Apache CAS already set up and available to developers.

`docker/auth_cas.conf` and the `a2enmod auth_cas` step in `docker/app.Dockerfile` exist to support this path
locally. **Do not delete them** — they are load-bearing in the image build, and this is a supported approach,
not leftover cruft. To turn it on, add these two lines to the top of `public/.htaccess`; the committed
file does not contain them:

```apache
AuthType CAS
Require valid-user
```

### Authorization

Resolves the signed-in user's IU Grouper groups to locally managed roles and permissions, and serves the
framework's administration pages at `/framework/authorization`. **On by default**, as
`->addAuthorization(IdentitySource::Oidc, AppPermission::class)` in `config/app.php`. With IU Login it
needs:

- **Grouper settings, read by `config/grouper.php`:** `GROUPER_SERVICE_URL`, `GROUPER_USERNAME`,
  `GROUPER_STEM` (blank means the ACM default, `iu:roles:sys:acm`) and the secret `GROUPER_PASSWORD`.
- **`AUTHORIZATION_SYSTEM_ADMIN_GROUP`**, read by `config/authorization.php`: the **ACM label** of the group
  whose members administer the app.
- **The framework's migrations**, which `composer migrate` runs.

All of these settings go in the 1Password Environment (see [Environment and secrets](#environment-and-secrets)).
With any of them blank except `GROUPER_STEM`, the application fails at startup, naming the problem.
Persona sign-in reads none of them: groups and the System Admin label come from `config/personas.php`.

A member of the System Admin group reaches the administration pages through the **System settings** menu in
the header (`rvt_page` adds it), registers groups by their ACM label, and grants roles the cases of
`src/Authorization/AppPermission.php` — the application's permission catalog. Checks read
`Gate::authorize(AppPermission::ExampleView)` in PHP (as `ExampleController` does) and
`can('example.view')` in templates. A denied check answers 403, a guest is asked to sign in, and Grouper
being unreachable answers 503. `framework/AGENTS.md` covers the Gate, policies and the full
`addAuthorization()` signature.

**Turning authorization off**, for an app that doesn't need it: comment out `->addAuthorization(…)` in
`config/app.php`, delete `config/grouper.php`, and remove `grants:` from `config/personas.php`. The
framework tables can stay; nothing reads them. Remove `Gate::authorize()` from `ExampleController` too,
and the tests in `tests/Feature/ExamplePageTest.php` that expect a 403, a 503 or the System settings menu.

For CAS instead of IU Login, use `IdentitySource::Cas`, drop `->addAuthentication()`, and remove
`RequireSignIn` from `routes/routes.php` (without `addAuthentication()` it fails at dispatch; Apache
authenticates instead). Persona sign-in supports IU Login apps only, so a CAS app must not have
`PERSONA_SIGN_IN_ENABLED` on, and the persona feature tests don't apply to it.

### Personas

Locally and in tests, nobody signs in through IU Login and nothing calls Grouper. `guild/dev-tools`
(`require-dev`) replaces both with **personas**: made-up users, each with a username, a name and Grouper
groups, declared in `config/personas.php`. Everything after sign-in is the app's real code: the membership
cache, the grant tables, the Gate. The `guild/dev-tools` README is the full reference.

It is on when `PERSONA_SIGN_IN_ENABLED` is `true`. The framework discovers the installed package, so
`config/app.php` doesn't name it.

**The roster**, `config/personas.php`:

| Key | Name | Groups | Can |
|---|---|---|---|
| `editor` | Erin Editor | Starter Editors | `example.view`, through the `Editor` role |
| `newcomer` | Nico Newcomer | none | nothing; the example page is 403 |
| `admin` | Ada Admin | Starter Admins, the roster's System Admin group | everything, and the administration pages |
| `outage` | Grouper Down | Grouper is unreachable for this persona | nothing; pages that need groups are 503 |

The roster's `grants:` are written into the framework's grant tables when the picker opens and before
each test; grants added through `/framework/authorization` survive. Usernames start with `persona-`, so
none is a real IU username. Groups name real Grouper groups, so the same grants work in production.

**The picker**, `/_dev/personas`, lists each persona with what it can do, and signs you in as one. A guest
on a page with `RequireSignIn` is sent there and returned afterwards. **The banner** at the top of every
HTML page says who you are signed in as, with **Switch persona**, **Sign out** and **Simulate a Grouper
outage**.

**Five minutes with personas:**

1. `cd docker && ./up-personas.sh -d`, and wait for `docker compose logs app` to show Apache starting.
2. Open `https://localhost:8443`; it sends you to the picker.
3. Choose **Sign in as Erin Editor**; the example page opens, with the banner on top.
4. **Switch persona** to Nico Newcomer; the example page is 403.
5. Switch to Ada Admin; **System settings** in the header opens `/framework/authorization`.
6. Run the tests: `docker compose exec app composer test`.

**Feature tests** sign in as a persona in one call, and run in one process against in-memory SQLite:

```php
use Guild\DevTools\Testing\VisitsApplication;

final class ExamplePageTest extends TestCase
{
    use VisitsApplication;

    public function testEditorsSeeTheExample(): void
    {
        $this->signedInAs('editor')->get('/')->assertOk()->assertSee('Example Page');
    }
}
```

`asGuest()`, `duringDirectoryOutage()`, `post()` (CSRF token filled in) and the assertions are in the
dev-tools README. Feature tests see the banner too, and its name is in a `<strong>` tag:
`assertSee('signed in as <strong>Erin Editor</strong>')`. `phpunit.xml.dist` turns persona sign-in on and
blanks both secrets with `force="true"`, so tests run the same in any container.

**Browser tests** (Playwright) sign in through the picker, as a person would:

```ts
export async function signInAs(page: Page, key: string): Promise<void> {
  await page.goto('/_dev/personas');
  await page.locator('form').filter({ has: page.locator(`input[name="persona"][value="${key}"]`) })
    .getByRole('button').click();
}
```

**Safety.** Persona sign-in lets anyone become anyone, so several layers keep it out of production:

- `guild/dev-tools` is `require-dev`; AppKube builds with `composer install --no-dev`
- it is off unless `PERSONA_SIGN_IN_ENABLED` is `true`, which production never sets
- it refuses to start if `OIDC_CLIENT_SECRET` or `GROUPER_PASSWORD` is set
- the picker and sign-in answer 404 unless the host is `localhost`, `127.0.0.1` or `::1`
- every switch and sign-out replaces the session ID and is logged

Never move `guild/dev-tools` into `require`, and never set `PERSONA_SIGN_IN_ENABLED` in a deployed
environment.

**What production needs**, which persona sign-in hides locally:

- `OIDC_*` settings and the `OIDC_CLIENT_SECRET` secret
- `GROUPER_*` settings and the `GROUPER_PASSWORD` secret
- `AUTHORIZATION_SYSTEM_ADMIN_GROUP`
- the framework's migrations, run against the production database

### Rivet

The example page renders through `guild/rivet`, IU's Rivet Design System components for Twig and Latte.
`->addRivet(require __DIR__ . '/rivet.php')` registers the components and must come after
`->addTemplateEngine()` (it throws `ConfigurationException` otherwise). `config/rivet.php` returns the
`PageDefaults` the `rvt_page` layout reads on every page: app title, navigation, footer links.

`templates/example/index.html.twig` shows the pattern: `{% rvt_page %}` wraps the page and loads Rivet's CSS
and JavaScript from unpkg itself, so there is no asset pipeline and `public/css/` / `public/js/` stay empty.
Component tags (`rvt_accordion`, …) nest inside it. `rivet/README.md` is the component reference.

### Local environment (Docker)

```bash
cp docker/.env.example docker/.env        # optional: ports, OP_ENVIRONMENT_ID, personal overrides
cd docker && ./up-personas.sh             # personas; ./up-iu-services.sh for IU Login and Grouper
```

`docker/docker-entrypoint.sh` runs `composer install`, then `composer migrate`, every time the `app`
container starts, before Apache. `composer migrate` runs the framework's Phinx migrations and is the one
place local migrations run. A failure of either is logged (`docker compose logs app`) but does not stop
the container; rerun it with `docker compose exec app composer install` (or `migrate`). The entrypoint
is copied into the image, so after changing it or `app.Dockerfile`, start with `--build`
(`./up-personas.sh --build`).

With `DISPLAY_ERRORS_ENABLED=true` (the local default), `bootstrap/app.php` displays errors, except
deprecations raised inside `vendor/`, which it logs instead: printed before a redirect's headers, they
break IU Login on PHP 8.5.

Always bring the stack up through a start script; see the landmine on recreation.

Three services, from `docker/docker-compose.yml`:

| Service | Image / build | Ports (host:container) |
|---|---|---|
| `app` | built from `app.Dockerfile` (`php:8.5-apache`) | `APP_HTTP_PORT:80`, `APP_HTTPS_PORT:443` (default `8080`/`8443`) |
| `database` | `mysql:8.4` | `9906:3306` |
| `mailcatcher` | `sj26/mailcatcher` | `1080:1080` (web UI), `1025:1025` (SMTP) |

`app` depends on `database` being healthy (`mysqladmin ping`, 20 retries) and on `mailcatcher`. The image
installs the `intl`, `gd`, `xdebug`, `oci8`, `pdo_mysql`, `ldap`, and `zip` extensions, and enables
`rewrite`, `ssl`, `default-ssl`, and `auth_cas`.

Compose bind-mounts `../:/var/www` **and** `../public:/var/www/html`, so edits inside the container write
straight back to the host tree — `vendor/` included.

**Run `composer` through the container, not a host install** — `docker compose exec app composer install`
(or `update`, `require`, `check`, etc.). The point of this Docker setup is that a developer machine doesn't
need PHP or Composer installed at all; running composer on the host defeats that, and its platform check
(PHP version + loaded extensions) validates against whatever runs it — the host's PHP, not the container's
Debian PHP 8.5 with `intl`/`gd`/`xdebug`/`oci8`/`pdo_mysql`/`ldap`/`zip`. The bind mount means `vendor/`
still ends up in the host tree either way — only where composer itself resolves and runs changes.

**The `app` service's host ports are configurable, not hardcoded.** `docker/docker-compose.yml` reads
`APP_HTTP_PORT` and `APP_HTTPS_PORT` from `docker/.env` (falling back to `8080`/`8443` if unset; copy
`docker/.env.example` to get started). The HTTPS port is also passed into the container as an environment
variable, because two Apache config files need to agree with it for the auth flow to work correctly:

- `docker/000-default.conf`'s `RewriteRule ^ https://%{SERVER_NAME}:${APP_HTTPS_PORT}%{REQUEST_URI}` on the
  `*:80` vhost — so plain `http://localhost:8080` (or whatever `APP_HTTP_PORT` resolves to) redirects, path
  preserved, to a URL that actually resolves, at the HTTPS port (self-signed cert, so expect a browser
  warning).
- `docker/auth_cas.conf`'s `CASRootProxiedAs https://localhost:${APP_HTTPS_PORT}`, needed for the CAS auth
  flow.

Both files are checked-in **templates** — they still contain the literal `${APP_HTTPS_PORT}` placeholder.
Compose bind-mounts them read-only into the container at `*.template` paths (not their real Apache config
paths), and `docker/docker-entrypoint.sh` renders them with `envsubst` into the paths Apache actually reads,
every time the container starts — before calling the base image's own entrypoint. This is why changing the
port only requires `docker compose up` again, not an image rebuild: the substitution happens at container
start, not at build time.

## Conventions

**This repo is formatted with Laravel Pint (PSR-12 preset), configured in `pint.json`.** Run `composer
format` to apply it, `composer format:check` to verify without writing. Its rules are authoritative for
anything it enforces — don't hand-fix a style issue Pint would catch, and don't fight its output. The
patterns below are *observed*, not a style guide, and cover only what Pint doesn't decide.

- **How you organize `src/` is your call.** This is a starter template, so the internal structure of your
  application code is a decision for whoever builds the app — there is no layout convention to conform to
  here. `src/Example/` is not a pattern to copy: it exists to keep the example code in one identifiable
  place so it is easy to delete when you no longer want it, and it may move.
- Templates load from `templates/` — that one *is* fixed by the framework, not by this repo. The engine is
  chosen by `addTemplateEngine(TemplateEngine::Twig)`, and there is **no `config/view.php`**. Controllers
  reference templates by string path (see `src/Example/ExampleController.php` for a working example).

## Landmines

- **Editing a sibling (`../framework`, `../access`, `../rivet`, `../grouper`) does nothing here until you
  publish.** Their `vendor/guild/*` copies are real directories holding downloaded zipballs, not symlinks —
  typically several commits behind those repos' HEADs. See
  [Getting a change to consumers](#getting-a-change-to-consumers).
- **`RequireSignIn` on the whole router breaks persona sign-in.** `$router->lazyMiddleware(RequireSignIn::class)`
  also guards `/_dev/personas`, so a guest is sent from the picker to the picker. Attach it to routes or
  route groups.
- **Removing `->addAuthentication()` while routes use `RequireSignIn` fails at dispatch** with a
  `TypeError`: nothing provides the sign-in method it needs.
- **`OIDC_CLIENT_ID` and `OIDC_CLIENT_SECRET` are blank until the 1Password Environment defines them.** With
  IU Login, a blank value trips `OidcConfiguration`'s validation and the app fails at startup. Persona
  sign-in doesn't read them.
- **A plain `docker compose up` after `./up-iu-services.sh` switches to persona mode.** Compose sees the
  missing Environment values as a configuration change and recreates `app` with persona sign-in on; the
  banner at the top of each page shows it. Bring the stack up through a start script every time; `exec`,
  `restart` and `stop` are safe.
- **`op run … docker compose up` without the script fails at boot.** `PERSONA_SIGN_IN_ENABLED` defaults
  to `true` and the Environment sets both secrets, so persona sign-in refuses to start. Use
  `./up-iu-services.sh`, which turns the flag off.
- **Variable names in the 1Password Environment must match `docker-compose.yml` exactly.** Compose passes
  only the names it lists, so a misspelled or extra variable never reaches the container.
- **The framework binds a default `LoggerInterface`** — Monolog on the `framework` channel, writing through
  `error_log()`, which in this image reaches Apache's `ErrorLog` and so `docker compose logs app`. Replace it
  with `->withLogger()` on the builder. See `framework/AGENTS.md` for the AppKube caveat.
- **`bin/`, `lib/`, `html/`, `public/css/`, and `public/js/` are empty.** Git tracks none of them, so a
  fresh clone has none of them either. `html/` is gitignored and unused — Docker mounts `public/` to the
  container's docroot instead.
- **Composer repositories are not transitive.** A sibling package declaring a VCS repository for one of
  *its* dependencies does not make that dependency resolvable here — this package's own `composer.json`
  needs the same VCS entry. Every package reached through `guild/framework` (`guild/access`,
  `guild/rivet`, `guild/grouper`) has its VCS repository declared here for that reason, even the ones not
  required directly. Losing sync between a sibling's new dependency and this file's `repositories` list
  breaks resolution silently for anyone whose `vendor/` still holds an old snapshot — `composer.lock` is
  gitignored here, so a fresh clone gets no warning until it runs `composer update`.

## Branching and pull requests

**Do not commit directly to `develop`.** Work on a feature branch and open a pull request against
`develop`.

```bash
git checkout develop && git pull        # start from an up-to-date develop
git checkout -b <short-descriptive-name>
# ... commit your work ...
git push -u origin <short-descriptive-name>
```

Then open a PR **targeting `develop`**, not `main`. Nothing routine should land on `main` directly.

### Branch and release model

Three stages, and **tags live on `main`, never on `develop`**:

```
feature branch  --PR-->  develop  --PR-->  main  --> tag (release)
```

- **`develop`** accumulates day-to-day work. This is where your feature PR goes.
- **`main`** is the released state. `develop` is merged into it **via its own pull request** when the
  accumulated work is ready to release.
- **Tags are applied to `main`** after that merge.

This is the intended model across all the Guild packages. This package has not reached v1 yet, so `main`
and tagging are not in use here today — but assume this flow for new work. Note that nothing consumes this
package, so tagging it is about marking releases of the starter itself, not about publishing to a dependent.

### Before opening a PR

One thing to check, specific to this package:

- **No path repository should be left in `composer.json`.** If you used one to test against a local sibling
  (see below), revert it before committing. It will break the build for everyone else, since `../framework`
  won't exist on their machine or in CI.

## Getting a change to consumers

This package is the bottom of the dependency chain — nothing depends on it, so there is nothing to publish
outward. The relevant direction is the reverse: **getting sibling changes to appear here.**

**Local dev loop** — point this app at your working copy of a sibling. Add this *above* the existing VCS
entries in `composer.json`, then `docker compose exec app composer update guild/framework`:

```json
{ "type": "path", "url": "../framework", "options": { "symlink": true } }
```

**Revert it before committing** — it is a local-only convenience, and `composer.json` is tracked, so a
forgotten path repository can otherwise leak into a commit.

**Real publish loop:**

- **For `guild/framework` and `guild/rivet`:** required directly at tag constraints, and **tags are cut
  from `main`, not `develop`**. Land the change on that repo's `develop` via a pull request, merge
  `develop` into `main`, tag `main`, then `docker compose exec app composer update guild/framework` (or
  `guild/rivet`) here. Composer resolves the GitHub repository, not your local path, so neither an unmerged
  feature branch nor an untagged merge is visible. Raise the constraint in `composer.json` when this app
  needs code from the new tag.
- **For `guild/access`:** reached only transitively through the framework, at whatever constraint
  `guild/framework` declares (a tag constraint, not a branch, and **tags are cut from `main`, not
  `develop`**). Getting a change here means: land it on that repo's `develop` via a PR, merge `develop` into
  `main` via its own PR, then tag `main` (`git tag <next> && git push --tags`) — only then can
  `composer update guild/framework guild/access` pull the new `guild/access` in. Treat "merged into `develop`" and
  "released" as two separate states, usually separated in time — while waiting on a release, use the
  path-repository loop above (pointed at `../access`, applied to whichever sibling requires it directly).
- **For `guild/grouper`:** transitive, like `guild/access`, at the framework's tag constraint, with tags
  cut from `main`.

**A partial update moves only the packages you name.** `composer update guild/framework` leaves every
sibling at its locked version, so a framework change that raises its `guild/grouper` constraint fails to
resolve. Update the siblings together:

```bash
docker compose exec app composer update guild/framework guild/grouper guild/rivet guild/access
```

`--with-dependencies` also works, but moves third-party packages (Illuminate and the rest) as well.

**Never hand-edit `vendor/guild/framework/`, `vendor/guild/access/`, `vendor/guild/rivet/`, or
`vendor/guild/grouper/`.** The next `composer install` reverts it, and the change never reaches the real
package.

If `composer update` fails with `Could not authenticate against github.com`, that is a local credential
problem — Composer needs a valid GitHub token in `~/.composer/auth.json`. Note the mixed transports across
the workspace: this repo's `origin` is SSH while its Composer VCS repositories are HTTPS, so
`composer update` can fail in situations where `git push` works fine.
