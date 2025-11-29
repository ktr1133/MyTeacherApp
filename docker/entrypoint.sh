#!/bin/bash
set -e

# storageとbootstrap/cacheの権限を修正
echo "Setting up permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 元のコマンドを実行
exec "$@"
