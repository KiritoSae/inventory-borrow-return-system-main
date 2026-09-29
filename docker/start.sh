#!/bin/sh
set -e

# Start php-fpm in the background
php-fpm -D

# Automatically create the database schema (tables + initial data)
# before the app starts serving requests. Safe to run on every
# deployment since init-db.php skips statements that already exist.
php /app/init-db.php

# Start nginx in the foreground
nginx -g 'daemon off;'
