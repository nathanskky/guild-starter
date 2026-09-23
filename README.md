# Guild Starter

The runnable example application for the [Guild framework](https://github.com/nathanskky/guild-framework),
and the starting point for new Guild apps.

> **Developing here?** Read [AGENTS.md](AGENTS.md) first. It covers the boot flow, directory layout, the two
> supported authentication approaches, authorization, verification commands, conventions, and the known
> landmines. It is written for AI coding agents but is the most complete developer documentation for this
> project.

## Requirements

- Docker

PHP (`~8.5.0`) and Composer run inside the `app` container; neither needs to be installed on the host.

## Getting started

```bash
cp app.env.example app.env
cp database.env.example database.env
cp docker/.env.example docker/.env        # optional: host ports, see below
cd docker && docker compose up
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

Environment variables come from Docker's `env_file:` entries (`app.env`, `database.env`) — **nothing in the
code parses a `.env` file**, so running outside Docker means running with no environment. Both `.env` files
are gitignored; the `.example` files are the committed templates.

## Authentication

Two approaches are supported — OIDC through the front controller, or Apache `mod_auth_cas` with
`.htaccess`. Neither is enabled out of the box. See
[AGENTS.md](AGENTS.md#authentication-two-supported-approaches) for which to choose and what each requires.

## Authorization

Grouper groups mapped to roles and permissions, with administration pages at `/framework/authorization`.
Wired but commented out in `config/app.php`; it needs one of the authentication approaches above, the
Grouper settings `config/authorization.php` reads, and the framework's migrations. See
[AGENTS.md](AGENTS.md#authorization).

`GROUPER_PASSWORD` is never written to a file. Export it in your shell before `docker compose up`, and
`docker-compose.yml` passes it into the container; do not put it in `app.env` or `docker/.env`. The other
`GROUPER_*` settings and `AUTHORIZATION_SYSTEM_ADMIN_GROUP` go in `app.env`, which `app.env.example` does not
list.

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
