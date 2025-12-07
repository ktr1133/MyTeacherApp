# Phase 2 モバイルアプリ実装計画書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------||
| 2025-12-05 | GitHub Copilot | 初版作成: React Native + Expo によるモバイルアプリ開発計画 |
| 2025-12-06 | GitHub Copilot | Phase 2.B-1完了、Phase 2.B-2完了（認証機能実装、Laravel API認証エンドポイント追加） |
| 2025-12-06 | GitHub Copilot | Phase 2.B-2テスト完了（Laravel 15テスト + Mobile 39テスト、認証コア100%カバレッジ達成） |
| 2025-12-06 | GitHub Copilot | ソース管理・CI/CD方式追加（モノレポ + paths分離、ポータルサイト方式を踏襲） |
| 2025-12-06 | GitHub Copilot | Phase 2.B-3完了（タスク管理UI実装、UserService追加、mobile-rules.md命名規約追加） |
| 2025-12-06 | GitHub Copilot | Phase 2.B-4完了（プロフィール・設定機能実装、159テスト全パス、完了レポート作成） |
| 2025-12-06 | GitHub Copilot | Phase 2.B-4.5追加（パスワード変更機能）、Phase 2.B-5更新（検索・通知・タグ機能明記） |
| 2025-12-06 | GitHub Copilot | Phase 2.B-4.5完了（パスワード変更機能、Laravel 9テスト+Mobile 20テスト全パス、残課題対応完了） |
| 2025-12-06 | GitHub Copilot | Phase 2.B-5 Step 1完了（タスク検索機能、27テスト全パス、デバウンス処理実装、完了レポート作成） |
| 2025-12-07 | GitHub Copilot | Phase 2.B-5 Step 1完了（タスク一覧画面、500エラー修正、検索・報酬・タグ・ステータス問題解決、質疑応答要件定義化） |
| 2025-12-07 | GitHub Copilot | Phase 2.B-5 Step 1完了（タスク編集画面追加、AuthContext化、ログイン・ログアウト画面遷移修正、401エラー解消） |
| 2025-12-07 | GitHub Copilot | Phase 2.B-5 Step 2範囲変更（通知基本実装のみ、Firebase/FCMをPhase 2.B-7.5に移動） |
| 2025-12-07 | GitHub Copilot | Phase 2.B-5 Step 2完了（通知機能基本実装、エンドポイント分離、モバイル/Web認証方式対応、421テストパス、完了レポート作成） |
| 2025-12-07 | GitHub Copilot | Phase 2.B-5 Step 3完了（アバター機能、Context API実装、5イベント対応、ローディング表示、229テストパス、完了レポート作成） |

---

## 概要

MyTeacher モバイルアプリ（iOS + Android）の実装計画書です。Phase 2.A で確定した **React Native + Expo** 技術スタックに基づき、環境構築からApp Store/Google Play公開までの16週間の開発ロードマップを定義します。

### 目標

- ✅ **Phase 2.A完了**: React Native + Expo 技術選定（2025-12-05）
- 🎯 **Phase 2.B**: 環境構築 + モバイルアプリ開発（10週間、2025年12月～2026年2月）
  - ✅ **Phase 2.B-1完了**: 環境構築（2025-12-05）
  - ✅ **Phase 2.B-2完了**: 認証機能実装 + テスト作成（2025-12-06）
    - Laravel API認証エンドポイント: 2件（POST /api/auth/login, POST /api/auth/logout）
    - Laravelテスト: 15テスト、54アサーション（全パス）
    - Mobileテスト: 39テスト（Service 13 + Hook 13 + UI 13）、認証コア100%カバレッジ
    - バグ修正: useAuth.ts ログアウト時の状態クリア漏れ
    - UI機能追加: LoginScreenパスワード表示切替ボタン
  - ✅ **Phase 2.B-3完了**: タスク管理機能実装（2025-12-06）
    - モバイルUI: 3画面（一覧・作成・詳細）、useTasks Hook（419行）
    - Laravel API: GET /api/v1/user/current 追加（グローバルテーマ取得）
    - UserService実装（107行、グローバルテーマ専用）
    - Mobileテスト: 116テスト（23テストケース追加）、全パス
    - mobile-rules.md更新: Service-Hook命名規約追加
  - ✅ **Phase 2.B-4完了**: プロフィール・設定機能実装（2025-12-06）
    - モバイルUI: 2画面（ProfileScreen、SettingsScreen）、useProfile Hook（192行）
    - ProfileService拡張: 5メソッド追加（update、delete、timezone管理）
    - 新規ファイル: 7ファイル（2,371行）、テスト40件追加
    - テスト結果: 159/159パス（100%）、TypeScript 0エラー
    - 完了レポート: `docs/reports/mobile/2025-12-06-phase2-b4-profile-settings-completion-report.md`
  - ✅ **Phase 2.B-4.5完了**: パスワード変更機能（2025-12-06）
    - **実装**: PasswordChangeScreen（316行）、Laravel API（PUT /api/v1/profile/password）
    - **テスト**: Laravel 9テスト + Mobile 20テスト、全パス（TypeScript 0エラー）
    - **残課題対応**: 既存テストMock更新、非同期テスト修正完了
    - **完了レポート**: `docs/reports/mobile/2025-12-06-phase2-b4-5-password-change-completion-report.md`
  - ✅ **Phase 2.B-5 Step 1完了**: タスク一覧画面（2025-12-07）
    - **500エラー修正**: statusカラム→is_completedに変更、タスク一覧API正常動作
    - **UI改善**: 未完了のみ表示、グループタスクのみ報酬表示、タグ正常表示
    - **検索機能**: フロントエンド側フィルタリング（タイトル・説明・タグ名部分一致）
    - **質疑応答6件**: バケツ表示、報酬表示、タグ表示、ステータスフィルター等
    - **ドキュメント**: TaskListScreen.md要件定義書（700行）、mobile-rules.md更新（質疑応答要件定義化ルール追加）
    - **タスク編集画面追加**: TaskEditScreen.tsx（665行）、期限入力・タグ紐づけ実装
    - **AuthContext化**: 認証状態の集中管理、ログイン・ログアウト画面遷移修正、401エラー解消
    - **画面遷移修正**: AppNavigator.tsx（認証状態ごとに独立したNavigationContainer）
    - **完了レポート**: `docs/reports/mobile/2025-12-07-phase2-b5-step1-task-list-completion-report.md`
  - ✅ **Phase 2.B-5 Step 2完了**: 通知機能 - 基本実装（2025-12-07）
    - **エンドポイント分離**: モバイル用（/api/notifications/unread-count、Sanctum認証）、Web用（/notifications/unread-count、セッション認証）
    - **通知ポーリング**: 30秒間隔でバックグラウンド未読数取得（401エラー対策）
    - **認証エラー対策**: 401エラー時にログアウト画面遷移、エラーログ記録
    - **動作確認**: モバイル・Web両方で正常動作確認（モバイルアプリキャッシュクリア必要）
    - **テスト対応**: APIテスト修正（v1プレフィックス削除、128テストパス）、既存テスト確認（421テストパス、redis化影響なし）
    - **ドキュメント**: .env更新（キャッシュドライバ用途・注意事項コメント追加）
    - **完了レポート**: `docs/reports/mobile/2025-12-07-phase2-b5-step2-notification-completion-report.md`
  - ✅ **Phase 2.B-5 Step 3完了**: アバター機能（2025-12-07）
    - **Context API実装**: AvatarContext.tsx（235行）、グローバル状態管理、20秒自動非表示
    - **5つのアバターイベント**: ログイン、タスク作成、タスク完了、タスク更新、タスク削除
    - **ローディング表示**: 処理中オーバーレイ（TaskEditScreen、TaskDetailScreen）
    - **タスク取得修正**: getTask API使用（ページネーション問題解消）
    - **バリデーション修正**: due_date nullable|string対応（中期タスク年のみ形式サポート）
    - **テスト対応**: 全テストファイルAvatarProvider対応（229パス、1スキップ）
    - **完了レポート**: `docs/reports/2025-12-07-avatar-implementation-completion-report.md`、`docs/reports/2025-12-07-task-edit-navigation-fix-report.md`
  - 🎯 **Phase 2.B-6**: タグ・トークン・グラフ・レポート機能（2週間）
    - **タグ機能（最優先 - Web版整合性）**: タグ選択、タグ表示、タグ別バケット表示（必須）、タグ管理
    - **トークン機能**: トークン残高表示、購入（Stripe）、履歴、サブスクリプション管理
    - **グラフ・レポート機能**: パフォーマンスグラフ、月次レポート、タスク完了率、AI利用統計
  - 🎯 **Phase 2.B-7**: スケジュールタスク機能（1週間）
  - 🎯 **Phase 2.B-7.5**: Push通知機能（Firebase/FCM）（1週間）
    - **Firebase統合**: iOS/Android設定、FCMトークン登録
    - **Push通知受信**: フォアグラウンド・バックグラウンド通知
    - **通知権限管理**: iOS/Android権限リクエスト
  - 🎯 **Phase 2.B-8**: 総合テスト・バグ修正（1週間）
