# MyTeacher モバイルアプリ開発規則

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-06 | GitHub Copilot | Service層とHook層のメソッド命名規則を追加（TypeScript規約4項） |
| 2025-12-05 | GitHub Copilot | 初版作成: モバイルアプリ開発規則 |

---

## プロジェクト構造

### ディレクトリ構成

```
/home/ktr/mtdev/mobile/
├── App.tsx                         # エントリーポイント
├── app.json                        # Expo設定
├── package.json                    # 依存関係
├── tsconfig.json                   # TypeScript設定
├── TESTING.md                      # テストガイド
├── src/
│   ├── screens/                    # 画面コンポーネント
│   │   ├── auth/                   # 認証画面
│   │   │   ├── LoginScreen.tsx
│   │   │   └── RegisterScreen.tsx
│   │   ├── tasks/                  # タスク管理画面
│   │   │   ├── TaskListScreen.tsx
│   │   │   ├── TaskDetailScreen.tsx
│   │   │   ├── CreateTaskScreen.tsx
│   │   │   └── TaskApprovalScreen.tsx
│   │   ├── groups/                 # グループ管理画面
│   │   │   ├── GroupListScreen.tsx
│   │   │   ├── GroupDetailScreen.tsx
│   │   │   └── GroupMembersScreen.tsx
│   │   ├── profile/                # プロフィール画面
│   │   │   ├── ProfileScreen.tsx
│   │   │   └── SettingsScreen.tsx
│   │   ├── avatars/                # アバター管理画面
│   │   │   ├── AvatarListScreen.tsx
│   │   │   └── AvatarCreateScreen.tsx
│   │   ├── notifications/          # 通知画面
│   │   │   └── NotificationListScreen.tsx
│   │   ├── tokens/                 # トークン管理画面
│   │   │   ├── TokenBalanceScreen.tsx
│   │   │   └── PurchaseScreen.tsx
│   │   └── reports/                # レポート画面
│   │       ├── MonthlyReportScreen.tsx
│   │       └── PerformanceScreen.tsx
│   ├── components/                 # 再利用可能コンポーネント
│   │   ├── common/                 # 共通コンポーネント
│   │   │   ├── Button.tsx
│   │   │   ├── Card.tsx
│   │   │   ├── Loading.tsx
│   │   │   ├── Input.tsx
│   │   │   └── Modal.tsx
│   │   ├── tasks/                  # タスク関連コンポーネント
│   │   │   ├── TaskCard.tsx
│   │   │   └── TaskStatusBadge.tsx
│   │   └── charts/                 # グラフコンポーネント
│   │       └── PerformanceChart.tsx
│   ├── navigation/                 # ナビゲーション設定
│   │   ├── AppNavigator.tsx        # ルートナビゲーター
│   │   ├── AuthStack.tsx           # 認証スタック（未実装）
│   │   └── MainTabs.tsx            # メインタブ（未実装）
│   ├── services/                   # API通信層
│   │   ├── api.ts                  # Axiosインスタンス
│   │   ├── auth.service.ts         # 認証サービス
│   │   ├── task.service.ts         # タスクサービス（未実装）
│   │   ├── group.service.ts        # グループサービス（未実装）
│   │   ├── notification.service.ts # 通知サービス（未実装）
│   │   └── token.service.ts        # トークンサービス（未実装）
│   ├── hooks/                      # カスタムフック
│   │   ├── useAuth.ts              # 認証Hook
│   │   ├── useTasks.ts             # タスクHook（未実装）
│   │   └── useNotifications.ts     # 通知Hook（未実装）
│   ├── utils/                      # ユーティリティ
│   │   ├── storage.ts              # AsyncStorageラッパー
│   │   └── constants.ts            # 定数定義
│   └── types/                      # TypeScript型定義
│       ├── task.types.ts           # タスク型
│       └── api.types.ts            # API型
└── assets/                         # 画像・フォント等
    ├── images/
    └── fonts/
```

### ファイル配置ルール

1. **画面コンポーネント**: `src/screens/{機能名}/` に配置
   - 命名: `{機能名}Screen.tsx` （例: `LoginScreen.tsx`, `TaskListScreen.tsx`）
   - 1画面 = 1ファイル

2. **再利用コンポーネント**: `src/components/{カテゴリ}/` に配置
   - 命名: `{コンポーネント名}.tsx` （例: `Button.tsx`, `TaskCard.tsx`）
   - 複数画面で使用する場合のみ作成

