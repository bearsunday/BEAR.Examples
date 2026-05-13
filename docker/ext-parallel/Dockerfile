# ext-parallel runtime for `bin/async.php`.
#
# Provides ZTS PHP + ext-parallel so MyVendor.Cms can run Article's three
# #[Embed]s (author / category / tagList) in parallel via BEAR.Async's
# ParallelRuntimeModule. Mirrors vendor/bear/async/demo/Dockerfile but is
# kept as a standalone file so the parallel adapter is discoverable
# under docker/ alongside ext-swoole.

FROM php:8.5-zts-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libssl-dev \
        libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pcntl pdo pdo_mysql pdo_sqlite zip

RUN pecl install parallel \
    && docker-php-ext-enable parallel

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