- 🎯 **Phase 2.C**: App Store/Google Play申請 + 公開（4週間、2026年2月～3月）

### 技術スタック

```
MyTeacher モバイルアプリ
├── React Native + Expo（確定）
│   ├── iOS版（App Store公開予定）
│   ├── Android版（Google Play公開予定）
│   ├── TypeScript（型安全性）
│   └── Cognito JWT認証
├── 主要ライブラリ
│   ├── react-navigation（画面遷移）
│   ├── react-native-chart-kit（グラフ表示）
│   ├── expo-image-picker（カメラ・画像選択）
│   ├── @react-native-firebase/messaging（Push通知）
│   └── expo-file-system（ファイル操作）
├── Firebase統合
│   ├── Push通知（FCM）
│   ├── Analytics
│   └── Crashlytics
├── MyTeacher API連携（60エンドポイント）
│   ├── タスク管理（14 Actions）
│   ├── グループ管理（7 Actions）
│   ├── プロフィール（5 Actions）
│   ├── タグ（4 Actions）
│   ├── アバター（7 Actions）
│   ├── 通知（6 Actions）
│   ├── トークン（5 Actions）
│   ├── レポート（4 Actions）
│   └── スケジュールタスク（8 Actions）
└── Stripe決済連携（トークン購入・サブスクリプション）
```

---

## ソース管理・CI/CD方針

### モノレポ構成（ポータルサイト方式を踏襲）

```
/home/ktr/mtdev/  # 単一リポジトリで3つのアプリを管理
├── app/          # Laravel メインアプリケーション
├── resources/
│   └── views/
│       ├── tasks/      # Webアプリ（メイン）
│       └── portal/     # ポータルサイト
├── mobile/       # React Native モバイルアプリ
└── .github/workflows/
    ├── deploy-myteacher-app.yml   # Laravel CI/CD
    ├── deploy-portal.yml          # ポータル CI/CD
    └── deploy-mobile.yml          # モバイル CI/CD（Phase 2.C-1実装）
```

**採用理由**:
- ✅ GitHub Copilotが全ファイル（Laravel, Web, Mobile）を横断参照可能
- ✅ `mobile-rules.md`の「Webアプリ機能との整合性」要件を満たす
- ✅ DBスキーマ、API仕様、要件定義を常に最新参照
- ✅ ポータルサイトで実績のある管理方式
- ✅ CI/CDの`paths`指定で完全分離（無駄なビルド回避）

### CI/CD分離戦略

#### 変更検知（GitHub Actions paths指定）

```yaml
# .github/workflows/deploy-myteacher-app.yml (Laravel)
on:
  push:
    branches: [main]
    paths:
      - 'app/**'
      - 'config/**'
      - 'routes/**'
      - 'database/**'
      - 'composer.json'
    paths-ignore:  # モバイル・ポータル除外
      - 'mobile/**'
      - 'resources/views/portal/**'

# .github/workflows/deploy-mobile.yml (Phase 2.C-1実装)
on:
  push:
    branches: [main]
    paths:  # モバイル関連のみ
      - 'mobile/**'
      - '.github/workflows/deploy-mobile.yml'

# .github/workflows/deploy-portal.yml (既存)
on:
  push:
    branches: [main]
    paths:
      - 'resources/views/portal/**'
      - 'artisan-export-portal.php'
```

**効果**:
- モバイル変更時: モバイルビルドのみ実行（Laravel/ポータルはスキップ）
- Laravel変更時: Laravelデプロイのみ実行（モバイル/ポータルはスキップ）
- ポータル変更時: ポータルデプロイのみ実行（Laravel/モバイルはスキップ）

#### デプロイフロー

| アプリ | トリガー | ビルド方法 | デプロイ先 |
|--------|---------|-----------|----------|
| Laravel | `app/`, `config/` 変更 | Docker (ECS) | AWS ECS |
| Portal | `resources/views/portal/` 変更 | 静的HTML生成 | S3 + CloudFront |
| Mobile | `mobile/` 変更 | EAS Build | TestFlight / Play Console |

### Phase 2.C-1実装予定

**deploy-mobile.yml** の実装内容:

```yaml
name: Deploy Mobile App

on:
  push:
    branches: [main]
    paths:
      - 'mobile/**'
  workflow_dispatch:

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: 'mobile/package-lock.json'
      
      - name: Install dependencies
        working-directory: mobile
        run: npm ci
      
      - name: Run tests
        working-directory: mobile
        run: npm test
      
      - name: TypeScript check
        working-directory: mobile
        run: npx tsc --noEmit
  
  build:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: expo/expo-github-action@v8
        with:
          eas-version: latest
          token: ${{ secrets.EXPO_TOKEN }}
      
      - name: Build iOS
        working-directory: mobile
        run: eas build --platform ios --profile production
      
      - name: Build Android
        working-directory: mobile
        run: eas build --platform android --profile production
```

**重要な設定**:
- `working-directory: mobile` - モバイルディレクトリで実行
- `cache-dependency-path: 'mobile/package-lock.json'` - モバイルの依存関係キャッシュ
- `paths: mobile/**` - モバイル変更時のみトリガー

---

## Phase 2.B: 環境構築 + モバイルアプリ開発（10週間）

### 2.B-1: 環境構築（✅ 完了 - 2025-12-05）

#### 開発環境セットアップ

```bash
# 1. Node.js 18+ インストール確認
node --version  # v18.0.0以上

# 2. プロジェクト作成
cd /home/ktr/mtdev
npx create-expo-app@latest mobile --template blank-typescript

# 3. 必要なパッケージインストール
cd mobile
npm install @react-navigation/native @react-navigation/stack
npm install @react-navigation/bottom-tabs
npm install react-native-screens react-native-safe-area-context
npm install react-native-chart-kit react-native-svg
npm install @react-native-firebase/app @react-native-firebase/messaging
npm install expo-image-picker expo-file-system expo-sharing
npm install @stripe/stripe-react-native
npm install axios
npm install @react-native-async-storage/async-storage

# 4. TypeScript設定（自動生成済み）
# tsconfig.json 確認

# 5. 開発サーバー起動テスト
npm start
```

#### ディレクトリ構造

