#!/bin/bash
# 開発環境Webhookテストスクリプト
# Phase 1.1.9 - stripe listenを使用したローカルテスト

set -e

echo "=========================================="
echo "開発環境Webhookテスト"
echo "=========================================="
echo ""
echo "【注意】"
echo "このスクリプトは開発環境専用です。"
echo "本番環境では test-production-webhook.sh を使用してください。"
echo ""

# 環境確認
if [ -z "$APP_URL" ]; then
    APP_URL="http://localhost:8080"
fi

echo "【1】環境確認"
echo "--------------------"
echo "対象URL: $APP_URL"
echo ""

# Stripe CLI確認
if ! command -v stripe &> /dev/null; then
    echo "✗ Stripe CLIがインストールされていません"
    echo "インストール: https://stripe.com/docs/stripe-cli"
    exit 1
fi
echo "✓ Stripe CLI: $(stripe --version)"
echo ""

# アプリケーション起動確認
echo "【2】アプリケーション起動確認"
echo "--------------------"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL/health" --max-time 5 || echo "000")
if [ "$HTTP_CODE" == "200" ]; then
    echo "✓ アプリケーション起動中"
else
    echo "✗ アプリケーションが起動していません（HTTP $HTTP_CODE）"
    echo "composer dev を実行してアプリケーションを起動してください"
    exit 1
fi
echo ""

# Stripe Listen開始
echo "【3】Stripe Webhookリスナー起動"
echo "--------------------"
echo "stripe listenを起動します..."
echo "（Ctrl+Cで終了）"
echo ""

WEBHOOK_ENDPOINT="$APP_URL/api/webhooks/stripe/token-purchase"
echo "転送先: $WEBHOOK_ENDPOINT"
echo ""

# リスナーをバックグラウンドで起動
stripe listen --forward-to "$WEBHOOK_ENDPOINT" \
    --events checkout.session.completed,payment_intent.succeeded,payment_intent.payment_failed \
    > /tmp/stripe-listener-dev.log 2>&1 &
LISTENER_PID=$!

echo "リスナーPID: $LISTENER_PID"
echo "ログファイル: /tmp/stripe-listener-dev.log"
echo ""
echo "リスナー起動待機中..."
sleep 5

# リスナー起動確認
if ! ps -p $LISTENER_PID > /dev/null; then
    echo "✗ リスナー起動失敗"
    cat /tmp/stripe-listener-dev.log
    exit 1
fi

echo "✓ リスナー起動成功"
echo ""
grep "webhook signing secret" /tmp/stripe-listener-dev.log | tail -1
echo ""

# テストイベント送信
echo "【4】テストイベント送信"
echo "--------------------"

echo "Test 1: checkout.session.completed"
stripe trigger checkout.session.completed
echo "✓ 送信完了"
echo ""

sleep 3

echo "Test 2: payment_intent.succeeded"
stripe trigger payment_intent.succeeded
echo "✓ 送信完了"
echo ""

sleep 3

echo "Test 3: payment_intent.payment_failed"
stripe trigger payment_intent.payment_failed
echo "✓ 送信完了"
echo ""

sleep 3

# リスナー停止
echo "【5】テスト完了"
echo "--------------------"
kill $LISTENER_PID 2>/dev/null

echo "リスナーログ:"
cat /tmp/stripe-listener-dev.log | grep -E "-->|<--" | tail -20
echo ""

echo "=========================================="
echo "開発環境テスト完了"
echo "=========================================="
echo ""
echo "【確認項目】"
echo "□ 3つのイベントが送信された"
echo "□ アプリケーションがイベントを受信した"
echo "□ storage/logs/laravel.log にログが記録された"
echo ""
echo "詳細ログ確認:"
echo "  tail -f storage/logs/laravel.log | grep -i webhook"
echo ""
