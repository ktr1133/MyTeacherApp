# モバイルアプリ 画面遷移フロー要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 初版作成: 画面遷移フロー要件定義 |
| 2025-12-09 | GitHub Copilot | セクション8更新: エラーハンドリング実装完了を記録 |
| 2025-12-11 | GitHub Copilot | 完了レポート作成: 画面遷移・エラーハンドリング実装完了レポート（2025-12-11-navigation-error-handling-completion-report.md） |

---

## 1. 概要

MyTeacherモバイルアプリの画面遷移フローを定義する。本ドキュメントでは、認証フロー、メインナビゲーション構造、各機能への遷移パターンを明確化し、Webアプリ（レスポンシブデザイン）との整合性を確保する。

### 対象フェーズ

- Phase 2.B-2〜2.B-8（認証〜全機能実装）

### 基本方針

1. **Web版との整合性**: Webアプリのレスポンシブデザイン（375px幅相当）と同等の画面構成
2. **ハンバーガーメニュー方式**: Webアプリの狭幅時と同じナビゲーション方式を採用
3. **アバター未作成時の対応**: タスク一覧画面上部にバナー表示（任意作成）
4. **ログイン後のデフォルト遷移**: タスク一覧画面（Webアプリの`/dashboard`相当）

---

## 2. 認証フロー

### 2.1 初回起動時

```
アプリ起動
  ↓
AsyncStorage確認（auth_token）
  ↓
├─ トークン有り → タスク一覧画面（自動ログイン）
└─ トークン無し → ログイン画面
```

### 2.2 ログイン画面

**画面**: `LoginScreen.tsx`

**表示要素**（Web版 `/login` と同等）:
- ロゴ
- メールアドレス入力フィールド
- パスワード入力フィールド
- ログインボタン
- 「アカウントをお持ちでない方」リンク → 新規登録画面へ遷移
- パスワードを忘れた方リンク（Phase 2.B-4で実装）

**遷移先**:
- ログイン成功 → タスク一覧画面
- 新規登録リンク → 新規登録画面

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/auth/login.blade.php`

### 2.3 新規登録画面

**画面**: `RegisterScreen.tsx`

**表示要素**（Web版 `/register` と同等）:
- メールアドレス入力フィールド
- パスワード入力フィールド
- パスワード確認入力フィールド
- 表示名入力フィールド（オプション）
- 登録ボタン
- 「アカウントをお持ちの方」リンク → ログイン画面へ遷移

**DBスキーマ対応**:
- 送信フィールド: `email`, `password`, `name`（オプション）
- バックエンド側で自動生成: `username`, `cognito_sub`, `auth_provider`

**遷移先**:
- 登録成功 → タスク一覧画面（自動ログイン）
- ログインリンク → ログイン画面

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/auth/register.blade.php`
- マイグレーション: `/home/ktr/mtdev/database/migrations/0001_01_01_000000_create_users_table.php`

---

## 3. メインナビゲーション構造

### 3.1 ナビゲーション方式

**採用方式**: ハンバーガーメニュー（ドロワー）

**理由**:
- Webアプリの狭幅時（375px未満）と同じUI/UX
- サイドバーの内容を完全再現可能
- タブナビゲーションよりも多くのメニュー項目を収容可能

### 3.2 ハンバーガーメニュー構成

**Web版サイドバー**（`/home/ktr/mtdev/resources/views/components/layouts/sidebar.blade.php`）の内容をモバイル版ドロワーに完全移植。

#### 一般ユーザーメニュー

| # | メニュー項目 | アイコン | 遷移先画面 | Web版ルート | バッジ表示 | 備考 |
|---|------------|---------|----------|-----------|----------|------|
| 1 | タスクリスト（Todo） | クリップボード | タスク一覧画面 | `/dashboard` | 未完了件数 | デフォルト画面 |
| 2 | 承認待ち | 時計 | 承認待ち一覧画面 | `/tasks/pending-approvals` | 承認待ち件数 | グループ管理者のみ表示 |
| 3 | タグ管理（タグ） | タグ | タグ一覧画面 | `/tags` | なし | - |
| 4 | 教師アバター（サポートアバター） | ユーザー | アバター管理画面 | `/avatars/edit` | なし | - |
| 5 | 実績 | 棒グラフ | 実績レポート画面 | `/reports/performance` | なし | - |
| 6 | トークン（コイン） | コイン | トークン購入画面 | `/tokens/purchase` | 残高警告 | 残高低下時に赤丸表示 |
| 7 | サブスクリプション | クレジットカード | サブスクリプション管理画面 | `/subscriptions` | なし | グループ管理者のみ表示 |
| 8 | 設定 | 歯車 | 設定画面 | `/profile/edit` | なし | - |

