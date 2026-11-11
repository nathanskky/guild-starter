#!/bin/sh
# Start the stack with real IU Login and Grouper, loading settings and secrets from a
# 1Password Environment.
#
#   ./up-iu-services.sh       # foreground
#   ./up-iu-services.sh -d    # any `docker compose up` arguments pass through
#
# The Environment ID comes from OP_ENVIRONMENT_ID in the shell, else from docker/.env.
# `op run` loads the Environment's variables into docker compose's environment, and
# docker-compose.yml passes the settings it lists into the app container. Without an ID
# or the 1Password CLI this stops, rather than start IU Login with no secrets.
set -e

cd "$(dirname "$0")"

if [ -z "${OP_ENVIRONMENT_ID:-}" ] && [ -f .env ]; then
    OP_ENVIRONMENT_ID=$(sed -n 's/^OP_ENVIRONMENT_ID=//p' .env | tail -n 1 | tr -d "\"' \r")
fi

if [ -z "${OP_ENVIRONMENT_ID:-}" ]; then
    echo "up-iu-services.sh: OP_ENVIRONMENT_ID is not set. Set it in docker/.env (see docker/.env.example)," >&2
    echo "or start with ./up-personas.sh for persona sign-in." >&2
    exit 1
fi

if ! command -v op >/dev/null 2>&1; then
    echo "up-iu-services.sh: the 1Password CLI (op) is not installed, but it is needed to load the" >&2
    echo "Environment. Install it, then turn on Integrate with 1Password CLI in the 1Password app's" >&2
    echo "Developer settings: https://developer.1password.com/docs/cli/get-started/" >&2
    exit 1
fi

export PERSONA_SIGN_IN_ENABLED=false
exec op run --environment "$OP_ENVIRONMENT_ID" -- docker compose up "$@"
