#!/bin/bash
set -e

# すべての出力をstderrに送る（CloudWatch Logsで確実に表示）
exec 2>&1

echo "[Entrypoint] Starting initialization..."
echo "[Entrypoint] Environment check:"
echo "  - APP_KEY: ${APP_KEY:0:20}..."
echo "  - APP_ENV: ${APP_ENV}"
echo "  - LOG_CHANNEL: ${LOG_CHANNEL}"
echo "  - DB_HOST: ${DB_HOST}"

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

if ! php artisan config:cache 2>&1; then
    echo "[Entrypoint] ERROR: config:cache failed!" >&2
    exit 1
fi

if ! php artisan route:cache 2>&1; then
    echo "[Entrypoint] ERROR: route:cache failed!" >&2
    exit 1
fi

if ! php artisan view:cache 2>&1; then
    echo "[Entrypoint] ERROR: view:cache failed!" >&2
    exit 1
fi

# =============================================================================
# 3. storageとbootstrap/cacheの権限を修正
# =============================================================================
echo "[Entrypoint] Setting up permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "[Entrypoint] Initialization complete. Starting Apache..."

# 元のコマンドを実行
exec "$@"