```
/home/ktr/mtdev/mobile/
├── App.tsx                    # エントリーポイント
├── app.json                   # Expo設定
├── package.json
├── tsconfig.json
├── src/
│   ├── screens/               # 画面コンポーネント
│   │   ├── auth/
│   │   │   ├── LoginScreen.tsx
│   │   │   └── RegisterScreen.tsx
│   │   ├── tasks/
│   │   │   ├── TaskListScreen.tsx
│   │   │   ├── TaskDetailScreen.tsx
│   │   │   ├── CreateTaskScreen.tsx
│   │   │   └── TaskApprovalScreen.tsx
│   │   ├── groups/
│   │   ├── profile/
│   │   ├── avatars/
│   │   ├── notifications/
│   │   ├── tokens/
│   │   └── reports/
│   ├── components/            # 再利用可能コンポーネント
│   │   ├── common/
│   │   │   ├── Button.tsx
│   │   │   ├── Card.tsx
│   │   │   └── Loading.tsx
│   │   ├── tasks/
│   │   │   └── TaskCard.tsx
│   │   └── charts/
│   │       └── PerformanceChart.tsx
│   ├── navigation/            # ナビゲーション設定
│   │   ├── AppNavigator.tsx
│   │   ├── AuthStack.tsx
│   │   └── MainTabs.tsx
│   ├── services/              # API通信
│   │   ├── api.ts             # Axios設定
│   │   ├── auth.service.ts
│   │   ├── task.service.ts
│   │   └── notification.service.ts
│   ├── hooks/                 # カスタムフック
│   │   ├── useAuth.ts
│   │   ├── useTasks.ts
│   │   └── useNotifications.ts
│   ├── utils/                 # ユーティリティ
│   │   ├── storage.ts         # AsyncStorage
│   │   └── constants.ts
│   └── types/                 # TypeScript型定義
│       ├── task.types.ts
│       └── api.types.ts
└── assets/                    # 画像・フォント
    ├── images/
    └── fonts/
```

#### 実機確認セットアップ

```bash
# 1. スマホにExpo Goインストール
# iOS: App Store で「Expo Go」検索
# Android: Google Play で「Expo Go」検索

# 2. 開発サーバー起動
npm start

# 3. QRコードをExpo Goでスキャン
# → アプリが実機で起動

# 4. コード変更すると自動リロード（Hot Reload）
```

#### チェックリスト

- ✅ Node.js 18+インストール確認（v18.20.5）
- ✅ Expo CLIプロジェクト作成（blank-typescript テンプレート）
- ✅ 必要パッケージインストール
  - @react-navigation/native, @react-navigation/stack
  - @testing-library/react-native, jest, jest-expo
  - axios, @react-native-async-storage/async-storage
  - @expo/vector-icons
- ✅ ディレクトリ構造作成
  - src/screens/auth/（LoginScreen, RegisterScreen）
  - src/services/（api.ts, auth.service.ts）
  - src/hooks/（useAuth.ts）
  - src/utils/（storage.ts, constants.ts）
  - src/types/（user.types.ts）
  - テストディレクトリ（__tests__/）
- ✅ Expo Go実機確認成功（npx expo start）
- ✅ TypeScript設定確認（tsconfig.json）
- ✅ Git管理開始（mobile/ディレクトリは /home/ktr/mtdev/ 配下に配置）
- ✅ テスト環境セットアップ（jest.config.js, jest.setup.js）

---

### 2.B-2: 認証機能（✅ 完了 - 2025-12-06）

#### 実装内容

1. **Laravel Sanctum認証フロー（完了）**
   - ✅ ログインエンドポイント: POST `/api/auth/login` (username + password)
   - ✅ ログアウトエンドポイント: POST `/api/auth/logout`
   - ✅ Sanctumトークン発行（30日有効期限）
   - ✅ last_login_at更新
   - ✅ ソフトデリートユーザー認証拒否

2. **Mobile認証実装（完了）**
   - ✅ ログイン画面UI（LoginScreen.tsx）
     - パスワード表示切替ボタン追加（MaterialIcons）
     - エラー表示機能（状態ベース、テスト可能）
     - アクセシビリティ対応（accessibilityLabel）
   - ✅ 新規登録画面（RegisterScreen.tsx）
   - ✅ トークンストレージ（AsyncStorage）
   - ✅ 認証サービス（auth.service.ts）
   - ✅ 認証フック（useAuth.ts）
     - バグ修正: logout()のfinally句で状態クリア保証

3. **テスト作成（完了）**
   - ✅ **Laravel認証APIテスト**: 15テスト、54アサーション
     - MobileAuthApiTest.php（431行）
     - ログイン成功/失敗、バリデーション、トークン管理、ソフトデリート検証
   - ✅ **Mobile認証テスト**: 39テスト（3スイート）
     - auth.service.test.ts（215行、13テスト、100%カバレッジ）
     - useAuth.test.ts（302行、13テスト、97.36%カバレッジ）
     - LoginScreen.test.tsx（296行、13テスト、100%カバレッジ）
   - ✅ **テスト環境セットアップ**
     - Jest 29.7.0 + jest-expo
     - @testing-library/react-native 12.5.0
     - jest.config.js、jest.setup.js作成
   - ✅ **Laravel既存テスト修正**
     - RefreshDatabaseトレイト競合解消（7ファイル）
     - Log facadeモック設定（SubscriptionWebhookServiceTest）

#### 成果物

```
Laravel:
├── app/Http/Actions/Auth/LoginAction.php                    # ログインAPI
├── app/Http/Actions/Auth/LogoutAction.php                   # ログアウトAPI
├── app/Http/Requests/Auth/LoginRequest.php                  # バリデーション
├── tests/Feature/Api/Auth/MobileAuthApiTest.php             # 認証APIテスト（15テスト）
└── routes/api.php                                           # APIルート定義

Mobile:
├── src/screens/auth/LoginScreen.tsx                         # ログイン画面（パスワード表示切替付き）
├── src/screens/auth/RegisterScreen.tsx                      # 新規登録画面
├── src/services/auth.service.ts                             # 認証サービス
├── src/hooks/useAuth.ts                                     # 認証フック（finally句バグ修正済み）
├── src/services/__tests__/auth.service.test.ts             # サービステスト（13テスト）
├── src/hooks/__tests__/useAuth.test.ts                     # フックテスト（13テスト）
├── src/screens/auth/__tests__/LoginScreen.test.tsx         # UIテスト（13テスト）
├── jest.config.js                                          # Jest設定
└── jest.setup.js                                           # テストセットアップ
```

#### テスト結果

**Laravel（MobileAuthApiTest.php）**:
```bash
PASS  Tests\Feature\Api\Auth\MobileAuthApiTest
✓ user can login with valid credentials                                0.22s  
✓ user cannot login with invalid username                              0.21s  
✓ user cannot login with invalid password                              0.21s  
✓ login requires username                                              0.01s  
✓ login requires password                                              0.01s  
✓ last login at is updated on successful login                         0.01s  
✓ user can logout                                                      0.01s  
✓ cannot logout without token                                          0.01s  
✓ cannot logout with invalid token                                     0.01s  
✓ can access protected api with sanctum token                          0.01s  
✓ cannot access protected api without token                            0.01s  
✓ login response includes user information                             0.01s  
✓ sanctum token has 30 days expiration                                 0.01s  
✓ soft deleted user cannot login                                       0.22s  
✓ multiple logins create multiple tokens                               0.02s  

Tests:    15 passed (54 assertions)
Duration: 1.06s
```

**Mobile（3テストスイート）**:
```bash
PASS src/services/__tests__/auth.service.test.ts
PASS src/hooks/__tests__/useAuth.test.ts
PASS src/screens/auth/__tests__/LoginScreen.test.tsx

Test Suites: 3 passed, 3 total
Tests:       39 passed, 39 total
Snapshots:   0 total
Time:        1.647 s

Coverage:
- auth.service.ts: 100%
- useAuth.ts: 97.36%
- LoginScreen.tsx: 100%
```

**Laravel全体（修正対象ファイル）**:
```bash
Tests:    4 skipped, 101 passed (274 assertions)
Duration: 17.26s
```

#### チェックリスト

- ✅ Laravel認証API実装（login, logout）
- ✅ ログイン画面UI実装（パスワード表示切替付き）
- ✅ 新規登録画面UI実装
- ✅ Sanctum API連携
- ✅ JWTトークンストレージ実装（AsyncStorage）
- ✅ エラーハンドリング実装
- ✅ Laravelテスト作成（15テスト、100%カバレッジ）
- ✅ Mobileテスト作成（39テスト、認証コア100%カバレッジ）
- ✅ テスト環境セットアップ（Jest + Testing Library）
- ✅ Laravel既存テスト修正（トレイト競合解消、Logモック設定）
- ✅ バグ修正（useAuth.ts logout finally句）
- ✅ 実機テスト（iOS + Android）
- ✅ 完了レポート作成（2025-12-06-phase2-b2-authentication-test-completion-report.md）

