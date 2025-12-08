# サブスクリプション管理画面 要件定義書（モバイルアプリ）

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-01-18 | GitHub Copilot | 初版作成: モバイルアプリのサブスクリプション管理画面UI仕様 |

---

## 1. 概要

MyTeacherモバイルアプリにおけるサブスクリプション管理画面のUI/UX仕様書です。ネイティブUIでのサブスクリプション管理を実現し、Stripe Checkout/Billing PortalはWebView方式で連携します。

### 1.1 目的

- ユーザーがサブスクリプションプランを確認・変更・キャンセルできる
- プラン比較、請求履歴の閲覧、決済情報の管理
- WebViewを使用したStripe決済との連携

### 1.2 対象ユーザー

- **親ユーザー**: グループ管理者（サブスクリプション管理権限あり）
- **子どもユーザー**: 画面非表示（`useChildTheme()` でブロック）

### 1.3 画面一覧

| No | 画面名 | 説明 | 遷移元 |
|----|-------|------|-------|
| 1 | SubscriptionManageScreen | サブスク管理画面（プラン確認、変更、キャンセル） | 設定画面、タブナビゲーション |
| 2 | SubscriptionPlanSelectScreen | プラン選択画面（新規加入、プラン変更） | SubscriptionManageScreen |
| 3 | StripeCheckoutWebView | Stripe Checkout（WebView） | SubscriptionPlanSelectScreen |
| 4 | StripeBillingPortalWebView | Stripe Billing Portal（WebView） | SubscriptionManageScreen |

---

## 2. 画面詳細仕様

### 2.1 SubscriptionManageScreen（サブスク管理画面）

**ファイルパス**: `mobile/src/screens/SubscriptionManageScreen.tsx`

**概要**: サブスクリプション情報の表示、プラン変更、キャンセル、請求履歴の確認を行う画面。

#### 2.1.1 画面構成

```
┌────────────────────────────────────┐
│ ← サブスクリプション管理            │
├────────────────────────────────────┤
│                                    │
│ 【現在のプラン】                    │
│ ┌──────────────────────────────┐  │
│ │ ファミリープラン                │  │
│ │ ¥500/月                        │  │
│ │                                │  │
│ │ ステータス: 有効               │  │
│ │ 次回更新日: 2025-02-15         │  │
│ │ [プランを変更]                 │  │
│ └──────────────────────────────┘  │
│                                    │
│ 【プラン特典】                      │
│ ✓ 無制限のグループタスク作成        │
│ ✓ 月次レポートの閲覧                │
│ ✓ グループトークン共有              │
│                                    │
│ 【請求履歴】                        │
│ ┌──────────────────────────────┐  │
│ │ 2025-01-15  ¥500  [PDF]      │  │
│ │ 2024-12-15  ¥500  [PDF]      │  │
│ └──────────────────────────────┘  │
│                                    │
│ [決済情報を管理]                    │
│ [サブスクリプションをキャンセル]      │
│                                    │
└────────────────────────────────────┘
```

#### 2.1.2 表示項目

**現在のプランセクション**:
- プラン名（ファミリープラン/エンタープライズプラン）
- 月額料金
- ステータス（有効/キャンセル済み/トライアル中）
- 次回更新日（`ends_at` がnullの場合は自動更新）
- トライアル終了日（トライアル期間中のみ表示）
- **[プランを変更]** ボタン

**プラン特典セクション**:
- プランに含まれる機能一覧（`config('const.stripe.subscription_plans')[plan]['features']` から取得）
- チェックマーク付きリスト表示

**請求履歴セクション**:
- 直近10件の請求履歴（日付、金額、PDFリンク）
- タップでPDF請求書をWebViewで開く

**アクションボタン**:
- **[決済情報を管理]**: Stripe Billing PortalをWebViewで開く
- **[サブスクリプションをキャンセル]**: キャンセル確認ダイアログ → APIコール

#### 2.1.3 API連携

