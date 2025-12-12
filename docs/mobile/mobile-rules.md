# MyTeacher モバイルアプリ開発規則

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-12 | GitHub Copilot | ポータル実装レポート保管先を追加: `/home/ktr/mtdev/docs/reports/portal/` |
| 2025-12-09 | GitHub Copilot | レスポンシブ設計方針を全面刷新: Dimensions API積極使用、Android/iOS/タブレット対応 |
| 2025-12-09 | GitHub Copilot | 画面デザイン方針の詳細化、Tailwind CSS変換規則追加 |
| 2025-12-08 | GitHub Copilot | テストコード規約に型安全性に関する禁止事項を追加 |
| 2025-12-07 | GitHub Copilot | バックエンド齟齬対応方針を追加（総則8項） |
| 2025-12-07 | GitHub Copilot | 画像機能に関する注意事項を追加（総則7項） |
| 2025-12-07 | GitHub Copilot | 質疑応答結果の要件定義化ルール追加（総則6項） |
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

5. **データベーススキーマの確認（重要）**
   - **原則**: 実装時は **必ず** Laravelのマイグレーションファイル（`/home/ktr/mtdev/database/migrations/`）を参照すること
   - モデルクラス（`/home/ktr/mtdev/app/Models/`）の `$fillable` プロパティを確認し、**存在するカラムのみを使用**
   - **推測によるカラム名の使用は厳禁**（`/home/ktr/mtdev/.github/copilot-instructions.md`の「不具合対応方針」に従う）
   
   - **検証手順**:
     ```bash
     # マイグレーションファイルでカラム確認
     cat /home/ktr/mtdev/database/migrations/*_{テーブル名}.php | grep '\$table->'
     
     # モデルのfillableプロパティ確認
     grep -A 30 'protected \$fillable' /home/ktr/mtdev/app/Models/{モデル名}.php
     ```
   
   - **禁止事項**:
     - ❌ マイグレーションファイルを確認せずにカラム名を推測
     - ❌ 他のプロジェクト・Stack Overflowのコード例をそのまま使用
     - ❌ Webアプリで使用しているカラムを確認せず、独自カラム名を使用
     - ❌ 存在しないカラムを型定義・API呼び出しに含める

6. **質疑応答結果の要件定義化（重要）**
   - **原則**: 実装中の質疑応答（仕様確認、UI調整、機能追加等）の結果は **必ず要件定義書として保管** すること
   - 保管先: `/home/ktr/mtdev/definitions/mobile/` 配下
   - ファイル名: `{画面名/機能名}.md`（例: `TaskListScreen.md`, `AuthFlow.md`）
   - 形式: `/home/ktr/mtdev/.github/copilot-instructions.md` の「レポート作成規則」に準拠
   
   - **要件定義書の必須セクション**:
     ```markdown
     # {画面名/機能名} 要件定義書
     
     ## 更新履歴
     | 日付 | 更新者 | 更新内容 |
     
     ## 1. 概要
     - 対応フェーズ、関連画面、目的
     
     ## 2. 画面仕様（UI要件の場合）
     - 画面構成、表示要素、データ取得仕様
     
     ## 3. 機能要件
     - 各機能の詳細仕様、実装方式
     
     ## 4. UI/UXガイドライン
     - テーマ対応、レスポンシブ対応
     
     ## 5. データベーススキーマ対応
     - 使用テーブル、カラム一覧
     
     ## 6. エラーハンドリング
     - エラーパターン、メッセージ
     
     ## 7. パフォーマンス要件
     - 応答時間目標、最適化手法
     
     ## 8. テスト要件
     - 単体テスト、統合テスト、E2Eテスト
     
     ## 9. 実装ファイル
     - 実装ファイル一覧、テストファイル
     
     ## 10. 将来対応
     - 次フェーズの機能追加予定
     
     ## 11. Webアプリとの差分
     - 実装済み機能、未実装機能、モバイル独自機能
     
     ## 12. 参考資料
     - 関連ドキュメント、完了レポート
     
     ## 13. 質疑応答履歴
     - 質問と回答のペア、決定事項
     ```
   
   - **要件定義化のタイミング**:
     1. Phase完了時（必須）
     2. 仕様変更が発生した場合（随時）
     3. 質疑応答が5件以上累積した場合（推奨）
   
   - **質疑応答履歴の記録方法**:
     - 質問: ユーザーからの質問をそのまま記載
     - 回答: 決定事項、実装内容、理由を明記
     - 影響範囲: 変更したファイル、行番号を記載
   
   - **禁止事項**:
     - ❌ 質疑応答結果を口頭・チャットのみで終わらせる
     - ❌ 決定事項を要件定義書に反映しない
     - ❌ 仕様変更を個別ファイルのコメントのみで記録
     - ❌ 将来参照できない形式で保管（Markdown以外）

