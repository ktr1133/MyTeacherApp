#!/bin/bash
# 本番環境Webhookテストスクリプト
# Phase 1.1.9 - 実際のStripe Checkoutフローでテスト

set -e

echo "=========================================="
echo "本番環境Webhookテスト"
echo "=========================================="
echo ""
echo "【テスト方法】"
echo "本番環境で実際にトークンを購入してWebhook動作を確認します"
echo ""
echo "【重要】"
echo "stripe listenは開発環境専用ツールです。"
echo "本番環境では実際のStripe Checkoutフローでテストしてください。"
echo ""

# 環境変数確認
echo "【1】環境変数確認"
echo "--------------------"
if [ -f .env ]; then
    APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f 2)
    STRIPE_KEY=$(grep "^STRIPE_KEY=" .env | cut -d '=' -f 2 | cut -c 1-20)
    WEBHOOK_SECRET=$(grep "^STRIPE_WEBHOOK_SECRET=" .env | cut -d '=' -f 2 | cut -c 1-20)
    
    echo "✓ APP_URL: $APP_URL"
    echo "✓ STRIPE_KEY: ${STRIPE_KEY}..."
    echo "✓ WEBHOOK_SECRET: ${WEBHOOK_SECRET}..."
else
    echo "✗ .envファイルが見つかりません"
    exit 1
fi
echo ""

# Webhookエンドポイント確認
echo "【2】Webhookエンドポイント確認"
echo "--------------------"
WEBHOOK_URL="https://my-teacher-app.com/api/webhooks/stripe/token-purchase"
echo "エンドポイント: $WEBHOOK_URL"
echo ""
echo "到達性チェック中..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$WEBHOOK_URL" \
    -H "Content-Type: application/json" \
    -d '{"test":"endpoint"}' \
    --max-time 10)

if [ "$HTTP_CODE" == "400" ]; then
    echo "✓ エンドポイント到達可能（署名検証エラーは正常）"
elif [ "$HTTP_CODE" == "200" ]; then
    echo "✗ 警告: 署名なしで200を返却（セキュリティリスク）"
else
    echo "✗ エラー: HTTP $HTTP_CODE"
    exit 1
fi
echo ""

# テスト手順表示
echo "【3】テスト実施手順"
echo "--------------------"
echo "以下の手順で本番環境のWebhookをテストしてください："
echo ""
echo "■ 方法1: 実際の購入フロー（推奨）"
echo "  1. ブラウザで https://my-teacher-app.com/tokens/packages にアクセス"
echo "  2. トークンパッケージを選択"
echo "  3. Stripe Checkoutで決済（テストカード: 4242 4242 4242 4242）"
echo "  4. トークン残高が増加することを確認"
echo "  5. token_transactionsテーブルにレコードが作成されることを確認"
echo ""
echo "■ 方法2: Stripe Dashboardからテスト送信"
echo "  1. https://dashboard.stripe.com/test/webhooks にアクセス"
echo "  2. Webhookエンドポイントを選択"
echo "  3. 「Send test webhook」ボタンをクリック"
echo "  4. 「checkout.session.completed」イベントを選択して送信"
echo ""
echo "■ ログ確認コマンド"
echo "  aws logs tail /ecs/myteacher-production --since 5m --follow --region ap-northeast-1 | grep -i webhook"
echo ""

# CloudWatch Logsモニタリングオプション
echo "【4】ログモニタリング（オプション）"
echo "--------------------"
read -p "CloudWatch Logsをモニタリングしますか？ (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "ログモニタリング開始..."
    echo "（別ターミナルでStripe Checkoutを実行してください）"
    echo "（Ctrl+Cで終了）"
    echo ""
    aws logs tail /ecs/myteacher-production --since 1m --follow --region ap-northeast-1 \
        | grep -E "Webhook|token|stripe|POST /api/webhooks" --color=always
fi

echo ""
echo "=========================================="
echo "テスト完了"
echo "=========================================="
echo ""
echo "【検証項目チェックリスト】"
echo "□ Stripe Checkoutページが表示された"
echo "□ 決済が正常に完了した"
echo "□ トークン残高が増加した"
echo "□ token_transactionsテーブルにレコードが作成された"
echo "□ CloudWatch Logsに成功ログが記録された"
echo ""
echo "全て✓の場合、Webhook実装は正常です。"
echo ""