3. **サービス層**: `src/services/` に配置
   - 命名: `{機能名}.service.ts` （例: `auth.service.ts`, `task.service.ts`）
   - API通信のみを担当

4. **カスタムフック**: `src/hooks/` に配置
   - 命名: `use{機能名}.ts` （例: `useAuth.ts`, `useTasks.ts`）
   - 状態管理とビジネスロジックを担当

5. **型定義**: `src/types/` に配置
   - 命名: `{機能名}.types.ts` （例: `task.types.ts`, `api.types.ts`）
   - interfaceとtypeのみ定義

---

## 総則

### 開発の基本原則

1. **copilot-instructions.mdの遵守**
   - `/home/ktr/mtdev/.github/copilot-instructions.md` に記載されたプロジェクト全体の開発規則を遵守すること
   - 特に以下の項目に注意：
     - 不具合対応方針（ログベースでの原因特定）
     - コード修正時の全体チェック（静的解析ツール使用）
     - レポート作成規則（完了時の報告書作成）

2. **要件定義ファイルの参照**
   - 実装時は必ず対応する要件定義ファイル（`/home/ktr/mtdev/definitions/*.md`）を参照すること
   - 要件定義書に記載されていない機能は実装しない
   - 不明点は要件定義書の更新を先に行う

3. **OpenAPI仕様の参照（必須）**
   - **実装時は必ず** `/home/ktr/mtdev/docs/api/openapi.yaml` を参照すること
   - APIエンドポイント、リクエスト/レスポンス形式、認証方法をOpenAPI仕様に合わせる
   - **注意**: 現在、認証API（`/auth/login`, `/auth/register`）はopenapi.yamlに未定義のため、実装前に追加が必要
   - OpenAPI仕様にない機能は実装しない（バックエンド側の実装が前提）

4. **Webアプリ機能との整合性**
   - **基本方針**: モバイル版は **Webアプリと同等の機能** を有すること
   
   - **実装前の差分検出手順（必須）**:
     
     **ステップ1: 対象ファイルの特定**
     ```bash
     # 例: プロフィール画面の場合
     ls -la /home/ktr/mtdev/resources/views/profile/
     # 結果: edit.blade.php, timezone.blade.php, partials/ を確認
     ```
     
     **ステップ2: Bladeファイルの全文読解**
     - 対象ファイルを **1行目から最終行まで** 読み、UIパーツを抽出
     - `read_file` ツールで複数回に分けて全体を確認
     - **見落とし防止**: `<form>`, `<a>`, `<button>`, `@include`, `@if` の全出現箇所をリストアップ
     
     **ステップ3: 機械的検出によるダブルチェック**
     ```bash
     # リンク・ボタンの網羅的検出
     grep_search('<a href=', isRegexp=false, includePattern='resources/views/profile/*.blade.php')
     grep_search('<button', isRegexp=false, includePattern='resources/views/profile/*.blade.php')
     grep_search('@include', isRegexp=false, includePattern='resources/views/profile/*.blade.php')
     
     # フォームフィールドの検出
     grep_search('name=', isRegexp=false, includePattern='resources/views/profile/*.blade.php')
     
     # 条件分岐の検出（成人限定機能等）
     grep_search('@if', isRegexp=false, includePattern='resources/views/profile/*.blade.php')
     ```
     
     **ステップ4: 検出結果の構造化リスト作成**
     | # | 種別 | ラベル/テキスト | 遷移先/アクション | Blade行番号 | モバイル実装状況 |
     |---|------|---------------|----------------|-----------|--------------|
     | 1 | リンク | "グループ管理画面へ" | `route('group.edit')` | 139 | ❌ 未実装 |
     | 2 | リンク | "タイムゾーン設定" | `route('profile.timezone')` | 165 | ✅ 実装済み |
     | 3 | フォーム | "プロフィール編集" | POST `/profile` | 50-80 | ✅ 実装済み |
     | 4 | セクション | "パスワード変更" | @include | 180 | ❌ 未実装 |
     | 5 | セクション | "アカウント削除" | @include | 200 | ✅ 実装済み |
     
     **ステップ5: 差分サマリー作成**
     - ✅ **実装済み**: X件
     - ❌ **未実装**: Y件（優先度: 高/中/低を明記）
     - ⚠️ **モバイル独自**: Z件（要件定義書への記載要否を判断）
   
   - **実装時の確認手順**:
     1. 上記の差分検出手順を実施し、構造化リストを作成
     2. Webアプリに存在する機能は **すべてモバイル版にも実装**
     3. Webアプリに存在しない機能を追加する場合は、**事前に要件定義書に明記**し、承認を得る
   
   - **画面デザイン方針**: 
     - Webアプリのレスポンシブデザインと **同等の画面構成** とすること
     - モバイルネイティブの操作性（スワイプ、タップ等）に最適化
     - 情報の過不足は許容しない（Webアプリと同じ情報を表示）
   
   **モバイル固有機能の扱い**:
   - モバイル特有の機能（カメラ、プッシュ通知、位置情報等）は、**要件定義書に明記**されている場合のみ実装可能
   - 例外的に追加が必要な場合:
     1. 要件定義書（`/home/ktr/mtdev/definitions/*.md`）に追記
     2. Webアプリ側への実装も検討（API側は共通化）
     3. 完了レポートに「モバイル固有機能」として明記
   
   **チェックリスト**:
   - [ ] Bladeファイルを1行目から最終行まで読解した
   - [ ] `grep_search`でリンク・ボタン・フォームを機械的に検出した
   - [ ] 検出結果を構造化リスト（表形式）にまとめた
   - [ ] Webアプリの全機能をモバイル版に実装した（または未実装理由を明記）
   - [ ] モバイル固有機能は要件定義書に明記されている
   - [ ] 画面構成・情報量がWebアプリと一致している

