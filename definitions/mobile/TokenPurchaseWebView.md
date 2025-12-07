# トークン購入機能（モバイル版） 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-07 | GitHub Copilot | 初版作成: モバイルアプリトークン購入機能（WebView方式） |

---

## 1. 概要

MyTeacher モバイルアプリにおけるトークン購入機能は、**WebView方式**でLaravel側の既存Stripe Checkout画面を表示し、Web版と同じ決済フローを実現します。これにより、実装コストを最小化し、Web/モバイルで統一されたUXを提供します。

### 1.1 採用技術

**WebView方式（方式B）** を採用

**メリット**:
1. ✅ **実装コストが低い** - Laravel側の既存画面を再利用、モバイル側は50行程度
2. ✅ **Web/モバイルで決済フロー統一** - 同じStripe Checkout Session、同じWebhook処理
3. ✅ **セキュリティが高い** - 決済処理は全てサーバーサイド、Stripe APIキー漏洩リスクなし
4. ✅ **保守性が高い** - 決済ロジックの変更はLaravel側のみ、アプリ更新不要
5. ✅ **子ども承認フローとの親和性** - Web版と同じ承認待ち画面を表示可能

**デメリット**:
1. ❌ ネイティブUIではない - WebViewはネイティブアプリのUIと異なる
2. ❌ 読み込み時間 - Laravel画面のレンダリング待ち（1-2秒）
3. ❌ WebView特有の問題 - Cookie管理、戻るボタンの挙動制御

### 1.2 対応プラットフォーム

| プラットフォーム | 実装状況 | 認証方式 | 決済方式 |
|----------------|---------|---------|----------|
| **Web** | ✅ 実装済み | セッション + CSRF | Stripe Checkout（サーバー側） |
| **モバイル** | 🎯 Phase 2.B-6実装予定 | Sanctum（トークン） | WebView方式（Laravel画面表示） |

---

## 2. トークン残高表示機能

### 2.1 機能要件

**概要**: ユーザーの現在のトークン残高、月次無料枠、購入履歴を表示する画面。

**アクセスルート**:
- **モバイル**: `TokenBalanceScreen`

**API**:
- `GET /api/tokens/balance` - トークン残高取得
- `GET /api/tokens/history?page=1` - 購入履歴取得（ページネーション）

**出力項目（残高）**:

| 項目 | 型 | 説明 |
|------|-----|------|
| `balance` | integer | 現在のトークン残高 |
| `free_monthly` | integer | 月次無料枠 |
| `used_this_month` | integer | 今月の使用量 |
| `low_threshold` | integer | 残高低下の閾値 |
| `is_low` | boolean | 残高が閾値未満か |

**画面構成（モバイル版）**:
- **TokenBalanceScreen.tsx**:
  - トークン残高カード（大きく表示）
  - 残高低下警告バナー（is_low = true時）
  - 月次無料枠プログレスバー
  - 購入ボタン（FAB: Floating Action Button）
  - 購入履歴リスト（下スクロール、無限ロード）

**レスポンス例**:
```json
{
  "success": true,
  "data": {
    "balance": 450000,
    "free_monthly": 1000000,
    "used_this_month": 550000,
    "low_threshold": 200000,
    "is_low": false,
    "next_reset_date": "2025-01-01T00:00:00+09:00"
  }
}
```

---

## 3. トークン購入機能（WebView方式）

### 3.1 機能要件

**概要**: Laravel側の既存Stripe Checkout画面をWebViewで表示し、トークンパッケージを購入する機能。

**処理フロー**:
```
1. ユーザーが「購入」ボタンタップ
2. モバイルアプリでWebView表示
   - URL: https://myteacher.app/tokens/purchase
   - 認証: SanctumトークンをCookieに設定
3. Laravel側でStripe Checkout Session作成
4. Stripe決済画面表示（WebView内）
5. ユーザーが決済情報入力・決済実行
6. Stripe Webhook → Laravel処理（トークン付与）
7. 決済成功ページ表示（/tokens/purchase-success）
8. WebViewのURL変化を監視（onNavigationStateChange）
9. success_url検出 → WebView閉じる → トークン残高更新
```