7. **画像機能に関する注意事項（重要）**
   - **タスク画像アップロード機能は実装済み**:
     - バックエンドAPI: `POST /api/tasks/{taskId}/images`（UploadTaskImageApiAction.php）
     - モバイルUI: TaskDetailScreen.tsx の `handleImagePick()`
     - 画像削除: `DELETE /api/tasks/images/{imageId}`（DeleteTaskImageApiAction.php）
   
   - **TaskImageモデルのカラム名**:
     - **正**: `file_path`（マイグレーション定義）
     - **誤**: `path`（存在しないカラム、APIレスポンスのみ）
     - **注意**: API層で `$img->path` を参照するとエラー（`$img->file_path` を使用）
   
   - **画像データのAPI返却形式**:
     ```php
     'images' => $task->images
         ->filter(fn($img) => !empty($img->file_path))  // null安全対策
         ->map(fn($img) => [
             'id' => $img->id,
             'path' => $img->file_path,  // ✅ file_pathを使用
             'url' => Storage::disk('s3')->url($img->file_path),
         ])
         ->values()
         ->toArray(),
     ```

8. **バックエンドとモバイル間の齟齬対応方針（重要）**

   **基本原則**: バックエンド（Laravel）とモバイル（React Native）の間でAPI仕様やデータ構造に齟齬が発生した場合、**原則としてモバイル側で対応する**。

   **理由**:
   - バックエンド変更はWeb版、モバイル版、管理画面など複数のクライアントに影響
   - モバイル側の変更は単一クライアントのみに影響
   - バックエンドは既に本番稼働中で既存機能の安定性が重要
   - モバイル側のみの修正で開発時間を短縮可能

   **対応手順**:

   **Step 1: 齟齬の特定と分析**
   ```bash
   # 1. バックエンドAPIのレスポンスを確認
   # 実際のレスポンス構造をログで確認
   
   # 2. テーブル定義とマイグレーションファイルを確認
   cat /home/ktr/mtdev/database/migrations/*_{テーブル名}.php | grep '\$table->'
   
   # 3. モデルクラスのプロパティを確認
   grep -A 30 'protected \$fillable' /home/ktr/mtdev/app/Models/{モデル名}.php
   
   # 4. モバイル側の型定義を確認
   cat /home/ktr/mtdev/mobile/src/types/{機能名}.types.ts
   ```

   **Step 2: モバイル側での対応（優先）**

   対応方法の優先順位:

   **① 型定義の修正**（最優先）
   ```typescript
   // 修正前（モバイル想定）
   interface NotificationTemplate {
     content: string;
     priority: 'low' | 'normal' | 'high';
   }
   
   // 修正後（バックエンド実装に合わせる）
   interface NotificationTemplate {
     content: string | null; // バックエンドのmessageカラムがマッピングされる
     priority: 'info' | 'normal' | 'important'; // Laravel実装値
   }
   ```

   **② 表示ロジックの修正**
   ```typescript
   // 修正前
   {item.template?.priority === 'high' && <Badge />}
   
   // 修正後（バックエンド実装値に合わせる）
   {item.template?.priority === 'important' && <Badge />}
   ```

   **③ データ変換層の追加**（必要に応じて）
   ```typescript
   // services/notification.service.ts
   const mapPriority = (priority: string): string => {
     const mapping = { 'info': 'low', 'important': 'high' };
     return mapping[priority] || priority;
   };
   ```

   **Step 3: バックエンド変更が必要な場合**

   以下の条件を**すべて満たす**場合のみ、バックエンド変更を検討:
   - ✅ モバイル側での対応が技術的に困難または不可能
   - ✅ バックエンド変更による既存機能への影響が軽微
   - ✅ Web版などの他クライアントにも同様の修正が必要
   - ✅ バックエンド変更により将来的な保守性が向上

   **必須手順**:
   
   1. **質問として提示**（勝手に修正しない）
      ```
      以下のバックエンド修正が必要です。実施してよろしいですか？
      
      【修正内容】
      - ファイル: app/Models/NotificationTemplate.php
      - 変更: contentアクセサを追加してmessageカラムをマッピング
      
      【理由】
      - モバイル側での対応が困難
      
      【影響範囲】
      - Web版: 影響なし（contentアクセサは追加のみ）
      - 管理画面: 影響なし
      ```
   
   2. **承認後に実装**
      - 明示的な承認を得てから修正
      - 修正内容を詳細にドキュメント化
   
   3. **影響範囲の確認**
      - Web版での動作確認
      - 管理画面での動作確認
      - APIテストの実行

   **具体例: 通知機能の齟齬対応**

   発生した齟齬:
   - バックエンド: `message`カラム、`type`カラム、priority値 `'info' | 'normal' | 'important'`
   - モバイル想定: `content`プロパティ、`category`プロパティ、priority値 `'low' | 'normal' | 'high'`

   対応方法:
   - ✅ **採用**: モバイル側の型定義を修正（`content`, `category`, priority値）
   - ❌ **不採用**: バックエンドにアクセサ追加（影響範囲が大きい）

   修正箇所:
   ```typescript
   // mobile/src/types/notification.types.ts
   export interface NotificationTemplate {
     content: string | null;              // バックエンドのmessageがマッピング
     priority: 'info' | 'normal' | 'important'; // Laravel実装値
     category: string | null;             // バックエンドのtypeがマッピング
   }
   
   // mobile/src/screens/notifications/NotificationListScreen.tsx
   {item.template?.priority === 'important' && <Badge />}
   ```

   **禁止事項**:
   - ❌ バックエンド実装を確認せずにモバイル側の想定で型定義を作成
   - ❌ 承認なしでバックエンドコードを修正
   - ❌ 影響範囲を確認せずにバックエンド変更を実施
   - ❌ Web版との整合性を確認せずにAPIレスポンス形式を変更

   - **画像アップロードの制約**:
     - 最大サイズ: 10MB
     - 許可形式: JPEG, PNG, JPG, GIF
     - ウイルススキャン: 有効（設定による）
     - 保存先: S3/MinIO `task_approvals/` ディレクトリ
   
   - **チェックリスト**:
     - [ ] マイグレーションファイルでカラム名を確認した（`file_path`）
     - [ ] API層で `$img->file_path` を使用している（`$img->path` ではない）
     - [ ] `file_path` が `null` の場合はスキップ（`filter()`使用）
     - [ ] 画像アップロード機能の実装状況を確認した（実装済み）
     - ❌ 質疑応答結果を口頭・チャットのみで終わらせる
     - ❌ 実装完了後に要件定義書を作成せずに次フェーズに進む
     - ❌ 質疑応答履歴を省略・簡略化する