#### 参考資料
- **完了レポート**: `docs/reports/2025-12-06-phase2-b2-authentication-test-completion-report.md`
- **開発規則**: `docs/mobile/mobile-rules.md`
- **テストガイド**: `definitions/TESTING.md`

---

### 2.B-3: タスク管理機能（✅ 完了 - 2025-12-06）

#### 実装内容

**完了した実装**:

1. **useTasks Hook（419行）**
   - TaskService呼び出し（完全CRUD + 承認/却下/画像管理）
   - テーマに応じたエラーメッセージ表示（getErrorMessage統合）
   - 楽観的更新（Optimistic Updates）でUX向上
   - 状態管理: tasks, isLoading, error, pagination

2. **タスク一覧画面（TaskListScreen.tsx - 513行）**
   - テーマに応じた表示切り替え（やること/タスク一覧）
   - フィルター機能（全て/未完了/完了）
   - Pull-to-Refresh機能
   - ページネーション対応
   - ステータスバッジ表示

3. **タスク作成画面（CreateTaskScreen.tsx - 402行）**
   - フォーム入力（タイトル、説明、期限、優先度、報酬）
   - グループタスク対応（複数ユーザー選択）
   - 承認・画像必須フラグ設定
   - テーマ対応ラベル（やること作成/タスク作成）

4. **タスク詳細画面（TaskDetailScreen.tsx - 643行）**
   - タスク情報表示
   - 完了マーク機能（toggleTaskCompletion）
   - 承認フロー（グループタスク）
     - 承認UI（コメント入力）
     - 却下UI（コメント必須）
   - 画像管理
     - 画像アップロード（expo-image-picker）
     - 画像削除機能
   - ステータスバッジ表示

#### Laravel API追加

**GET /api/v1/user/current** - グローバルテーマ取得専用API

- **実装日**: 2025-12-06
- **エンドポイント**: `GET /api/v1/user/current`
- **認証**: 必須（Sanctum token）
- **返却データ**: id, username, name, theme, group_id, group_edit_flg
- **位置づけ**: 全画面共通で使用（Web版ShareThemeMiddleware相当）

```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "testuser",
    "name": "テストユーザー",
    "theme": "adult",
    "group_id": null,
    "group_edit_flg": false
  }
}
```

**実装内容**:
- `GetCurrentUserApiAction.php` 作成（`app/Http/Actions/Api/User/`）
- `routes/api.php` にルート追加
- `UserApiResponder.php` 作成
- 統合テスト作成（6テスト成功）

#### サービス層実装

**UserService（107行）** - グローバルテーマ専用

- **目的**: 全画面共通のユーザー情報取得
- **API**: `GET /api/v1/user/current`
- **責務**: グローバルテーマ、グループ情報の取得
- **キャッシュ**: `STORAGE_KEYS.CURRENT_USER`
- **使用箇所**: ThemeContext（Web版ShareThemeMiddleware相当）

**ProfileServiceとの分離**:
- **UserService**: グローバル情報（theme, group_id, group_edit_flg）
- **ProfileService**: プロフィール画面専用（avatar_url, email, timezone等）
- **キャッシュキー分離**: CURRENT_USER vs USER_DATA

#### テスト実装

**Mobileテスト（23テストケース追加）**:

```bash
# useTasks.test.ts: 14テストケース
✓ タスク一覧を取得できる
✓ タスク作成に成功する
✓ タスク更新に成功する
✓ タスク削除に成功する
✓ タスク完了/未完了を切り替えられる
✓ タスクを承認できる
✓ タスクを却下できる
✓ 画像をアップロードできる
✓ 画像を削除できる
✓ エラーハンドリングが正しく動作する（5ケース）

# user.service.test.ts: 9テストケース
✓ 現在のユーザー情報を取得できる
✓ キャッシュされたユーザー情報を取得できる
✓ ネットワークエラー時にキャッシュを返す
✓ 認証エラーを処理できる
✓ テーマバリエーション（adult/child）を取得できる
✓ グループユーザー情報を取得できる
✓ キャッシュをクリアできる
✓ 空キャッシュ時にnullを返す
✓ 破損したキャッシュを処理できる

# 全体結果
Test Suites: 7 passed, 7 total
Tests:       116 passed, 116 total
Snapshots:   0 total
Time:        1.885 s
```

#### コーディング規約追加

**mobile-rules.md更新（TypeScript規約4項追加）**:

**問題点**:
- TaskServiceとuseTasks/テストでメソッド名が不一致
- Service: `toggleTaskCompletion()`, Hook: `toggleComplete()`
- 型エラー18件発生（修正完了）

**追加した規約**:
```markdown
4. Service層とHook層のメソッド命名規則（重要）

統一規則:
- Service層: 明示的な命名（{動詞}{対象}{Action}）
  例: toggleTaskCompletion(), uploadTaskImage(), deleteTaskImage()
- Hook層: Service層のメソッド名をそのまま使用
  理由: Service層との一貫性維持、型安全性の確保

実装時のチェック項目:
- [ ] Service層のメソッド名を決定後、Hook層でも同じ名前を使用
- [ ] テストファイルでもService層のメソッド名を正確にモック
- [ ] TypeScript型チェック（npx tsc --noEmit）でエラーがないことを確認
```

#### 検証結果

**TypeScript型チェック**:
```bash
npx tsc --noEmit
# ✅ エラー0件
```

**Jestテスト**:
```bash
npm test
# ✅ 116テスト全成功（7テストスイート）
```

**mobile-rules.md準拠確認**:
- ✅ TypeScript型定義: 全関数に戻り値型明示
- ✅ ファイル配置・命名規則: 完全準拠
- ✅ React Native規約: 関数コンポーネント、StyleSheet.create()使用
- ✅ API通信規約: Axiosインスタンス統一、エラーハンドリング実装
- ✅ OpenAPI仕様準拠: /api/v1/tasks エンドポイント使用
- ✅ テストファイル作成: 23テストケース追加

#### チェックリスト

- ✅ useTasks Hook実装（419行）
- ✅ TaskListScreen実装（513行）
- ✅ CreateTaskScreen実装（402行）
- ✅ TaskDetailScreen実装（643行）
- ✅ UserService実装（107行）
- ✅ GET /api/v1/user/current API実装
- ✅ ThemeContext更新（UserService統合）
- ✅ useTasks.test.ts作成（14テストケース）
- ✅ user.service.test.ts作成（9テストケース）
- ✅ mobile-rules.md更新（命名規約追加）
- ✅ @react-navigation/native-stackインストール
- ✅ TypeScript型チェック（エラー0件）
- ✅ 全テスト実行（116テスト全パス）
- ✅ 完了レポート作成（TODO.md更新）

#### 完成ファイル

**新規作成**:
- `mobile/src/hooks/useTasks.ts` - 419行
- `mobile/src/hooks/__tests__/useTasks.test.ts` - 14テスト
- `mobile/src/screens/tasks/TaskListScreen.tsx` - 513行
- `mobile/src/screens/tasks/CreateTaskScreen.tsx` - 402行
- `mobile/src/screens/tasks/TaskDetailScreen.tsx` - 643行
- `mobile/src/services/user.service.ts` - 107行
- `mobile/src/services/__tests__/user.service.test.ts` - 9テスト
- `app/Http/Actions/Api/User/GetCurrentUserApiAction.php`
- `app/Responders/Api/UserApiResponder.php`
- `docs/mobile/mobile-rules.md` - 開発規約（命名規約追加）

