#!/bin/bash
set -e

echo "[Entrypoint] Starting initialization..."

# =============================================================================
# 1. 既存のキャッシュをクリア（ビルド時の古い設定を削除）
# =============================================================================
echo "[Entrypoint] Clearing cached config, routes, and views..."
rm -rf /var/www/html/bootstrap/cache/config.php
rm -rf /var/www/html/bootstrap/cache/routes-*.php
rm -rf /var/www/html/storage/framework/views/*

# =============================================================================
# 2. 環境変数を使って新しいキャッシュを生成
# =============================================================================
echo "[Entrypoint] Regenerating cache with runtime environment variables..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# =============================================================================
# 3. storageとbootstrap/cacheの権限を修正
# =============================================================================
echo "[Entrypoint] Setting up permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "[Entrypoint] Initialization complete. Starting Apache..."

# 元のコマンドを実行
exec "$@"
