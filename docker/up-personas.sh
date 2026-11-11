#!/bin/sh
# Start the stack with persona sign-in: no IU Login, no Grouper, no 1Password.
#
#   ./up-personas.sh       # foreground
#   ./up-personas.sh -d    # any `docker compose up` arguments pass through
#
# Sign in as a persona from config/personas.php at https://localhost:8443/_dev/personas.
# Both secrets are blanked for compose, since persona sign-in refuses to start beside
# real IU Login or Grouper credentials; a shell that exported them would otherwise trip it.
set -e

cd "$(dirname "$0")"

export PERSONA_SIGN_IN_ENABLED=true OIDC_CLIENT_SECRET= GROUPER_PASSWORD=
exec docker compose up "$@"