#### 管理者メニュー（管理者ユーザーのみ表示）

| # | メニュー項目 | アイコン | 遷移先画面 | Web版ルート | 備考 |
|---|------------|---------|----------|-----------|------|
| 9 | 一般メニュー表示切替 | 目アイコン | - | - | 一般メニューの表示/非表示切替 |
| 10 | ユーザー管理 | 複数ユーザー | ユーザー管理画面 | `/admin/users` | Phase 2外（将来対応） |
| 11 | パッケージ設定 | ドルマーク | パッケージ設定画面 | `/admin/token-packages` | Phase 2外（将来対応） |
| 12 | トークン統計 | 棒グラフ | トークン統計画面 | `/admin/token-stats` | Phase 2外（将来対応） |
| 13 | 課金履歴 | クレジットカード | 課金履歴画面 | `/admin/payment-history` | Phase 2外（将来対応） |
| 14 | ポータルサイト管理 | 地球儀 | サブメニュー展開 | - | 親メニュー（展開可能） |
| 14-1 | ポータルサイト | パズル | ポータルサイト | `/portal` | Phase 2外（将来対応） |
| 14-2 | メンテナンス情報 | 警告 | メンテナンス管理画面 | `/admin/portal/maintenances` | Phase 2外（将来対応） |
| 14-3 | お問い合わせ | メール | お問い合わせ管理画面 | `/admin/portal/contacts` | Phase 2外（将来対応） |
| 14-4 | FAQ管理 | はてな | FAQ管理画面 | `/admin/portal/faqs` | Phase 2外（将来対応） |
| 14-5 | アプリ更新履歴 | ダウンロード | 更新履歴管理画面 | `/admin/portal/updates` | Phase 2外（将来対応） |

#### トークン残高表示（ドロワー下部固定）

**表示内容**:
- トークン（コイン）アイコン
- 残高総数（大きく表示）
- 無料残高 / 有料残高（小さく表示）
- 残高が低下時（`isLowBalance`）: 「トークン購入」リンク表示

**実装**:
- Web版の`sidebar.blade.php` Lines 434-476 を参照
- トークン残高取得: `GET /api/tokens/balance`
- 低残高閾値: `config('const.token.low_threshold')` = 200,000トークン

### 3.3 ヘッダー構成（全画面共通）

**固定要素**:
- 左: ハンバーガーメニューボタン（3本線アイコン）
- 中央: 画面タイトル（動的変更）
- 右: 通知アイコン（未読バッジ付き）

**画面タイトル例**:
- タスク一覧: 「タスクリスト」または「ToDo」（テーマに応じて）
- タスク詳細: タスクタイトル（長い場合は省略）
- プロフィール: 「設定」

---

## 4. 主要画面への遷移パターン

### 4.1 タスク管理機能

#### タスク一覧画面（デフォルト画面）

**画面**: `TaskListScreen.tsx`

**遷移元**:
- ログイン成功後
- 新規登録成功後
- ハンバーガーメニュー > タスクリスト

**表示要素**（Web版 `/dashboard` と同等）:
- **アバター未作成バナー**（条件付き表示）
  - 表示条件: `user.teacher_avatar_id === null`
  - デザイン: 上部固定、背景色: 薄いピンク、アイコン: 人物
  - テキスト: 「あなただけのサポートアバターを作成しましょう！」
  - タップ時: アバター作成画面へ遷移
  - 参照: Web版では初回ログイン時にモーダル表示（`resources/views/components/avatar-setup-modal.blade.php`）
- タスクリスト（無限スクロール）
- 各タスクカード:
  - タスクタイトル
  - 優先度バッジ
  - タグ表示
  - 期限表示
  - 画像サムネイル（添付画像がある場合）
