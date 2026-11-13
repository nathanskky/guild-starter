# Turn off authorization

> For developers whose Guild app needs sign-in but no permissions, roles or Grouper groups.

This guide shows you how to remove authorization from an app created from guild/starter.

## Before you start

Your app has no permission checks of its own yet. A check left in place fails once authorization is gone,
because nothing provides `Gate`.

## Remove authorization

1. In `config/app.php`, delete the `->addAuthorization(…)` line.
2. Delete `config/grouper.php` and `config/authorization.php`.
3. In `config/personas.php`, remove the `grants:` argument.
4. In `src/Example/ExampleController.php`, remove the `Gate` constructor argument and the `authorize()` call.
5. In `tests/Feature/ExamplePageTest.php`, delete the tests that expect a 403 or a 503.
6. Run the tests: `docker compose exec app composer test`.

The framework's tables can stay in the database; nothing reads them. Users still sign in through IU Login,
or as personas locally, and every signed-in user can open every page that requires sign-in.

## Related

- guild/framework's [Set up authorization](https://github.com/nathanskky/guild-framework/blob/main/docs/guides/set-up-authorization.md) turns it back on.
