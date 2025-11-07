#!/bin/bash

# ========================================
# Laravel Scheduler用Cron設定スクリプト
# ========================================

echo "========================================="
echo "Laravel Scheduler Cron Setup"
echo "========================================="

# 変数設定
LARAVEL_PATH="/var/www/html"
PHP_PATH="/usr/bin/php"
LOG_PATH="/var/log/laravel-scheduler.log"
CRON_USER="www-data"

# Laravel パスの確認
if [ ! -d "$LARAVEL_PATH" ]; then
    echo "Error: Laravel directory not found at $LARAVEL_PATH"
    exit 1
fi

# artisan ファイルの確認
if [ ! -f "$LARAVEL_PATH/artisan" ]; then
    echo "Error: artisan file not found"
    exit 1
fi

# 既存のcron設定を確認
echo ""
echo "現在のcron設定を確認中..."
sudo crontab -u $CRON_USER -l 2>/dev/null | grep -q "schedule:run"

if [ $? -eq 0 ]; then
    echo "Warning: Laravel Scheduler用のcron設定が既に存在します"
    read -p "上書きしますか? (y/n): " answer
    if [ "$answer" != "y" ]; then
        echo "中止しました"
        exit 0
    fi
    
    # 既存設定を削除
    sudo crontab -u $CRON_USER -l 2>/dev/null | grep -v "schedule:run" | sudo crontab -u $CRON_USER -
    echo "既存設定を削除しました"
fi

# 新しいcron設定を追加
echo ""
echo "新しいcron設定を追加中..."

(sudo crontab -u $CRON_USER -l 2>/dev/null; echo "* * * * * cd $LARAVEL_PATH && $PHP_PATH artisan schedule:run >> $LOG_PATH 2>&1") | sudo crontab -u $CRON_USER -

if [ $? -eq 0 ]; then
    echo "✓ Cron設定が完了しました"
else
    echo "✗ Cron設定に失敗しました"
    exit 1
fi

# ログファイルの作成と権限設定
echo ""
echo "ログファイルを設定中..."

sudo touch $LOG_PATH
sudo chown $CRON_USER:$CRON_USER $LOG_PATH
sudo chmod 644 $LOG_PATH

echo "✓ ログファイルの設定が完了しました"

# ログローテーション設定
echo ""
echo "ログローテーション設定を追加中..."

sudo tee /etc/logrotate.d/laravel-scheduler > /dev/null <<EOF
$LOG_PATH {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0644 $CRON_USER $CRON_USER
}
EOF

echo "✓ ログローテーション設定が完了しました"

# 設定内容の表示
echo ""
echo "========================================="
echo "設定完了"
echo "========================================="
echo "Cron User: $CRON_USER"
echo "Laravel Path: $LARAVEL_PATH"
echo "PHP Path: $PHP_PATH"
echo "Log Path: $LOG_PATH"
echo ""
echo "現在のcron設定:"
sudo crontab -u $CRON_USER -l
echo "========================================="
echo ""
echo "スケジューラーが正常に動作しているか確認するには:"
echo "  tail -f $LOG_PATH"
echo ""
echo "手動でスケジュールタスクを実行するには:"
echo "  php $LARAVEL_PATH/artisan scheduled-tasks:execute"
echo ""
echo "Dry-runモードで実行予定を確認するには:"
echo "  php $LARAVEL_PATH/artisan scheduled-tasks:execute --dry-run"
echo "========================================="