5. **データベーススキーマの確認**
   - 実装時は **必ず** Laravelのマイグレーションファイル（`/home/ktr/mtdev/database/migrations/`）を参照すること
   - モデルクラス（`/home/ktr/mtdev/app/Models/`）の `$fillable` プロパティを確認し、存在するカラムのみを使用
   - **存在しないカラムを指定してエラーを発生させない**
   - 特に認証関連は以下のカラムを確認：
     - `users` テーブル: `id`, `username`, `email`, `name`, `password`, `cognito_sub`, `auth_provider` など

6. **テストファイルの作成（必須）**
   - 機能実装完了後、**必ず** テストファイルを作成すること
   - テストファイル配置: `/home/ktr/mtdev/mobile/__tests__/{機能名}/`
   - テストフレームワーク: Jest（Expoデフォルト）
   - カバレッジ目標: 80%以上
   - テストパターン:
     - **単体テスト**: Services, Hooks, Utils
     - **統合テスト**: API通信
     - **E2Eテスト**: 画面遷移（Phase 2.B-8で実装）

7. **実装完了後の全体確認（必須）**
   - コード修正後、必ず以下を実行：
     - TypeScript型チェック: `npx tsc --noEmit`
     - 静的解析: ESLint実行（Phase 2.B-3で設定）
     - テスト実行: `npm test`
     - インポートパスの確認
     - 未使用変数・インポートの削除
   - `/home/ktr/mtdev/.github/copilot-instructions.md` の「コード修正時の遵守事項」に従う

8. **画面のデザイン方針**
   - web版のレスポンシブデザインと同等の画面とすること


---

## 技術スタック

### コア技術

```
MyTeacher モバイルアプリ
├── React Native + Expo（確定）
│   ├── iOS版（App Store公開予定）
│   ├── Android版（Google Play公開予定）
│   ├── TypeScript（型安全性）
│   └── JWT認証（Laravel API経由）
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
│   ├──通知（6 Actions）
│   ├── トークン（5 Actions）
│   ├── レポート（4 Actions）
│   └── スケジュールタスク（8 Actions）
└── Stripe決済連携（トークン購入・サブスクリプション）
```

### バージョン要件

| 技術 | バージョン | 備考 |
|------|-----------|------|
| Node.js | 20.19.5以上 | 推奨: LTS最新版 |
| Expo SDK | 54 | 現在使用中 |
| React Native | 0.76.5 | Expo SDK 54に含まれる |
| TypeScript | 5.3.3 | Expoデフォルト |
| React Navigation | 6.x | 最新版 |

### 開発ツール

- **IDE**: VSCode（推奨）
- **デバッガ**: Chrome DevTools, React Native Debugger
- **テスト**: Jest + React Native Testing Library
- **静的解析**: ESLint + TypeScript（Phase 2.B-3で設定）
- **フォーマッタ**: Prettier（Phase 2.B-3で設定）

