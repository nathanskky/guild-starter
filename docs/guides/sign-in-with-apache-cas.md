# Sign in with Apache CAS

> For developers whose Guild app signs people in with Apache `mod_auth_cas` instead of IU Login.

This guide shows you how to switch an app created from guild/starter from IU Login to Apache CAS, which
authenticates before PHP runs. IU's AppKube PHP image ships with Apache CAS set up.

## Before you start

- Your app doesn't rely on persona sign-in; guild/dev-tools supports IU Login apps only.
- Your Apache in production has `mod_auth_cas` configured, as AppKube's image does.

## Switch to CAS

1. Add these lines to the top of `public/.htaccess`:

   ```apache
   AuthType CAS
   Require valid-user
   ```

2. In `config/app.php`, delete `->addAuthentication()`, and change `IdentitySource::Oidc` to
   `IdentitySource::Cas`.
3. In `routes/routes.php`, remove `RequireSignIn` from every route; Apache now requires sign-in.
4. Delete `config/authentication.php`.
5. Remove persona sign-in by following guild/dev-tools'
   [Remove dev-tools](https://github.com/nathanskky/guild-dev-tools/blob/main/docs/guides/remove-dev-tools.md),
   because it supports IU Login apps only.

Without `addAuthentication()`, a route that still uses `RequireSignIn` fails when a request reaches it.
Under CAS, protect `/framework` in your Apache configuration too, because the framework can't send anyone
to sign in.

Locally, `docker/auth_cas.conf` configures the module; its `CASRootProxiedAs` follows `APP_HTTPS_PORT`.

## Related

- guild/framework's [Add sign-in](https://github.com/nathanskky/guild-framework/blob/main/docs/guides/add-sign-in.md) covers IU Login.
