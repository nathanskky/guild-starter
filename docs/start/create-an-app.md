# Create an app

> For developers starting a new Guild app from guild/starter.

When you finish, you have a new app in its own folder, running locally, with you signed in as a persona.

You need [Composer](https://getcomposer.org/download/) and Docker on your machine. Any PHP version that
runs Composer works, because the app's dependencies install inside its container.

1. Create the app, replacing `my-app` with your app's folder name:

   ```bash
   composer create-project --no-install \
     --repository='{"type":"vcs","url":"https://github.com/nathanskky/guild-starter.git"}' \
     --remove-vcs \
     guild/starter my-app
   ```

   Composer copies the starter, gives the app its own `README.md`, `AGENTS.md`, `CLAUDE.md`,
   `composer.json` and `docker/.env`, and runs `git init` on a `main` branch.

2. Start the app with persona sign-in:

   ```bash
   cd my-app/docker && ./up-personas.sh
   ```

   The first start takes longer than later ones: the `app` container installs the dependencies,
   creates the database tables, and then starts Apache.

3. Open `https://localhost:8443` and accept the self-signed certificate warning.

   The app sends you to the persona picker.

4. Select **Sign in as Erin Editor**.

   The example page opens, with a banner at the top that names Erin Editor.

Your app is running. Next:

- [Run locally](run-locally.md) explains the start scripts, ports and services.
- [Remove the example code](../guides/remove-the-example-code.md) clears the example page when you start your own.
- guild/dev-tools' [Turn persona sign-in on and off](https://github.com/nathanskky/guild-dev-tools/blob/main/docs/start/turn-persona-sign-in-on-and-off.md) switches to real IU Login.
