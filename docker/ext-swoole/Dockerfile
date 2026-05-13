# ext-swoole runtime for the AsyncSwooleModule path.
#
# Provides ZTS PHP + ext-swoole so MyVendor.Cms can run under a long-lived
# Swoole HTTP server with coroutine-parallel #[Embed] execution. Mirrors
# vendor/bear/async/demo/Dockerfile but kept as a standalone file so the
# swoole adapter is discoverable under docker/ alongside ext-parallel.
#
# Note: enabling Swoole inside MyVendor.Cms also requires installing
# AsyncSwooleModule + PdoPoolModule in AppModule. This image only
# provisions the runtime; the application wiring is intentionally
# left as a follow-up step (see docs/journal/handoff.md).

FROM php:8.4-zts

ARG SWOOLE_VERSION=6.1.8

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libssl-dev \
        libsqlite3-dev \
        libbrotli-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pcntl pdo pdo_mysql pdo_sqlite zip

RUN yes '' | pecl install swoole-${SWOOLE_VERSION} \
    && docker-php-ext-enable swoole

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
