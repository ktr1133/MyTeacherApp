# Webhook テストスクリプト

Phase 1.1.9 - Stripe Webhookテスト用スクリプト

## スクリプト一覧

### 1. `test-production-webhook.sh` - 本番環境テスト（推奨）

**用途**: 本番環境のWebhook動作確認

**実行方法**:
```bash
cd /home/ktr/mtdev
./scripts/test-production-webhook.sh
```

**実施内容**:
- 環境変数確認（APP_URL, STRIPE_KEY, WEBHOOK_SECRET）
- Webhookエンドポイントの到達性確認
- テスト手順のガイド表示
- CloudWatch Logsのモニタリング（オプション）

**推奨される実際のテスト方法**:
1. ブラウザで https://my-teacher-app.com/tokens/packages にアクセス
2. トークンパッケージを選択
3. Stripe Checkoutで決済（テストカード: `4242 4242 4242 4242`）
4. トークン残高が増加することを確認
5. `token_transactions`テーブルにレコードが作成されることを確認

### 2. `test-local-webhook.sh` - 開発環境テスト

**用途**: ローカル開発環境のWebhook動作確認

**実行方法**:
```bash
cd /home/ktr/mtdev
./scripts/test-local-webhook.sh
```

**実施内容**:
- アプリケーション起動確認
- `stripe listen`でWebhookリスナー起動
- テストイベント送信（3種類）
  - `checkout.session.completed`
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
- ログ確認

**前提条件**:
- アプリケーションが起動中（`composer dev`）
- Stripe CLIがインストール済み

### 3. `test-webhook-production.sh` - 旧スクリプト（非推奨）

**⚠️ 非推奨**: このスクリプトは`stripe listen`で本番環境にイベントを転送しようとしますが、Webhookシークレット不一致により常に失敗します。

**理由**:
- `stripe listen`はセッションごとに新しいシークレットを生成
- 本番環境の`.env`に設定されている固定シークレットと不一致
- 署名検証に失敗（400エラー）

**代替**: `test-production-webhook.sh`を使用してください

## テスト方法の比較

| 方法 | 環境 | 推奨度 | 理由 |
|------|------|--------|------|
| 実際のStripe Checkout | 本番 | ✅ 推奨 | 実際のフローを完全に検証できる |
| Stripe Dashboard「Send test webhook」 | 本番 | ✅ 推奨 | 正しいシークレットで検証できる |
| stripe listen | 開発 | ✅ 推奨 | ローカル開発に最適 |
| stripe listen → 本番URL | 本番 | ❌ 非推奨 | シークレット不一致で常に失敗 |

## トラブルシューティング

### 本番環境でWebhookが動作しない

**症状**: トークン購入後もトークンが付与されない

**確認項目**:
1. Stripe DashboardにWebhookエンドポイントが登録されているか
   ```
   https://dashboard.stripe.com/test/webhooks
   ```

2. 登録されているエンドポイントURL
   ```
   https://my-teacher-app.com/api/webhooks/stripe/token-purchase
   ```

3. イベントタイプが登録されているか
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`

4. Webhookシークレットが`.env`に設定されているか
   ```bash
   grep STRIPE_WEBHOOK_SECRET .env
   ```

5. CloudWatch Logsでエラーを確認
   ```bash
   aws logs tail /ecs/myteacher-production --since 10m --region ap-northeast-1 | grep -i webhook
   ```

### 開発環境でstripe listenが起動しない

**症状**: `stripe: command not found`

**解決策**: Stripe CLIをインストール
```bash
# macOS
brew install stripe/stripe-cli/stripe

# Linux
wget https://github.com/stripe/stripe-cli/releases/download/v1.21.11/stripe_1.21.11_linux_x86_64.tar.gz
tar -xvf stripe_1.21.11_linux_x86_64.tar.gz
sudo mv stripe /usr/local/bin/
```

### 署名検証エラー（400エラー）

**症状**: CloudWatch Logsに`Signature verification failed`

**原因**: Webhookシークレットの不一致

**解決策**:
1. Stripe Dashboardから正しいシークレットを取得
2. `.env`ファイルを更新
   ```
   STRIPE_WEBHOOK_SECRET=whsec_xxxxx
   ```
3. ECSタスクを再起動して環境変数を反映

## ログ確認コマンド

### 本番環境（CloudWatch Logs）
```bash
# リアルタイムモニタリング
aws logs tail /ecs/myteacher-production --follow --region ap-northeast-1 | grep -i webhook

# 直近10分のログ
aws logs tail /ecs/myteacher-production --since 10m --region ap-northeast-1 | grep -i webhook
```

### 開発環境（Laravel Logs）
```bash
# リアルタイムモニタリング
tail -f storage/logs/laravel.log | grep -i webhook

# 最新100行
tail -100 storage/logs/laravel.log | grep -i webhook
```

## 関連ドキュメント

- [Phase 1.1.9 テスト統合完了レポート](../docs/reports/2025-12-04-phase-1-1-9-test-consolidation-report.md)
- [本番環境Webhookテスト結果レポート](../docs/reports/2025-12-04-production-webhook-test-report.md)
- [Stripe Webhook Documentation](https://stripe.com/docs/webhooks)
- [Stripe CLI Documentation](https://stripe.com/docs/stripe-cli)