**実装コード例**:
```typescript
// mobile/src/screens/tokens/TokenPurchaseWebViewScreen.tsx
import React from 'react';
import { WebView } from 'react-native-webview';
import { useAuth } from '../../hooks/useAuth';
import { useTokens } from '../../hooks/useTokens';

export const TokenPurchaseWebViewScreen = ({ navigation }) => {
  const { token } = useAuth();
  const { refreshBalance } = useTokens();

  const handleNavigationStateChange = (navState) => {
    // 決済成功ページ検出
    if (navState.url.includes('/tokens/purchase-success')) {
      // トークン残高更新
      refreshBalance();
      // WebView閉じる
      navigation.goBack();
    }
    
    // キャンセルページ検出
    if (navState.url.includes('/tokens/purchase-cancel')) {
      navigation.goBack();
    }
  };

  return (
    <WebView
      source={{
        uri: `${API_BASE_URL}/tokens/purchase`,
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      }}
      onNavigationStateChange={handleNavigationStateChange}
      style={{ flex: 1 }}
      startInLoadingState={true}
      javaScriptEnabled={true}
      domStorageEnabled={true}
    />
  );
};
```

**Laravel側の対応**:
- Sanctum認証をセッション認証に変換（WebView用ミドルウェア）
- CSRF保護は既存のまま（Bladeテンプレート内で `@csrf` トークン自動埋め込み）

### 3.2 パッケージ一覧表示

**API**:
- `GET /api/tokens/packages` - トークンパッケージ一覧取得

**レスポンス例**:
```json
{
  "success": true,
  "data": {
    "packages": [
      {
        "id": 1,
        "name": "スターターパック",
        "token_amount": 500000,
        "price": 500,
        "stripe_price_id": "price_xxx",
        "description": "初めての方におすすめ"
      },
      {
        "id": 2,
        "name": "スタンダードパック",
        "token_amount": 1000000,
        "price": 900,
        "stripe_price_id": "price_yyy",
        "description": "お得な基本パック",
        "discount_rate": 10
      }
    ]
  }
}
```

**画面構成**:
- パッケージカード（価格、トークン数、割引率表示）
- 「購入する」ボタン → WebView画面遷移

---

## 4. 子ども承認フロー機能

### 4.1 機能要件（新規API実装必要）

**概要**: 子どもユーザーがトークン購入をリクエストし、親ユーザーが承認する機能。

**処理フロー**:
```
1. 子どもユーザーがパッケージ選択
2. 「購入リクエスト送信」ボタンタップ
3. API呼び出し: POST /api/tokens/purchase-requests
   - body: { package_id: 1 }
4. Laravel側で承認待ちレコード作成（token_purchase_requests テーブル）
5. 親ユーザーに通知送信（notification_type: 'approval_required'）
6. 子ども画面: 「承認待ち」タブに移動、ポーリング開始
7. 親ユーザーがWeb/モバイルで承認
   - API: PUT /api/tokens/purchase-requests/{id}/approve
8. 承認後、Stripe Checkout Session自動作成
9. 親ユーザーが決済実行（WebView）
10. Webhook処理でトークン付与
11. 子ども画面: 承認完了通知、トークン残高更新
```

**新規APIエンドポイント**:

| エンドポイント | メソッド | 説明 |
|--------------|---------|------|
| `POST /api/tokens/purchase-requests` | POST | 購入リクエスト作成 |
| `GET /api/tokens/purchase-requests` | GET | 承認待ちリクエスト一覧 |
| `PUT /api/tokens/purchase-requests/{id}/approve` | PUT | リクエスト承認 |
| `PUT /api/tokens/purchase-requests/{id}/reject` | PUT | リクエスト却下 |

**データモデル（新規テーブル）**:
```sql
CREATE TABLE token_purchase_requests (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    requester_user_id BIGINT NOT NULL COMMENT '子どもユーザーID',
    approver_user_id BIGINT NOT NULL COMMENT '親ユーザーID',
    package_id BIGINT NOT NULL COMMENT 'トークンパッケージID',
    status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    stripe_session_id VARCHAR(255) NULL COMMENT 'Stripe Checkout Session ID',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES token_packages(id) ON DELETE CASCADE,
    INDEX idx_token_purchase_requests_requester (requester_user_id),
    INDEX idx_token_purchase_requests_approver (approver_user_id),
    INDEX idx_token_purchase_requests_status (status)
);
```