| 処理 | APIエンドポイント | メソッド |
|------|----------------|---------|
| 画面初期表示 | `/api/v1/subscriptions/current` | GET |
| 画面初期表示 | `/api/v1/subscriptions/invoices` | GET |
| プラン変更画面遷移 | `/api/v1/subscriptions/plans` | GET |
| 決済情報管理 | `/api/v1/subscriptions/billing-portal` | POST |
| サブスクキャンセル | `/api/v1/subscriptions/cancel` | POST |

#### 2.1.4 エラーハンドリング

| エラー | 表示内容 | 対応 |
|-------|---------|-----|
| サブスク未加入 | 「現在サブスクリプションに加入していません」メッセージ + [プランを選択] ボタン | SubscriptionPlanSelectScreenに遷移 |
| 401 Unauthorized | 「認証エラーが発生しました。再ログインしてください。」 | ログイン画面に遷移 |
| 403 Forbidden | 「サブスクリプション管理権限がありません。」 | 前の画面に戻る |
| 500 Internal Server Error | 「エラーが発生しました。時間をおいて再度お試しください。」 | エラーダイアログ表示 |

#### 2.1.5 状態管理

```typescript
interface SubscriptionState {
  subscription: {
    plan: 'family' | 'enterprise' | null;
    active: boolean;
    stripe_status: string;
    ends_at: string | null;
    trial_ends_at: string | null;
  } | null;
  invoices: {
    id: string;
    date: string;
    total: number;
    amount_paid: number;
    status: string;
    currency: string;
    invoice_pdf: string;
  }[];
  loading: boolean;
  error: string | null;
}
```

---

### 2.2 SubscriptionPlanSelectScreen（プラン選択画面）

**ファイルパス**: `mobile/src/screens/SubscriptionPlanSelectScreen.tsx`

**概要**: サブスクリプションプラン一覧を表示し、新規加入またはプラン変更を行う画面。

#### 2.2.1 画面構成

```
┌────────────────────────────────────┐
│ ← プランを選択                      │
├────────────────────────────────────┤
│                                    │
│ 【ファミリープラン】（現在のプラン） │
│ ┌──────────────────────────────┐  │
│ │ ¥500/月                        │  │
│ │ 14日間無料トライアル            │  │
│ │                                │  │
│ │ ✓ 最大6名                      │  │
│ │ ✓ 1グループ                    │  │
│ │ ✓ 無制限のグループタスク        │  │
│ │ ✓ 月次レポート                 │  │
│ │ ✓ グループトークン共有          │  │
│ │                                │  │
│ │ [現在のプラン]                 │  │
│ └──────────────────────────────┘  │
│                                    │
│ 【エンタープライズプラン】          │
│ ┌──────────────────────────────┐  │
│ │ ¥3,000/月（基本20名）          │  │
│ │ + ¥150/名（追加メンバー）      │  │
│ │ 14日間無料トライアル            │  │
│ │                                │  │
│ │ ✓ 最大20名（基本）             │  │
│ │ ✓ 5グループ                    │  │
│ │ ✓ ファミリープランの全機能      │  │
│ │ ✓ 統計レポート（将来実装）      │  │
│ │ ✓ 優先サポート（将来実装）      │  │
│ │                                │  │
│ │ [このプランに変更]             │  │
│ └──────────────────────────────┘  │
│                                    │
└────────────────────────────────────┘
```

#### 2.2.2 表示項目

**プランカード** (config/const.phpから取得):
- プラン名
- 月額料金
- 無料トライアル期間
- 最大メンバー数、最大グループ数
- プラン特典（`features` フィールド）
- **現在のプラン** バッジ（加入中のプランのみ）
- **[このプランに変更]** ボタン（他のプランのみ）

**注釈**:
- エンタープライズプランの追加メンバー料金（¥150/月/名）
- 無料トライアル期間中はいつでもキャンセル可能（請求なし）

#### 2.2.3 API連携

| 処理 | APIエンドポイント | メソッド |
|------|----------------|---------|
| 画面初期表示 | `/api/v1/subscriptions/plans` | GET |
| プラン選択 | `/api/v1/subscriptions/checkout` | POST |
| プラン変更 | `/api/v1/subscriptions/update` | POST |

#### 2.2.4 画面遷移