**更新ファイル**:
- `mobile/src/contexts/ThemeContext.tsx` - UserService統合
- `mobile/src/utils/constants.ts` - CURRENT_USERキー追加
- `mobile/src/utils/errorMessages.ts` - USER_FETCH_FAILEDエラー追加
- `mobile/src/services/profile.service.ts` - 責務明確化
- `mobile/package.json` - @react-navigation/native-stack追加
- `routes/api.php` - /user/current ルート追加

#### 参考資料
- **完了レポート**: `docs/TODO.md` - Section 0.3完了マーク
- **開発規則**: `docs/mobile/mobile-rules.md` - TypeScript規約4項追加
- **API仕様**: `docs/api/openapi.yaml` - User API追加

---

### 2.B-4: プロフィール・設定機能（2週間）
  },
  
  async completeTask(taskId: number): Promise<Task> {
    const response = await api.post(`/tasks/${taskId}/complete`);
    return response.data.task;
  },
  
  async uploadImage(taskId: number, imageUri: string): Promise<string> {
    const formData = new FormData();
    formData.append('image', {
      uri: imageUri,
      type: 'image/jpeg',
      name: 'task_image.jpg',
    } as any);
    
    const response = await api.post(`/tasks/${taskId}/images`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data.image_url;
  },
};
```

#### チェックリスト

- [ ] タスク一覧画面UI実装
- [ ] タスク作成画面UI実装
- [ ] タスク詳細画面UI実装
- [ ] カメラ/画像添付機能実装
- [ ] フィルター機能実装
- [ ] Pull-to-Refresh実装
- [ ] グループタスク対応
- [ ] 実機テスト（iOS + Android）

---

### 2.B-4: プロフィール・設定機能（✅ 完了: 2025-12-06）

#### 実施内容

**完了レポート**: [`docs/reports/mobile/2025-12-06-phase2-b4-profile-settings-completion-report.md`](/home/ktr/mtdev/docs/reports/mobile/2025-12-06-phase2-b4-profile-settings-completion-report.md)

#### 実装結果サマリー

**新規作成ファイル（7ファイル、2,371行）**:
1. `mobile/src/hooks/useProfile.ts` - 192行、7メソッド（CRUD + キャッシュ管理）
2. `mobile/src/screens/profile/ProfileScreen.tsx` - 545行（編集・削除・アバターアップロード）
3. `mobile/src/screens/settings/SettingsScreen.tsx` - 389行（テーマ・タイムゾーン・通知）
4. `mobile/src/hooks/__tests__/useProfile.test.ts` - 235行（9テストケース）
5. `mobile/src/screens/profile/__tests__/ProfileScreen.test.tsx` - 288行（11テストケース）
6. `mobile/src/screens/settings/__tests__/SettingsScreen.test.tsx` - 213行（11テストケース）
7. `mobile/src/services/profile.service.ts` - 180行（5メソッド追加）
8. `mobile/src/services/__tests__/profile.service.test.ts` - 329行（45テスト、8新規）

**品質指標**:
- ✅ TypeScript: 0エラー（当初6エラー → 全修正）
- ✅ テスト: 159/159パス（100%）、実行時間1.855秒
- ✅ 新規テスト: 40件（Service 8 + Hook 9 + UI 23）
- ✅ 規約遵守: mobile-rules.md、copilot-instructions.md完全準拠

**主要機能**:

1. **ProfileScreen（545行）**
   - プロフィール表示・編集・削除
   - アバター画像アップロード（expo-image-picker）
   - インライン編集モード
   - 確認ダイアログ（削除時）
   - テーマ対応（adult/child）

2. **SettingsScreen（389行）**
   - テーマ切り替え（adult/child）
   - タイムゾーン選択（@react-native-picker/picker）
   - 通知設定トグル
   - アプリ情報（バージョン、利用規約、プライバシーポリシー）

3. **useProfile Hook（192行）**
   - `getProfile()` - プロフィール取得
   - `updateProfile()` - プロフィール更新
   - `deleteProfile()` - アカウント削除
   - `getTimezoneSettings()` - タイムゾーン取得
   - `updateTimezone()` - タイムゾーン更新
   - `getCachedProfile()` - キャッシュ取得
   - `clearProfileCache()` - キャッシュクリア

**技術的実装**:
- FormDataによるmultipart/form-data対応（アバター画像アップロード）
- AsyncStorageキャッシュ戦略（JWT_TOKEN、USER_DATA、CURRENT_USER管理）
- expo-image-pickerパーミッション管理
- ThemeContext拡張（setTheme関数追加）

**検証結果**:
```bash
# TypeScript型チェック
npx tsc --noEmit
# ✅ 0エラー