9. **テストファイルの作成（必須）**
   - 機能実装完了後、**必ず** テストファイルを作成すること
   - テストファイル配置: `/home/ktr/mtdev/mobile/__tests__/{機能名}/`
   - テストフレームワーク: Jest（Expoデフォルト）
   - カバレッジ目標: 80%以上
   - ファイル作成後、TypeScriptの警告を確認
   - テストパターン:
     - **単体テスト**: Services, Hooks, Utils
     - **統合テスト**: API通信
     - **E2Eテスト**: 画面遷移（Phase 2.B-8で実装）

10. **実装完了後の全体確認（必須）**
   - コード修正後、必ず以下を実行：
     - TypeScriptの警告を確認
     - TypeScript型チェック: `npx tsc --noEmit`
     - 静的解析: ESLint実行（Phase 2.B-3で設定）
     - テスト実行: `npm test`
     - インポートパスの確認
     - 未使用変数・インポートの削除
   - `/home/ktr/mtdev/.github/copilot-instructions.md` の「コード修正時の遵守事項」に従う

11. **画面デザイン方針（重要）**
   
   **原則**: モバイルアプリの各画面は、**Webアプリのレスポンシブデザイン（375px幅相当）と同等の画面構成**とすること。
   
   **実装前の必須手順**:
   
   **Step 1: 対象Bladeファイルの特定**
   ```bash
   # 例: タスク一覧画面の場合
   ls -la /home/ktr/mtdev/resources/views/tasks/
   # 結果: index.blade.php を確認
   ```
   
   **Step 2: Bladeファイルの全文読解**
   - 対象ファイルを **1行目から最終行まで** 読み、UIパーツを抽出
   - `read_file` ツールで複数回に分けて全体を確認
   - **見落とし防止**: `<form>`, `<a>`, `<button>`, `@include`, `@if` の全出現箇所をリストアップ
   
   **Step 3: Tailwind CSSクラスの抽出**
   ```bash
   # レスポンシブブレークポイントの確認
   grep -E "sm:|md:|lg:|xl:" /home/ktr/mtdev/resources/views/tasks/index.blade.php
   
   # 375px幅相当のクラスを確認（sm:未満 = モバイル幅）
   # Tailwind CSS: sm = 640px, md = 768px
   # モバイル向けは sm: なしのクラスを使用
   ```
   
   **Step 4: UI要素の構造化リスト作成**
   
   | # | 種別 | ラベル/テキスト | Tailwind CSS クラス | 遷移先/アクション | Blade行番号 | モバイル実装 |
   |---|------|---------------|-------------------|----------------|-----------|------------|
   | 1 | ヘッダー | 「タスクリスト」 | `text-2xl font-bold` | - | 10 | Header.tsx |
   | 2 | ボタン | 「新規作成」 | `bg-blue-600 rounded-lg px-4 py-2` | `/tasks/create` | 25 | FAB |
   | 3 | リスト | タスクカード | `bg-white shadow rounded-lg p-4` | `/tasks/{id}` | 50-80 | TaskCard.tsx |
   | 4 | バッジ | 優先度 | `bg-red-500 text-white px-2 py-1 rounded` | - | 65 | Badge.tsx |
   
   **Step 5: React Native実装への変換規則**
   
   | Tailwind CSS | React Native StyleSheet | 備考 |
   |-------------|------------------------|------|
   | `flex` | `display: 'flex'` | デフォルトでflex |
   | `flex-col` | `flexDirection: 'column'` | - |
   | `gap-4` | `gap: 16` | 1単位 = 4px |
   | `p-4` | `padding: 16` | 1単位 = 4px |
   | `rounded-lg` | `borderRadius: 8` | lg = 8px |
   | `shadow` | `shadowColor: '#000', shadowOffset: {width: 0, height: 2}, shadowOpacity: 0.1, shadowRadius: 4` | iOS/Android両対応 |
   | `bg-blue-600` | `backgroundColor: '#2563EB'` | Tailwind色定義参照 |
   | `text-2xl` | `fontSize: 24` | 2xl = 24px |
   | `font-bold` | `fontWeight: 'bold'` | - |
   
   **Step 6: レスポンシブ対応の実装（Dimensions API使用）**
   
   **原則**: **レスポンシブ対応を最優先**とし、Dimensions APIを積極的に使用してデバイス間の表示差異を吸収する。
   
   **必須参照ドキュメント**: `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`
   
   **実装手順**:
   
   1. **useResponsive() Hookのインポート**
      ```typescript
      import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '@/utils/responsive';
      import { useChildTheme } from '@/hooks/useChildTheme';
      
      const MyComponent = () => {
        const { width, deviceSize, isPortrait } = useResponsive();
        const isChildTheme = useChildTheme();
        const theme = isChildTheme ? 'child' : 'adult';
      ```
   
   2. **フォントサイズの動的計算**
      ```typescript
      const styles = StyleSheet.create({
        title: {
          fontSize: getFontSize(18, width, theme), // Web版 text-lg (18px) ベース
        },
        body: {
          fontSize: getFontSize(14, width, theme), // Web版 text-sm (14px) ベース
        },
      });
      ```
      
      **子ども向けテーマ**: 大人向けより20%大きいフォント（わかりやすさ重視）
   
   3. **余白の動的計算**
      ```typescript
      const styles = StyleSheet.create({
        container: {
          padding: getSpacing(16, width), // Web版 p-4 (16px) ベース
        },
        card: {
          marginBottom: getSpacing(12, width), // Web版 mb-3 (12px) ベース
        },
      });
      ```
      
      **最小余白**: baseSpacingの50%（視認性確保）
   
   4. **角丸の動的計算**
      ```typescript
      const styles = StyleSheet.create({
        card: {
          borderRadius: getBorderRadius(16, width), // Web版 rounded-2xl (16px) ベース
        },
      });
      ```
   
   5. **シャドウのプラットフォーム別対応**
      ```typescript
      const styles = StyleSheet.create({
        card: {
          ...getShadow(4), // elevation 4 相当（iOS/Android自動判定）
          backgroundColor: '#fff',
        },
      });
      ```
   
   6. **画面回転リスナーの実装**
      ```typescript
      const { isPortrait, isLandscape } = useResponsive();
      
      const styles = StyleSheet.create({
        container: {
          flexDirection: isLandscape ? 'row' : 'column',
        },
      });
      ```
   
   **ブレークポイント定義**（Android端末考慮済み）:
   
   | カテゴリ | 画面幅範囲 | 対象デバイス例 |
   |---------|-----------|--------------|
   | 超小型 | 〜320px | Galaxy Fold (280px), iPhone SE 1st (320px) |
   | 小型 | 321px〜374px | iPhone SE 2nd/3rd (375px), Pixel 4a (393px) |
   | 標準 | 375px〜413px | iPhone 12/13/14 (390px), Pixel 7 (412px) |
   | 大型 | 414px〜767px | iPhone Pro Max (430px) |
   | タブレット小 | 768px〜1023px | iPad mini (768px) |
   | タブレット | 1024px〜 | iPad Pro (1024px) |
   
   **Platform.select() 使用場面**:
   - カレンダー選択（DateTimePicker）
   - 時刻選択（DateTimePicker）
   - セレクトボックス（iOS: カスタム実装、Android: 標準Picker）
   - キーボード回避（KeyboardAvoidingView）
   - Safe Area対応（iOS）
   
   **Web CSS → React Native 変換**:
   
   | Web CSS | React Native StyleSheet |
   |---------|------------------------|
   | `text-lg` (18px) | `fontSize: getFontSize(18, width, theme)` |
   | `p-4` (16px) | `padding: getSpacing(16, width)` |
   | `rounded-2xl` (16px) | `borderRadius: getBorderRadius(16, width)` |
   | `shadow` | `...getShadow(4)` |
   | `bg-gradient-to-r from-[#59B9C6] to-purple-600` | `<LinearGradient colors={['#59B9C6', '#9333EA']} />` |
   
   **Step 7: Webアプリとの差分確認**
   
   - ✅ **実装済み**: Webアプリの全UI要素をモバイル版に実装
   - ❌ **未実装**: Webアプリに存在するがモバイル版に未実装の要素（理由を明記）
   - ⚠️ **モバイル独自**: モバイル特有のUI要素（FAB、スワイプ等）
   
   **禁止事項**:
   - ❌ Bladeファイルを読まずに推測でデザイン実装
   - ❌ Tailwind CSSクラスを無視して独自デザイン実装
   - ❌ レスポンシブブレークポイントを確認せず375px幅以外を基準にする
   - ❌ Webアプリに存在する要素をモバイル版で省略（情報の過不足は不可）
   
   **チェックリスト**:
   - [ ] Bladeファイルを1行目から最終行まで読解した
   - [ ] app.css、dashboard.css等のすべてのCSSファイルを参照した
   - [ ] JavaScript動的生成のHTML/スタイルも確認した
   - [ ] Tailwind CSSクラスを抽出し、React Native StyleSheetに変換した
   - [ ] **`useResponsive()` Hookを実装した**
   - [ ] **`getFontSize()` でフォントサイズを動的計算した**
   - [ ] **`getSpacing()` で余白を動的計算した**
   - [ ] **`getBorderRadius()` で角丸を動的計算した**
   - [ ] **`getShadow()` でPlatform別シャドウを設定した**
   - [ ] **画面回転リスナーを実装した**
   - [ ] **子ども向けテーマでフォントを1.2倍に拡大した**
   - [ ] **ヘッダータイトルの折り返し対策を実装した**（adjustsFontSizeToFit使用）
   - [ ] **モーダルカードの見切れ対策を実装した**（Android対応）
   - [ ] UI要素を構造化リスト（表形式）にまとめた
   - [ ] Webアプリの全UI要素をモバイル版に実装した
   - [ ] Webアプリとの差分を明記した
   - [ ] **Android実機テスト**（Pixel 7、Galaxy S21、Galaxy Fold）
   - [ ] **iOS実機テスト**（iPhone SE、iPhone 12、iPhone Pro Max）
   - [ ] **タブレット実機テスト**（iPad mini、iPad Pro）
   - [ ] **画面回転テスト**（縦向き・横向き両方）

