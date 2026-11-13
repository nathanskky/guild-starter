# guild/starter

The template for new Guild apps: IU web apps in PHP on [guild/framework](https://github.com/nathanskky/guild-framework).
A new app comes with IU Login sign-in, Grouper-based permissions, IU Rivet pages, persona sign-in for
local development, and a Docker stack.

## Create an app

You need Composer and Docker:

```bash
composer create-project --no-install \
  --repository='{"type":"vcs","url":"https://github.com/nathanskky/guild-starter.git"}' \
  --remove-vcs \
  guild/starter my-app
cd my-app/docker && ./up-personas.sh
```

Then open `https://localhost:8443` and choose a persona.

## Start here

[Create an app](docs/start/create-an-app.md) walks you through the first run, step by step.

## All docs

The [docs index](docs/index.md) lists every page for running and configuring an app. How a Guild app
works is in [guild/framework's docs](https://github.com/nathanskky/guild-framework/blob/main/docs/index.md).
