# Environment variables

> For developers checking a setting a Guild app created from guild/starter reads.

`docker/docker-compose.yml` lists every variable the app reads. [Manage secrets](../guides/manage-secrets.md)
explains where each value comes from.

| Variable | Default | Meaning |
|---|---|---|
| `PERSONA_SIGN_IN_ENABLED` | `true` | Persona sign-in on or off; set by the start scripts, never by you |
| `APP_HTTP_PORT` | `8080` | Host port for HTTP, which redirects to HTTPS; read by Compose only |
| `APP_HTTPS_PORT` | `8443` | Host port for HTTPS |
| `DEFAULT_TIMEZONE` | `UTC` | PHP's default time zone |
| `DISPLAY_ERRORS_ENABLED` | `true` | Show PHP errors on the page |
| `MYSQL_HOST` | `database` | Database host |
| `MYSQL_PORT` | `3306` | Database port |
| `MYSQL_DATABASE` | `app_database` | Database name; the local `database` service uses it too |
| `MYSQL_USERNAME` | `app_user` | Database user |
| `MYSQL_PASSWORD` | `app_password` | Database password for the local database only |
| `OIDC_ISSUER` | `https://idp.login.iu.edu` | IU Login's address |
| `OIDC_CLIENT_ID` | Blank | Your IU Login client ID |
| `OIDC_REDIRECT_URI` | `https://localhost:8443/signin-oidc` | The redirect URI registered for your IU Login client |
| `OIDC_CLIENT_SECRET` | Blank | Secret; 1Password Environment only |
| `GROUPER_SERVICE_URL` | Blank | Grouper's REST base URL |
| `GROUPER_USERNAME` | Blank | Your Grouper service account |
| `GROUPER_PASSWORD` | Blank | Secret; 1Password Environment only |
| `GROUPER_STEM` | Blank | Grouper stem; blank means `iu:roles:sys:acm` |
| `AUTHORIZATION_SYSTEM_ADMIN_GROUP` | Blank | ACM label of the group that administers the app |
| `OP_ENVIRONMENT_ID` | Blank | Your 1Password Environment's ID, in `docker/.env`; read by `up-iu-services.sh` |

With IU Login, a blank `OIDC_*`, `GROUPER_*` or `AUTHORIZATION_SYSTEM_ADMIN_GROUP` value stops the app at
startup, except `GROUPER_STEM`. Persona sign-in reads none of them, and refuses to start if either secret
is set.
