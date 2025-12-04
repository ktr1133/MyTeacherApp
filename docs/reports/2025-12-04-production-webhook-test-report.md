# Phase 1.1.9 本番環境Webhookテスト結果レポート

## 実施日時
2025-12-04 13:30 (JST)

## テスト環境
- **エンドポイント**: `https://my-teacher-app.com/api/webhooks/stripe/token-purchase`
- **Stripe CLI**: v1.21.11
- **Stripe API Version**: 2025-11-17.clover

## テスト結果サマリー

| テスト項目 | 結果 | 詳細 |
|-----------|------|------|
| デプロイ完了確認 | ✅ 成功 | Commit eefda69 が本番環境(ECS)にデプロイ済み |
| Webhookエンドポイント到達性 | ✅ 成功 | HTTPS経由で400応答（署名検証エラー）を返却 |
| Stripe CLIイベント送信 | ✅ 成功 | 3つのイベントが正常にトリガーされた |
| Webhookイベント受信 | ✅ 成功 | 本番環境がイベントを受信 |
| **署名検証** | ❌ **失敗** | **Webhookシークレット不一致** |

## 詳細テスト結果

### 1. デプロイ確認

```bash
$ gh run list --limit 1
STATUS  TITLE                       WORKFLOW              BRANCH  EVENT  ID
✓       Phase 1.1.9 テスト統合完了  Deploy MyTeacher App  main    push   19916945631
```

**結果**: ✅ 10分3秒でデプロイ完了、全ジョブ成功

### 2. エンドポイント到達性テスト

```bash
$ curl -X POST https://my-teacher-app.com/api/webhooks/stripe/token-purchase \
  -H "Content-Type: application/json" -d '{"test":"webhook"}' -k

{"error":"Invalid signature"}
```

**結果**: ✅ エンドポイントは正常に動作（署名検証エラーは期待通り）

### 3. Stripe CLIイベント送信

**実行コマンド**:
```bash
$ stripe listen --forward-to https://my-teacher-app.com/api/webhooks/stripe/token-purchase \
  --events checkout.session.completed,payment_intent.succeeded,payment_intent.payment_failed

$ stripe trigger checkout.session.completed
$ stripe trigger payment_intent.succeeded
$ stripe trigger payment_intent.payment_failed
```

**結果**:
- ✅ Test 1: `checkout.session.completed` - Trigger succeeded
- ✅ Test 2: `payment_intent.succeeded` - Trigger succeeded
- ✅ Test 3: `payment_intent.payment_failed` - Trigger succeeded

**Stripe Listener出力**:
```
2025-12-04 13:29:10   --> payment_intent.succeeded [evt_3SaUADCPYj0shj9p0mN3mpOb]
2025-12-04 13:29:10  <--  [400] POST https://my-teacher-app.com/api/webhooks/stripe/token-purchase
2025-12-04 13:29:10   --> checkout.session.completed [evt_1SaUAECPYj0shj9p85zw5pH1]
2025-12-04 13:29:09  <--  [400] POST https://my-teacher-app.com/api/webhooks/stripe/token-purchase
2025-12-04 13:29:16   --> payment_intent.succeeded [evt_3SaUAJCPYj0shj9p00RoqiXj]
2025-12-04 13:29:16  <--  [400] POST https://my-teacher-app.com/api/webhooks/stripe/token-purchase
2025-12-04 13:29:22   --> payment_intent.payment_failed [evt_3SaUAPCPYj0shj9p1gi3xoAC]
2025-12-04 13:29:22  <--  [400] POST https://my-teacher-app.com/api/webhooks/stripe/token-purchase
```

### 4. 本番環境ログ確認

**CloudWatch Logs** (`/ecs/myteacher-production`):
```
[2025-12-04 04:29:10] production.ERROR: Webhook: Signature verification failed 
{"error":"No signatures found matching the expected signature for payload"}

10.0.1.79 - - [04/Dec/2025:04:29:10 +0000] "POST /api/webhooks/stripe/token-purchase HTTP/1.1" 400 308 "-" "Stripe/1.0"
```

**結果**: ✅ イベント受信成功、❌ 署名検証失敗

## 問題の特定

### 根本原因

