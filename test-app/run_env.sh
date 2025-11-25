#!/bin/sh

# This script runs docker-compose.yml with selectable storage options.
# Usage:
#   ./run_env.sh <db> <cache> <queue> <sessions>
# Example:
#   ./run_env.sh mysql redis redis redis
#
# Supported storage options:
#   DB: mysql, sqlite
#   Cache: redis, array
#   Queue: redis, sync
#   Sessions: redis, array

set -euo pipefail

DB_OPTION="${1:-}"
CACHE_OPTION="${2:-}"
QUEUE_OPTION="${3:-}"
SESSION_OPTION="${4:-}"

if [ -z "$SESSION_OPTION" ]; then
  echo "Usage: $0 <db> <cache> <queue> <sessions>"
  echo "Example: $0 mysql redis redis redis"
  exit 1
fi

# Build the docker-compose env file for the requested stack combination.
./docker/scripts/build_compose_env.sh "$DB_OPTION" "$CACHE_OPTION" "$QUEUE_OPTION" "$SESSION_OPTION"

# Build the Laravel application's .env file that will be bind-mounted into the container.
./docker/scripts/build_laravel_env.sh "$DB_OPTION" "$CACHE_OPTION" "$QUEUE_OPTION" "$SESSION_OPTION"

# Build the base laravel application image if it doesn't exist or Dockerfile.base has changed. Uses IMAGE_REPO from the env file.
set -a
. "docker/.env"
set +a
docker build -f docker/img_laravel/Dockerfile.laravel-base -t "${IMAGE_REPO}:laravel-base.latest" .

# Build the profile flags for the requested stack combination.
PROFILE_FLAGS="$(./docker/scripts/build_profile_flags.sh "$DB_OPTION" "$CACHE_OPTION" "$QUEUE_OPTION" "$SESSION_OPTION")"

# Remove volumes for the selected profiles (clean start)
docker compose -f "docker/docker-compose.yml" $PROFILE_FLAGS down -v --remove-orphans

# Run docker compose with the selected profiles
docker compose -f "docker/docker-compose.yml" $PROFILE_FLAGS up -d --build