- フローティングアクションボタン（FAB）: 「+」ボタン → タスク作成画面へ遷移
- プルダウンリフレッシュ

**API**:
- タスク一覧取得: `GET /api/tasks?page={page}`
- アバター存在確認: `GET /api/profile` のレスポンスに `teacher_avatar_id` を含む

**遷移先**:
- タスクカードタップ → タスク詳細画面
- FABタップ → タスク作成画面
- アバター未作成バナータップ → アバター作成画面

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/tasks/index.blade.php`
- OpenAPI: `/tasks` エンドポイント

#### タスク詳細画面

**画面**: `TaskDetailScreen.tsx`

**遷移元**:
- タスク一覧画面 > タスクカードタップ
- 通知 > タスク関連通知タップ

**表示要素**（Web版 `/tasks/{id}` と同等）:
- タスクタイトル
- 詳細説明
- 優先度
- 期限
- タグ一覧
- 添付画像一覧（スワイプ可能）
- 完了ボタン / 未完了ボタン（トグル）
- 編集ボタン → タスク編集画面へ遷移
- 削除ボタン（確認ダイアログ表示）
- 承認ステータス（承認待ちの場合）

**API**:
- タスク詳細取得: `GET /api/tasks/{id}`
- 完了トグル: `PATCH /api/tasks/{id}/complete` または `PATCH /api/tasks/{id}/uncomplete`
- 削除: `DELETE /api/tasks/{id}`

**遷移先**:
- 編集ボタン → タスク編集画面
- 削除後 → タスク一覧画面（自動遷移）

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/tasks/show.blade.php`

#### タスク作成画面

**画面**: `CreateTaskScreen.tsx`

**遷移元**:
- タスク一覧画面 > FABタップ

**表示要素**（Web版 `/tasks/create` と同等）:
- タスクタイトル入力
- 詳細説明入力（複数行）
- 優先度選択（ドロップダウン）
- 期限日時選択（DateTimePicker）
- タグ選択（複数選択可能）
- 画像添付ボタン（カメラ起動 or ギャラリー選択）
- 保存ボタン
- キャンセルボタン → タスク一覧画面へ戻る

**API**:
- タスク作成: `POST /api/tasks`
- タグ一覧取得: `GET /api/tags`
- 画像アップロード: `POST /api/tasks/{id}/images`

**遷移先**:
- 保存成功 → タスク詳細画面（作成したタスク）
- キャンセル → タスク一覧画面

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/tasks/create.blade.php`

#### タスク編集画面

**画面**: `EditTaskScreen.tsx`

**遷移元**:
- タスク詳細画面 > 編集ボタン

**表示要素**: タスク作成画面と同等（既存データがプリフィル）

**API**:
- タスク更新: `PUT /api/tasks/{id}`
- 画像削除: `DELETE /api/tasks/images/{imageId}`

**遷移先**:
- 更新成功 → タスク詳細画面
- キャンセル → タスク詳細画面

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/tasks/edit.blade.php`

#### 承認待ち一覧画面

**画面**: `TaskApprovalScreen.tsx`

**遷移元**:
- ハンバーガーメニュー > 承認待ち

**表示条件**: `user.canEditGroup() === true`（グループ管理者のみ）

**表示要素**（Web版 `/tasks/pending-approvals` と同等）:
- タブ切り替え: 「タスク承認」「トークン購入申請」
- タスク承認タブ:
  - 承認待ちタスク一覧
  - 各タスクカード: タイトル、申請者、期限、画像
  - 承認ボタン / 却下ボタン
- トークン購入申請タブ:
  - 承認待ち購入申請一覧
  - 各申請カード: 申請者、パッケージ名、金額
  - 承認ボタン / 却下ボタン

**API**:
- 承認待ちタスク一覧: `GET /api/tasks/pending-approvals`
- タスク承認: `POST /api/tasks/{id}/approve`
- タスク却下: `POST /api/tasks/{id}/reject`
- 購入申請一覧: `GET /api/tokens/purchase-requests`
- 購入申請承認: `POST /api/tokens/purchase-requests/{id}/approve`
- 購入申請却下: `POST /api/tokens/purchase-requests/{id}/reject`

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/tasks/pending-approvals.blade.php`

### 4.2 グループ管理機能（Phase 2.B-3）

**画面**: 
- `GroupListScreen.tsx`: グループ一覧
- `GroupDetailScreen.tsx`: グループ詳細
- `GroupMembersScreen.tsx`: メンバー一覧

**遷移フロー**:
```
ハンバーガーメニュー > グループ管理（未追加） → GroupListScreen
  ↓
