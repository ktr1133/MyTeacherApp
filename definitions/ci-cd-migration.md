# CI/CD 移行計画（GitHub Actions）

## 更新履歴

- 2025-11-28: 初版作成。ポータル配信・Task Service CI/CD のパス修正・検証結果を反映。

## 1. 概要

- 対象: `.github/workflows/deploy-portal.yml`（ポータル静的配信）、`.github/workflows/task-service-ci-cd.yml`（Task Service）
- 背景: リポジトリルートを `/` に移行。従来の `laravel/` プレフィックス前提のパスや、相対パス `../services/...` に起因する誤トリガー・実行失敗が発生。
- 目的: トリガー・ビルド・デプロイの各フェーズでのパス整合と、手動実行の検証性向上。

## 2. 現状と課題

- 現状
  - Portal: 修正反映済み、手動実行成功。
  - Task Service: パス修正反映済み。push トリガーでの実行前提、手動実行は未対応。
- 課題
  - 手動検証の効率化（`workflow_dispatch` 未定義の解消）
  - AWS 側リソース（ECR/ECS/CodeDeploy）設定のドキュメント整備と整合確認
  - キャッシュ戦略（Composer/npm）の最適化

## 3. 変更内容（適用済み）

- `.github/workflows/task-service-ci-cd.yml`
  - トリガー `paths` を `services/task-service/**` に統一（`pull_request` も同様）
  - `working-directory`、`cache-dependency-path`、Docker `context`/`file` を `services/task-service/...` に修正
  - CodeDeploy `codedeploy-appspec` を `services/task-service/aws/appspec.yml` に修正
- `.github/workflows/deploy-portal.yml`
  - `laravel/` プレフィックスを削除し、実パスに合わせた `paths` に修正
  - 不在の `scripts/export-portal-static.sh` 参照を削除し、`artisan-export-portal.php` を使用
  - エクスポート手順を PHP スクリプト実行へ統一

## 4. 検証結果（2025-11-28）

- Portal: `workflow_dispatch` による手動実行成功（Run ID: 19756254180）。ビルド→エクスポート→S3→CloudFront 一連成功。
- Task Service: `workflow_dispatch` 未定義のため手動起動は 422。push トリガーでの実行を想定。

## 5. 残課題（Backlog）

- [ ] Task Service に `workflow_dispatch` 追加（オプション: `build-only`、`skip-deploy`）
- [ ] Codecov/成果物のパス点検・整合性テスト
- [ ] ECR/ECS/CodeDeploy 実リソース名・権限整合のチェックリスト化
- [ ] Composer/npm キャッシュキーの精査（ヒット率と実行時間のバランス）
- [ ] 障害通知のフォールバック設計（Slack 不達時の対応）

## 6. スケジュール（案）

- 〜2025-11-29: `workflow_dispatch` 追加、手動実行での Task Service 基本線検証
- 〜2025-12-02: ステージング環境での E2E デプロイ確認（develop）
- 〜2025-12-06: 本番反映（main）・運用ドキュメント更新

## 7. リスクと対応

- AWS 資材の権限不足/命名不整合 → 事前に `aws` CLI or IaC 定義で照合、権限レビュー
- CloudFront 無効化コスト → パス限定 or 更新頻度調整で最適化
- 手動実行の誤操作 → 入力パラメータで安全弁（`skip-deploy` 既定 `true` など）

## 8. 成果物

- 修正済みワークフロー:
  - `.github/workflows/deploy-portal.yml`
  - `.github/workflows/task-service-ci-cd.yml`
- 中途報告: `docs/reports/2025-11-28_GitHubActions_CICD_Migration_Mid_Report.md`
