# =========================
# 1. BUILD STAGE
# =========================
FROM ubuntu:24.04 AS build

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qqy && apt-get install -qqy \
    git curl unzip composer \
    php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-bcmath php8.3-gd php8.3-mysql \
    php8.3-intl php8.3-opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

ARG REPO_URL=https://github.com/ganslm/snipe-it.git
ARG REPO_BRANCH=Eder

RUN git clone --depth 1 --branch ${REPO_BRANCH} ${REPO_URL} .

RUN composer clear-cache

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist


# =========================
# sanity check (IMPORTANT)
# =========================
RUN test -f vendor/autoload.php
RUN test -d vendor/laravel/framework/src/Illuminate/View


# =========================
# 2. RUNTIME STAGE
# =========================
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qqy && apt-get install -qqy \
    apache2 libapache2-mod-php8.3 \
    php8.3 php8.3-cli \
    php8.3-curl php8.3-mysql php8.3-gd \
    php8.3-mbstring php8.3-xml php8.3-zip \
    php8.3-bcmath php8.3-ldap php8.3-redis \
    php8.3-intl php8.3-opcache \
    mysql-client cron supervisor curl wget git \
    ca-certificates unzip \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite ssl

# Apache config
RUN echo '<VirtualHost *:80>\n\
DocumentRoot /var/www/html/public\n\
<Directory /var/www/html/public>\n\
AllowOverride All\n\
Require all granted\n\
</Directory>\n\
ErrorLog /proc/self/fd/2\n\
CustomLog /proc/self/fd/1 combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN a2ensite 000-default.conf

RUN echo "log_errors=On" > /etc/php/8.3/apache2/conf.d/docker-logging.ini \
 && echo "error_log=/proc/self/fd/2" >> /etc/php/8.3/apache2/conf.d/docker-logging.ini

# =========================
# COPY Certificate
# =========================
COPY eder-root.crt /usr/local/share/ca-certificates/eder-ca.crt
RUN update-ca-certificates

# =========================
# COPY APP
# =========================
COPY --from=build /app /var/www/html

# =========================
# FIX PERMISSIONS (IMPORTANT ORDER)
# =========================
RUN useradd -m docker || true

RUN chown -R docker:www-data /var/www/html

# Laravel writable dirs
RUN chmod -R 775 /var/www/html/storage \
 && chmod -R 775 /var/www/html/bootstrap/cache


# =========================
# SNIPE-IT STORAGE STRUCTURE
# =========================
RUN mkdir -p /var/lib/snipeit/data /var/lib/snipeit/keys /var/lib/snipeit/dumps

RUN rm -rf /var/www/html/public/uploads \
 && ln -s /var/lib/snipeit/data/uploads /var/www/html/public/uploads \
 && rm -rf /var/www/html/storage/private_uploads \
 && ln -s /var/lib/snipeit/data/private_uploads /var/www/html/storage/private_uploads \
 && rm -rf /var/www/html/storage/app/backups \
 && ln -s /var/lib/snipeit/dumps /var/www/html/storage/app/backups


# =========================
# startup script
# =========================
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
chown -R www-data:www-data /var/www/html/storage || true\n\
chown -R www-data:www-data /var/lib/snipeit || true\n\
\n\
# Laravel safety (prevents half-broken boots)\n\
php /var/www/html/artisan optimize:clear || true\n\
\n\
service cron start || true\n\
apachectl -D FOREGROUND\n' > /startup.sh \
 && chmod +x /startup.sh


# =========================
# VOLUME
# =========================
VOLUME ["/var/lib/snipeit"]

EXPOSE 80

CMD ["/startup.sh"]
