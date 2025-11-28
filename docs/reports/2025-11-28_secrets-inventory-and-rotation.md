# シークレット棚卸し・削除およびローテーション方針（2025-11-28）

## 実施サマリ

- 目的: CIからDBメンテナンスを撤回した方針に沿い、未使用のリポジトリシークレットを棚卸し・削除。あわせてローテーションの推奨を提示。
- 実施: GitHub Actions リポジトリシークレット `DB_HOST`/`DB_PORT`/`DB_NAME`/`DB_USER`/`DB_PASSWORD` を削除済み（CIで未使用のため）。
- 現存シークレット: `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`、`S3_BUCKET_NAME`、`CLOUDFRONT_DISTRIBUTION_ID`/`CLOUDFRONT_DOMAIN_NAME`、`SLACK_WEBHOOK_URL`、`TEST_JWT_TOKEN`。

## 事実（リポジトリ現況）

- 削除前に存在したDB系シークレット:
  - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
  - 用途: `.github/workflows/task-service-ci-cd.yml` の `db-observe`（到達性問題により非採用）
- `DB_MAINTENANCE_LAMBDA`: リポジトリシークレットとしては未設定（存在なし）
- 削除後の一覧（抜粋）:
  - `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `S3_BUCKET_NAME`, `CLOUDFRONT_*`, `SLACK_WEBHOOK_URL`, `TEST_JWT_TOKEN`

## 実施手順（記録）

- 一覧: `gh secret list`
- 削除: `yes | gh secret delete <NAME>` をDB系5件に適用
- 確認: 再度 `gh secret list` により消滅を確認

## ローテーションおよびセキュリティ推奨

- GitHubリポジトリシークレット（現存）
  - `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`: 最小権限（ECR/ECS/CloudFront/S3必要範囲）でのIAMポリシー確認。定期ローテーション推奨。
  - `SLACK_WEBHOOK_URL`: 必要に応じてWebhook再発行。
  - `TEST_JWT_TOKEN`: テスト用の期限・権限を限定。必要に応じて再発行。

- リポジトリ内にコミットされている機密（開発用 `.env`）
  - `laravel/.env` にクラウド鍵/APIキー（例: Cognito、OpenAI、Replicate）が記録されているため、漏えいリスクが高い。
  - 推奨: 直近のローテーション（新キー発行と旧キー失効）、`.env`の秘匿化（サンプルは `.env.example`、実値はSecrets Manager/Parameter Storeやデプロイ環境変数）
  - `.env` が既に履歴に存在するため、秘匿化後も履歴上の秘匿値は無効化（キー失効）でリスク低減すること。

## 付帯対応（任意）

- `.github/workflows/task-service-ci-cd.yml` の `db-observe`/`db-optimize-*` セクションは利用停止に合わせてコメントアウト/削除を検討（将来復帰時はLambda方式を推奨）。
- TerraformバックエンドのS3+DynamoDBロック化と `*.tfstate*` のリポジトリ非管理化を検討。

## 参考コマンド

```bash
# 一覧
cd /home/ktr/mtdev
gh secret list

# 削除（例）
yes | gh secret delete DB_HOST

# 追加/更新（例）
echo -n "value" | gh secret set TEST_JWT_TOKEN
```