**新規加入の場合**:
1. プラン選択ボタンタップ
2. `POST /api/v1/subscriptions/checkout` でCheckout Session作成
3. `checkout_url` を取得
4. StripeCheckoutWebViewに遷移（`checkout_url` を渡す）

**プラン変更の場合**:
1. プラン変更ボタンタップ
2. 確認ダイアログ表示（「エンタープライズプランに変更しますか?」）
3. `POST /api/v1/subscriptions/update` でプラン変更
4. 成功メッセージ表示 → SubscriptionManageScreenに戻る

#### 2.2.5 エラーハンドリング

| エラー | 表示内容 | 対応 |
|-------|---------|-----|
| 400 Bad Request | 「プラン選択エラーが発生しました。」 | エラーダイアログ表示 |
| 401 Unauthorized | 「認証エラーが発生しました。再ログインしてください。」 | ログイン画面に遷移 |
| 403 Forbidden | 「サブスクリプション管理権限がありません。」 | 前の画面に戻る |
| 500 Internal Server Error | 「エラーが発生しました。時間をおいて再度お試しください。」 | エラーダイアログ表示 |

#### 2.2.6 状態管理

```typescript
interface PlanSelectState {
  plans: {
    plan_id: 'family' | 'enterprise';
    name: string;
    amount: number;
    currency: string;
    interval: string;
    max_members: number;
    max_groups: number;
    trial_days: number;
    features: {
      unlimited_group_tasks: boolean;
      monthly_reports: boolean;
      group_token_sharing: boolean;
      statistics_reports?: boolean;
      priority_support?: boolean;
    };
  }[];
  current_plan: 'family' | 'enterprise' | null;
  additional_member_price: number;
  loading: boolean;
  error: string | null;
}
```

---

### 2.3 StripeCheckoutWebView（Stripe Checkout WebView）

**ファイルパス**: `mobile/src/screens/StripeCheckoutWebView.tsx`

**概要**: Stripe CheckoutページをWebViewで表示し、決済処理を行う画面。

#### 2.3.1 画面構成

```
┌────────────────────────────────────┐
│ ← Stripe Checkout                  │
├────────────────────────────────────┤
│                                    │
│ [WebView: Stripe Checkoutページ]   │
│                                    │
│ （Stripeホスティングの決済画面）      │
│                                    │
└────────────────────────────────────┘
```

#### 2.3.2 実装詳細

**WebView設定**:
- `javaScriptEnabled`: true
- `domStorageEnabled`: true
- `source.uri`: `checkout_url`（APIレスポンスから取得）

**ディープリンク処理**:
```typescript
// WebViewのonNavigationStateChange
const handleNavigationStateChange = (navState: WebViewNavigation) => {
  const { url } = navState;
  
  if (url.startsWith('myteacher://subscription/success')) {
    // 決済成功
    navigation.navigate('SubscriptionManageScreen', { refresh: true });
    showSuccessToast('サブスクリプションに加入しました。');
  } else if (url.startsWith('myteacher://subscription/cancel')) {
    // 決済キャンセル
    navigation.goBack();
    showInfoToast('決済をキャンセルしました。');
  }
};
```

**API設定**:
- `return_url`: `myteacher://subscription/success`
- `cancel_url`: `myteacher://subscription/cancel`

#### 2.3.3 エラーハンドリング

| エラー | 表示内容 | 対応 |
|-------|---------|-----|
| WebViewロードエラー | 「ページの読み込みに失敗しました。」 | エラーダイアログ表示 → 前の画面に戻る |
| ネットワークエラー | 「インターネット接続を確認してください。」 | エラーダイアログ表示 → 前の画面に戻る |

---

### 2.4 StripeBillingPortalWebView（Stripe Billing Portal WebView）

**ファイルパス**: `mobile/src/screens/StripeBillingPortalWebView.tsx`

**概要**: Stripe Billing PortalをWebViewで表示し、決済方法変更、請求書ダウンロード等を行う画面。

#### 2.4.1 画面構成