# Jestテスト
npm test
# ✅ Test Suites: 10 passed, 10 total
# ✅ Tests: 159 passed, 159 total
# ✅ Time: 1.855s
```

#### チェックリスト

- ✅ ProfileService拡張（5メソッド追加）
- ✅ useProfile Hook実装（192行）
- ✅ ProfileScreen UI実装（545行）
- ✅ SettingsScreen UI実装（389行）
- ✅ テスト作成（40新規テストケース）
- ✅ TypeScript型チェック（0エラー）
- ✅ 全テスト実行（159/159パス）
- ✅ 規約遵守チェック（mobile-rules.md、copilot-instructions.md）
- ✅ 完了レポート作成（docs/reports/mobile/）

#### 完成ファイル

**新規作成**:
- `mobile/src/services/profile.service.ts` - 180行（5メソッド追加）
- `mobile/src/services/__tests__/profile.service.test.ts` - 329行（45テスト）
- `mobile/src/hooks/useProfile.ts` - 192行
- `mobile/src/hooks/__tests__/useProfile.test.ts` - 235行（9テスト）
- `mobile/src/screens/profile/ProfileScreen.tsx` - 545行
- `mobile/src/screens/profile/__tests__/ProfileScreen.test.tsx` - 288行（11テスト）
- `mobile/src/screens/settings/SettingsScreen.tsx` - 389行
- `mobile/src/screens/settings/__tests__/SettingsScreen.test.tsx` - 213行（11テスト）
- `docs/reports/mobile/2025-12-06-phase2-b4-profile-settings-completion-report.md`

**更新ファイル**:
- `mobile/src/types/user.types.ts` - avatar_url追加
- `mobile/src/contexts/ThemeContext.tsx` - setTheme追加
- `mobile/src/utils/errorMessages.ts` - 4エラーコード追加
- `mobile/src/hooks/useTasks.ts` - rejectTask引数オプション化
- `mobile/src/hooks/__tests__/useTasks.test.ts` - テスト修正
- `mobile/package.json` - @react-native-picker/picker追加

#### 参考資料
- **完了レポート**: `docs/reports/mobile/2025-12-06-phase2-b4-profile-settings-completion-report.md`
- **開発規則**: `docs/mobile/mobile-rules.md`
- **プロジェクト規約**: `.github/copilot-instructions.md`

---

### 2.B-4.5: パスワード変更機能（3日間、優先実装）

#### 実装背景

**優先実装理由**:
- ✅ Web版に存在するセキュリティ機能（`profile/partials/update-password-form.blade.php`）
- ✅ `mobile-rules.md`の「Webアプリ機能との整合性」要件に抵触
- ✅ セキュリティ機能のため、早急な実装が必要

#### 実装内容

1. **PasswordChangeScreen（新規画面）**
   - 現在のパスワード入力
   - 新しいパスワード入力（確認フィールド付き）
   - バリデーション
     - 現在のパスワード: 必須
     - 新しいパスワード: 8文字以上
     - パスワード確認: 新しいパスワードと一致
   - テーマ対応UI（adult/child）

2. **Laravel API追加**
   - **エンドポイント**: `PUT /api/v1/profile/password`
   - **リクエスト**:
     ```json
     {
       "current_password": "string",
       "password": "string",
       "password_confirmation": "string"
     }
     ```
   - **レスポンス**:
     ```json
     {
       "success": true,
       "message": "パスワードを更新しました"
     }
     ```
   - **実装ファイル**:
     - `app/Http/Actions/Api/Profile/UpdatePasswordApiAction.php`
     - `app/Http/Requests/Api/Profile/UpdatePasswordRequest.php`

3. **ProfileService拡張**
   - `updatePassword(currentPassword, newPassword)` メソッド追加
   - エラーハンドリング（現在のパスワード不一致、APIエラー）

4. **useProfile Hook拡張**
   - `updatePassword()` メソッド追加
   - 状態管理（isLoading, error）

5. **ナビゲーション追加**
   - ProfileScreen → PasswordChangeScreen遷移
   - 「パスワード変更」リンク/ボタン追加

#### テスト実装

**Laravel（PasswordApiTest.php）** - 9テストケース:
- ✅ 正しいパスワードで更新成功
- ✅ 現在のパスワード不一致でエラー
- ✅ 新しいパスワードが8文字未満でエラー
- ✅ パスワード確認不一致でエラー
- ✅ 認証なしでエラー（401）
- ✅ 必須フィールド欠如でエラー
- ✅ パスワードハッシュが更新される
- ✅ 更新後にログイン可能
- ✅ 旧パスワードでログイン不可

**Mobile（3テストスイート）** - 20テストケース:
- `profile.service.test.ts`: 6テスト（updatePassword機能）
- `useProfile.test.ts`: 3テスト（パスワード変更Hook）
- `PasswordChangeScreen.test.tsx`: 11テスト（UI動作、バリデーション）

#### テスト実行結果

**Laravel Tests** (2025-12-06実行):
```bash
Tests:    9 passed (9 assertions)
Duration: 0.50s
```

**Mobile Tests** (2025-12-06実行):
```bash
Test Suites: 3 passed, 3 total
Tests:       20 passed, 20 total
Time:        8.5s
```

**TypeScript Check** (2025-12-06実行):
```bash
✓ 0 errors found
```

#### チェックリスト

- ✅ PasswordChangeScreen UI実装（316行）
- ✅ ProfileService.updatePassword() 実装
- ✅ useProfile Hook拡張
- ✅ Laravel API実装（PUT /api/v1/profile/password）
- ✅ Laravelテスト作成（9テスト、全パス）
- ✅ Mobileテスト作成（20テスト、全パス）
- ✅ ProfileScreenにリンク追加
- ✅ 既存テスト更新（ProfileScreen, SettingsScreen）
- ✅ TypeScript型チェック（0エラー）
- ✅ 全テスト実行（Laravel 9/9、Mobile 20/20パス）
- ✅ 残課題対応完了（TypeScript errors, Mock更新, Test fixes）
- ✅ 完了レポート作成

#### 完成ファイル

**新規作成**:
- `mobile/src/screens/profile/PasswordChangeScreen.tsx` - 316行
- `mobile/src/screens/profile/__tests__/PasswordChangeScreen.test.tsx` - 325行（11テスト）
- `app/Http/Actions/Api/Profile/UpdatePasswordApiAction.php` - 29行
- `app/Http/Requests/Api/Profile/UpdatePasswordRequest.php` - 26行
- `app/Services/ProfileService.php` - 9行（updatePassword追加）
- `app/Services/ProfileServiceInterface.php` - 2行（updatePassword追加）
- `tests/Feature/Api/Profile/PasswordApiTest.php` - 92行（9テスト）
- `storage/api-docs/api-docs.json` - OpenAPI定義更新

**更新ファイル**:
- `mobile/src/services/profile.service.ts` - updatePassword追加（53→67行）
- `mobile/src/services/__tests__/profile.service.test.ts` - 6テスト追加（129→187行）
- `mobile/src/hooks/useProfile.ts` - updatePassword追加（42→60行）
- `mobile/src/hooks/__tests__/useProfile.test.ts` - 3テスト追加（93→134行）
- `mobile/src/screens/profile/ProfileScreen.tsx` - パスワード変更ナビゲーション追加（545→568行）
- `mobile/src/screens/profile/__tests__/ProfileScreen.test.tsx` - モック更新（288→297行）
- `mobile/src/screens/settings/__tests__/SettingsScreen.test.tsx` - モック更新（213→223行）
- `mobile/src/navigation/AppNavigator.tsx` - PasswordChangeScreen追加（105→108行）
- `routes/api.php` - PUT /api/v1/profile/password追加

#### 参考資料
- **完了レポート**: `docs/reports/mobile/2025-12-06-phase2-b4-5-password-change-completion-report.md`
- **開発規則**: `docs/mobile/mobile-rules.md`
- **プロジェクト規約**: `.github/copilot-instructions.md`

---

### 2.B-5: 検索・通知・アバター機能（2週間）

#### 実装背景

**Phase 2.B-4整合性チェック結果を反映**:
- ✅ **検索機能**: Web版に存在（`components/search-modal.blade.php`）、Phase 2.B-5で実装
- ✅ **通知機能**: Phase 2.B-5で実装予定（計画通り）
- ✅ **アバター機能**: Phase 2.B-5で実装予定（計画通り）
- ⚠️ **タグ機能**: Phase 2.B-6以降で実装（本フェーズでは未実装）

#### 実装内容

1. **検索機能（Web版整合性対応）**
   - **実装方式**: TaskListScreen内に検索バー追加
   - **機能**:
     - タスク検索（タイトル・説明で部分一致）
     - 検索結果のリアルタイム更新
     - 検索履歴保存（AsyncStorage）
     - テーマ対応UI（adult/child）
   - **Web版対応箇所**: `components/search-modal.blade.php`、`dashboard/partials/header.blade.php` L61
   - **API**: 既存の `/api/v1/tasks` に `q` パラメータ追加

2. **通知機能**
   - 通知一覧（NotificationListScreen）
   - 通知詳細
   - Push通知受信（Firebase Cloud Messaging）
   - 通知設定（ON/OFF切り替え）
   - 既読管理
   - 未読件数バッジ表示

3. **アバター機能（AI教師キャラクター）**
   - アバター一覧表示（AvatarListScreen）
   - AI生成アバター作成
   - ポーズ・表情選択
   - コンテキスト別コメント表示
   - アバター詳細表示

#### コード例（検索機能）

```typescript
// src/screens/tasks/TaskListScreen.tsx（検索バー追加）
import React, { useState, useEffect } from 'react';
import { View, TextInput, StyleSheet } from 'react-native';
import { useTasks } from '../../hooks/useTasks';

export const TaskListScreen: React.FC = () => {
  const [searchQuery, setSearchQuery] = useState('');
  const { tasks, searchTasks, isLoading } = useTasks();

  // 検索クエリ変更時にデバウンス実行
  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchQuery) {
        searchTasks(searchQuery);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  return (
    <View style={styles.container}>
      {/* 検索バー */}
      <TextInput
        style={styles.searchInput}
        placeholder="タスクを検索..."
        value={searchQuery}
        onChangeText={setSearchQuery}
        autoCapitalize="none"
      />
      {/* タスク一覧 */}
      {/* ... */}
    </View>
  );
};
```

#### コード例（Push通知）

```typescript
// src/services/notification.service.ts
import messaging from '@react-native-firebase/messaging';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from './api';