12. **API追加時の手順（必須）**
   
   **原則**: 新しいAPIエンドポイントを追加する場合、**必ず以下の順序で実施**してください。
   
   **Step 1: 要件定義書の作成・更新**
   - 保管先: `/home/ktr/mtdev/definitions/mobile/` または `/home/ktr/mtdev/definitions/`
   - 新機能の場合: 新規ファイル作成（例: `SubscriptionManagementAPI.md`）
   - 既存機能の拡張: 既存ファイルに追記（更新履歴に記録）
   - 必須セクション: API仕様、リクエスト/レスポンス形式、エラーハンドリング
   
   **Step 2: バックエンドAPI実装**
   - `routes/api.php` にルート定義追加
   - Action-Service-Repositoryパターンで実装
   - バリデーション、認証、権限チェック実装
   - テスト実装（Feature Test, Unit Test）
   
   **Step 3: OpenAPI仕様書の更新** ← **【必須】**
   - ファイル: `/home/ktr/mtdev/docs/api/openapi.yaml`
   - tagsセクションに新しいタグを追加（必要に応じて）
   - pathsセクションにエンドポイント定義を追加
   - リクエスト/レスポンススキーマを定義（JSON Schema形式）
   - 認証方式（`SanctumAuth`）を明記
   - HTTPステータスコード（200, 400, 401, 403, 404, 500）を定義
   
   **Step 4: モバイル側の実装**
   - TypeScript型定義作成（`src/types/{機能名}.types.ts`）
   - API通信層実装（`src/services/{機能名}.service.ts`）
   - カスタムフック実装（`src/hooks/use{機能名}.ts`）
   - 画面コンポーネント実装（`src/screens/{機能名}/`）
   - テスト実装（`__tests__/{機能名}/`）
   
   **OpenAPI更新時の注意事項**:
   
   1. **既存エンドポイントとの整合性確認**
      ```bash
      # routes/api.phpで定義されている全エンドポイントを確認
      grep -E "Route::(get|post|put|patch|delete)" /home/ktr/mtdev/routes/api.php
      
      # openapi.yamlで定義されているエンドポイントを確認
      grep -E "^  /" /home/ktr/mtdev/docs/api/openapi.yaml
      ```
   
   2. **スキーマ定義のベストプラクティス**
      - プロパティには必ず `description` を記載
      - enum値は実装と完全一致させる
      - nullable値は明示的に `nullable: true` を記載
      - 例を `example` プロパティで提供
   
   3. **レスポンススキーマの共通化**
      - `components.schemas` に共通スキーマを定義
      - 複数エンドポイントで使用するスキーマは `$ref` で参照
   
   **禁止事項**:
   - ❌ OpenAPI仕様書を更新せずにAPI実装
   - ❌ routes/api.phpとopenapi.yamlの不一致を放置
   - ❌ エンドポイント追加後にOpenAPI仕様書の更新を忘れる
   - ❌ スキーマ定義が実装と異なる（型、フィールド名、enum値）
   
   **チェックリスト**:
   - [ ] 要件定義書を作成・更新した
   - [ ] routes/api.phpにルート定義を追加した
   - [ ] バックエンドAPI実装完了（Action、Service、Repository）
   - [ ] バックエンドテスト実装完了（Feature Test、Unit Test）
   - [ ] **openapi.yamlにエンドポイント定義を追加した**
   - [ ] リクエスト/レスポンススキーマを定義した
   - [ ] HTTPステータスコードを定義した
   - [ ] モバイル側の型定義を作成した
   - [ ] モバイル側のAPI通信層を実装した
   - [ ] モバイル側のテストを実装した

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

