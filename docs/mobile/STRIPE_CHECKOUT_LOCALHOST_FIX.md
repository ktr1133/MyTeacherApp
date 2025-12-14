# Stripe Checkout Localhost リダイレクト問題の修正

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-14 | GitHub Copilot | 初版作成: localhost リダイレクト問題の解決策 |

## 問題の概要

モバイルアプリでStripe Checkout決済完了後、localhostへのリダイレクトが失敗し、エラー「サーバに接続できませんでした」(-1004) が発生していた。

## 根本原因

### 1. バックエンド設定の問題

```bash
# /home/ktr/mtdev/.env
APP_URL=http://localhost:8091  # ❌ 問題: localhost URL
```

### 2. Stripe Checkout の success_url

```php
// app/Repositories/Subscription/SubscriptionEloquentRepository.php
$successUrl = $isMobile
    ? config('app.url') . '/api/subscriptions/success?session_id={CHECKOUT_SESSION_ID}'
    : route('subscriptions.success') . '?session_id={CHECKOUT_SESSION_ID}';
```

- `config('app.url')` が `http://localhost:8091` を返す
- Stripe Checkoutが決済完了後にこのURLへリダイレクト
- **モバイルデバイスは開発PCのlocalhostに接続できない**

### 3. エラーログ

```
LOG  [SubscriptionWebView] 🔗 Should start load: http://localhost:8091/api/subscriptions/success
ERROR [SubscriptionWebView] ❌ WebView error detected: {
  "code": -1004,
  "description": "サーバに接続できませんでした。",
  "domain": "NSURLErrorDomain",
  "url": "https://checkout.stripe.com/..."
}
```

## 解決策

### 1. バックエンド: APP_URL を ngrok に変更

```bash
# /home/ktr/mtdev/.env
APP_URL=https://fizzy-formless-sandi.ngrok-free.dev  # ✅ 修正
```

**重要**: `.env` ファイルは `.gitignore` に含まれているため、手動で変更が必要。

### 2. モバイル: localhost リダイレクト検出機能追加

```typescript
// mobile/src/screens/subscriptions/SubscriptionWebViewScreen.tsx
const isLocalhost = request.url.includes('localhost') || request.url.includes('127.0.0.1');

if (isNgrok || isLocalhost) {
  // 開発環境: WebView接続をブロック、ネイティブ処理
  Alert.alert('購入完了', '...');
  return false;
}
```

## 実装詳細

### モバイル側の変更

**ファイル**: `mobile/src/screens/subscriptions/SubscriptionWebViewScreen.tsx`

```typescript
onShouldStartLoadWithRequest={(request) => {
  const isLocalhost = request.url.includes('localhost') || request.url.includes('127.0.0.1');
  const isNgrok = backendHost.includes('ngrok');
  
  if (request.url.includes(backendHost) || isLocalhost) {
    if (request.url.includes('/api/subscriptions/success')) {
      if (isNgrok || isLocalhost) {
        // 開発環境: ネイティブ処理
        Alert.alert('購入完了', '...');
        navigation.navigate('SubscriptionManage');
        return false; // WebView読み込みブロック
      }
    }
  }
  
  return true; // 本番環境: 通常通り
}}
```

### 環境別の動作

| 環境 | APP_URL | 動作 |
|------|---------|------|
| **開発（localhost）** | `http://localhost:8091` | ❌ モバイル接続不可 → ネイティブ処理 |
| **開発（ngrok）** | `https://xxx.ngrok-free.dev` | ✅ リダイレクト検出 → ネイティブ処理 |
| **本番** | `https://example.com` | ✅ WebViewで読み込み → 通常処理 |

## テスト手順

### 1. バックエンド設定確認

```bash
cd /home/ktr/mtdev
grep "^APP_URL=" .env
# 期待値: APP_URL=https://fizzy-formless-sandi.ngrok-free.dev
```

### 2. モバイルアプリのテスト

1. 新しいビルドをインストール
2. サブスクリプション購入画面へ
3. Stripe Checkoutで決済完了
4. **期待される動作**:
   - ✅ `🔄 Backend redirect detected` ログ
   - ✅ `🚧 Dev environment (ngrok/localhost) - handling natively` ログ
   - ✅ 「購入完了」Alert表示
   - ✅ サブスクリプション管理画面へ自動遷移

### 3. ログ確認

**成功時のログ**:
```
[SubscriptionWebView] 🔗 Should start load: https://fizzy-formless-sandi.ngrok-free.dev/api/subscriptions/success
[SubscriptionWebView] 🌐 Backend host: fizzy-formless-sandi.ngrok-free.dev isNgrok: true isLocalhost: false
[SubscriptionWebView] 🔄 Backend redirect detected
[SubscriptionWebView] ✅ Success redirect detected
[SubscriptionWebView] 🚧 Dev environment (ngrok/localhost) - handling natively
```

**localhost検出時のログ**:
```
[SubscriptionWebView] 🔗 Should start load: http://localhost:8091/api/subscriptions/success
[SubscriptionWebView] 🌐 Backend host: fizzy-formless-sandi.ngrok-free.dev isNgrok: true isLocalhost: true
[SubscriptionWebView] 🔄 Backend redirect detected
[SubscriptionWebView] ✅ Success redirect detected
[SubscriptionWebView] 🚧 Dev environment (ngrok/localhost) - handling natively
```

## 注意事項

### 開発環境

- **ngrok URLは一時的**: ngrokを再起動すると新しいURLが発行される
- **APP_URL更新が必要**: ngrok再起動時は `.env` の `APP_URL` を更新
- **モバイルAPIも更新**: `mobile/src/utils/constants.ts` の `BASE_URL` も同期

### 本番環境

- `APP_URL` は本番ドメインを設定
- WebViewは通常通り本番URLを読み込む
- `onNavigationStateChange` で成功/キャンセル検出
- localhost/ngrok検出ロジックは影響しない

## 関連ファイル

| ファイル | 役割 |
|---------|------|
| `/home/ktr/mtdev/.env` | バックエンドAPP_URL設定 |
| `mobile/src/screens/subscriptions/SubscriptionWebViewScreen.tsx` | WebViewリダイレクト処理 |
| `mobile/src/utils/constants.ts` | モバイルAPI Base URL |
| `app/Repositories/Subscription/SubscriptionEloquentRepository.php` | Stripe Checkout success_url設定 |

## 今後の改善案

### 1. 環境変数の自動同期

開発環境でngrokを起動時、自動的に以下を更新：
- `.env` の `APP_URL`
- `mobile/src/utils/constants.ts` の `BASE_URL`

### 2. 本番環境での検証

本番デプロイ時に以下を確認：
- `APP_URL` が本番ドメインになっているか
- Stripe Checkout の success_url が正しいか
- WebViewの読み込みが正常に動作するか

### 3. エラーハンドリング強化

localhost検出時にユーザーへの通知を改善：
```typescript
if (isLocalhost) {
  console.warn('[SubscriptionWebView] ⚠️ Backend is using localhost - this is a development configuration issue');
}
```

## 参考

- **Issue**: #stripe-checkout-localhost-redirect
- **Commit**: 2489866 (mobile), backend .env manual update
- **関連ドキュメント**: `docs/mobile/STRIPE_CHECKOUT_FIX.md`