グループカードタップ → GroupDetailScreen
  ↓
メンバータブ → GroupMembersScreen
```

**注意**: 現在のWeb版サイドバーに「グループ管理」メニューが存在しないため、Phase 2.B-3実装時に以下を実施:
1. Web版サイドバーへの追加（または既存画面からのアクセス方法確認）
2. モバイル版ハンバーガーメニューへの追加

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/groups/`
- OpenAPI: `/groups` エンドポイント

### 4.3 タグ管理機能

**画面**: `TagListScreen.tsx`

**遷移元**:
- ハンバーガーメニュー > タグ管理（タグ）

**表示要素**（Web版 `/tags` と同等）:
- タグ一覧（カード表示）
- 各タグカード: タグ名、使用回数
- タグ作成ボタン（FAB）
- タグ編集 / 削除機能

**API**:
- タグ一覧取得: `GET /api/tags`
- タグ作成: `POST /api/tags`
- タグ更新: `PUT /api/tags/{id}`
- タグ削除: `DELETE /api/tags/{id}`

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/tags/list.blade.php`
- OpenAPI: `/tags` エンドポイント

### 4.4 アバター管理機能（Phase 2.B-7）

**画面**: 
- `AvatarListScreen.tsx`: アバター一覧（既存アバター切り替え）
- `AvatarCreateScreen.tsx`: アバター作成

**遷移元**:
- ハンバーガーメニュー > 教師アバター（サポートアバター）
- タスク一覧画面 > アバター未作成バナータップ

**表示要素**（Web版 `/avatars/edit` と同等）:
- 現在のアバター表示（ポーズ・表情切り替え）
- アバターコメント表示（イベント連動）
- 新規アバター作成ボタン → `AvatarCreateScreen`へ遷移
- アバター一覧（既存アバター切り替え）

**API**:
- アバター一覧取得: `GET /api/avatars`
- アバター詳細取得: `GET /api/avatars/{id}`
- アバター作成開始: `POST /api/avatars`
- アバター生成ジョブ開始: `POST /api/avatars/{id}/generate`

**参照**:
- 要件定義: `/home/ktr/mtdev/definitions/AvatarDefinition.md`
- Bladeファイル: `/home/ktr/mtdev/resources/views/avatars/edit.blade.php`
- OpenAPI: `/avatars` エンドポイント

### 4.5 実績レポート機能（Phase 2.B-7）

**画面**: 
- `MonthlyReportScreen.tsx`: 月次レポート
- `PerformanceScreen.tsx`: 実績グラフ

**遷移元**:
- ハンバーガーメニュー > 実績

**表示要素**（Web版 `/reports/performance` と同等）:
- 月次実績グラフ（react-native-chart-kit使用）
- タスク完了率
- トークン消費推移
- 月選択ドロップダウン

**API**:
- 月次レポート取得: `GET /api/reports/monthly?month={YYYY-MM}`
- グラフデータ取得: `GET /api/reports/performance?start={date}&end={date}`

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/reports/performance.blade.php`
- OpenAPI: `/reports` エンドポイント

### 4.6 トークン・決済機能（Phase 2.B-6）

**画面**: 
- `TokenBalanceScreen.tsx`: トークン残高・履歴
- `PurchaseScreen.tsx`: トークン購入

**遷移元**:
- ハンバーガーメニュー > トークン（コイン）
- ドロワー下部 > トークン購入リンク

**表示要素**（Web版 `/tokens/purchase` と同等）:
- トークン残高表示（無料/有料の内訳）
- トークンパッケージ一覧（カード表示）
- 購入ボタン → Stripe決済フロー
- トークン履歴タブ
- 消費履歴一覧（無限スクロール）

