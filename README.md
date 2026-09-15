# Guild Starter

The runnable example application for the [Guild framework](https://github.com/nathanskky/guild-framework),
and the starting point for new Guild apps.

> **Developing here?** Read [AGENTS.md](AGENTS.md) first. It covers the boot flow, the two supported
> authentication approaches, verification commands, conventions, and the known landmines. It is written for
> AI coding agents but is the most complete developer documentation for this project.

## Requirements

- PHP `~8.5.0`
- Composer
- Docker (for the local environment)

## Getting started

```bash
composer install          # note: composer.lock is tracked here - see AGENTS.md
cp app.env.example app.env
cp database.env.example database.env
cd docker && docker compose up
```

This brings up three services:

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
| `config/database.php` | an Eloquent connection array |
| `routes/routes.php` | a closure receiving the `Router` |

Environment variables come from Docker's `env_file:` entries (`app.env`, `database.env`) — **nothing in the
code parses a `.env` file**, so running outside Docker means running with no environment. Both `.env` files
are gitignored; the `.example` files are the committed templates.

## Authentication

Two approaches are supported — OIDC through the front controller, or Apache `mod_auth_cas` with
`.htaccess`. Neither is enabled out of the box. See
[AGENTS.md](AGENTS.md#authentication-two-supported-approaches) for which to choose and what each requires.

## Tests

```bash
composer test
```

`tests/` is currently empty, so this fails with `No tests executed!` on purpose rather than reporting a
false pass. Writing the first test is the fix.