---

## 機能別規則

### 1. 認証機能（Phase 2.B-2）

#### 実装規則

1. **JWT認証の実装**
   - トークン保存: AsyncStorage（キー: `auth_token`）
   - ユーザー情報保存: AsyncStorage（キー: `user`）
   - Axios Interceptorで自動JWT付与
   - 401エラー時の自動ログアウト実装

2. **DBスキーマとの対応**
   - **重要**: `users` テーブルのカラムを確認してから実装
   - 登録時に送信するフィールド:
     - `email` (必須): メールアドレス
     - `password` (必須): パスワード
     - `name` (オプション): 表示名
   - **送信不要なフィールド（バックエンド側で自動生成/設定）**:
     - `username`: emailから自動生成（重複時は連番付与）
     - `cognito_sub`: Cognito認証時のみ使用（モバイル独自認証では `null`）
     - `auth_provider`: バックエンド側で自動設定（モバイル用は `'sanctum'` または `'mobile'`）
   
3. **認証方式（マルチアプリハブ構想との整合性）**
   - **Phase 2方針**: **独自認証API実装**（Cognito不使用）
   - **理由**:
     - ✅ 計画書の「将来アプリ（Phase 2-3）: 各アプリ独自認証 + API連携用トークン」に準拠
     - ✅ Phase 3でPortal独立化時の「各アプリ認証連携」に対応
     - ✅ ParentShare・AI-Senseiとの認証方式統一
     - ✅ Phase 5のSSO統合時の柔軟性確保
   - **実装技術**: Laravel Sanctum + AuthHelper統合
   - **エンドポイント**: `/api/auth/login`, `/api/auth/register` を新規実装
   - **Web版との共存**: `dual.auth` ミドルウェアで Cognito JWT（Web版）と Sanctum（モバイル版）の両対応
   - **参照**: `/home/ktr/mtdev/docs/architecture/multi-app-hub-infrastructure-strategy.md` Lines 222-226