- **要件定義**: `/home/ktr/mtdev/definitions/*.md`（認証機能の記載あり）
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

4. **型安全性に関する禁止事項（重要）**
   
   **原則**: テストコードでも型安全性を最優先とし、`as any`による型チェック回避を極力避ける。
   
   #### ❌ 禁止事項
   
   - **`as any`によるモック型キャスト**
     ```typescript
     // ❌ NG: 型チェックを完全に回避
     mockUseTokens.mockReturnValue({
       balance: null,
       loadBalance: jest.fn(),
       // 必須プロパティが不足しているが as any でエラーを隠蔽
     } as any);
     ```
   
   - **部分的なモック定義で`as any`使用**
     ```typescript
     // ❌ NG: 実装の変更時にテストが追従できない
     const mockUser = {
       id: 1,
       name: 'Test User',
     } as any;
     ```
   
   #### ✅ 推奨事項
   
   1. **型定義を明示的にエクスポート**
      ```typescript
      // hooks/useTokens.ts
      export interface UseTokensReturn {
        balance: TokenBalance | null;
        loadBalance: () => Promise<void>;
        // ... 全プロパティを定義
      }
      
      export const useTokens = (): UseTokensReturn => {
        // 実装
      };
      ```
   
   2. **テストヘルパー関数で完全な型を生成**
      ```typescript
      // __tests__/helper.ts
      const createMockUseTokensReturn = (
        overrides?: Partial<UseTokensReturn>
      ): UseTokensReturn => ({
        balance: null,
        packages: [],
        history: [],
        isLoading: false,
        error: null,
        loadBalance: jest.fn(),
        loadPackages: jest.fn(),
        // ... 全プロパティにデフォルト値
        ...overrides,
      });
      
      // テストで使用
      mockUseTokens.mockReturnValue(createMockUseTokensReturn({
        balance: mockBalance,
      }));
      ```
   
   3. **型定義の更新時にテストも自動検証される**
      - インターフェース変更時、ヘルパー関数がコンパイルエラーを出す
      - 実装とテストの乖離を防ぐ
   
   #### 例外的に許容されるケース
   
   - **外部ライブラリの型が不完全な場合**（Navigation等）
     ```typescript
     // ✅ OK: React Navigationの型が複雑すぎる場合のみ
     const mockNavigation = {
       goBack: jest.fn(),
       navigate: jest.fn(),
     } as any;
     ```
   
   - **その場合も、可能な限り部分的な型定義を使用**
     ```typescript
     // ✅ Better: 使用するメソッドのみ型定義
     const mockNavigation: Pick<NavigationProp<any>, 'goBack' | 'navigate'> = {
       goBack: jest.fn(),
       navigate: jest.fn(),
     };
     ```
   
   #### 違反時の影響
   
   - ❌ 実装変更時にテストが気づかず失敗
   - ❌ 実行時エラーが発生（型チェックをすり抜けた不正な値）
   - ❌ コードレビューで指摘 → 修正コスト増加
   
   **TypeScriptの型システムを最大限活用し、テストコードの信頼性を確保すること。**

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
   - **モバイルアプリ**: `/home/ktr/mtdev/docs/reports/mobile/`
   - **ポータルサイト**: `/home/ktr/mtdev/docs/reports/portal/`
   - ファイル名: `YYYY-MM-DD-{タスク名}-report.md`
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