```
┌────────────────────────────────────┐
│ ← 決済情報管理                      │
├────────────────────────────────────┤
│                                    │
│ [WebView: Stripe Billing Portal]   │
│                                    │
│ （Stripeホスティングの管理画面）      │
│                                    │
└────────────────────────────────────┘
```

#### 2.4.2 実装詳細

**WebView設定**:
- `javaScriptEnabled`: true
- `domStorageEnabled`: true
- `source.uri`: `portal_url`（APIレスポンスから取得）

**ディープリンク処理**:
```typescript
// WebViewのonNavigationStateChange
const handleNavigationStateChange = (navState: WebViewNavigation) => {
  const { url } = navState;
  
  if (url.startsWith('myteacher://subscription/manage')) {
    // 管理画面に戻る
    navigation.goBack();
  }
};
```

**API設定**:
- `return_url`: `myteacher://subscription/manage`

#### 2.4.3 エラーハンドリング

| エラー | 表示内容 | 対応 |
|-------|---------|-----|
| WebViewロードエラー | 「ページの読み込みに失敗しました。」 | エラーダイアログ表示 → 前の画面に戻る |
| ネットワークエラー | 「インターネット接続を確認してください。」 | エラーダイアログ表示 → 前の画面に戻る |

---

## 3. 画面遷移フロー

### 3.1 新規加入フロー

```
SubscriptionManageScreen（未加入）
  ↓ [プランを選択] タップ
SubscriptionPlanSelectScreen
  ↓ [このプランに変更] タップ
  ↓ API: POST /api/v1/subscriptions/checkout
  ↓ checkout_url取得
StripeCheckoutWebView
  ↓ 決済完了（ディープリンク: myteacher://subscription/success）
SubscriptionManageScreen（加入済み）
  ↓ 成功トースト表示
```

### 3.2 プラン変更フロー

```
SubscriptionManageScreen（加入済み）
  ↓ [プランを変更] タップ
SubscriptionPlanSelectScreen
  ↓ [このプランに変更] タップ
  ↓ 確認ダイアログ表示
  ↓ API: POST /api/v1/subscriptions/update
SubscriptionManageScreen
  ↓ 成功トースト表示
```

### 3.3 サブスクキャンセルフロー

```
SubscriptionManageScreen（加入済み）
  ↓ [サブスクリプションをキャンセル] タップ
  ↓ 確認ダイアログ表示（「期間終了まで利用可能です」）
  ↓ API: POST /api/v1/subscriptions/cancel
SubscriptionManageScreen
  ↓ 「次回更新日: 2025-02-15（キャンセル済み）」表示
  ↓ 成功トースト表示
```

### 3.4 決済情報管理フロー

```
SubscriptionManageScreen（加入済み）
  ↓ [決済情報を管理] タップ
  ↓ API: POST /api/v1/subscriptions/billing-portal
  ↓ portal_url取得
StripeBillingPortalWebView
  ↓ 操作完了（ディープリンク: myteacher://subscription/manage）
SubscriptionManageScreen
```

---

## 4. UI/UXガイドライン

### 4.1 カラースキーム

| 要素 | カラー | 用途 |
|------|-------|------|
| プライマリボタン | `#007AFF`（iOS Blue） | [プランを変更]、[このプランに変更] |
| セカンダリボタン | `#5856D6`（iOS Purple） | [決済情報を管理] |
| デンジャーボタン | `#FF3B30`（iOS Red） | [サブスクリプションをキャンセル] |
| 現在のプランバッジ | `#34C759`（iOS Green） | [現在のプラン] |
| 無効ボタン | `#C7C7CC`（iOS Gray） | トライアル中の変更不可ボタン |

### 4.2 フォント

| 要素 | フォント | サイズ |
|------|---------|-------|
| プラン名 | Bold | 20pt |
| 月額料金 | Bold | 24pt |
| 特典リスト | Regular | 16pt |
| 請求履歴 | Regular | 14pt |
| ボタンテキスト | Semibold | 16pt |

### 4.3 レイアウト

- **パディング**: 16px（画面端）、12px（カード内）
- **カード**: 角丸8px、影（elevation 2）、背景白
- **ボタン**: 角丸8px、高さ48px
- **リスト項目**: 高さ60px、区切り線あり