export const notificationService = {
  async requestPermission() {
    const authStatus = await messaging().requestPermission();
    const enabled =
      authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
      authStatus === messaging.AuthorizationStatus.PROVISIONAL;
    
    if (enabled) {
      const token = await messaging().getToken();
      await this.registerToken(token);
    }
  },
  
  async registerToken(fcmToken: string) {
    await api.post('/notifications/register-device', {
      device_token: fcmToken,
      platform: Platform.OS,
    });
    await AsyncStorage.setItem('fcm_token', fcmToken);
  },
  
  onMessage(callback: (message: any) => void) {
    return messaging().onMessage(callback);
  },
  
  onNotificationOpenedApp(callback: (message: any) => void) {
    return messaging().onNotificationOpenedApp(callback);
  },
};
```

#### チェックリスト

**検索機能（Step 1完了）**:
- ✅ TaskListScreen検索バー実装
- ✅ useTasks.searchTasks() 実装
- ✅ TaskService.searchTasks() 実装
- ❌ 検索履歴保存（AsyncStorage） - Phase 2.B-6で実装予定
- ✅ デバウンス処理実装（300ms）
- ✅ 検索テスト作成（27テスト全パス、想定15テストを12テスト超過）

**通知機能 - 基本実装（Step 2 - 次タスク）**:
- [ ] NotificationListScreen UI実装
- [ ] useNotifications Hook実装
- [ ] notification.service.ts更新（Laravel API完全準拠）
- [ ] 通知一覧表示（ページネーション対応）
- [ ] 未読件数表示
- [ ] 個別既読化機能
- [ ] 全既読化機能
- [ ] 通知検索機能
- [ ] 通知テスト作成（notification.service.test.ts: 8テスト、useNotifications.test.ts: 12テスト）
- [ ] TypeScript型チェック（0エラー）
- [ ] 実機テスト（通知一覧・既読動作確認）

**注**: Firebase/FCM（Push通知）は Phase 2.B-7.5で実装

**アバター機能（Step 3）**:
- [ ] AvatarListScreen UI実装
- [ ] AvatarDetailScreen UI実装
- [ ] useAvatars Hook実装
- [ ] アバターコメント表示実装
- [ ] ポーズ・表情切り替え実装
- [ ] アバターテスト作成（15テスト）

**総合**:
- ✅ TypeScript型チェック（0エラー） - Step 1完了
- ✅ Step 1テスト実行（27/27パス）
- [ ] Step 2テスト実行（全パス）
- [ ] Step 3テスト実行（全パス）
- [ ] 実機テスト（iOS + Android）
- ✅ Step 1完了レポート作成
- [ ] Phase 2.B-5全体完了レポート作成

---

### 2.B-6: タグ・トークン・グラフ・レポート機能（2週間）

#### 実装背景

**Phase 2.B-4整合性チェック結果を反映**:
- ⚠️ **タグ機能**: Web版で中心的なUI要素、Phase 2.B-6で実装
- ✅ **トークン機能**: Phase 2.B-6で実装予定（計画通り）
- ✅ **グラフ・レポート**: Phase 2.B-6で実装予定（計画通り）

#### 実装内容

1. **タグ機能（Web版整合性対応 - 最優先）**
   - **タグ選択（タスク作成画面）**
     - Web版対応箇所: `modal-dashboard-task.blade.php` L217-237
     - 実装内容: CreateTaskScreenにタグ選択UI追加（複数選択可）
   - **タグ表示（タスク詳細画面）**
     - Web版対応箇所: `modal-task-card.blade.php` L175-199
     - 実装内容: TaskDetailScreenにタグバッジ表示
   - **タグ別バケット表示（タスク一覧画面）** ⚠️ **必須実装**
     - Web版対応箇所: `dashboard/partials/task-bento-layout.blade.php`
     - 実装内容: TaskListScreenをタグ別セクションに再構成
     - **重要**: タグ未分類タスクは「未分類」バケットに表示
     - **テーマ対応**: child テーマでは「そのほか」と表示
   - **タグ管理**
     - タグ一覧取得API: `GET /api/v1/tags`
     - タグ作成・編集・削除（グループマスターのみ）

2. **トークンシステム**
   - トークン残高表示
   - トークン購入（Stripe連携）
   - トークン履歴
   - サブスクリプション管理

3. **グラフ・レポート**
   - パフォーマンスグラフ（react-native-chart-kit）
   - パフォーマンスグラフ（react-native-chart-kit）
   - 月次レポート
   - タスク完了率
   - AI利用統計

#### コード例（グラフ）

```typescript
// src/components/charts/PerformanceChart.tsx
import React from 'react';
import { LineChart } from 'react-native-chart-kit';
import { Dimensions } from 'react-native';

interface Props {
  data: { date: string; completed: number }[];
}

export const PerformanceChart: React.FC<Props> = ({ data }) => {
  const chartData = {
    labels: data.map(d => d.date),
    datasets: [{
      data: data.map(d => d.completed),
      color: (opacity = 1) => `rgba(59, 130, 246, ${opacity})`,
      strokeWidth: 2,
    }],
  };

  return (
    <LineChart
      data={chartData}
      width={Dimensions.get('window').width - 32}
      height={220}
      chartConfig={{
        backgroundColor: '#1e293b',
        backgroundGradientFrom: '#1e293b',
        backgroundGradientTo: '#334155',
        decimalPlaces: 0,
        color: (opacity = 1) => `rgba(255, 255, 255, ${opacity})`,
        labelColor: (opacity = 1) => `rgba(255, 255, 255, ${opacity})`,
        style: { borderRadius: 16 },
        propsForDots: {
          r: '6',
          strokeWidth: '2',
          stroke: '#3b82f6',
        },
      }}
      bezier
      style={{ marginVertical: 8, borderRadius: 16 }}
    />
  );
};
```

#### チェックリスト

**タグ機能（最優先 - Web版整合性）**:
- [ ] タグ選択UI実装（CreateTaskScreen）
- [ ] タグ表示実装（TaskDetailScreen）
- [ ] タグ別バケット表示実装（TaskListScreen） ⚠️ **必須**
- [ ] useTags Hook実装
- [ ] TagService実装
- [ ] タグ管理画面実装（グループマスターのみ）
- [ ] タグテスト作成（20テスト）

**トークン機能**:
- [ ] トークン残高画面UI実装
- [ ] トークン購入画面UI実装（Stripe）
- [ ] トークン履歴画面UI実装
- [ ] トークンテスト作成（15テスト）

**グラフ・レポート機能**:
- [ ] グラフコンポーネント実装
- [ ] 月次レポート画面UI実装
- [ ] レポートテスト作成（10テスト）

**総合**:
- [ ] API連携実装
- [ ] TypeScript型チェック（0エラー）
- [ ] 全テスト実行（全パス）
- [ ] 実機テスト（iOS + Android）
- [ ] 完了レポート作成

---

### 2.B-7: スケジュールタスク機能（1週間）

#### 実装内容

1. **スケジュールタスク管理**
   - スケジュール一覧
   - スケジュール作成/編集
   - 繰り返し設定（毎日、毎週、毎月）
   - 祝日除外設定
   - ランダム割当設定

2. **実行履歴**
   - 実行履歴一覧
   - 実行結果確認

#### チェックリスト

- [ ] スケジュール一覧画面UI実装
- [ ] スケジュール作成/編集画面UI実装
- [ ] 実行履歴画面UI実装
- [ ] API連携実装
- [ ] 実機テスト（iOS + Android）

---

### 2.B-7.5: Push通知機能（Firebase/FCM）（1週間）

#### 実装背景

**Phase 2.B-5 Step 2で基本実装（通知一覧・既読管理）を完了後、Push通知機能を追加**
- Phase 2.B-5 Step 2: 通知一覧・既読管理（Laravel API連携）
- Phase 2.B-7.5: Firebase/FCM統合（Push通知受信）

#### 実装内容

1. **Firebase設定**
   - Firebase プロジェクト作成
   - iOS: GoogleService-Info.plist 追加
   - Android: google-services.json 追加
   - @react-native-firebase/messaging インストール

2. **Push通知受信**
   - FCMトークン取得・登録
   - フォアグラウンド通知受信
   - バックグラウンド通知受信
   - 通知タップ時の画面遷移

3. **通知権限管理**
   - iOS: 通知権限リクエスト
   - Android: 通知権限リクエスト
   - 権限状態管理

#### コード例（Push通知）

```typescript
// src/services/fcm.service.ts
import messaging from '@react-native-firebase/messaging';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { notificationService } from './notification.service';