**API**:
- トークン残高取得: `GET /api/tokens/balance`
- トークン履歴取得: `GET /api/tokens/history?page={page}`
- パッケージ一覧取得: `GET /api/tokens/packages`
- 購入開始: `POST /api/tokens/purchase`

**参照**:
- 要件定義: `/home/ktr/mtdev/definitions/Purchase.md`
- Bladeファイル: `/home/ktr/mtdev/resources/views/tokens/purchase.blade.php`
- OpenAPI: `/tokens` エンドポイント

### 4.7 サブスクリプション管理機能（Phase 2.B-6）

**画面**: `SubscriptionScreen.tsx`

**遷移元**:
- ハンバーガーメニュー > サブスクリプション

**表示条件**: `user.group_id !== null && user.canEditGroup() === true`

**表示要素**（Web版 `/subscriptions` と同等）:
- 現在のプラン表示
- プラン変更ボタン
- 次回更新日表示
- 決済履歴
- プラン解約ボタン

**API**:
- サブスクリプション情報取得: `GET /api/subscriptions`
- プラン一覧取得: `GET /api/subscriptions/plans`
- プラン変更: `POST /api/subscriptions/change-plan`
- 解約: `POST /api/subscriptions/cancel`

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/subscriptions/index.blade.php`

### 4.8 通知機能（Phase 2.B-5）

**画面**: `NotificationListScreen.tsx`

**遷移元**:
- ヘッダー > 通知アイコン

**表示要素**（Web版 `/notifications` と同等）:
- 通知一覧（無限スクロール）
- 各通知カード:
  - アイコン（種別に応じて）
  - タイトル
  - 本文
  - 未読バッジ
  - タップ時: 関連画面へ遷移（タスク詳細等）
- 全既読ボタン
- プルダウンリフレッシュ

**API**:
- 通知一覧取得: `GET /api/notifications?page={page}`
- 未読件数取得: `GET /api/notifications/unread-count`
- 既読化: `PATCH /api/notifications/{id}/read`
- 全既読化: `POST /api/notifications/read-all`

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/notifications/index.blade.php`
- OpenAPI: `/notifications` エンドポイント

### 4.9 プロフィール・設定機能（Phase 2.B-4）

**画面**: 
- `ProfileScreen.tsx`: プロフィール表示・編集
- `SettingsScreen.tsx`: 設定

**遷移元**:
- ハンバーガーメニュー > 設定

**表示要素**（Web版 `/profile/edit` と同等）:
- プロフィール編集セクション:
  - 表示名
  - メールアドレス
  - アバター画像アップロード
- パスワード変更セクション:
  - 現在のパスワード
  - 新しいパスワード
  - パスワード確認
- 設定セクション:
  - テーマ選択（adult/child）
  - タイムゾーン選択
  - 通知設定（ON/OFF）
- アカウント削除セクション:
  - 削除ボタン（確認ダイアログ）

**API**:
- プロフィール取得: `GET /api/profile`
- プロフィール更新: `PUT /api/profile`
- パスワード変更: `PUT /api/profile/password`
- アカウント削除: `DELETE /api/profile`

**参照**:
- Bladeファイル: `/home/ktr/mtdev/resources/views/profile/edit.blade.php`
- OpenAPI: `/profile` エンドポイント

---

## 5. 画面遷移図（全体フロー）

```
┌─────────────────────────────────────────────────────────────┐
│                        アプリ起動                            │
└──────────────┬──────────────────────────────────────────────┘
               │
               ├─ トークン有り → タスク一覧画面（ログイン済み）
               └─ トークン無し → ログイン画面
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
                ログイン        新規登録        パスワード
                成功            成功            リセット
                    │               │               │
                    └───────────────┴───────────────┘
                                    │
                            ┌───────▼───────┐
                            │ タスク一覧画面 │ ← デフォルト画面
                            └───────┬───────┘
                                    │
        ┌───────────────────────────┼───────────────────────────┐
        │                           │                           │
    ハンバーガー                FAB                    タスクカード
    メニュー展開                タップ                    タップ
        │                           │                           │
        ▼                           ▼                           ▼
┌───────────────┐          ┌───────────────┐          ┌───────────────┐
│ドロワーメニュー│          │タスク作成画面  │          │タスク詳細画面  │
└───────┬───────┘          └───────────────┘          └───────┬───────┘
        │                                                      │
        ├─ タスクリスト → タスク一覧画面                      ├─ 編集 → タスク編集画面
        ├─ 承認待ち → 承認待ち一覧画面                        └─ 削除 → タスク一覧画面
        ├─ タグ管理 → タグ一覧画面
        ├─ 教師アバター → アバター管理画面
        ├─ 実績 → 実績レポート画面
        ├─ トークン → トークン購入画面
        ├─ サブスクリプション → サブスクリプション管理画面
        └─ 設定 → プロフィール・設定画面
```

