# Run locally

> For developers starting, stopping and reaching a Guild app on their own machine.

When you finish, your app runs in Docker in the sign-in mode you chose, and you know which address and
port reaches each service.

PHP and Composer run inside the `app` container, so your machine needs only Docker.

## Start the app

1. Optional: to change the host ports or set personal overrides, copy `docker/.env.example` to
   `docker/.env` and edit it. An app created from starter already has `docker/.env`.

2. From `docker/`, start the stack with one of the two scripts:

   | Script | Signs in through | Needs |
   |---|---|---|
   | `./up-personas.sh` | Personas from `config/personas.php` | Nothing |
   | `./up-iu-services.sh` | IU Login, with groups from Grouper | The app's 1Password Environment; see [Manage secrets](../guides/manage-secrets.md) |

   Both scripts pass their arguments to `docker compose up`, so `./up-personas.sh -d` starts in the
   background.

3. Open `https://localhost:8443`, or your `APP_HTTPS_PORT`. Expect a self-signed certificate warning.

Each time the `app` container starts, it runs `composer install` and the framework's migrations before
Apache. If either fails, the container still starts; read `docker compose logs app`, then rerun it with
`docker compose exec app composer install` or `docker compose exec app composer migrate`.

## Services and ports

| Service | Purpose | Host port |
|---|---|---|
| `app` | Apache and PHP 8.5 | `8080` for HTTP, which redirects to `8443` for HTTPS |
| `database` | MySQL 8.4 | `9906` |
| `mailcatcher` | Catches the mail your app sends; web UI at `http://localhost:1080` | `1080`, and `1025` for SMTP |

To change the app's ports, set `APP_HTTP_PORT` and `APP_HTTPS_PORT` in `docker/.env`, then start the
stack again with a script. No image rebuild is needed.

## Restart and stop

Start the stack through a script every time. A plain `docker compose up` recreates the `app` container
whenever its settings changed, and it starts in persona mode. After `./up-iu-services.sh`, a plain `up`
switches the app to personas; the banner at the top of each page shows it.

`docker compose restart`, `stop` and `exec` don't recreate the container, so they keep the current mode.
They also don't read changed settings: after changing `docker/.env`, start the stack again with a script.

After changing `docker/docker-entrypoint.sh` or `docker/app.Dockerfile`, start with `--build`, such as
`./up-personas.sh --build`.

## Run Composer and the tests

Run Composer inside the container, not on your machine, so it checks the container's PHP and extensions:

```bash
docker compose exec app composer test     # PHPUnit
docker compose exec app composer check    # Pint, PHPStan, then PHPUnit
```

Feature tests sign in as personas; guild/dev-tools' [Test as a persona](https://github.com/nathanskky/guild-dev-tools/blob/main/docs/guides/test-as-a-persona.md)
shows how to write one.

## Errors and logs

With `DISPLAY_ERRORS_ENABLED=true`, the local default, the app shows PHP errors on the page. Deprecations
raised inside `vendor/` go to the log instead, because printed before a redirect they break IU Login.
The framework's log and PHP's errors appear in `docker compose logs app`.

## Related

- [Manage secrets](../guides/manage-secrets.md) sets up IU Login and Grouper credentials.
- [Environment variables](../reference/environment-variables.md) lists every setting the app reads.