**画面構成（モバイル版）**:
- **TokenPurchaseScreen.tsx** - 子ども用タブ追加:
  - 「パッケージ一覧」タブ（既存）
  - 「承認待ち」タブ（新規）
    - 承認待ちリクエスト一覧
    - ステータスバッジ（pending, approved, rejected）
    - 30秒ポーリング（自動更新）

---

## 5. 購入履歴表示機能

### 5.1 機能要件

**概要**: ユーザーのトークン購入履歴を一覧表示する機能。

**API**:
- `GET /api/tokens/history?page=1&per_page=20`

**出力項目**:

| 項目 | 型 | 説明 |
|------|-----|------|
| `id` | integer | トランザクションID |
| `type` | string | 取引種別（purchase, consume, free_reset等） |
| `amount` | integer | トークン数（正負） |
| `balance_after` | integer | 取引後残高 |
| `description` | string | 取引内容 |
| `created_at` | datetime | 取引日時 |

**レスポンス例**:
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 100,
        "type": "purchase",
        "amount": 500000,
        "balance_after": 950000,
        "description": "スターターパック購入",
        "created_at": "2025-12-07T10:00:00+09:00"
      },
      {
        "id": 99,
        "type": "consume",
        "amount": -50000,
        "balance_after": 450000,
        "description": "AI機能: タスク分解",
        "created_at": "2025-12-06T15:30:00+09:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 50,
      "last_page": 3
    }
  }
}
```

**画面構成（モバイル版）**:
- **TokenHistoryScreen.tsx**:
  - 履歴リスト（FlatList）
  - 取引種別アイコン（購入=+、消費=-）
  - 金額表示（色分け: 購入=緑、消費=赤）
  - 無限スクロール（次ページ自動ロード）
  - Pull-to-Refresh機能

---

## 6. 技術仕様

### 6.1 WebView実装詳細

**必要なパッケージ**:
```bash
npm install react-native-webview
```

**iOS設定**:
- `Info.plist` に `NSAppTransportSecurity` 設定（HTTPS必須）

**Android設定**:
- `AndroidManifest.xml` にインターネット権限追加
- `android:usesCleartextTraffic="true"` （開発環境のみ）

**Cookie管理**:
- Sanctumトークンを `Authorization` ヘッダーで送信
- Laravel側でセッションCookie自動発行

**戻るボタン制御**:
```typescript
const handleBackPress = () => {
  if (webViewRef.current) {
    webViewRef.current.goBack();
    return true; // Androidの戻るボタンをオーバーライド
  }
  return false;
};

useEffect(() => {
  BackHandler.addEventListener('hardwareBackPress', handleBackPress);
  return () => BackHandler.removeEventListener('hardwareBackPress', handleBackPress);
}, []);
```

### 6.2 API一覧

| エンドポイント | メソッド | 認証 | 説明 |
|--------------|---------|------|------|
| `/api/tokens/balance` | GET | Sanctum | トークン残高取得 |
| `/api/tokens/history` | GET | Sanctum | 購入履歴取得 |
| `/api/tokens/packages` | GET | Sanctum | パッケージ一覧取得 |
| `/api/tokens/purchase-requests` | POST | Sanctum | 購入リクエスト作成（子ども） |
| `/api/tokens/purchase-requests` | GET | Sanctum | 承認待ちリクエスト一覧 |
| `/api/tokens/purchase-requests/{id}/approve` | PUT | Sanctum | リクエスト承認（親） |
| `/api/tokens/purchase-requests/{id}/reject` | PUT | Sanctum | リクエスト却下（親） |

### 6.3 モバイル実装ファイル

**Service層**:
- `mobile/src/services/token.service.ts` - API通信ロジック
  - `getBalance(): Promise<TokenBalance>`
  - `getHistory(page: number): Promise<TokenHistory>`
  - `getPackages(): Promise<TokenPackage[]>`
  - `createPurchaseRequest(packageId: number): Promise<PurchaseRequest>`
  - `getPurchaseRequests(): Promise<PurchaseRequest[]>`
  - `approvePurchaseRequest(id: number): Promise<void>`
  - `rejectPurchaseRequest(id: number): Promise<void>`

**Hook層**:
- `mobile/src/hooks/useTokens.ts` - React状態管理
  - `balance: TokenBalance | null`
  - `history: TokenTransaction[]`
  - `packages: TokenPackage[]`
  - `purchaseRequests: PurchaseRequest[]`
  - `isLoading: boolean`
  - `refreshBalance(): Promise<void>`
  - `loadMore(): Promise<void>`

**画面層**:
- `mobile/src/screens/tokens/TokenBalanceScreen.tsx` - トークン残高画面
- `mobile/src/screens/tokens/TokenPurchaseWebViewScreen.tsx` - 購入WebView画面
- `mobile/src/screens/tokens/TokenHistoryScreen.tsx` - 購入履歴画面
- `mobile/src/screens/tokens/TokenPurchaseRequestListScreen.tsx` - 承認待ちリスト（子ども用）

**型定義**:
```typescript
// mobile/src/types/token.types.ts
export interface TokenBalance {
  balance: number;
  free_monthly: number;
  used_this_month: number;
  low_threshold: number;
  is_low: boolean;
  next_reset_date: string;
}

