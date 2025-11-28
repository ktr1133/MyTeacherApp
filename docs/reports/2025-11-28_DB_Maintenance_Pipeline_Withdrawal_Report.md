# DBメンテナンスのCI/CD組み込み中止レポート（2025-11-28）

## 要約

- 背景: CI/CDにDB観測（db-observe）および最適化（db-optimize）を組み込む試行を実施。
- 事実: 本番RDSはプライベート配置（`publicly_accessible=false`）であり、GitHubホステッドランナー（VPC外）からRDSへ到達不能。
- 結果: `psql` による直結ベースの `db-observe` が恒常的に失敗。ネットワーク構成上の恒久対策なしに安定稼働は不可。
- 決定: 当面は「パイプラインへのDBメンテナンス（観測/最適化）の組み込みを中止」し、運用系に切り分ける。

## 経緯（時系列）

1. CI/CDの改善の一環として、以下を追加（手動実行対応）。
   - `db-observe`（読み取りのみ）: GitHub Actions上で `psql` を使い、上位クエリ/ロック/テーブル統計等を収集してアーティファクト化。
   - `db-optimize-(staging|production)`（任意実行）: Lambda 経由で `analyze`/`vacuum analyze`/`reindex` の実行を想定。
2. 秘密情報（`DB_HOST`/`DB_PORT`/`DB_NAME`/`DB_USER`/`DB_PASSWORD`）を登録し、`db-observe` を手動実行。
3. 実行は失敗。ログ取得も不安定で、到達性問題が濃厚であることを確認。
4. Terraformの状態から、RDSがプライベート（`publicly_accessible=false`）であること、RDS Proxy未構築であることを確認。
5. 以上により、ホステッドランナーからの直接接続は構造的に困難と判断。

## 事実関係（リポジトリ内確認）

- RDS: `myteacher-production-db` が存在し、`publicly_accessible=false`（プライベート）。DB Subnet Group/SGを伴うプライベート配置。
- パラメータグループ: `shared_preload_libraries=pg_stat_statements`、`pg_stat_statements.track=all` など観測に有用な設定は適用済み。
- RDS Proxy: IaC/状態ファイル上の痕跡なし（未構築）。
- CI定義: `.github/workflows/task-service-ci-cd.yml` に `db-observe`（`psql`直結）と `db-optimize-*`（Lambda呼び出し）のジョブを定義。
- Lambda実装: Portal CMS用は存在するが、DB観測/最適化用Lambdaの実装は未検出。

## 根本原因

- 「RDSがVPC内プライベート」「GitHubホステッドランナーはVPC外」という構成が根因。到達性がなく、 `psql` 直結の `db-observe` は安定して動作しない。

## 決定

- 当面、CI/CDパイプラインにおけるDBメンテナンス（観測・最適化）の組み込みを取りやめる。
- 既存ワークフローの `db-observe`/`db-optimize-*` は「使わない前提」とし、ドキュメント上で非推奨化を明記。

## 影響範囲

- CI/CD: 手動実行でのDB観測/最適化を提供しない。アプリのビルド/デプロイフロー自体には影響なし。
- Secrets: 登録済みのDB接続用Secretsは運用判断で保持または削除。不要であれば削除を推奨。
- ドキュメント: 本レポートと計画書（`definitions/ci-cd-migration.md`）に決定を反映。

## 代替案（検討済み）

- 代替A: VPC内Lambdaで観測/最適化を実施（推奨）。
  - VPC/Subnet/SGを割当てたLambdaで `psql`/SDKを実行し、結果をS3へ保存。手動起動（コンソール/CLI）やCloudWatch Eventで定期観測。
- 代替B: VPC内セルフホステッドランナーを用意し、CIから実行。
  - 管理コストが高く、用途が限定的なため今回は見送り。
- 代替C: RDS Proxy導入 + 必要に応じて踏み台やPrivateLink整備。
  - 将来的な接続性改善策としては有効だが、今回のスコープ外。

## 今後の対応

- 本レポートを共有し、CI/CDからDBメンテナンスを切り離す方針を明確化。
- 必要に応じて:
  - 運用向けの「DB観測Lambda（Read-only）」と「DB最適化Lambda」を別途実装し、オンデマンド/定期運用へ委譲。
  - 不要Secretsの棚卸し・削除、漏えいリスクのある設定値のローテーション。
  - TerraformバックエンドのS3/DynamoDB化（`*.tfstate*`のリポジトリ排除）を検討。

## 参考（リポジトリ内痕跡）

- RDS関連: `infrastructure/terraform/terraform.tfstate*`（`publicly_accessible=false`、DB Subnet/SG、ParamGroup設定 等）
- CI定義: `.github/workflows/task-service-ci-cd.yml`（`db-observe`/`db-optimize-*`）
- 既存Lambda: `lambda/portal-cms/dist/portal-cms.zip`（Portal CMS用）
