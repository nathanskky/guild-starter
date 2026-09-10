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
- **`composer.lock` is tracked here** — uniquely among the four packages. See [Landmines](#landmines).

## Sibling packages

These four repos are developed side by side but are **four independent git repos**. There is no root
`composer.json` and no root git repository, so each is cloned and installed on its own. Do not invent
root-level tooling or a shared root autoloader.

| Package | Namespace | Role |
|---|---|---|
| `guild/starter` *(this one)* | `Guild\Starter\` | Runnable example app |
| `guild/framework` | `Guild\Framework\` | Application kernel / DI container. Required as `dev-develop` (branch tip) |
| `guild/access` | `Guild\Access\` | IU Login (OIDC) authentication. Required **directly** as `^1.0`, as well as transitively through the framework |
| `iu/notifications` | `IU\Notifications\` | IU Notifications API client. Fully independent; not used here |

`framework/AGENTS.md` documents the `ApplicationBuilder` API this app configures.
`access/README.md` is the authoritative reference for OIDC config fields and redirect behavior.

## Verify your change

**Run `composer check` before you change anything and keep that output as your baseline.** The working tree
may carry in-progress work that is not yours. Your obligation is **no new failures** against that baseline.

```bash
composer test     # phpunit
composer check    # every check this package has (currently just tests)
```

**`composer test` fails right now, by design.** `tests/` is empty, and `phpunit.xml.dist` sets
`failOnEmptyTestSuite="true"` so an empty run reports `No tests executed!` and exits non-zero rather than
falsely exiting 0. **The fix is to write a test, not to remove the flag.** Test classes belong under
`Guild\Starter\Test\`. For the house test style, read `access/tests/` in the sibling repo.

**There is no PHPStan in this package** — it is not in `require-dev` and there is no `phpstan.neon`. So
there is no `composer analyse` here, unlike the three libraries. Adding PHPStan means touching the tracked
`composer.lock`, which is why it hasn't been done yet.

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
  `->addRouting()->addIlluminateDatabase()->addTemplateEngine(TemplateEngine::Twig)->enableAutoWiring()->create()`.
- `public/.htaccess` rewrites all requests to `index.php` (front-controller pattern).

**Config is executable PHP that returns objects, arrays, or closures** — not config arrays across the board.
This is the single most important convention to internalize here:

| File | Must return |
|---|---|
| `config/app.php` | the built `Application` |
| `config/authentication.php` | an `OidcConfiguration` **object** (checked with `instanceof`) |
| `config/database.php` | a plain Eloquent connection **array** |
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
- the `OIDC_*` env vars it reads (see Landmines — they are not in `app.env.example` yet)

Middleware is attached in routing code, not in config. `routes/routes.php` carries a commented example:
`// $router->lazyMiddleware(OidcAuthenticationMiddleware::class);` applies it to every route; it can also be
attached per-route or per-group.

**2. Apache `mod_auth_cas` + `.htaccess`** — authentication handled at the web-server layer, before PHP
runs. Appropriate for apps simple enough to skip a front controller, or otherwise architected so that
Apache-level auth fits. This matters especially for **AppKube** (IU's Kubernetes environment): the PHP
container image used there ships with Apache CAS already set up and available to developers.

`docker/auth_cas.conf` and the `a2enmod auth_cas` step in `docker/app.Dockerfile` exist to support this path
locally. **Do not delete them** — they are load-bearing in the image build, and this is a supported approach,
not leftover cruft. `public/.htaccess` contains the `AuthType CAS` / `Require valid-user` lines needed to
turn it on, commented out by default.

### Local environment (Docker)

```bash
cd docker && docker compose up
```

Three services, from `docker/docker-compose.yml`:

| Service | Image / build | Ports (host:container) |
|---|---|---|
| `app` | built from `app.Dockerfile` (`php:8.5-apache`) | `8080:80`, `8443:443` |
| `database` | `mysql:8.4` | `9906:3306` |
| `mailcatcher` | `sj26/mailcatcher` | `1080:1080` (web UI), `1025:1025` (SMTP) |

`app` depends on `database` being healthy (`mysqladmin ping`, 20 retries) and on `mailcatcher`. The image
installs the `intl`, `gd`, `xdebug`, `oci8`, `pdo_mysql`, `ldap`, and `zip` extensions, and enables
`rewrite`, `ssl`, `default-ssl`, and `auth_cas`.

Compose bind-mounts `../:/var/www` **and** `../public:/var/www/html`, so edits inside the container write
straight back to the host tree — and `vendor/` is host-resolved, meaning macOS-installed dependencies are
executed by Debian PHP inside the container.

> **Open item — the dev URL and the CAS port.** `docker/000-default.conf` has an unconditional
> `Redirect / https://localhost/` on the `*:80` vhost. That drops the port, so plain
> `http://localhost:8080` redirects to a URL that does not resolve; go to the HTTPS port directly
> (self-signed cert, so expect a browser warning).
>
> Separately, Apache CAS needs the port present in `CASRootProxiedAs` for the auth flow to work, so that
> value has to agree with whatever port Docker publishes. Ideally the port would be dynamic and follow
> Docker automatically; whether that is achievable has not been settled. **Treat the canonical dev URL and
> the `CASRootProxiedAs` handling as unresolved** — check `docker/docker-compose.yml` and
> `docker/auth_cas.conf` for the values currently in effect rather than trusting a URL written in any doc,
> including this one.

## Conventions

**Match the file you are editing. Do not reformat existing code as a side effect of your change.** The
patterns below are *observed*, not a style guide — they emerged organically rather than by decision. When a
formatter/linter lands in this repo, its config becomes authoritative and this section should shrink to a
pointer at it.

- **`<?php declare(strict_types=1);` on one line.** Every PHP file in the workspace does this — including
  the config and bootstrap files — with no exceptions. It departs from PSR-12 §3 deliberately; an agent that
  "fixes" it touches every file.
- **Empty class/method bodies use hugged `{}`** on the line after the signature. Do not expand them.
- **How you organize `src/` is your call.** This is a starter template, so the internal structure of your
  application code is a decision for whoever builds the app — there is no layout convention to conform to
  here. `src/Example/` is not a pattern to copy: it exists to keep the example code in one identifiable
  place so it is easy to delete when you no longer want it, and it may move.
- Templates load from `templates/` — that one *is* fixed by the framework, not by this repo. The engine is
  chosen by `addTemplateEngine(TemplateEngine::Twig)`, and there is **no `config/view.php`**. Controllers
  reference templates by string path (see `src/Example/ExampleController.php` for a working example).

## Landmines

- **`composer.lock` is tracked here, uniquely among the four packages.** `composer install` therefore
  installs the *committed* lock, which can downgrade or remove what is currently vendored. **Run
  `git status` on it before installing**, and be deliberate about whether a lock change belongs in your
  commit.
- **Editing `../framework` or `../access` does nothing here until you publish.** `vendor/guild/framework`
  and `vendor/guild/access` are real directories holding downloaded zipballs, not symlinks — typically
  several commits behind those repos' HEADs. See [Getting a change to consumers](#getting-a-change-to-consumers).
- **Authentication is not wired up as committed.** `config/app.php` does not call `addAuthentication()`, so
  `config/authentication.php` is currently inert. Uncommenting the middleware line in `routes/routes.php`
  without also adding `addAuthentication()` fails at dispatch: autowiring reaches
  `OidcAuthenticationMiddleware::__construct(OidcConfiguration, ?LoggerInterface)` and cannot construct
  `OidcConfiguration`, whose constructor requires three strings.
- **The `OIDC_*` env vars are undocumented.** `config/authentication.php` reads `OIDC_ISSUER`,
  `OIDC_CLIENT_ID`, `OIDC_CLIENT_SECRET`, and `OIDC_REDIRECT_URI`, but **none of them appear in
  `app.env.example`**. Unset `$_ENV` keys read as `""`, which trips `OidcConfiguration`'s validation and
  throws `OidcConfigurationException`. Enabling OIDC means: builder call + four env vars + an
  `app.env.example` update.
- **No `LoggerInterface` is bound in the container**, yet `AuthenticationServiceProvider` passes
  `LoggerInterface::class` as a constructor argument. Autowiring cannot instantiate an interface, so an app
  that enables authentication must bind a logger itself. The logger argument to
  `OidcAuthenticationService` is optional — omitting it is fine — but the provider as written expects the
  binding.
- **Five `LDAP_*` keys in `app.env` are read by no code.** Orphaned configuration; don't assume an LDAP
  integration exists.
- **`bin/`, `lib/`, `html/`, `tests/`, `public/css/`, and `public/js/` are all empty.** `html/` is
  gitignored and unused — Docker mounts `public/` to the container's docroot instead.

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

This is the intended model across all four Guild packages. This package has not reached v1 yet, so `main`
and tagging are not in use here today — but assume this flow for new work. Note that nothing consumes this
package, so tagging it is about marking releases of the starter itself, not about publishing to a dependent.

### Before opening a PR

Two things to check, both specific to this package:

- **`composer.lock` is tracked.** Decide deliberately whether a lock change belongs in your PR. A lock diff
  that arrived incidentally — from a `composer update` you ran while debugging, or from a temporary path
  repository — should not be in it.
- **No path repository should be left in `composer.json`.** If you used one to test against a local sibling
  (see below), revert it before committing. It will break the build for everyone else, since `../framework`
  won't exist on their machine or in CI.

## Getting a change to consumers

This package is the bottom of the dependency chain — nothing depends on it, so there is nothing to publish
outward. The relevant direction is the reverse: **getting sibling changes to appear here.**

**Local dev loop** — point this app at your working copy of a sibling. Add this *above* the existing VCS
entries in `composer.json`, then `composer update guild/framework`:

```json
{ "type": "path", "url": "../framework", "options": { "symlink": true } }
```

**Revert it before committing** — it is a local-only convenience, and this repo's `composer.lock` is
tracked, so a path repository can otherwise leak into a commit.

**Real publish loop:**

- **For `guild/framework`:** land the change on that repo's `develop` via a pull request, then
  `composer update guild/framework` here. Merging is what matters — Composer resolves
  `https://github.com/nathanskky/guild-framework.git`, not your local path, so an unmerged feature branch is
  invisible. This package requires `dev-develop`, which tracks the tip of `develop`, so no `composer.json`
  edit is needed once the PR is merged.
- **For `guild/access`:** the constraint here is `^1.0` — a *tag* constraint, not a branch — so a merge is
  not enough, and **tags are cut from `main`, not `develop`**. The full path is: land the change on that
  repo's `develop` via a PR, merge `develop` into `main` via its own PR, then tag `main`
  (`git tag <next> && git push --tags`). Only then will `composer update guild/access` see it. Treat
  "merged into `develop`" and "released" as two separate states, usually separated in time — while waiting
  on a release, use the path-repository loop above. Note this package requires `guild/access` *directly* as
  well as transitively through the framework, so both constraints must be satisfiable.

**Never hand-edit `vendor/guild/framework/` or `vendor/guild/access/`.** The next `composer install`
reverts it, and the change never reaches the real package.

If `composer update` fails with `Could not authenticate against github.com`, that is a local credential
problem — Composer needs a valid GitHub token in `~/.composer/auth.json`. Note the mixed transports across
the workspace: this repo's `origin` is SSH while its Composer VCS repositories are HTTPS, so
`composer update` can fail in situations where `git push` works fine.