### 4.4 アクセシビリティ

- **VoiceOver/TalkBack対応**: すべてのボタンに `accessibilityLabel` 設定
- **コントラスト比**: WCAG AA準拠（4.5:1以上）
- **タッチターゲット**: 最小44x44pt（iOS）、48x48dp（Android）

---

## 5. 実装上の注意事項

### 5.1 権限チェック

**子どもテーマユーザーのブロック**:
```typescript
// SubscriptionManageScreen.tsx
useEffect(() => {
  if (user.use_child_theme) {
    // 子どもテーマユーザーは画面非表示
    navigation.goBack();
    showErrorToast('サブスクリプション管理権限がありません。');
  }
}, [user]);
```

**グループ管理権限チェック**:
- API側で `SubscriptionService::canManageSubscription()` を使用
- 403エラー時は前の画面に戻る

### 5.2 ローディング状態

**画面初期表示時**:
```typescript
// SubscriptionManageScreen.tsx
const [loading, setLoading] = useState(true);

useEffect(() => {
  const fetchData = async () => {
    setLoading(true);
    try {
      const [subscription, invoices] = await Promise.all([
        api.getCurrentSubscription(),
        api.getInvoices(),
      ]);
      setSubscription(subscription);
      setInvoices(invoices);
    } catch (error) {
      handleError(error);
    } finally {
      setLoading(false);
    }
  };
  fetchData();
}, []);
```

**ボタンタップ時**:
- ボタンを無効化（`disabled={loading}`）
- インジケーター表示

### 5.3 エラーハンドリング

**統一エラーハンドリング**:
```typescript
const handleError = (error: ApiError) => {
  if (error.status === 401) {
    // 認証エラー → ログイン画面
    navigation.navigate('Login');
    showErrorToast('認証エラーが発生しました。再ログインしてください。');
  } else if (error.status === 403) {
    // 権限エラー → 前の画面
    navigation.goBack();
    showErrorToast('サブスクリプション管理権限がありません。');
  } else if (error.status === 404) {
    // サブスク未加入 → プラン選択画面
    setSubscription(null);
  } else {
    // その他のエラー
    showErrorToast('エラーが発生しました。時間をおいて再度お試しください。');
  }
};
```

### 5.4 トースト/ダイアログ

**成功トースト**:
```typescript
showSuccessToast('サブスクリプションに加入しました。');
showSuccessToast('プランを変更しました。');
showSuccessToast('サブスクリプションをキャンセルしました。');
```

**確認ダイアログ**:
```typescript
// プラン変更確認
Alert.alert(
  'プラン変更',
  'エンタープライズプランに変更しますか？',
  [
    { text: 'キャンセル', style: 'cancel' },
    { text: '変更', onPress: () => handlePlanUpdate() },
  ]
);

// サブスクキャンセル確認
Alert.alert(
  'サブスクリプションキャンセル',
  'サブスクリプションをキャンセルしますか？期間終了まで利用可能です。',
  [
    { text: 'キャンセル', style: 'cancel' },
    { text: 'キャンセルする', style: 'destructive', onPress: () => handleSubscriptionCancel() },
  ]
);
```

### 5.5 リフレッシュ機能

**Pull to Refresh**:
```typescript
// SubscriptionManageScreen.tsx
<ScrollView
  refreshControl={
    <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
  }
>
  {/* コンテンツ */}
</ScrollView>
```

### 5.6 ディープリンク登録

**app.json**:
```json
{
  "expo": {
    "scheme": "myteacher",
    "ios": {
      "associatedDomains": ["applinks:myteacher.app"]
    },
    "android": {
      "intentFilters": [
        {
          "action": "VIEW",
          "data": [
            {
              "scheme": "myteacher",
              "host": "subscription"
            }
          ],
          "category": ["BROWSABLE", "DEFAULT"]
        }
      ]
    }
  }
}
```

---

## 6. テスト観点

### 6.1 機能テスト

