FROM docker.io/phplist/phplist:3.7.0@sha256:403c58329e0c932d25bfbc337d0869b8e2f9bc3d072eb1dab9244dd3990d0582

USER root

# Keep plugin versions pinned so image rebuilds are reproducible.
ARG COMMON_PLUGIN_VERSION=3.35.6
ARG RSS_FEED_PLUGIN_VERSION=3.0.3

# Port 8080 instead of 80 (non-root can't bind below 1024)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf

# Our own vhost: port 8080, site root = /email, phpList kept at /lists
COPY docker-apache-phplist.conf /etc/apache2/sites-available/docker-apache-phplist.conf

# PHP sessions somewhere writable
RUN printf 'session.save_path = /tmp\n' > /etc/php/8.2/apache2/conf.d/99-openshift.ini

# No cron/logrotate in the container (cron becomes OpenShift CronJobs)
RUN rm -f /etc/cron.d/phplist-crontab /etc/logrotate.d/phplist

# Config additions needed for RSS repeat campaigns and disabling click tracking.
# Guarded so a rebuild can't append twice; php -l fails the build on a syntax error.
RUN cp /etc/phplist/config.php /etc/phplist/config.php.orig \
 && if ! grep -q "USE_REPETITION" /etc/phplist/config.php; then \
      printf "\ndefine('USE_REPETITION', 1);\ndefine('DEFAULT_MESSAGEAGE', 15768000);\n" >> /etc/phplist/config.php; \
    fi \
 && if ! grep -q "CLICKTRACK" /etc/phplist/config.php; then \
      printf "\ndefine('CLICKTRACK', 0);\n" >> /etc/phplist/config.php; \
    else \
      sed -i "s/define(['\"]CLICKTRACK['\"],\s*[0-1]);/define('CLICKTRACK', 0);/g" /etc/phplist/config.php; \
    fi \
 && php -l /etc/phplist/config.php

# Static CLI wrapper (env vars come from the pod/job, not baked in)
RUN printf '#!/bin/bash\nexec /usr/bin/php /var/www/phpList3/public_html/lists/admin/index.php -c /etc/phplist/config.php "$@"\n' > /usr/bin/phplist \
 && chmod 755 /usr/bin/phplist

# Install the upstream Common and RSS Feed plugins from their tagged releases.
RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates curl unzip \
 && mkdir -p /tmp/common-plugin /tmp/rssfeed-plugin \
 && curl --fail --location --silent --show-error \
      "https://github.com/bramley/phplist-plugin-common/archive/refs/tags/${COMMON_PLUGIN_VERSION}.zip" \
      -o /tmp/common-plugin.zip \
 && unzip -q /tmp/common-plugin.zip -d /tmp/common-plugin \
 && cp -a "/tmp/common-plugin/phplist-plugin-common-${COMMON_PLUGIN_VERSION}/plugins/." \
      /var/www/phpList3/public_html/lists/admin/plugins/ \
 && curl --fail --location --silent --show-error \
      "https://github.com/bramley/phplist-plugin-rssfeed/archive/refs/tags/${RSS_FEED_PLUGIN_VERSION}.zip" \
      -o /tmp/rssfeed-plugin.zip \
 && unzip -q /tmp/rssfeed-plugin.zip -d /tmp/rssfeed-plugin \
 && cp -a "/tmp/rssfeed-plugin/phplist-plugin-rssfeed-${RSS_FEED_PLUGIN_VERSION}/plugins/." \
      /var/www/phpList3/public_html/lists/admin/plugins/ \
 && rm -rf /tmp/common-plugin /tmp/rssfeed-plugin \
      /tmp/common-plugin.zip /tmp/rssfeed-plugin.zip \
      /var/lib/apt/lists/*

# Keep the locally maintained service-area plugin.
COPY plugins/ServiceAreaFilterPlugin.php /var/www/phpList3/public_html/lists/admin/plugins/

# Custom email frontend and feed code (served at /email/)
COPY email/ /var/www/phpList3/public_html/email/

# Replacement entrypoint (same path the base image uses)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
 && chmod 755 /usr/local/bin/docker-entrypoint.sh

# Writable by GID 0, which is what OpenShift gives every pod
RUN mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 /var/tmp/phplistupdate \
 && chgrp -R 0 /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
      /var/tmp/phplistupdate /var/www/phpList3 /etc/phplist \
 && chmod -R g=u /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
      /var/tmp/phplistupdate /var/www/phpList3 /etc/phplist

EXPOSE 8080
USER 1001