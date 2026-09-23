# AGENTS.md

Guidance for AI coding agents (and new humans) working in this repository.

## What this is

`guild/starter` — the runnable example application for the Guild framework, and the reference for how a
consuming app is wired. Namespace `Guild\Starter\`, autoloaded from `src/`. Unlike its siblings this is a
`project`, not a `library`: it is meant to be run, and to be copied as the starting point for new apps.

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
| `guild/framework` | `Guild\Framework\` | Application kernel / DI container. Required as `dev-develop` (branch tip) |
| `guild/access` | `Guild\Access\` | IU Login (OIDC) authentication. Reached transitively through the framework; not required directly |
| `guild/grouper` | `Guild\Grouper\` | IU Grouper group-membership lookup, used by the framework's authorization layer. Reached transitively; not required directly, but its VCS repository is declared here (see Landmines) |
| `iu/notifications` | `IU\Notifications\` | IU Notifications API client. Fully independent; not used here |
| `guild/rivet` | `Guild\Rivet\` | IU Rivet Design System components, reached through the framework. Required **directly** as `dev-develop` too — Composer's minimum-stability check only exempts a *root* package's own dev-branch requirements, not a transitive one |

`framework/AGENTS.md` documents the `ApplicationBuilder` API this app configures.
`access/README.md` is the authoritative reference for OIDC config fields and redirect behavior.

## Verify your change

**Run `composer check` before you change anything and keep that output as your baseline.** The working tree
may carry in-progress work that is not yours. Your obligation is **no new failures** against that baseline.

**Run composer through the container, not a host install** — see
[Local environment (Docker)](#local-environment-docker) for why. From `docker/`:

```bash
docker compose exec app composer test          # phpunit
docker compose exec app composer format:check  # pint, PSR-12 style check (writes nothing)
docker compose exec app composer check         # test, then style check; stops at the first failure. No PHPStan here yet.
```

**`composer test` fails right now, by design.** `tests/` is empty, and `phpunit.xml.dist` sets
`failOnEmptyTestSuite="true"` so an empty run reports `No tests executed!` and exits non-zero rather than
falsely exiting 0. **The fix is to write a test, not to remove the flag.** Test classes belong under
`Guild\Starter\Test\`. For the house test style, read `access/tests/` in the sibling repo.

**There is no PHPStan in this package** — it is not in `require-dev` and there is no `phpstan.neon`. So
there is no `composer analyse` here, unlike the three libraries.

Static analysis levels across the workspace, for reference: `access` `max`, `framework` `10`,
`notification` `5`, `starter` none. Do not assume one bar.

## Architecture

### Boot flow

```
public/index.php  →  bootstrap/app.php  →  config/app.php  →  Application::configure($basePath)…->run()
```

- `public/index.php` requires `bootstrap/app.php` and calls `->run()`.
- `bootstrap/app.php` requires `vendor/autoload.php`, then sets PHP error display and the default timezone
  from `$_ENV['DISPLAY_ERRORS_ENABLED']` and `$_ENV['DEFAULT_TIMEZONE']`, then returns `config/app.php`.
- `config/app.php` builds the application through the framework's fluent builder. As committed:
  `->addRouting()->addIlluminateDatabase()->addTemplateEngine(TemplateEngine::Twig)->addRivet(…)->enableAutoWiring()->create()`,
  with `->addAuthorization(…)` present but commented out (see [Authorization](#authorization)).
- `public/.htaccess` rewrites all requests to `index.php` (front-controller pattern).

### Directory layout

| Path | Holds |
|---|---|
| `bootstrap/app.php` | autoload, PHP error/timezone defaults, then `require config/app.php` |
| `config/` | executable config files (table below) |
| `routes/routes.php` | routes and route middleware |
| `src/` | application code, `Guild\Starter\` — `Example/` (the example controller) and `Authorization/AppPermission.php` (the permission catalog) |
| `templates/` | Twig templates; `example/index.html.twig` is the example page |
| `public/` | docroot: `index.php` and `.htaccess` |
| `docker/` | compose file, Dockerfile, entrypoint, Apache config templates, `.env.example` for host ports |
| `app.env.example`, `database.env.example` | committed templates for the gitignored `app.env` / `database.env` |
| `tmp/` | gitignored runtime data: MySQL data (`tmp/mysql`) and Xdebug output (`tmp/xdebug`) |

**Config is executable PHP that returns objects, arrays, or closures** — not config arrays across the board.
This is the single most important convention to internalize here:

| File | Must return |
|---|---|
| `config/app.php` | the built `Application` |
| `config/authentication.php` | an `OidcConfiguration` **object** (checked with `instanceof`) |
| `config/authorization.php` | an `AuthorizationConfiguration` **object**, read only when `addAuthorization()` is called |
| `config/database.php` | a plain Eloquent connection **array** |
| `config/rivet.php` | a `Guild\Rivet\Page\PageDefaults` **object**, passed to `addRivet()` |
| `routes/routes.php` | a **closure**: `return static function (Router $router) { … };` |

Any `ApplicationBuilder::add*` failure reading or validating one of these throws
`Guild\Framework\Exception\ConfigurationException` rather than letting the raw parse/type error propagate.

**There is no `.env` file parsing anywhere in the code.** `bootstrap/app.php` reads `$_ENV` directly, and
env vars come from Docker's `env_file:` entries (`app.env`, `database.env`). Running outside Docker means
no environment at all. `app.env.example` and `database.env.example` are the committed templates; the real
`app.env` / `database.env` are gitignored — never copy real values back into the `.example` files.

### Authentication: two supported approaches

Both are legitimate. Which one fits depends on how the app is built and where it is deployed.

**1. OIDC via the front controller** — `guild/access` + `OidcAuthenticationMiddleware`. The default for apps
that route everything through `public/index.php`. To enable it you need all three of:

- `->addAuthentication()` in the builder chain in `config/app.php`
- `config/authentication.php` returning a valid `OidcConfiguration`
- real values for the `OIDC_*` env vars it reads (see Landmines — they're in `app.env.example`, but the
  client ID and secret ship blank)

Middleware is attached in routing code, not in config. `routes/routes.php` carries a commented example:
`// $router->lazyMiddleware(OidcAuthenticationMiddleware::class);` applies it to every route; it can also be
attached per-route or per-group.

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
framework's administration pages at `/framework/authorization`. **Off by default.** The pieces are all
committed; turning it on is:

1. **An identity source.** CAS (the `.htaccess` lines above) with `IdentitySource::Cas`, or OIDC
   (`->addAuthentication()`, earlier in the chain) with `IdentitySource::Oidc`. Authorization never
   authenticates anyone itself.
2. **Uncomment `->addAuthorization(IdentitySource::Cas, AppPermission::class)`** in `config/app.php`, changing
   the identity source to match step 1.
3. **The environment `config/authorization.php` reads:** `GROUPER_SERVICE_URL`, `GROUPER_USERNAME`,
   `GROUPER_STEM` (blank means the ACM default, `iu:roles:sys:acm`) and `AUTHORIZATION_SYSTEM_ADMIN_GROUP`
   (the **ACM label** of the group whose members administer the app) in `app.env` — `app.env.example` does
   not list them, so add them by hand — and `GROUPER_PASSWORD` exported in your shell before
   `docker compose up`; `docker-compose.yml` passes it through so it is never written to a file. With any of these blank the application fails at startup, naming the problem.
4. **The framework's migrations**, run from this repo:
   `docker compose exec app vendor/bin/phinx -c vendor/guild/framework/phinx.php migrate -e framework`.

Then a member of the System Admin group reaches the administration pages through the **System settings**
menu in the header (`rvt_page` adds it), registers groups by their ACM label, and grants roles the cases of
`src/Authorization/AppPermission.php` — the application's permission catalog. Checks read
`Gate::allows(AppPermission::ExampleView)` in PHP and `can('example.view')` in templates.
`framework/AGENTS.md` covers the Gate, policies and the full `addAuthorization()` signature.

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
cp app.env.example app.env && cp database.env.example database.env   # compose refuses to start without both
cd docker && docker compose up
docker compose exec app composer install                              # from docker/, once the stack is up
```

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
- **Authentication is not wired up as committed.** `config/app.php` does not call `addAuthentication()`, so
  `config/authentication.php` is inert. Uncommenting the middleware line in `routes/routes.php`
  without also adding `addAuthentication()` fails at dispatch: autowiring reaches
  `OidcAuthenticationMiddleware::__construct(OidcConfiguration, ?LoggerInterface)` and cannot construct
  `OidcConfiguration`, whose constructor requires three strings.
- **`OIDC_CLIENT_ID` and `OIDC_CLIENT_SECRET` ship blank in `app.env.example`** (alongside `OIDC_ISSUER`
  and `OIDC_REDIRECT_URI`, which carry example values). A blank value reads as `""`, which trips
  `OidcConfiguration`'s validation and throws `OidcConfigurationException`. Enabling OIDC means the
  `->addAuthentication()` builder call and real values for those two secrets in your local `app.env`.
- **The framework binds a default `LoggerInterface`** — Monolog on the `framework` channel, writing through
  `error_log()`, which in this image reaches Apache's `ErrorLog` and so `docker compose logs app`. Replace it
  with `->withLogger()` on the builder. See `framework/AGENTS.md` for the AppKube caveat.
- **Five `LDAP_*` keys (`app.env` and `app.env.example`) are read by no code.** Orphaned configuration,
  documented in the example file only for parity with the deployed `app.env`; don't assume an LDAP
  integration exists.
- **`bin/`, `lib/`, `html/`, `tests/`, `public/css/`, and `public/js/` are empty.** Git tracks none of them,
  so a fresh clone has none of them either; create `tests/` with the first test. `html/` is gitignored and
  unused — Docker mounts `public/` to the container's docroot instead.
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

- **For `guild/framework`:** land the change on that repo's `develop` via a pull request, then
  `docker compose exec app composer update guild/framework` here. Merging is what matters — Composer resolves
  `https://github.com/nathanskky/guild-framework.git`, not your local path, so an unmerged feature branch is
  invisible. This package requires `dev-develop`, which tracks the tip of `develop`, so no `composer.json`
  edit is needed once the PR is merged.
- **For `guild/rivet`:** required directly as `dev-develop`, same as `guild/framework` — land the change on
  that repo's `develop` via a PR, then `docker compose exec app composer update guild/rivet` here. No
  `composer.json` edit is needed once the PR is merged.
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
resolve, and one that needs newer `guild/rivet` code resolves but breaks at runtime (both sides track
`dev-develop`, so Composer cannot see the dependency). Update the siblings together:

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
