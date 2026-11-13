# AGENTS.md: guild/starter

> For maintainers of guild/starter and their agents. App developers start at [docs/index.md](docs/index.md).

The [workspace handbook](../AGENTS.md) covers cross-repo workflow and conventions.

guild/starter is the `composer create-project` template for new Guild apps, and a runnable app in its own
right. Unlike the other Guild repos it's a `project`, not a library: everything it ships becomes the first
commit of someone's app.

## Verify

`composer check` must pass. It runs Pint, PHPStan and PHPUnit, in that order. Run it in the container,
`docker compose exec app composer check` from `docker/`, unless you're testing an unreleased Guild
package through a path repository; then run it on the host, as the handbook says.

CI runs `composer check` and confirms that a `composer install --no-dev` build has no persona sign-in, on
every push to `develop` and `main` and every pull request.

## What a new app gets

`post-create-project-cmd` in `composer.json` copies the templates in `replacements/` over README,
AGENTS.md, CLAUDE.md, `composer.json`, `phpunit.xml.dist` and `.gitignore`, copies `docker/.env.example`
to `docker/.env`, removes `replacements/`, `docs/` and `.gitattributes`, and runs `git init`.

Everything else ships into every new app, so it follows these rules:

- A comment or page in a shipped file links to starter's docs by GitHub URL, never to `docs/` or AGENTS.md.
- Example code lives in `src/Example/` and `templates/example/`, so an app can delete it in one step.
- Examples use the app's own namespace in docs; starter's code keeps `Guild\Starter\`.

To check the hook, copy the repo to a scratch folder and create an app from the copy with a path
repository whose `versions` names the next tag, `symlink: false`. The app must have the three templates,
no `docs/`, and no pointer to a file that doesn't exist.

## Keeping docs current

Every code change gets a docs check in the same commit. Check README, `docs/`, AGENTS.md, the templates in
`replacements/` and the comments at the changed code for anything the change makes wrong or incomplete:

- Update a page that describes the changed behavior.
- If no page covers new public behavior, add one to `docs/` and list it in `docs/index.md`.
- If a maintainer needs a fact the code doesn't show, add a comment where the code is.
- If the behavior is gone, remove its page and its index entry.

Then check the pages that link to a changed page for text that's now wrong, and link a new page from
related pages. A non-obvious choice gets a decision record in `docs/decisions/`.

Public behavior here includes the start scripts, the settings in `docker-compose.yml`, the config files,
the post-create hook and what a new app contains. How a Guild app works belongs to guild/framework's docs,
and personas to guild/dev-tools'; link to them instead of restating them.

## Secrets

Secrets never live in a file, and agents must not try to read them. `OIDC_CLIENT_SECRET` and
`GROUPER_PASSWORD` come only from the 1Password Environment.

- Never give a secret a default in `docker-compose.yml`, or a value in `docker/.env` or any other file.
- Never run a command that prints a secret: `op`, `env` or `printenv` in the container, `docker inspect`, `docker compose config`.
- When a setting or secret is missing, tell the developer which variable to add to the 1Password Environment, then stop.

`.claude/settings.json` denies those commands for Claude Code. It ships to new apps, so keep it current.

## Gotchas

- `RequireSignIn` on the whole router also guards `/_dev/personas` and loops; attach it per route or group.
- `docker/auth_cas.conf` and the `auth_cas` step in `app.Dockerfile` are needed by the image build and the CAS path.
- The Apache configs are templates that `docker-entrypoint.sh` renders at each start, so a port change needs no rebuild.
- `PERSONA_SIGN_IN_ENABLED` defaults to `true` in Compose, so `op run … docker compose up` without the script fails at boot.
- `bin/`, `lib/`, `html/`, `public/css/` and `public/js/` are empty and untracked.

The comment at each gotcha's code explains the reason; the empty folders have no code to carry one.

The default `OIDC_REDIRECT_URI` is `https://localhost:8443/signin-oidc`, and no route in `routes/routes.php`
handles `/signin-oidc`. guild/framework's sign-in finishes only on a route that runs `RequireSignIn`, so
check this before relying on real IU Login locally.