- ✅ サブスク未加入時にプラン選択画面に遷移できる
- ✅ プラン一覧を正しく表示できる
- ✅ プラン選択後にStripe CheckoutをWebViewで開ける
- ✅ 決済成功後にSubscriptionManageScreenに戻り、加入状態を表示できる
- ✅ プラン変更ボタンタップで確認ダイアログを表示できる
- ✅ プラン変更APIコール成功時に成功トーストを表示できる
- ✅ サブスクキャンセルボタンタップで確認ダイアログを表示できる
- ✅ サブスクキャンセルAPIコール成功時に「キャンセル済み」表示に変わる
- ✅ 決済情報管理ボタンタップでStripe Billing PortalをWebViewで開ける
- ✅ 請求履歴を正しく表示できる
- ✅ 請求履歴のPDFリンクタップでPDFをWebViewで開ける

### 6.2 権限テスト

- ✅ 子どもテーマユーザーが画面にアクセスできない
- ✅ グループ管理権限なしのユーザーが403エラーを受け取る

### 6.3 エラーハンドリングテスト

- ✅ 401エラー時にログイン画面に遷移する
- ✅ 403エラー時に前の画面に戻る
- ✅ 404エラー時にサブスク未加入表示になる
- ✅ 500エラー時にエラーダイアログを表示する
- ✅ ネットワークエラー時にエラーダイアログを表示する

### 6.4 UI/UXテスト

- ✅ ローディング状態を正しく表示できる
- ✅ Pull to Refreshで最新情報を取得できる
- ✅ ボタンタップ時にボタンが無効化される
- ✅ トースト/ダイアログが適切なタイミングで表示される
- ✅ カラースキーム、フォント、レイアウトが仕様通りに表示される

### 6.5 WebViewテスト

- ✅ Stripe CheckoutページをWebViewで正しく表示できる
- ✅ ディープリンク（myteacher://subscription/success）で正しく遷移する
- ✅ ディープリンク（myteacher://subscription/cancel）で正しく遷移する
- ✅ Stripe Billing PortalページをWebViewで正しく表示できる
- ✅ ディープリンク（myteacher://subscription/manage）で正しく遷移する

---

## 7. 参考情報

### 7.1 参照ドキュメント

- **API仕様書**: `definitions/mobile/SubscriptionManagementAPI.md`
- **計画書**: `docs/plans/phase2-mobile-app-implementation-plan.md` (Phase 2.B-6)
- **トークン購入要件定義**: `definitions/mobile/TokenPurchaseWebView.md`（WebView方式の参考）
- **モバイルルール**: `docs/mobile/mobile-rules.md`
- **プラン設定**: `config/const.php` (Lines 130-180)

### 7.2 デザインリファレンス

- **iOS Human Interface Guidelines**: https://developer.apple.com/design/human-interface-guidelines/
- **Material Design**: https://material.io/design
- **Stripe UI Toolkit**: https://stripe.com/docs/stripe-apps/ui-toolkit

### 7.3 Stripe連携参考

- **Stripe Checkout**: https://stripe.com/docs/payments/checkout
- **Stripe Billing Portal**: https://stripe.com/docs/billing/subscriptions/customer-portal
- **Stripe Mobile SDKs**: https://stripe.com/docs/mobile

---

## 8. 次のステップ

1. **画面実装**:
   - SubscriptionManageScreen.tsx
   - SubscriptionPlanSelectScreen.tsx
   - StripeCheckoutWebView.tsx
   - StripeBillingPortalWebView.tsx

2. **APIクライアント実装**:
   - `mobile/src/api/subscriptionApi.ts` に7エンドポイントのラッパー関数追加
   - エラーハンドリング、トークン管理

3. **ナビゲーション設定**:
   - タブナビゲーション or ドロワーナビゲーションにサブスク管理画面を追加
   - ディープリンク設定（`app.json`）

4. **テスト実装**:
   - `mobile/__tests__/screens/SubscriptionManageScreen.test.tsx`
   - `mobile/__tests__/screens/SubscriptionPlanSelectScreen.test.tsx`

5. **動作確認**:
   - Stripe Test Modeでの決済フロー確認
   - WebViewのディープリンク動作確認
   - iOS/Android実機での動作確認

---

以上
