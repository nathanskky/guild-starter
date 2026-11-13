# Config files

> For developers looking up what each file in a Guild app's `config/` returns and when it's read.

Each file is PHP that returns an object, an array or a function. guild/framework's
[config files reference](https://github.com/nathanskky/guild-framework/blob/main/docs/reference/config-files.md)
says which builder call reads each framework file.

| File | Returns | Read |
|---|---|---|
| `config/app.php` | The built `Application` | On every request, from `bootstrap/app.php` |
| `config/authentication.php` | guild/access's `OidcConfiguration` | With IU Login; not while persona sign-in is on |
| `config/authorization.php` | `AuthorizationConfiguration` | On every request |
| `config/grouper.php` | `GrouperDirectoryConfiguration` | With Grouper; not while persona sign-in is on |
| `config/personas.php` | guild/dev-tools' `PersonaRoster` | Only while persona sign-in is on |
| `config/database.php` | An Eloquent connection array | On every request |
| `config/rivet.php` | guild/rivet's `PageDefaults` | On every request, passed to `addRivet()` |
| `routes/routes.php` | A function that receives the `Router` | On every request |

`config/authentication.php` stops the app with a message when `PERSONA_SIGN_IN_ENABLED` is on but
guild/dev-tools isn't installed, such as after `composer install --no-dev`.

Each file reads its settings from `$_ENV`; [Environment variables](environment-variables.md) lists them.