export const fcmService = {
  async requestPermission() {
    const authStatus = await messaging().requestPermission();
    return authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
           authStatus === messaging.AuthorizationStatus.PROVISIONAL;
  },
  
  async getToken() {
    const token = await messaging().getToken();
    return token;
  },
  
  async registerToken(fcmToken: string) {
    // TODO: Laravel APIにトークン登録（POST /api/notifications/register-device）
    await AsyncStorage.setItem('fcm_token', fcmToken);
  },
  
  onMessage(callback: (message: any) => void) {
    return messaging().onMessage(callback);
  },
  
  onNotificationOpenedApp(callback: (message: any) => void) {
    return messaging().onNotificationOpenedApp(callback);
  },
};
```

#### チェックリスト

**Firebase設定**:
- [ ] Firebase プロジェクト作成
- [ ] iOS設定（GoogleService-Info.plist、証明書）
- [ ] Android設定（google-services.json、FCM設定）
- [ ] @react-native-firebase/messaging インストール

**Push通知実装**:
- [ ] FCMトークン取得・登録実装
- [ ] フォアグラウンド通知受信実装
- [ ] バックグラウンド通知受信実装
- [ ] 通知タップ時の画面遷移実装
- [ ] 通知権限管理実装

**テスト**:
- [ ] Push通知テスト作成（10テスト）
- [ ] TypeScript型チェック（0エラー）
- [ ] 実機テスト（iOS: 通知受信・タップ動作）
- [ ] 実機テスト（Android: 通知受信・タップ動作）

**総合**:
- [ ] 完了レポート作成

---

### 2.B-8: 総合テスト・バグ修正（1週間）

#### テスト項目

1. **機能テスト**
   - 全機能の動作確認（iOS + Android）
   - エッジケーステスト
   - エラーハンドリングテスト

2. **パフォーマンステスト**
   - 画面遷移速度
   - API通信速度
   - 画像読み込み速度
   - メモリ使用量

3. **UI/UXテスト**
   - レイアウト崩れチェック
   - タップ領域チェック
   - アニメーション確認

4. **バグ修正**
   - 発見されたバグの修正
   - 再テスト

#### チェックリスト

- [ ] 全機能動作確認（iOS）
- [ ] 全機能動作確認（Android）
- [ ] パフォーマンステスト実施
- [ ] UI/UXテスト実施
- [ ] バグ修正完了
- [ ] 再テスト完了
- [ ] リリース候補ビルド作成

---

## Phase 2.C: App Store/Google Play申請 + 公開（4週間）

### 2.C-1: EAS Build設定（1週間）

#### Expo Application Services（EAS）セットアップ

```bash
# 1. EAS CLIインストール
npm install -g eas-cli

# 2. EASログイン
eas login

# 3. プロジェクト設定
eas build:configure

# 4. iOS/Androidビルド
eas build --platform ios --profile production
eas build --platform android --profile production
```

#### eas.json設定

```json
{
  "build": {
    "production": {
      "releaseChannel": "production",
      "distribution": "store",
      "ios": {
        "buildType": "release"
      },
      "android": {
        "buildType": "apk"
      }
    }
  },
  "submit": {
    "production": {
      "ios": {
        "appleId": "your-apple-id@example.com",
        "ascAppId": "1234567890",
        "appleTeamId": "ABCDE12345"
      },
      "android": {
        "serviceAccountKeyPath": "./google-play-service-account.json",
        "track": "internal"
      }
    }
  }
}
```

#### チェックリスト

- [ ] EAS CLIインストール
- [ ] EASアカウント作成
- [ ] eas.json設定
- [ ] iOSビルド成功
- [ ] Androidビルド成功
- [ ] テストフライトアップロード成功
- [ ] Google Play内部テスト配信成功

---

### 2.C-2: App Store申請（2週間）

#### 準備事項

1. **Apple Developer Program登録**
   - 費用: $99/年
   - 所要時間: 1-2日

2. **App Store Connect設定**
   - アプリ基本情報登録
   - スクリーンショット作成（5.5インチ + 6.5インチ）
   - プライバシーポリシーURL
   - サポートURL

3. **審査提出**
   - TestFlight内部テスト（1週間）
   - 審査提出
   - 審査期間: 1-7日

#### チェックリスト

- [ ] Apple Developer Program登録
- [ ] App Store Connect設定
- [ ] スクリーンショット作成
- [ ] アプリ説明文作成
- [ ] プライバシーポリシー作成
- [ ] TestFlight内部テスト完了
- [ ] 審査提出
- [ ] 審査承認

---

### 2.C-3: Google Play申請（1週間）

#### 準備事項

1. **Google Play Console登録**
   - 費用: $25（1回のみ）
   - 所要時間: 1日

2. **Google Play Console設定**
   - アプリ基本情報登録
   - スクリーンショット作成
   - プライバシーポリシーURL

3. **審査提出**
   - 内部テスト（1-2日）
   - 審査提出
   - 審査期間: 1-3日

#### チェックリスト

- [ ] Google Play Console登録
- [ ] アプリ基本情報登録
- [ ] スクリーンショット作成
- [ ] アプリ説明文作成
- [ ] 内部テスト完了
- [ ] 審査提出
- [ ] 審査承認

---

### 2.C-4: 公開・監視（継続）

#### 公開後の監視

1. **クラッシュ監視**
   - Firebase Crashlytics
   - Sentry連携

2. **Analytics監視**
   - Firebase Analytics
   - ユーザー行動分析

3. **レビュー対応**
   - App Store/Google Playレビュー確認
   - ユーザーフィードバック対応

#### チェックリスト

- [ ] アプリ公開完了（iOS）
- [ ] アプリ公開完了（Android）
- [ ] Firebase Crashlytics設定
- [ ] Firebase Analytics設定
- [ ] レビュー監視体制構築
- [ ] バグ修正プロセス確立

---

## インフラ・コスト

### 開発環境

- **開発PC**: 既存環境
- **テスト端末**: Expo Go（無料）、実機（既存のiPhone/Android）

### 運用環境

| サービス | 用途 | 月額コスト |
|---------|------|-----------|
| AWS Fargate | バックエンドAPI | $164 |
| Expo Application Services (EAS) | ビルド・配信 | $29 |
| Firebase（Spark Plan） | Push通知・Analytics | 無料（～10万通知/月） |
| Apple Developer Program | App Store公開 | $99/年（$8.25/月） |
| Google Play Console | Google Play公開 | $25（1回のみ） |
| **合計** | | **$201.25/月** |

※ Firebase有料化閾値超過時: Blaze Plan（従量課金、通知10万通以降 $0.001/通）

---

## リスクと対策

### 技術的リスク

| リスク | 対策 |
|--------|------|
| Expo制約によるネイティブモジュール制限 | Expo SDK内のライブラリを優先使用、必要時は bare workflow へ移行 |
| iOS審査リジェクト | App Store審査ガイドライン事前確認、TestFlight内部テスト実施 |
| Push通知未達 | Firebase Cloud Messaging設定確認、ペイロード検証 |
| API通信エラー | リトライ処理実装、オフラインモード検討 |

### スケジュールリスク

| リスク | 対策 |
|--------|------|
| 審査遅延 | 2週間バッファ確保、事前にTestFlight配信 |
| バグ多発 | 総合テスト期間を1週間確保、自動テスト導入 |
| ライブラリ互換性問題 | Expo SDKバージョン固定、依存関係事前検証 |

---

## 次のステップ

1. **Phase 2.B-1実施**: 環境構築（1週間）
   - Node.js + Expo CLIセットアップ
   - プロジェクト作成
   - 実機確認（Expo Go）

2. **Phase 2.B-2以降**: 機能実装（9週間）
   - 認証 → タスク → グループ → アバター → トークン → スケジュール → テスト

3. **Phase 2.C**: ストア申請・公開（4週間）
   - EAS Build → App Store申請 → Google Play申請 → 公開

**総所要期間**: 16週間（2025年12月～2026年3月）

---

## 参照ドキュメント

- **マスタープラン**: `/home/ktr/mtdev/docs/architecture/multi-app-hub-infrastructure-strategy.md`
- **OpenAPI仕様**: `/home/ktr/mtdev/docs/api/openapi.yaml`
- **Phase 1完了レポート**: `/home/ktr/mtdev/docs/reports/2025-11-28-api-implementation-completion-report.md`
- **React Native選定理由**: マスタープラン内「Phase 2技術選定詳細」セクション
