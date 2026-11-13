# Remove the example code

> For developers clearing guild/starter's example page out of their new app.

This guide shows you which files make up the example page, and what to keep when you remove it.

## Before you start

Your app was created from guild/starter, as in [Create an app](../start/create-an-app.md).

## Remove the example

1. Delete `src/Example/` and `templates/example/`.
2. In `routes/routes.php`, delete the `/` route that names `ExampleController`, and add your own routes.
3. Delete `tests/Feature/ExamplePageTest.php`, or rewrite it for your own page.
4. In `src/Authorization/AppPermission.php`, replace `ExampleView` with your app's permissions.
5. In `config/personas.php`, change the roster's grants to use your permissions.
6. In `config/rivet.php`, set your app's title and navigation.
7. Run the tests: `docker compose exec app composer test`.

How you organize `src/` is up to you; `src/Example/` isn't a layout to copy.

## Keep these

- `docker/auth_cas.conf` and the `auth_cas` step in `docker/app.Dockerfile`, which the image build needs.
- `config/`, `bootstrap/` and `public/`, which start the app.
- `.claude/settings.json`, which keeps secrets out of Claude Code's view.

## Related

- guild/framework's [docs](https://github.com/nathanskky/guild-framework/blob/main/docs/index.md) show how to add sign-in, permissions and policies to your own pages.
- guild/rivet's [Build a page](https://github.com/nathanskky/guild-rivet/blob/main/docs/guides/build-a-page.md) shows how to write a page.
