FROM php:8.2-cli

ENV DEBIAN_FRONTEND=noninteractive

RUN echo "force-unsafe-io" > /etc/dpkg/dpkg.cfg.d/02apt-speedup && \
    echo "Acquire::http {No-Cache=True;};" > /etc/apt/apt.conf.d/no-cache
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
            libzip-dev \
            vim \
            git \
            unzip \
            curl && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    docker-php-ext-install zip

# Install composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_USER_ID=33 \
    PHP_ENABLE_XDEBUG=1 \
    COMPOSER_HOME=/root/.composer/ \
    PATH=/app:/app/vendor/bin:/root/.composer/vendor/bin:$PATH

RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
&& curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
# Make sure we're installing what we think we're installing!
&& php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
&& php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer \
&& rm -f /tmp/composer-setup.*

COPY ./tests/docker/rootfs /

WORKDIR /app
