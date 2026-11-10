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
cd my-project/docker && docker compose up
```

In a second terminal, from `my-project/docker/`:

```bash
docker compose exec app composer install
```

App: `https://localhost:8443`.

## Requirements (developing the starter itself)

- Docker

PHP (`~8.5.0`) and Composer run inside the `app` container; neither needs to be installed on the host.

## Getting started

```bash
cp docker/.env.example docker/.env        # optional: host ports, 1Password, personal overrides
cd docker && ./up.sh
```

Then, from `docker/` in a second terminal, install dependencies inside the container:

```bash
docker compose exec app composer install
```

`composer.lock` is gitignored, so the install resolves current versions of every dependency. The example
page is then at `https://localhost:8443` (or your `APP_HTTPS_PORT`).

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
| `config/database.php` | an Eloquent connection array |
| `config/rivet.php` | a `PageDefaults` object: app title, navigation, footer links |
| `routes/routes.php` | a closure receiving the `Router` |

## Environment and secrets

The application reads its settings from `$_ENV`; **nothing in the code parses a `.env` file**, so running
outside Docker means running with no environment. The `app` service's `environment:` list in
`docker/docker-compose.yml` names every setting, each with a local default. Each value comes from the first
of these that sets it:

1. The app's [1Password Environment](https://developer.1password.com/docs/environments/), when the stack
   starts through `docker/up.sh`. Shared settings go here as visible variables, and secrets as hidden ones.
2. `docker/.env`, which is gitignored. It holds personal, non-secret overrides.
3. The default in `docker-compose.yml`.

**Secrets never live in a file**, so `OIDC_CLIENT_SECRET` and `GROUPER_PASSWORD` come only from the
1Password Environment. They have no default and must never go in `docker/.env`. Name each variable in the
Environment exactly as `docker-compose.yml` lists it; compose ignores any name it doesn't list.

```bash
cd docker && ./up.sh       # or ./up.sh -d; arguments pass through to `docker compose up`
```

`up.sh` runs `op run --environment <id> -- docker compose up`, taking the ID from `OP_ENVIRONMENT_ID` in
`docker/.env`.

Setup, once per developer:

1. Install the [1Password CLI](https://developer.1password.com/docs/cli/) (2.41.0 or later).
2. Turn on **Integrate with 1Password CLI** in the 1Password app's Developer settings.
3. Set `OP_ENVIRONMENT_ID` in `docker/.env` to your app's Environment ID.

With no ID set, `up.sh` runs plain `docker compose up`. The defaults apply and the secrets stay blank,
which is fine until a feature needs one. Once secrets are in use, always start the stack with `up.sh`. A plain
`docker compose up` sees blank secrets, treats that as a configuration change, and recreates `app` without
them. `docker compose exec`, `restart` and `stop` don't recreate the container, so they're safe.

## Authentication

Two approaches are supported — OIDC through the front controller, or Apache `mod_auth_cas` with
`.htaccess`. Neither is enabled out of the box. See
[AGENTS.md](AGENTS.md#authentication-two-supported-approaches) for which to choose and what each requires.

## Authorization

Grouper groups mapped to roles and permissions, with administration pages at `/framework/authorization`.
Wired but commented out in `config/app.php`; it needs one of the authentication approaches above, the
Grouper settings `config/authorization.php` reads, and the framework's migrations. See
[AGENTS.md](AGENTS.md#authorization).

Its settings (`GROUPER_*` and `AUTHORIZATION_SYSTEM_ADMIN_GROUP`) go in the 1Password Environment; see
[Environment and secrets](#environment-and-secrets). `GROUPER_PASSWORD` is a secret.

## Rivet

Pages render through [guild/rivet](https://github.com/nathanskky/guild-rivet), IU's Rivet Design System
components. `templates/example/index.html.twig` is a working example: `{% rvt_page %}` provides the layout
and loads Rivet's CSS and JavaScript, and `config/rivet.php` sets the app title and navigation.

## Tests

From `docker/`:

```bash
docker compose exec app composer test    # phpunit
docker compose exec app composer check   # phpunit, then the Pint (PSR-12) style check
```

There are no tests, so `composer test` fails with `No tests executed!` on purpose rather than reporting
a false pass. Writing the first test, under `tests/`, is the fix.
