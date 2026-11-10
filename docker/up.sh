#!/bin/sh
# Start the stack with secrets loaded from a 1Password Environment.
#
#   ./up.sh            # foreground
#   ./up.sh -d         # any `docker compose up` arguments pass through
#
# The Environment ID comes from OP_ENVIRONMENT_ID in the shell, else from docker/.env.
# `op run` loads the Environment's variables into docker compose's environment, and
# docker-compose.yml passes the secrets it lists into the app container. With no ID set,
# this runs plain `docker compose up`, and the secrets stay blank.
set -e

cd "$(dirname "$0")"

if [ -z "${OP_ENVIRONMENT_ID:-}" ] && [ -f .env ]; then
    OP_ENVIRONMENT_ID=$(sed -n 's/^OP_ENVIRONMENT_ID=//p' .env | tail -n 1 | tr -d "\"' \r")
fi

if [ -z "${OP_ENVIRONMENT_ID:-}" ]; then
    echo "up.sh: OP_ENVIRONMENT_ID is not set; starting without 1Password secrets." >&2
    exec docker compose up "$@"
fi

if ! command -v op >/dev/null 2>&1; then
    echo "up.sh: the 1Password CLI (op) is not installed, but OP_ENVIRONMENT_ID is set." >&2
    echo "Install it, then turn on Integrate with 1Password CLI in the 1Password app's" >&2
    echo "Developer settings: https://developer.1password.com/docs/cli/get-started/" >&2
    exit 1
fi

exec op run --environment "$OP_ENVIRONMENT_ID" -- docker compose up "$@"
