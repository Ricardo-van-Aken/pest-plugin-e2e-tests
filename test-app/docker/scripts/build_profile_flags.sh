#!/bin/sh

set -euo pipefail

if [ "$#" -ne 4 ]; then
  echo "Usage: $0 <db> <cache> <queue> <sessions>" >&2
  exit 1
fi

DB_OPTION="$1"
CACHE_OPTION="$2"
QUEUE_OPTION="$3"
SESSION_OPTION="$4"

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
    echo "Unsupported DB option: $DB_OPTION" >&2
    echo "Supported DB options: mysql, sqlite" >&2
    exit 1
    ;;
esac

case "$CACHE_OPTION" in
  redis) add_profile "cache-redis" ;;
  array) ;;
  *)
    echo "Unsupported cache option: $CACHE_OPTION" >&2
    echo "Supported cache options: redis, array" >&2
    exit 1
    ;;
esac

case "$QUEUE_OPTION" in
  redis) add_profile "queue-redis" ;;
  sync) ;;
  *)
    echo "Unsupported queue option: $QUEUE_OPTION" >&2
    echo "Supported queue options: redis, sync" >&2
    exit 1
    ;;
esac

case "$SESSION_OPTION" in
  redis) add_profile "session-redis" ;;
  array) ;;
  *)
    echo "Unsupported session option: $SESSION_OPTION" >&2
    echo "Supported session options: redis, array" >&2
    exit 1
    ;;
esac

PROFILE_FLAGS=""
for PROFILE in $PROFILES; do
  PROFILE_FLAGS="$PROFILE_FLAGS --profile $PROFILE"
done

printf "%s\n" "$PROFILE_FLAGS"