---

## 6. アバター未作成時の特別処理

### 6.1 バナー表示仕様

**表示画面**: タスク一覧画面（`TaskListScreen.tsx`）

**表示条件**: 
```typescript
user.teacher_avatar_id === null
```

**デザイン仕様**:
- 位置: ヘッダー直下（タスクリスト上部）
- 背景色: 薄いピンク（`#FFF5F5`）
- アイコン: 人物アイコン（左側配置）
- テキスト: 「あなただけのサポートアバターを作成しましょう！」
- 右側: 矢印アイコン
- タップ時: アバター作成画面へ遷移
- 閉じるボタン: なし（作成完了まで常に表示）

**Web版との対応**:
- Web版では初回ログイン時にモーダル表示（`resources/views/components/avatar-setup-modal.blade.php`）
- モバイル版ではバナー形式に変更（画面占有率を抑える）

**実装**:
```typescript
// TaskListScreen.tsx 内
{!user.teacher_avatar_id && (
  <TouchableOpacity 
    style={styles.avatarBanner}
    onPress={() => navigation.navigate('AvatarCreate')}
  >
    <Icon name="person" size={24} color="#E91E63" />
    <Text style={styles.bannerText}>
      あなただけのサポートアバターを作成しましょう！
    </Text>
    <Icon name="chevron-right" size={24} color="#E91E63" />
  </TouchableOpacity>
)}
```

### 6.2 アバター作成後の処理

**作成完了時**:
1. アバター作成画面でジョブ開始リクエスト送信
2. 成功レスポンス受信
3. ユーザー情報を再取得（`GET /api/profile`）
4. タスク一覧画面へ自動遷移
5. バナーが非表示になることを確認

**生成中の表示**:
- アバター作成画面に「生成中...」表示
- タスク一覧画面に戻った場合、バナーは非表示（`teacher_avatar_id`が設定済み）

---

## 7. ディープリンク対応（Phase 2.B-8）

### 7.1 対応URL

| URL | 遷移先画面 | パラメータ | 備考 |
|-----|----------|----------|------|
| `myteacher://tasks` | タスク一覧画面 | - | - |
| `myteacher://tasks/{id}` | タスク詳細画面 | `id`: タスクID | - |
| `myteacher://notifications` | 通知一覧画面 | - | - |
| `myteacher://notifications/{id}` | 通知詳細 → 関連画面へ遷移 | `id`: 通知ID | 通知種別に応じて遷移先変更 |
| `myteacher://tokens/purchase` | トークン購入画面 | - | - |

### 7.2 通知からの遷移

**Push通知タップ時**:
```
通知タップ
  ↓
通知ペイロードから遷移先情報取得
  ↓
├─ タスク関連 → タスク詳細画面（task_id）
├─ 承認関連 → 承認待ち一覧画面
├─ トークン関連 → トークン購入画面
└─ その他 → 通知一覧画面
```

**実装**:
- Firebase Cloud Messaging（FCM）のデータペイロードに遷移先情報を含める
- `notification.data.type`, `notification.data.task_id` 等のキーを使用

---

## 8. エラーハンドリング・画面遷移

### 8.1 認証エラー時

**401エラー発生時**:
```
401エラー検知（Axios Interceptor）
  ↓
AsyncStorageからトークン削除
  ↓
ログイン画面へ強制遷移
  ↓
エラーメッセージ表示: 「セッションが切れました。再度ログインしてください。」
```

### 8.2 ネットワークエラー時

**オフライン時**:
```
API呼び出し失敗（Network Error）
  ↓
エラーメッセージ表示: 「ネットワーク接続を確認してください。」
  ↓
リトライボタン表示（オプション）
```

