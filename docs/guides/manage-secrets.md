# Manage secrets

> For developers giving a Guild app its IU Login and Grouper settings and secrets.

This guide shows you how to load your app's settings and secrets from a 1Password Environment and start
the app with real IU Login and Grouper.

## Before you start

- Your app has a 1Password Environment, with access granted to you.
- You have the [1Password CLI](https://developer.1password.com/docs/cli/), version 2.41.0 or later.

## Where each setting comes from

The app reads settings from `$_ENV` only; nothing parses a `.env` file. The `environment:` list of the
`app` service in `docker/docker-compose.yml` names every setting. Compose fills each one from the first
source that sets it:

1. The start script, which sets `PERSONA_SIGN_IN_ENABLED`.
2. The 1Password Environment, when you start with `./up-iu-services.sh`.
3. `docker/.env`, which Git ignores.
4. The default in `docker-compose.yml`.

Secrets never live in a file. `OIDC_CLIENT_SECRET` and `GROUPER_PASSWORD` come only from the 1Password
Environment. Don't give them a default in `docker-compose.yml`, and don't put them in `docker/.env` or any
other file.

## Set up 1Password, once

1. In the 1Password app's Developer settings, turn on **Integrate with 1Password CLI**.
2. Copy your app's Environment ID: in the 1Password app, select **Developer**, then **View Environments**,
   your Environment, **Manage environment** and **Copy environment ID**.
3. Set `OP_ENVIRONMENT_ID` in `docker/.env` to that ID. The ID isn't a secret; it grants nothing without
   access to the Environment.

## Fill the Environment

In the 1Password Environment, add each variable with exactly the name `docker-compose.yml` lists, because
Compose ignores any other name:

- As hidden variables: `OIDC_CLIENT_SECRET` and `GROUPER_PASSWORD`.
- As visible variables: `OIDC_CLIENT_ID`, `GROUPER_SERVICE_URL`, `GROUPER_USERNAME` and `AUTHORIZATION_SYSTEM_ADMIN_GROUP`.
- Only if it differs from the default: `OIDC_REDIRECT_URI` and `GROUPER_STEM`.

[Environment variables](../reference/environment-variables.md) describes each one. Don't add
`PERSONA_SIGN_IN_ENABLED`; the start scripts set it.

## Start with IU Login and Grouper

From `docker/`, run:

```bash
./up-iu-services.sh
```

The script runs `op run --environment <id> -- docker compose up` with persona sign-in off. It stops with a
message if `OP_ENVIRONMENT_ID` is unset or the `op` CLI is missing, rather than starting IU Login with no
secrets. If a required setting is blank, the app stops at startup and names it.

## Switch back to personas

Stop the stack and start it with `./up-personas.sh`. That script blanks both secrets, because persona
sign-in refuses to start beside real credentials. A setting that lives only in the 1Password Environment
doesn't apply in persona mode; give it a default in `docker-compose.yml`, or set it in `docker/.env`.

## Add a setting

1. Add a line to the `app` service's `environment:` list: `- NAME=${NAME:-default}`.
2. Read it in `config/` as `$_ENV['NAME']`.

For a secret, leave the default blank, `- NAME=${NAME:-}`, and add its value to the 1Password Environment.

## Keep secrets out of your agent's view

Your app's `.claude/settings.json` denies Claude Code the commands that print secrets, such as `op`,
`printenv`, `docker inspect` and `docker compose config`. Keep those entries when you edit the file.

## Related

- [Run locally](../start/run-locally.md) explains the start scripts.
- guild/dev-tools' [Safety](https://github.com/nathanskky/guild-dev-tools/blob/main/docs/concepts/safety.md) explains what keeps persona sign-in out of production.
