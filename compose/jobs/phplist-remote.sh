#!/bin/sh
# Call phpList's remote-processing pages. Needs only curl.
#
#   phplist-remote get             fetch RSS items          (RssFeedPlugin)
#   phplist-remote queue           process the message queue
#   phplist-remote delete          delete old RSS items     (RssFeedPlugin)
#   phplist-remote get queue       several commands, run in order; stops at the first failure
#
# Environment:
#   BASE_URL        required. Admin URL as seen from this container,
#                   e.g. http://phplist:8080/lists/admin
#   REMOTE_SECRET   required. "Secret for remote processing" from phpList Config > Settings.
#   RSS_KEEP_DAYS   days of RSS items to keep for 'delete'. Default 30.
#   TIMEOUT         max seconds per request. Default 300.
set -eu

usage() {
  sed -n '2,14p' "$0" | sed 's/^# \{0,1\}//'
}

case "${1:-help}" in
  help|-h|--help) usage; exit 0 ;;
esac

: "${BASE_URL:?BASE_URL is not set}"
: "${REMOTE_SECRET:?REMOTE_SECRET is not set}"
BASE_URL=${BASE_URL%/}
TIMEOUT=${TIMEOUT:-300}
RSS_KEEP_DAYS=${RSS_KEEP_DAYS:-30}

# The secret goes through --data-urlencode so it is encoded properly and never
# appears on a command line we print.
fetch() {
  curl --fail-with-body --silent --show-error --get \
    --max-time "$TIMEOUT" --retry 2 --retry-delay 5 \
    --data-urlencode "secret=$REMOTE_SECRET" \
    "$@" "$BASE_URL/"
}

run() {
  name=$1; shift
  echo "== $name"
  body=$("$@") || { printf '%s\n' "$body"; echo "FAILED: $name" >&2; exit 1; }
  printf '%s\n' "$body"
}

for cmd in "$@"; do
  case $cmd in
    get)
      run get fetch --data-urlencode page=get --data-urlencode pi=RssFeedPlugin
      ;;
    queue)
      run queue fetch --data-urlencode page=processqueue
      # A real run answers with JSON. A wrong secret can come back as HTTP 200 with
      # an HTML login page, which curl would otherwise call a success.
      case $body in
        '{'*) ;;
        *) echo "FAILED: queue response was not JSON (wrong REMOTE_SECRET?)" >&2; exit 1 ;;
      esac
      ;;
    delete)
      run delete fetch --data-urlencode page=delete --data-urlencode pi=RssFeedPlugin \
        --data-urlencode "days=$RSS_KEEP_DAYS" --data-urlencode unusedfeeds=
      ;;
    *)
      echo "unknown command: $cmd" >&2
      usage >&2
      exit 2
      ;;
  esac
done