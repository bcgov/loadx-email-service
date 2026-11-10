FROM alpine:3.24
 
# curl for the phpList remote endpoints, MariaDB client for SQL
RUN apk add --no-cache curl mariadb-client
 
COPY db.sh /usr/local/bin/db
COPY backup.sh /usr/local/bin/backup
COPY cleanup.sql /usr/local/bin/cleanup.sql
COPY phplist-remote.sh /usr/local/bin/phplist-remote
RUN sed -i 's/\r$//' /usr/local/bin/db /usr/local/bin/backup /usr/local/bin/cleanup.sql /usr/local/bin/phplist-remote \
 && chmod 755 /usr/local/bin/db /usr/local/bin/backup /usr/local/bin/cleanup.sql /usr/local/bin/phplist-remote
 
# OpenShift runs this as a random UID in GID 0. Nothing here writes to disk.
USER 1001
WORKDIR /tmp
 
# Open a shell by default
CMD ["sh"]
 