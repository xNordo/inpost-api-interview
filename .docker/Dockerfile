FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app


# Keep container running
ENTRYPOINT ["tail", "-f", "/dev/null"]
