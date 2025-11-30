#!/bin/bash
set -e

# ==================================================================================
# ビルド時のテストモード
# ==================================================================================
if [ "$1" = "--test" ]; then
    echo "[entrypoint.sh] TEST MODE: entrypoint.sh is executable"
    exit 0
fi

# ==================================================================================
# 🔥🔥🔥 ENTRYPOINT.SH IS EXECUTING 🔥🔥🔥
# If you see Apache logs but NOT this message, then entrypoint.sh is being bypassed!
# ==================================================================================
echo "🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥" >&2
echo "🔥 ENTRYPOINT.SH IS RUNNING - TIMESTAMP: $(date)" >&2
echo "🔥 CALLED WITH ARGS: $@" >&2
echo "🔥 PARENT PROCESS: $(ps -p $PPID -o comm=)" >&2
echo "🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥" >&2

# すべての出力をstderrに送る（CloudWatch Logsで確実に表示）
exec 2>&1

echo "========================================"
echo "[Entrypoint] ENTRYPOINT.SH IS RUNNING!"
echo "[Entrypoint] Timestamp: $(date)"
echo "[Entrypoint] PID: $$"
echo "========================================"

# 起動マーカーファイル作成（デバッグ用）
touch /tmp/entrypoint-executed
echo "[Entrypoint] Created marker file: /tmp/entrypoint-executed"

echo "[Entrypoint] Starting initialization..."
echo "[Entrypoint] Environment check:"
echo "  - APP_KEY: ${APP_KEY:0:20}..."
echo "  - APP_ENV: ${APP_ENV}"
echo "  - LOG_CHANNEL: ${LOG_CHANNEL}"
echo "  - DB_HOST: ${DB_HOST}"
echo "  - PWD: $(pwd)"
echo "  - USER: $(whoami)"

# =============================================================================
# 1. 既存のキャッシュをクリア（ビルド時の古い設定を削除）
# =============================================================================
echo "[Entrypoint] Step 1: Clearing cached config, routes, and views..."
ls -la /var/www/html/bootstrap/cache/ || echo "[Entrypoint] Warning: bootstrap/cache not found"
rm -rf /var/www/html/bootstrap/cache/config.php
rm -rf /var/www/html/bootstrap/cache/routes-*.php
rm -rf /var/www/html/storage/framework/views/*
echo "[Entrypoint] Cache clearing completed"

# =============================================================================
# 2. 環境変数を使って新しいキャッシュを生成
# =============================================================================
echo "[Entrypoint] Step 2: Regenerating cache with runtime environment variables..."

echo "[Entrypoint] Running: php artisan config:cache"
if ! php artisan config:cache 2>&1; then
    echo "[Entrypoint] ERROR: config:cache failed!" >&2
    exit 1
fi
echo "[Entrypoint] config:cache succeeded"

echo "[Entrypoint] Running: php artisan route:cache"
if ! php artisan route:cache 2>&1; then
    echo "[Entrypoint] ERROR: route:cache failed!" >&2
    exit 1
fi
echo "[Entrypoint] route:cache succeeded"

echo "[Entrypoint] Running: php artisan view:cache"
if ! php artisan view:cache 2>&1; then
    echo "[Entrypoint] ERROR: view:cache failed!" >&2
    exit 1
fi
echo "[Entrypoint] view:cache succeeded"

# =============================================================================
# 3. storageとbootstrap/cacheの権限を修正
# =============================================================================
echo "[Entrypoint] Setting up permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# =============================================================================
# 4. Laravel Schedulerの起動（バックグラウンド）
# =============================================================================
echo "[Entrypoint] Step 4: Starting Laravel Scheduler in background..."

# スケジューラーログファイルの設定（日別ローテーション）
SCHEDULER_LOGFILE="storage/logs/scheduler-$(date '+%Y%m%d').log"

# スケジューラーをバックグラウンドで起動
(
    while true; do
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running scheduler..." >> "$SCHEDULER_LOGFILE" 2>&1
        php artisan schedule:run >> "$SCHEDULER_LOGFILE" 2>&1
        sleep 60
    done
) &

SCHEDULER_PID=$!
echo "[Entrypoint] Scheduler started with PID: $SCHEDULER_PID"
echo "[Entrypoint] Scheduler logs: $SCHEDULER_LOGFILE"

echo "[Entrypoint] Initialization complete. Starting Apache..."
echo "[Entrypoint] Executing command: $@"
echo "========================================"
echo "[Entrypoint] ENTRYPOINT.SH COMPLETED SUCCESSFULLY"
echo "========================================"

# 元のコマンドを実行
exec "$@"
