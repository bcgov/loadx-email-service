#!/bin/sh
# Open a MariaDB client session using the same env vars as the phpList containers.
#
#   db                          interactive prompt
#   db -e "SELECT NOW()"        run one statement
#   db < some.sql               run a script from stdin
#
# Environment:
#   PHPLIST_DATABASE_HOST      required
#   PHPLIST_DATABASE_NAME      required
#   PHPLIST_DATABASE_USER      required
#   PHPLIST_DATABASE_PASSWORD  required
#   PHPLIST_DATABASE_PORT      optional, default 3306
#   DB_SKIP_SSL=true           optional; use if the connection fails with a TLS/SSL error
set -eu

: "${PHPLIST_DATABASE_HOST:?PHPLIST_DATABASE_HOST is not set}"
: "${PHPLIST_DATABASE_NAME:?PHPLIST_DATABASE_NAME is not set}"
: "${PHPLIST_DATABASE_USER:?PHPLIST_DATABASE_USER is not set}"
: "${PHPLIST_DATABASE_PASSWORD:?PHPLIST_DATABASE_PASSWORD is not set}"

# Keeps the password off the command line (and out of `ps`).
export MYSQL_PWD="$PHPLIST_DATABASE_PASSWORD"

if command -v mariadb >/dev/null 2>&1; then client=mariadb; else client=mysql; fi

ssl=
[ "${DB_SKIP_SSL:-}" = "true" ] && ssl=--skip-ssl

# $ssl is intentionally unquoted: empty means no argument at all.
# shellcheck disable=SC2086
exec "$client" $ssl --connect-timeout=10 \
  -h "$PHPLIST_DATABASE_HOST" -P "${PHPLIST_DATABASE_PORT:-3306}" \
  -u "$PHPLIST_DATABASE_USER" -D "$PHPLIST_DATABASE_NAME" "$@"