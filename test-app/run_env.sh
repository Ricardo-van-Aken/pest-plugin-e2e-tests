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

COMPOSE_FILE="docker/docker-compose.yml"

PROFILES="core"

add_profile() {
  PROFILE_NAME="$1"
  if [ -n "$PROFILE_NAME" ]; then
    case " $PROFILES " in
      *" $PROFILE_NAME "*) ;;
      *) PROFILES="$PROFILES $PROFILE_NAME" ;;
    esac
  fi
}

case "$DB_OPTION" in
  mysql) add_profile "db-mysql" ;;
  sqlite) ;;
  *)
    echo "Unsupported DB option: $DB_OPTION"
    echo "Supported DB options: mysql, sqlite"
    exit 1
    ;;
esac

case "$CACHE_OPTION" in
  redis) add_profile "cache-redis" ;;
  array) ;;
  *)
    echo "Unsupported cache option: $CACHE_OPTION"
    echo "Supported cache options: redis, array"
    exit 1
    ;;
esac

case "$QUEUE_OPTION" in
  redis) add_profile "queue-redis" ;;
  sync) ;;
  *)
    echo "Unsupported queue option: $QUEUE_OPTION"
    echo "Supported queue options: redis, sync"
    exit 1
    ;;
esac

case "$SESSION_OPTION" in
  redis) add_profile "session-redis" ;;
  array) ;;
  *)
    echo "Unsupported session option: $SESSION_OPTION"
    echo "Supported session options: redis, array"
    exit 1
    ;;
esac

PROFILE_FLAGS=""
for PROFILE in $PROFILES; do
  PROFILE_FLAGS="$PROFILE_FLAGS --profile $PROFILE"
done

# Build the base laravel application image if it doesn't exist or Dockerfile.base has changed
docker build -f docker/img_laravel/Dockerfile.laravel-base -t local/e2e-tests:laravel-base.latest .

# Remove volumes for the selected profiles (clean start)
docker compose -f "$COMPOSE_FILE" $PROFILE_FLAGS down -v --remove-orphans

# Run docker compose with the selected profiles
docker compose -f "$COMPOSE_FILE" $PROFILE_FLAGS up -d --build