FROM php:8.4-fpm

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Etc/UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        build-essential \
        ca-certificates \
        cron \
        git \
        mariadb-client \
        software-properties-common \
        unzip \
        wget

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

