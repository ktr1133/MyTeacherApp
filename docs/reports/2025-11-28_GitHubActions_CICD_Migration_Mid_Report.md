# GitHub Actions CI/CD 移行・実装修正 中途報告（2025-11-28）

## 要約
- 対象: `.github/workflows/deploy-portal.yml`（ポータル静的配信）、`.github/workflows/task-service-ci-cd.yml`（Task Service CI/CD）
- 目的: リポジトリルート移行に伴うトリガーパスの整合、実行手順の不整合是正、手動実行での検証
- 結果: ポータルは手動トリガーで成功。Task Serviceはパス修正済みだが手動トリガー未対応（`workflow_dispatch`未定義）

## 実施内容（修正点）
- `.github/workflows/task-service-ci-cd.yml`
  - トリガー `paths`: `services/task-service/**` に統一（`pull_request` の `../services/...` も修正）
  - `working-directory` / `cache-dependency-path` / Docker `context` / `file` を `services/task-service/...` に修正
  - CodeDeploy `codedeploy-appspec` を `services/task-service/aws/appspec.yml` へ修正
- `.github/workflows/deploy-portal.yml`
  - トリガー `paths`: `laravel/` プレフィックスを排除し、実パスに合わせて `resources/views/portal/**`、`public/images/**`、`public/build/**`
  - 存在しない `scripts/export-portal-static.sh` 参照を削除し、代わりに実在する `artisan-export-portal.php` を参照
  - エクスポート手順を `php artisan-export-portal.php` 実行に変更

## 実行結果（検証）
- ポータル配信ワークフロー
  - 手動起動: `gh workflow run deploy-portal.yml --ref main`
  - 実行監視: `gh run watch 19756254180 --exit-status`
  - ステータス: 成功（Run ID: 19756254180）
  - 成功ステップ: アセットビルド、静的エクスポート、S3同期、CloudFront無効化
- Task Service CI/CD
  - 手動起動: 422（`workflow_dispatch` 未定義のため）
  - 現状: push（`main`/`develop`）での自動実行のみ対応

## 影響範囲・リスク
- 影響範囲: GitHub Actionsの起動条件／パス依存、Dockerビルドのコンテキスト、CodeDeploy AppSpecの参照パス
- リスク:
  - Task Service 手動起動不可（検証効率低下）
  - AWSシークレット・ECR/ECS設定の外部依存（本番・検証環境差異による失敗可能性）
  - CloudFront無効化の都度コスト（必要性・頻度のチューニング要）

## 残課題（Backlog）
- [ ] `task-service-ci-cd.yml` に `workflow_dispatch` を追加（必要なら入力パラメータも設計）
- [ ] `task-service` の Codecov パス／成果物パスを再点検（現状は `services/task-service/coverage/`）
- [ ] ECR/ECS の実リソース名・リージョン・権限の整合確認（IaC/運用定義と照合）
- [ ] `deploy-portal.yml` の npm/composer キャッシュキー最適化（ヒット率改善）
- [ ] 監視・通知の一元化（Slack 失敗時フォールバック、障害時の連動対応）

## 次のアクション（提案）
- Task Service: `workflow_dispatch` を追加し、`gh` で手動起動→`test`→`build` の基本線を検証
- 機能フラグ: `build-only` / `skip-deploy` などの入力設計（検証時の安全弁）
- デプロイ検証: ステージング（`develop`）の動作確認ジョブのヘルスチェック強化

---
本ドキュメントは中途報告です。詳細は `definitions/ci-cd-migration.md` の進捗セクションを参照してください。