3. **バリデーション**
   - Email形式チェック（正規表現: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`）
   - パスワード長: 8文字以上
   - 登録時のパスワード確認一致チェック

4. **エラーハンドリング**
   - ネットワークエラー: Alert表示
   - APIエラー: サーバーのエラーメッセージを表示
   - バリデーションエラー: フォーム下部に表示

#### テスト要件

- [ ] ログイン成功時、JWTがAsyncStorageに保存される
- [ ] ログイン失敗時、適切なエラーメッセージ表示
- [ ] 登録成功時、自動ログイン実行
- [ ] 401エラー時、自動ログアウト実行
- [ ] アプリ再起動後、ログイン状態が復元される

#### 参照ファイル

- **要件定義**: `/home/ktr/mtdev/definitions/Task.md`（認証機能の記載あり）
- **OpenAPI**: `/home/ktr/mtdev/docs/api/openapi.yaml`（**認証APIは未定義 - 追加必要**）
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/0001_01_01_000000_create_users_table.php`
- **モデル**: `/home/ktr/mtdev/app/Models/User.php`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/auth/`

---

### 2. タスク管理機能（Phase 2.B-3）

#### 実装規則

1. **タスクCRUD**
   - 一覧表示: Infinite Scroll（react-native-flatlist）
   - 詳細表示: モーダルまたは専用画面
   - 作成・編集: フォーム画面
   - 削除: 確認ダイアログ後に実行

2. **DBスキーマとの対応**
   - **必須確認**: `/home/ktr/mtdev/database/migrations/*_create_tasks_table.php`
   - 使用可能なカラムのみを型定義に含める
   - 仮想カラム（アクセサ）は型定義に含めない

3. **OpenAPI仕様の参照**
   - エンドポイント: `/tasks` 配下の14エンドポイント
   - リクエスト/レスポンス形式を厳密に遵守
   - 存在しないエンドポイントは実装しない

4. **Webアプリとの整合性**
   - `/home/ktr/mtdev/resources/views/tasks/` の画面構成を参照
   - Webアプリにある機能はすべてモバイル版にも実装
   - 優先度表示、タグ表示、画像添付機能を含む

#### テスト要件

- [ ] タスク一覧取得成功
- [ ] タスク詳細取得成功
- [ ] タスク作成成功
- [ ] タスク更新成功
- [ ] タスク削除成功
- [ ] バリデーションエラー表示

#### 参照ファイル

- **要件定義**: `/home/ktr/mtdev/definitions/Task.md`
- **OpenAPI**: `/home/ktr/mtdev/docs/api/openapi.yaml`（タスクAPI: 14エンドポイント）
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/*_create_tasks_table.php`
- **モデル**: `/home/ktr/mtdev/app/Models/Task.php`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/tasks/`

---

### 3. グループ管理機能（Phase 2.B-3）

#### 実装規則

1. **グループ機能**
   - グループ一覧表示
   - グループ詳細表示
   - メンバー一覧表示
   - グループタスク管理

2. **DBスキーマとの対応**
   - **必須確認**: `/home/ktr/mtdev/database/migrations/*_create_groups_table.php`
   - `groups` テーブルと `users` テーブルの関連を理解
   - `group_id`, `master_user_id` の役割を把握

3. **OpenAPI仕様の参照**
   - エンドポイント: `/groups` 配下の7エンドポイント

#### テスト要件

- [ ] グループ一覧取得成功
- [ ] グループ詳細取得成功
- [ ] メンバー一覧取得成功
- [ ] グループタスク一覧取得成功

#### 参照ファイル

- **OpenAPI**: `/home/ktr/mtdev/docs/api/openapi.yaml`（グループAPI: 7エンドポイント）
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/*_create_groups_table.php`
- **モデル**: `/home/ktr/mtdev/app/Models/Group.php`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/groups/`

---

### 4. プロフィール・設定機能（Phase 2.B-4）

#### 実装規則

1. **プロフィール管理**
   - プロフィール表示
   - プロフィール編集
   - アバター画像アップロード
   - パスワード変更

2. **設定画面**
   - 通知設定（ON/OFF）
   - テーマ設定（adult/child）
   - 言語設定（今後実装）

3. **DBスキーマとの対応**
   - `users` テーブルの `theme`, `timezone` カラムを活用
   - アバター画像はS3/MinIOに保存

#### テスト要件

- [ ] プロフィール取得成功
- [ ] プロフィール更新成功
- [ ] 画像アップロード成功
- [ ] 設定変更成功

#### 参照ファイル

- **OpenAPI**: `/home/ktr/mtdev/docs/api/openapi.yaml`（プロフィールAPI: 5エンドポイント）
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/0001_01_01_000000_create_users_table.php`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/profile/`

---

### 5. アバター機能（Phase 2.B-7）

#### 実装規則

1. **アバター表示**
   - AI生成アバター一覧表示
   - アバターコメント表示（イベント連動）
   - ポーズ・表情切り替え

2. **DBスキーマとの対応**
   - **必須確認**: `/home/ktr/mtdev/database/migrations/*_create_teacher_avatars_table.php`
   - `TeacherAvatar`, `AvatarImage`, `AvatarComment` テーブルの関連を理解

3. **OpenAPI仕様の参照**
   - エンドポイント: `/avatars` 配下の7エンドポイント

#### テスト要件

- [ ] アバター一覧取得成功
- [ ] アバター詳細取得成功
- [ ] アバターコメント取得成功
- [ ] アバター生成ジョブ開始成功

#### 参照ファイル

- **要件定義**: `/home/ktr/mtdev/definitions/AvatarDefinition.md`
- **OpenAPI**: `/home/ktr/mtdev/docs/api/openapi.yaml`（アバターAPI: 7エンドポイント）
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/*_create_teacher_avatars_table.php`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/avatars/`

---

### 6. 通知機能（Phase 2.B-5）

#### 実装規則

1. **Push通知**
   - Firebase Cloud Messaging（FCM）統合
   - 通知受信・表示
   - 通知一覧表示
   - 既読管理

2. **OpenAPI仕様の参照**
   - エンドポイント: `/notifications` 配下の6エンドポイント

#### テスト要件

- [ ] 通知一覧取得成功
- [ ] 通知既読化成功
- [ ] 未読件数取得成功
- [ ] FCMトークン登録成功

#### 参照ファイル

- **OpenAPI**: `/home/ktr/mtdev/docs/api/openapi.yaml`（通知API: 6エンドポイント）
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/*_create_notifications_table.php`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/notifications/`

---

### 7. トークン・決済機能（Phase 2.B-6）

#### 実装規則

1. **トークン管理**
   - 残高表示
   - 履歴表示
   - 消費・獲得表示

2. **Stripe決済**
   - トークン購入フロー
   - サブスクリプション管理
   - 決済履歴表示

3. **OpenAPI仕様の参照**
   - エンドポイント: `/tokens` 配下の5エンドポイント

#### テスト要件

- [ ] トークン残高取得成功
- [ ] トークン履歴取得成功
- [ ] トークン購入フロー完了
- [ ] Stripe決済成功

#### 参照ファイル

- **要件定義**: `/home/ktr/mtdev/definitions/Purchase.md`
- **OpenAPI**: `/home/ktr/mtdev/docs/api/openapi.yaml`（トークンAPI: 5エンドポイント）
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/*_create_token_transactions_table.php`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/tokens/`

---

### 8. レポート機能（Phase 2.B-7）

#### 実装規則

1. **月次レポート**
   - 実績グラフ表示（react-native-chart-kit使用）
   - タスク完了率表示
   - トークン消費グラフ表示

2. **OpenAPI仕様の参照**
   - エンドポイント: `/reports` 配下の4エンドポイント

#### テスト要件

- [ ] 月次レポート取得成功
- [ ] グラフデータ取得成功
- [ ] PDF生成リクエスト成功

#### 参照ファイル

- **OpenAPI**: `/home/ktr/mtdev/docs/api/openapi.yaml`（レポートAPI: 4エンドポイント）
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/*_create_monthly_summaries_table.php`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/reports/`

---

## コーディング規約

### TypeScript規約

1. **型定義**
   - すべての関数に戻り値の型を明示
   - `any` 型の使用禁止（やむを得ない場合は `unknown` を使用）
   - interface と type の使い分け:
     - オブジェクト形状: `interface` を使用
     - ユニオン型・ユーティリティ型: `type` を使用

2. **命名規則**
   - コンポーネント: `PascalCase` （例: `LoginScreen`, `TaskCard`）
   - 関数・変数: `camelCase` （例: `handleLogin`, `userName`）
   - 定数: `UPPER_SNAKE_CASE` （例: `API_BASE_URL`, `STORAGE_KEYS`）
   - 型定義: `PascalCase` （例: `User`, `TaskResponse`）

3. **ファイル命名**
   - コンポーネント: `{名前}.tsx`
   - サービス: `{名前}.service.ts`
   - Hook: `use{名前}.ts`
   - 型定義: `{名前}.types.ts`
   - テスト: `{名前}.test.ts` または `{名前}.test.tsx`

4. **Service層とHook層のメソッド命名規則（重要）**
   
   **問題**: Service層とHook層でメソッド名が不一致になると、型エラーやテスト失敗の原因となる。
   
   **統一規則**:
   - **Service層**: **明示的な命名**（`{動詞}{対象}{Action}`）を使用
     - 例: `toggleTaskCompletion()`, `uploadTaskImage()`, `deleteTaskImage()`
     - 理由: APIエンドポイントとの対応を明確化、複数リソースを扱う場合の曖昧性排除
   
   - **Hook層**: **Service層のメソッド名をそのまま使用**
     - 例: `toggleTaskCompletion()`, `uploadTaskImage()`, `deleteTaskImage()`
     - 理由: Service層との一貫性維持、型安全性の確保
   
   - **NG例** ❌:
     ```typescript
     // Service層
     async toggleTaskCompletion(taskId: number) { ... }
     
     // Hook層（NG: 名前が不一致）
     const toggleComplete = useCallback(async (taskId: number) => {
       await taskService.toggleComplete(taskId); // エラー: メソッドが存在しない
     }, []);
     ```
   
   - **OK例** ✅:
     ```typescript
     // Service層
     async toggleTaskCompletion(taskId: number): Promise<Task> { ... }
     
     // Hook層（OK: Service層と同じ名前）
     const toggleTaskCompletion = useCallback(async (taskId: number) => {
       const updatedTask = await taskService.toggleTaskCompletion(taskId);
       // ...
     }, [taskService]);
     ```
   
   **実装時のチェック項目**:
   - [ ] Service層のメソッド名を決定後、Hook層でも同じ名前を使用
   - [ ] テストファイルでもService層のメソッド名を正確にモック
   - [ ] TypeScript型チェック（`npx tsc --noEmit`）でエラーがないことを確認

### React Native規約

1. **コンポーネント設計**
   - 関数コンポーネントのみ使用（クラスコンポーネント禁止）
   - Hooksを活用した状態管理
   - 1コンポーネント = 1ファイル
   - 200行を超える場合は分割を検討

2. **スタイリング**
   - `StyleSheet.create()` を使用
   - インラインスタイル禁止（デバッグ時を除く）
   - 色・サイズは `constants.ts` に定義
   - レスポンシブ対応: `Dimensions` API使用

3. **パフォーマンス**
   - `useMemo`, `useCallback` を適切に使用
   - FlatList使用時は `keyExtractor` を必ず指定
   - 画像は `expo-image` を使用（キャッシュ機能あり）

### API通信規約

1. **Axiosインスタンス**
   - すべてのAPI通信は `src/services/api.ts` のインスタンスを使用
   - 直接 `axios` をインポートしない

2. **エラーハンドリング**
   - try-catch で必ずエラーをキャッチ
   - ネットワークエラーとAPIエラーを区別
   - ユーザーにわかりやすいエラーメッセージを表示

3. **レスポンス型定義**
   - OpenAPI仕様に基づいて型定義
   - ジェネリクス `ApiResponse<T>` を活用

### テストコード規約

1. **テストファイル配置**
   - `__tests__/{機能名}/` に配置
   - ファイル名: `{テスト対象}.test.ts`

2. **テストパターン**
   - AAA（Arrange-Act-Assert）パターン
   - describe → it の階層構造
   - モック使用時は `jest.mock()` を活用

3. **カバレッジ目標**
   - Services: 100%
   - Hooks: 90%以上
   - Components: 80%以上

---

## 静的解析・品質管理

### 必須チェック項目

1. **TypeScript型チェック**
   ```bash
   npx tsc --noEmit
   ```
   - コミット前に必ず実行
   - エラー0件を確認

2. **ESLint（Phase 2.B-3で設定）**
   ```bash
   npm run lint
   ```
   - 警告・エラーを修正してからコミット

3. **テスト実行**
   ```bash
   npm test
   ```
   - すべてのテストがパスすることを確認

4. **ビルド確認**
   ```bash
   npx expo build:web
   ```
   - ビルドエラーがないことを確認

---

## Git運用規則

### コミットメッセージ

```
feat: 機能追加
fix: バグ修正
docs: ドキュメント更新
style: コードフォーマット
refactor: リファクタリング
test: テスト追加・修正
chore: ビルド・設定変更
```

例:
```
feat: Phase 2.B-2 認証機能実装完了

- JWT認証システム実装
- ログイン・登録画面UI実装
- 認証状態管理Hook実装
```

### ブランチ戦略

- `main`: 本番環境
- `develop`: 開発環境（Phase 2.B以降で使用）
- `feature/{機能名}`: 機能開発ブランチ

---

## 完了報告規則

### Phase完了時の報告

1. **完了報告書作成**
   - 保管先: `/home/ktr/mtdev/docs/reports/mobile/`
   - ファイル名: `YYYY-MM-DD-phase2-{phase名}-completion-report.md`
   - 形式: copilot-instructions.mdの「レポート作成規則」に従う

2. **必須セクション**
   - 更新履歴
   - 概要
   - 計画との対応
   - 実施内容詳細
   - 成果と効果
   - 品質保証プロセス（TypeScript型チェック、テスト実行結果、規約遵守チェック）
   - 未完了項目・次のステップ
   - 添付資料（ファイル一覧、コミット情報、テスト実行結果、パッケージ情報）

3. **実装計画書更新**
   - `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md` の更新履歴に追記
   - 該当フェーズのステータスを「✅ 完了」に更新
   - 完了レポートへのリンクを追加

4. **テストガイド更新**
   - `/home/ktr/mtdev/mobile/TESTING.md` に新機能のテスト手順を追加

**例（Phase 2.B-4完了時）**:
```markdown
# 完了レポート
docs/reports/mobile/2025-12-06-phase2-b4-profile-settings-completion-report.md

# phase2-mobile-app-implementation-plan.md 更新内容
- 更新履歴に「2025-12-06 | GitHub Copilot | Phase 2.B-4完了」追記
- Phase 2.B-4セクション: 「🎯 実施中」→「✅ 完了: 2025-12-06」
- 完了レポートリンク追加
```

---

**作成者**: GitHub Copilot  
**最終更新**: 2025年12月6日  
**対象フェーズ**: Phase 2.B（モバイルアプリ開発）