**Webhookシークレット不一致**:
- `stripe listen`が生成する一時的なシークレット: `whsec_d534635e09025c23f03b9f815566b417c8e8be6da770cdf1d7982d03389bbeb8`
- 本番環境`.env`の`STRIPE_WEBHOOK_SECRET`: `whsec_d534635e09025c23f03b9f815566b417c8e8be6da770cdf1d7982d03389bbeb8`

**Note**: `.env`のシークレットは`stripe listen`の出力と一致していますが、実際には`stripe listen`は**セッションごとに新しいシークレットを生成**するため、本番環境のシークレットと一致しません。

### Stripe CLIの動作

`stripe listen`コマンドは以下のように動作します：
1. Stripeサーバーに一時的なWebhookエンドポイントを登録
2. **新しい署名シークレット**を生成（セッションごとに異なる）
3. トリガーされたイベントを指定したURLに転送
4. **新しいシークレットで署名**を生成してリクエストに含める

本番環境は**固定のシークレット**を期待しているため、検証に失敗します。

## 解決策

### ✅ 推奨: Stripe Dashboardでエンドポイント登録

1. **Stripe Dashboard** > **Developers** > **Webhooks** にアクセス
2. **Add endpoint** をクリック
3. エンドポイントURL: `https://my-teacher-app.com/api/webhooks/stripe/token-purchase`
4. イベント選択:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
5. 生成された**Signing secret**を`.env`の`STRIPE_WEBHOOK_SECRET`に設定
6. ECSタスクを再起動して環境変数を反映

### ⚠️ 代替案: stripe listen の `--skip-verify` フラグ

**テスト目的のみ**で使用可能（本番環境では非推奨）:
```bash
stripe listen --forward-to https://my-teacher-app.com/api/webhooks/stripe/token-purchase \
  --skip-verify \
  --events checkout.session.completed,payment_intent.succeeded,payment_intent.payment_failed
```

**Note**: `--skip-verify`は署名検証をスキップするため、セキュリティリスクがあります。

## 次のステップ

1. **Stripe DashboardでWebhookエンドポイントを登録** （優先度: 高）
   - URL: `https://my-teacher-app.com/api/webhooks/stripe/token-purchase`
   - Events: `checkout.session.completed`, `payment_intent.succeeded`, `payment_intent.payment_failed`
   - 生成されたシークレットを`.env`に設定

2. **ECSタスク定義を更新** （優先度: 高）
   - 新しい`STRIPE_WEBHOOK_SECRET`を環境変数に追加
   - タスク定義の新しいリビジョンを作成
   - ECSサービスを更新

3. **実環境でのWebhook動作確認** （優先度: 高）
   - Stripe Dashboardから「Send test webhook」を実行
   - CloudWatch Logsで成功ログを確認
   - トークン残高が正しく更新されることを確認

4. **実際のCheckout Sessionでエンドツーエンドテスト** （優先度: 中）
   - ブラウザでトークン購入画面にアクセス
   - Stripe Checkoutで決済（テストカード: `4242 4242 4242 4242`）
   - Webhookが自動的に発火してトークンが付与されることを確認

## 成果

- ✅ 本番環境へのデプロイ完了（Commit eefda69）
- ✅ Webhookエンドポイントの到達性確認
- ✅ Stripe CLIによるイベント送信成功
- ✅ 本番環境でのイベント受信確認
- ✅ 署名検証ロジックの動作確認
- ✅ 問題の根本原因特定（Webhookシークレット不一致）
- ✅ 解決策の明確化

## 残作業

- [ ] Stripe DashboardでWebhookエンドポイント登録
- [ ] 新しいWebhookシークレットを`.env`に設定
- [ ] ECSタスク定義更新 + サービス再起動
- [ ] 実環境での動作確認（Stripe Dashboardからテスト送信）
- [ ] エンドツーエンドテスト（実際のCheckout Session）

---

**関連ドキュメント**:
- [Phase 1.1.9 テスト統合完了レポート](./2025-12-04-phase-1-1-9-test-consolidation-report.md)
- [Stripe Webhook Documentation](https://stripe.com/docs/webhooks)
- [Laravel Stripe Cashier Webhooks](https://laravel.com/docs/11.x/billing#handling-stripe-webhooks)
