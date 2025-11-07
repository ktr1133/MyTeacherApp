#!/bin/bash

# ========================================
# スケジュールタスク手動実行スクリプト
# ========================================

LARAVEL_PATH="/var/www/html"
PHP_PATH="/usr/bin/php"

cd $LARAVEL_PATH

echo "========================================="
echo "スケジュールタスク手動実行"
echo "========================================="
echo ""

# 引数チェック
if [ "$1" = "--dry-run" ]; then
    echo "Dry-runモードで実行します（実際のタスク作成は行いません）"
    $PHP_PATH artisan scheduled-tasks:execute --dry-run
elif [ "$1" = "--date" ] && [ -n "$2" ]; then
    echo "指定日時で実行します: $2"
    $PHP_PATH artisan scheduled-tasks:execute --date="$2"
else
    echo "現在時刻で実行します"
    $PHP_PATH artisan scheduled-tasks:execute
fi

echo ""
echo "========================================="
echo "実行完了"
echo "========================================="