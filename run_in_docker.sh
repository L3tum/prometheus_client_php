#!/bin/sh

tty=
tty -s && tty=--tty

image="composer:latest"

if [ -z "$COMPOSER_CACHE_DIR" ]; then
  CACHE_DIR=~/tmp/php-compose-cache
else
  CACHE_DIR="$COMPOSER_CACHE_DIR"
fi
mkdir -p $CACHE_DIR

docker run $tty --interactive --rm \
       --user $(id -u) \
       --volume /var/run/docker.sock:/var/run/docker.sock \
       --volume $(pwd):$(pwd) \
       --volume $CACHE_DIR:/.composer/cache/ \
       --workdir $(pwd) \
       --entrypoint make \
       $(image) $*
