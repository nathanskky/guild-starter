# Guild Starter

The runnable example application for the [Guild framework](https://github.com/nathanskky/guild-framework),
and the starting point for new Guild apps.

> **Developing here?** Read [AGENTS.md](AGENTS.md) first. It covers the boot flow, directory layout, the two
> supported authentication approaches, authorization, verification commands, conventions, and the known
> landmines. It is written for AI coding agents but is the most complete developer documentation for this
> project.

## Creating a new app

Requires [Composer](https://getcomposer.org/download/) and Docker on the host. Any PHP version Composer runs
on will do: dependencies install inside the `app` container, not on the host.

```bash
composer create-project --no-install \
  --repository='{"type":"vcs","url":"https://github.com/nathanskky/guild-starter.git"}' \
  --remove-vcs \
  guild/starter my-project
cd my-project/docker && ./up-personas.sh
```

The `app` container runs `composer install` and the database migrations each time it starts. Open
`https://localhost:8443` and pick a persona; no credentials are needed.

## Requirements (developing the starter itself)

- Docker

PHP (`~8.5.0`) and Composer run inside the `app` container; neither needs to be installed on the host.

## Getting started

```bash
cp docker/.env.example docker/.env        # optional: host ports, 1Password, personal overrides
cd docker && ./up-personas.sh            # sign in as personas; or ./up-iu-services.sh, see below
```

The `app` container runs `composer install` and then `composer migrate` each time it starts, before
Apache. `composer.lock` is gitignored, so the first install resolves current versions of every dependency.
The example page is at `https://localhost:8443` (or your `APP_HTTPS_PORT`); it sends you to the persona
picker first.

There are two start scripts, one per sign-in mode. Both pass their arguments to `docker compose up`
(`./up-personas.sh -d`, say):

| Script | Signs in through | Needs |
|---|---|---|
| `docker/up-personas.sh` | Personas from `config/personas.php` | Nothing |
| `docker/up-iu-services.sh` | Real IU Login, with groups from Grouper | The app's 1Password Environment (below) |

The stack is three services:

| Service | Purpose | Ports (host:container) |
|---|---|---|
| `app` | Apache + PHP 8.5 | `8080:80`, `8443:443` by default (configurable, see below) |
| `database` | MySQL 8.4 | `9906:3306` |
| `mailcatcher` | Catches outbound mail; web UI | `1080:1080`, `1025:1025` |

Mailcatcher's web UI is at `http://localhost:1080`.

**A note on the app URL.** The app's host ports are set via `APP_HTTP_PORT`/`APP_HTTPS_PORT` in
`docker/.env` (copy `docker/.env.example` to get started; defaults to `8080`/`8443` if the file is absent).
The `*:80` virtual host redirects to HTTPS at whatever `APP_HTTPS_PORT` resolves to — go to the HTTPS port
directly and expect a self-signed certificate warning. `docker/auth_cas.conf`'s `CASRootProxiedAs` is kept
in sync with the same port automatically, since both files are rendered from templates at container start.
See [AGENTS.md](AGENTS.md#local-environment-docker) for detail.

## Configuration

Configuration is **executable PHP that returns objects, arrays, or closures** — not config arrays
throughout:

| File | Returns |
|---|---|
| `config/app.php` | the built `Application` (the builder chain lives here) |
| `config/authentication.php` | an `OidcConfiguration` object |
| `config/authorization.php` | an `AuthorizationConfiguration` object |
| `config/grouper.php` | a `GrouperDirectoryConfiguration` object: how to reach Grouper |
| `config/personas.php` | a `PersonaRoster` object: the personas for local sign-in and tests |
| `config/database.php` | an Eloquent connection array |
| `config/rivet.php` | a `PageDefaults` object: app title, navigation, footer links |
| `routes/routes.php` | a closure receiving the `Router` |

## Environment and secrets

The application reads its settings from `$_ENV`; **nothing in the code parses a `.env` file**, so running
outside Docker means running with no environment. The `app` service's `environment:` list in
`docker/docker-compose.yml` names every setting, each with a local default. Each value comes from the first
of these that sets it:

1. The app's [1Password Environment](https://developer.1password.com/docs/environments/), when the stack
   starts through `docker/up-iu-services.sh`. Shared settings go here as visible variables, and secrets as hidden ones.
2. `docker/.env`, which is gitignored. It holds personal, non-secret overrides.
3. The default in `docker-compose.yml`.

**Secrets never live in a file**, so `OIDC_CLIENT_SECRET` and `GROUPER_PASSWORD` come only from the
1Password Environment. They have no default and must never go in `docker/.env`. Name each variable in the
Environment exactly as `docker-compose.yml` lists it; compose ignores any name it doesn't list.

```bash
cd docker && ./up-iu-services.sh       # or ./up-iu-services.sh -d
```

`up-iu-services.sh` runs `op run --environment <id> -- docker compose up`, taking the ID from
`OP_ENVIRONMENT_ID` in `docker/.env`. It stops with a message when the ID is unset or the `op` CLI is
missing. `up-personas.sh` loads no 1Password Environment, so a setting that lives only there doesn't apply
in persona mode; give it a compose default or put it in `docker/.env`. Persona mode refuses to start if
either secret is set.

Setup, once per developer:

1. Install the [1Password CLI](https://developer.1password.com/docs/cli/) (2.41.0 or later).
2. Turn on **Integrate with 1Password CLI** in the 1Password app's Developer settings.
3. Set `OP_ENVIRONMENT_ID` in `docker/.env` to your app's Environment ID.

Start the stack through a script every time. A plain `docker compose up` starts in persona mode, so after
`up-iu-services.sh` it recreates `app` without the Environment's values and switches to personas; the
banner at the top of each page shows it. `docker compose exec`, `restart` and `stop` don't recreate the
container, so they're safe.

## Authentication

People sign in through IU Login (OIDC), and pages that need a signed-in user carry `RequireSignIn` in
`routes/routes.php`. Apache `mod_auth_cas` with `.htaccess` is also supported. See
[AGENTS.md](AGENTS.md#authentication-two-supported-approaches) for which to choose and what each requires.

## Authorization

Grouper groups mapped to roles and permissions, with administration pages at `/framework/authorization`.
On by default. With IU Login it needs the Grouper settings `config/grouper.php` reads, the System Admin
group label, and the framework's migrations. See [AGENTS.md](AGENTS.md#authorization).

Its settings (`GROUPER_*` and `AUTHORIZATION_SYSTEM_ADMIN_GROUP`) go in the 1Password Environment; see
[Environment and secrets](#environment-and-secrets). `GROUPER_PASSWORD` is a secret.

## Personas

Locally and in tests, you sign in as a **persona** instead of through IU Login: a made-up user whose
Grouper groups come from `config/personas.php`, so no credentials are needed. Pick one at
`/_dev/personas`; a banner on every page shows who you are. Persona sign-in comes from `guild/dev-tools`,
installed with `require-dev` only, so production builds don't contain it. See
[AGENTS.md](AGENTS.md#personas).

## Rivet

Pages render through [guild/rivet](https://github.com/nathanskky/guild-rivet), IU's Rivet Design System
components. `templates/example/index.html.twig` is a working example: `{% rvt_page %}` provides the layout
and loads Rivet's CSS and JavaScript, and `config/rivet.php` sets the app title and navigation.

## Tests

From `docker/`:

```bash
docker compose exec app composer test    # phpunit
docker compose exec app composer check   # Pint style check, PHPStan, then phpunit
```

`composer check` runs the Pint style check, PHPStan (level 10), then PHPUnit. CI runs it, plus a check
that a `--no-dev` build has no persona sign-in, on every push to `develop`/`main` and every pull request. Feature tests in
`tests/Feature/` sign in as personas; see [AGENTS.md](AGENTS.md#personas).