export interface TokenPackage {
  id: number;
  name: string;
  token_amount: number;
  price: number;
  stripe_price_id: string;
  description?: string;
  discount_rate?: number;
}

export interface PurchaseRequest {
  id: number;
  package_id: number;
  package_name: string;
  token_amount: number;
  price: number;
  status: 'pending' | 'approved' | 'rejected' | 'completed';
  created_at: string;
  approved_at?: string;
  rejected_at?: string;
}
```

---

## 7. テスト要件

### 7.1 Laravelテスト

**TokenPurchaseApiTest.php**（Feature Test）:
- ✅ トークン残高取得成功
- ✅ 購入履歴取得成功（ページネーション）
- ✅ パッケージ一覧取得成功
- ✅ 購入リクエスト作成成功（子どもユーザー）
- ✅ 購入リクエスト承認成功（親ユーザー）
- ✅ 購入リクエスト却下成功（親ユーザー）
- ✅ 未認証時は401エラー
- ✅ 子どもユーザー以外はリクエスト作成不可（403エラー）
- ✅ 他人のリクエストは承認・却下不可（403エラー）

### 7.2 モバイルテスト

**token.service.test.ts**（Service層）:
- ✅ getBalance()成功
- ✅ getHistory()成功（ページネーション）
- ✅ getPackages()成功
- ✅ createPurchaseRequest()成功
- ✅ エラーハンドリング（401, 403, 500）

**useTokens.test.ts**（Hook層）:
- ✅ トークン残高取得成功
- ✅ 購入履歴取得成功（無限ロード）
- ✅ 残高低下警告表示（is_low = true）
- ✅ エラー状態管理

**TokenPurchaseWebViewScreen.test.tsx**（UI層）:
- ✅ WebView表示
- ✅ 決済成功時のURL検出（onNavigationStateChange）
- ✅ WebView閉じた後のトークン残高更新
- ✅ キャンセル時の画面遷移

---

## 8. 制約事項・注意事項

### 8.1 WebView方式の制約

- iOS: `SFSafariViewController` vs `WKWebView` の選択（WKWebView推奨）
- Android: Cookie管理、JavaScript有効化の設定必要
- HTTPS必須（開発環境でも証明書必要、または例外設定）

### 8.2 決済フロー

- Stripe Checkout Session作成はLaravel側（サーバーサイド）
- Webhook処理でトークン付与（非同期、最大数秒のラグあり）
- 決済完了後、モバイル側で5秒待機してから残高更新推奨

### 8.3 セキュリティ

- Sanctumトークン認証必須
- CSRF保護はLaravel側で自動処理
- Stripe APIキーはサーバー側のみ保持

---

## 9. 参考資料

- **Web版実装**: `app/Http/Actions/Token/`, `resources/views/tokens/`
- **Stripe Checkout実装**: `app/Services/Token/TokenPurchaseService.php`
- **Webhook処理**: `app/Http/Actions/Token/HandleStripeWebhookAction.php`
- **API仕様**: `routes/api.php` L207-212
- **開発規則**: `docs/mobile/mobile-rules.md`
- **プロジェクト規約**: `.github/copilot-instructions.md`
- **Stripe公式ドキュメント**: https://docs.stripe.com/api/checkout/sessions
