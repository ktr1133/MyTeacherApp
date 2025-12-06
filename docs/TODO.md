# MyTeacher プロジェクト残タスク一覧

## 作成日

2025-12-02（更新: 2025-12-02 18:00）

## 概要

プロジェクト全体のドキュメント・レポート・プラン（**`multi-app-hub-infrastructure-strategy.md`含む**）を分析し、残タスクを優先度順に整理しました。

**参照ドキュメント**:
- `docs/architecture/multi-app-hub-infrastructure-strategy.md` - Phase 2以降のアーキテクチャ計画
- `docs/plans/phase1-1-stripe-subscription-plan.md` - Phase 1.1計画
- `docs/reports/2025-12-02-monthly-report-*.md` - 月次レポート改善状況
- 過去の会話履歴（個人別レポート生成要件）

---

## 🔴 高優先度（必須実装）

### 0. モバイルアプリ: テーマシステム実装（Phase 2.B-3）🆕⏳

**ステータス**: 実装中（基盤完了、UI実装待ち）  
**優先度**: 🔴 高（モバイルアプリの基盤機能）  
**作成日**: 2025-12-06

#### 0.1 Laravel側: テーマ取得専用API作成（完了）✅

**ステータス**: 完了  
**実装日**: 2025-12-06  
**優先度**: 🔴 高

**背景**:
- モバイルアプリでテーマ切り替え（adult/child）を実装中
- 現在は暫定的に `/api/v1/profile/edit` を使用（本来はプロフィール編集画面用）
- 責務の分離とパフォーマンス向上のため、専用API作成が必要

**完了した実装タスク**:
- [x] `GetCurrentUserApiAction.php` 作成（`app/Http/Actions/Api/User/`）
- [x] `routes/api.php` にルート追加（`GET /api/v1/user/current`）
- [x] Responder作成（`UserApiResponder.php`）
- [x] 統合テスト作成（認証済みユーザーの基本情報取得、6テスト成功）
- [x] API仕様書更新（`docs/api/openapi.yaml` - 認証API直後に配置）

**実装内容**:
- エンドポイント: `GET /api/v1/user/current`
- 認証: 必須（Sanctum token）
- 返却データ: id, username, name, theme, group_id, group_edit_flg
- 位置づけ: 全画面共通で使用（Web版ShareThemeMiddleware相当）

**返却データ例**:
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

**テスト結果**:
- 6テスト、25アサーション、全成功
- カバレッジ: 基本機能、認証、エラーハンドリング

**コミット**: 2025-12-06 feat(api): add GET /api/v1/user/current endpoint

#### 0.2 モバイル側: テーマシステム基盤（完了）✅

**ステータス**: 完了  
**実装日**: 2025-12-06

**完了項目**:
- [x] User型定義（ThemeType追加）
- [x] TaskService実装（9メソッド、エラーコード対応）
- [x] TaskServiceテスト（43テスト成功）
- [x] ThemeContext実装（Web版ミドルウェア相当）
- [x] errorMessagesユーティリティ（27種類のエラーコード）
- [x] ProfileService実装（暫定: /api/v1/profile/edit 使用）
- [x] ProfileServiceテスト（9テスト成功）

**コミット**: 2025-12-06（テーマシステム基盤実装）

#### 0.3 モバイル側: UI実装（完了）✅

**ステータス**: 完了  
**実装日**: 2025-12-06  
**優先度**: 🔴 高

**完了した実装タスク**:
- [x] useTasks Hook実装（419行）
  - TaskService呼び出し
  - テーマに応じたエラーメッセージ表示（getErrorMessage統合）
  - 完全CRUD + 承認/却下/画像管理
  - 楽観的更新（Optimistic Updates）でUX向上
- [x] TaskListScreen実装（513行）
  - テーマに応じた表示切り替え（やること/タスク一覧）
  - フィルター（全て/未完了/完了）
  - Pull-to-Refresh機能
  - ページネーション対応
- [x] CreateTaskScreen実装（402行）
  - グループタスク対応
  - 承認・画像必須フラグ設定
  - テーマ対応ラベル（やること作成/タスク作成）
- [x] TaskDetailScreen実装（643行）
  - 承認・却下UI（コメント入力）
  - 画像アップロード（expo-image-picker）
  - 画像削除機能
  - ステータスバッジ表示
- [x] UserService実装（107行）
  - GET /api/v1/user/current 専用サービス
  - グローバルテーマ取得（Web版ShareThemeMiddleware相当）
  - ProfileServiceと責務分離（CURRENT_USER vs USER_DATA）
  - ThemeContextで使用
- [x] テスト実装
  - useTasks.test.ts: 14テストケース
  - user.service.test.ts: 9テストケース
  - 全116テストパス（7テストスイート）
- [x] mobile-rules.md更新
  - Service層とHook層のメソッド命名規則追加
  - メソッド名不一致の再発防止規約（TypeScript規約4項）
- [x] 依存関係追加
  - @react-navigation/native-stack

**検証結果**:
- ✅ TypeScript型チェック: エラー0件
- ✅ Jestテスト: 116テスト全成功
- ✅ mobile-rules.md準拠確認完了

**コミット**: 2025-12-06 feat: Phase 2.B-3 モバイルタスク管理UI実装完了

**Phase 2.B-3完了**: モバイルタスク管理機能（一覧・作成・詳細・承認・画像）実装完了
  - タスク一覧表示
  - 完了/未完了切り替え
- [ ] CreateTaskScreen実装
  - グループタスク作成対応
  - テーマに応じたラベル表示
- [ ] TaskDetailScreen実装
  - タスク詳細表示
  - 承認/却下機能
  - 画像アップロード

**見積**: 3日

**関連**:
- 暫定対応: `/api/v1/profile/edit` 使用中
- 将来対応: Laravel側で専用API作成後、モバイル側を切り替え

---


**概要**: AI教師システム（別リポジトリ）

**実装タスク**:
- [ ] 新規Laravelリポジトリ作成
- [ ] 独立DB構築（AI-Sensei専用RDS）
- [ ] AI機能実装（学習支援、質問応答）
- [ ] MyTeacherとのAPI連携（タスクデータ活用）
- [ ] デプロイ環境構築（独立ECS Service）

**実施条件**: ParentShare完了後、ユーザー数500人超過時

**見積**: 20-30日

---


**作成者**: GitHub Copilot  
**最終更新**: 2025-12-02 18:00  
**次回更新**: Phase 1.1完了時（2025-12-20予定）  
**参照**: `docs/architecture/multi-app-hub-infrastructure-strategy.md`（Phase 2以降計画）