### 8.3 404エラー時（リソース不存在）

**タスク詳細画面で404発生時**:
```
404エラー検知
  ↓
エラーメッセージ表示: 「タスクが見つかりません。」
  ↓
タスク一覧画面へ自動遷移（3秒後）
```

### 8.4 エラーハンドリング実装状況

**実装完了日**: 2025-12-09

**実装内容**:

1. **Navigation Reference Utility**（`/home/ktr/mtdev/mobile/src/utils/navigationRef.ts`）
   - `createNavigationContainerRef()`でグローバルナビゲーション参照を作成
   - `navigate()`: 任意の画面への遷移関数
   - `resetTo()`: ナビゲーションスタックをリセットして画面遷移

2. **401エラー時の自動ログアウト**（`/home/ktr/mtdev/mobile/src/services/api.ts:40-59`）
   ```typescript
   // AsyncStorageからトークン削除
   await AsyncStorage.removeItem('auth_token');
   
   // Alert表示（英語）
   Alert.alert(
     'Session Expired',
     'Your session has expired. Please login again.',
     [{ text: 'OK', onPress: () => resetTo('Login') }]
   );
   ```

3. **404エラー時の自動遷移**（`/home/ktr/mtdev/mobile/src/services/api.ts:60-73`）
   ```typescript
   Alert.alert(
     'Not Found',
     'The requested resource was not found. You will be redirected to the task list.',
     [{
       text: 'OK',
       onPress: () => {
         setTimeout(() => resetTo('TaskList'), 3000);
       }
     }]
   );
   ```

4. **ネットワークエラー時のリトライ機能**（`/home/ktr/mtdev/mobile/src/services/api.ts:74-103`）
   ```typescript
   Alert.alert(
     'Network Error',
     'Please check your internet connection and try again.',
     [
       {
         text: 'Retry',
         onPress: () => api.request(error.config!)
       },
       { text: 'Cancel', style: 'cancel' }
     ]
   );
   ```

**テスト結果**:
- ✅ 全テストスイート成功: 54/54
- ✅ 全テストケース成功: 1036/1041（5件スキップ）
- ✅ jest.setup.jsへのnavigationRefモック追加完了

**影響ファイル**:
- `/home/ktr/mtdev/mobile/src/utils/navigationRef.ts`（新規作成）
- `/home/ktr/mtdev/mobile/src/navigation/AppNavigator.tsx`（navigationRef統合）
- `/home/ktr/mtdev/mobile/src/services/api.ts`（エラーハンドリング実装）
- `/home/ktr/mtdev/mobile/src/types/api.types.ts`（User型拡張）
- `/home/ktr/mtdev/mobile/jest.setup.js`（navigationRefモック追加）

---

## 9. パフォーマンス要件

### 9.1 画面遷移速度

- 画面遷移: 300ms以内（アニメーション含む）
- API応答待ち: ローディング表示（1秒以上かかる場合）

### 9.2 リスト表示最適化

- 無限スクロール: 20件ずつ取得
- FlatListの`windowSize`設定: 10（メモリ最適化）
- 画像の遅延読み込み: `expo-image`使用（キャッシュ有効）

---

## 10. テーマ対応（adult/child）

### 10.1 表示テキストの切り替え

| 画面要素 | adultテーマ | childテーマ |
|---------|-----------|-----------|
| タスクリスト | タスクリスト | ToDo |
| タグ管理 | タグ管理 | タグ |
| 教師アバター | 教師アバター | サポートアバター |
| トークン | トークン | コイン |

### 10.2 テーマ設定取得

**API**: `GET /api/profile` のレスポンスに `theme` フィールドを含む

**実装**:
```typescript
const themeText = {
  taskList: user.theme === 'adult' ? 'タスクリスト' : 'ToDo',
  avatar: user.theme === 'adult' ? '教師アバター' : 'サポートアバター',
  token: user.theme === 'adult' ? 'トークン' : 'コイン',
};
```

---

## 11. 将来対応（Phase 3以降）

### 11.1 管理者機能（Phase 3）

- ユーザー管理画面
- トークンパッケージ設定画面
- トークン統計画面
- 課金履歴画面
- ポータルサイト管理画面

