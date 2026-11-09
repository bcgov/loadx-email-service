#!/bin/sh
# Nightly phpList database backup with retention purge.
#
#   backup.sh
#
# Environment:
#   PHPLIST_DATABASE_HOST      required
#   PHPLIST_DATABASE_NAME      required
#   PHPLIST_DATABASE_USER      required
#   PHPLIST_DATABASE_PASSWORD  required
#   PHPLIST_DATABASE_PORT      optional, default 3306
#   BACKUP_DIR                 optional, default /backups
#   KEEP_DAYS                  optional, default 31
#   DB_SKIP_SSL=true           optional; use if the connection fails with a TLS/SSL error
#
# Exits non-zero on any failure, so a caller using `set -e` (or &&) will not
# go on to run the cleanup. Old backups are purged only after a verified new one exists.
set -eu

: "${PHPLIST_DATABASE_HOST:?PHPLIST_DATABASE_HOST is not set}"
: "${PHPLIST_DATABASE_NAME:?PHPLIST_DATABASE_NAME is not set}"
: "${PHPLIST_DATABASE_USER:?PHPLIST_DATABASE_USER is not set}"
: "${PHPLIST_DATABASE_PASSWORD:?PHPLIST_DATABASE_PASSWORD is not set}"

BACKUP_DIR="${BACKUP_DIR:-/backups}"
KEEP_DAYS="${KEEP_DAYS:-31}"

[ -d "$BACKUP_DIR" ] && [ -w "$BACKUP_DIR" ] || { echo "ERROR: $BACKUP_DIR is missing or not writable" >&2; exit 1; }

TS=$(date +%Y%m%d-%H%M%S)
OUT="$BACKUP_DIR/${PHPLIST_DATABASE_NAME}-$TS.sql"

# Keeps the password off the command line (and out of `ps`).
export MYSQL_PWD="$PHPLIST_DATABASE_PASSWORD"

if command -v mariadb-dump >/dev/null 2>&1; then dump=mariadb-dump; else dump=mysqldump; fi

ssl=
[ "${DB_SKIP_SSL:-}" = "true" ] && ssl=--skip-ssl

echo "== Backup -> $OUT.gz"
# Write to .partial names so a half-written file is never mistaken for a good backup.
# $ssl is intentionally unquoted: empty means no argument at all.
# shellcheck disable=SC2086
$dump $ssl \
  -h "$PHPLIST_DATABASE_HOST" -P "${PHPLIST_DATABASE_PORT:-3306}" \
  -u "$PHPLIST_DATABASE_USER" \
  --single-transaction --quick --routines --triggers \
  "$PHPLIST_DATABASE_NAME" > "$OUT.partial"
gzip -9 < "$OUT.partial" > "$OUT.gz.partial"
rm -f "$OUT.partial"

# Verify: archive is intact and the dump finished (mysqldump writes this trailer last).
gzip -t "$OUT.gz.partial"
gzip -dc "$OUT.gz.partial" | tail -n 1 | grep -q "Dump completed"
mv "$OUT.gz.partial" "$OUT.gz"
ls -lh "$OUT.gz"

echo "== Purging backups older than $KEEP_DAYS days"
find "$BACKUP_DIR" -maxdepth 1 -name "${PHPLIST_DATABASE_NAME}-*.sql.gz" -mtime +$((KEEP_DAYS - 1)) -print -delete
# Clean up leftovers from earlier interrupted runs.
find "$BACKUP_DIR" -maxdepth 1 -name "*.partial" -mmin +120 -delete

echo "== Backup complete"