### 11.2 グループ機能拡張（Phase 3）

- グループ作成画面
- グループ招待機能
- グループチャット機能

### 11.3 オフライン対応（Phase 4）

- オフラインデータキャッシュ
- 同期機能
- コンフリクト解決

---

## 12. 実装ファイル

### 12.1 画面コンポーネント

```
/home/ktr/mtdev/mobile/src/screens/
├── auth/
│   ├── LoginScreen.tsx
│   └── RegisterScreen.tsx
├── tasks/
│   ├── TaskListScreen.tsx（デフォルト画面）
│   ├── TaskDetailScreen.tsx
│   ├── CreateTaskScreen.tsx
│   ├── EditTaskScreen.tsx
│   └── TaskApprovalScreen.tsx
├── groups/
│   ├── GroupListScreen.tsx
│   ├── GroupDetailScreen.tsx
│   └── GroupMembersScreen.tsx
├── profile/
│   ├── ProfileScreen.tsx
│   └── SettingsScreen.tsx
├── avatars/
│   ├── AvatarListScreen.tsx
│   └── AvatarCreateScreen.tsx
├── notifications/
│   └── NotificationListScreen.tsx
├── tokens/
│   ├── TokenBalanceScreen.tsx
│   └── PurchaseScreen.tsx
└── reports/
    ├── MonthlyReportScreen.tsx
    └── PerformanceScreen.tsx
```

### 12.2 ナビゲーション

```
/home/ktr/mtdev/mobile/src/navigation/
├── AppNavigator.tsx（ルートナビゲーター）
├── AuthStack.tsx（認証スタック）
└── DrawerNavigator.tsx（ハンバーガーメニュー）
```

### 12.3 共通コンポーネント

```
/home/ktr/mtdev/mobile/src/components/
├── common/
│   ├── Header.tsx（ヘッダー）
│   ├── DrawerContent.tsx（ドロワーコンテンツ）
│   └── AvatarBanner.tsx（アバター未作成バナー）
└── tasks/
    └── TaskCard.tsx（タスクカード）
```

---

## 13. 参考資料

- **Phase 2実装計画**: `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md`
- **モバイル開発規則**: `/home/ktr/mtdev/docs/mobile/mobile-rules.md`
- **OpenAPI仕様**: `/home/ktr/mtdev/docs/api/openapi.yaml`
- **Web版サイドバー**: `/home/ktr/mtdev/resources/views/components/layouts/sidebar.blade.php`
- **Web版ルート定義**: `/home/ktr/mtdev/routes/web.php`
- **タスク要件定義**: `/home/ktr/mtdev/definitions/Task.md`
- **アバター要件定義**: `/home/ktr/mtdev/definitions/AvatarDefinition.md`
- **トークン要件定義**: `/home/ktr/mtdev/definitions/Purchase.md`

---

## 14. 質疑応答履歴

### Q1: ホーム画面の位置付けと機能
**質問**: モバイル版のホーム画面には何を表示すべきか？  
**回答**: ログイン後はタスク一覧画面に遷移。ホーム画面はWebアプリのサイドバーと同じ内容（ハンバーガーメニュー）を表示。

### Q2: 画面遷移の起点
**質問**: ログイン後の最初の画面はどこか？  
**回答**: 
- アバター未生成: タスク一覧画面（バナー表示）
- アバター生成済: タスク一覧画面

### Q3: ナビゲーション方式
**質問**: タブナビゲーションかドロワーナビゲーションか？  
**回答**: ハンバーガーメニュー（ドロワー）。Webアプリの狭幅時と同等。

### Q4: デザイン参照の優先順位
**質問**: どの画面サイズを基準にするか？  
**回答**: スマートフォン幅（375px程度）を基準にする。

### Q5: Phase 2.Bの範囲
**質問**: 全画面をリニューアル対象とするか？  
**回答**: Phase 2.B-2〜2.B-8の全画面をリニューアル対象とする。

### Q6: アバター未生成時の初期画面
**質問**: 強制遷移パターンか案内パターンか？  
**回答**: 案内パターン。タスク一覧画面上部にバナー表示。タップでアバター編集画面へ遷移。アバター未作成でも他機能は利用可能。
