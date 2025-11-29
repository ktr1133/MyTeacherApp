# スケジューラーログ日別ファイル化 実装レポート

**作成日**: 2025年11月27日  
**対象フェーズ**: 運用改善  
**ステータス**: ✅ 完了

---

## 概要

Laravel Schedulerのログ出力を単一ファイル形式から日別ファイル形式に変更し、ログ管理の改善とファイルサイズ制御を実現した。

### 変更内容サマリー

- **変更前**: `storage/logs/scheduler.log` (単一ファイル)
- **変更後**: `storage/logs/scheduler-YYYYMMDD.log` (日別ファイル)

---

## 完了したタスク

### 1. 要件定義の更新

**ファイル**: `definitions/batch.md`

以下のセクションを日別ファイル形式に更新:

- ✅ **Section 2.1 実行環境**: 本番環境のログ出力仕様を`scheduler-YYYYMMDD.log`に変更
- ✅ **Section 2.2.2 本番環境の設定**: 実装コード例とログファイル名を更新
- ✅ **Section 2.2.5 スケジューラーの監視**: ログ確認コマンドを日別形式に対応
  - 当日ログ: `tail -f storage/logs/scheduler-$(date +%Y%m%d).log`
  - 過去7日間: `ls -lt storage/logs/scheduler-*.log | head -7`
- ✅ **Section 2.5 ログ管理**: ログファイル配置の説明を更新
- ✅ **Section 7.2 運用手順**: 監視方法のコマンド例を更新

### 2. 実装ファイルの更新

#### `docker/entrypoint-production.sh`

**変更内容**:
```bash
# 変更前
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running scheduler..." >> storage/logs/scheduler.log 2>&1
su -s /bin/bash www-data -c "php artisan schedule:run" >> storage/logs/scheduler.log 2>&1

# 変更後
LOGFILE="storage/logs/scheduler-$(date '+%Y%m%d').log"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running scheduler..." >> "$LOGFILE" 2>&1
su -s /bin/bash www-data -c "php artisan schedule:run" >> "$LOGFILE" 2>&1
```

**ポイント**:
- `LOGFILE`変数で日別ログファイル名を動的生成
- 日付が変わると自動的に新しいファイルに切り替わる
- タイムスタンプ形式: `YYYYMMDD` (例: `scheduler-20251127.log`)

#### `.github/copilot-instructions.md`

**変更内容**:
```markdown
# 変更前
Cron設定: * * * * * cd /var/www/html && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1

# 変更後
スケジューラー設定: Docker entrypoint内で60秒間隔で実行、ログは storage/logs/scheduler-YYYYMMDD.log に日別出力
```

**理由**: 実際の実装がDockerベースであることを正確に反映

### 3. 本番環境へのデプロイ

#### デプロイ手順

1. **Dockerイメージビルド**:
   ```bash
   docker build -t myteacher-app:latest -f Dockerfile.production .
   ```
   - ビルド時間: 1.8秒（キャッシュ利用）
   - 結果: ✅ 成功

2. **ECRへのプッシュ**:
   ```bash
   docker tag myteacher-app:latest 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-production:latest
   docker push 469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-production:latest
   ```
   - イメージサイズ: 856 layers
   - Digest: `sha256:22c1fcbcfb904dde2f8af88cd0c3847c525dbf39cb7c4982d49e52988eb80ddc`
   - 結果: ✅ 成功

3. **ECSサービス更新**:
   ```bash
   aws ecs update-service \
     --cluster myteacher-production-cluster \
     --service myteacher-production-app-service \
     --force-new-deployment \
     --region ap-northeast-1
   ```
   - 結果: ✅ 成功

#### デプロイタイムライン

| 時刻 | イベント | ステータス |
|------|---------|-----------|
| 01:52:18 | 新タスク起動開始 | PRIMARY デプロイメント開始 |
| 01:52:51 | スケジューラー起動 (PID: 51) | ✅ 正常起動 |
| 01:53:14 | 2つ目のタスク起動 | ✅ 正常起動 |
| 01:54:07 | 旧タスク停止開始 | DRAINING状態へ移行 |
| 01:55:30 | デプロイ完了 | PRIMARY デプロイメントのみ |

#### デプロイ結果

- **タスク数**: 2タスク (Desired: 2, Running: 2)
- **ヘルスステータス**: `RUNNING HEALTHY`
- **タスク定義**: `myteacher-production-app:27`
- **アプリケーション応答**: HTTP 200 OK (0.197秒)

### 4. Git管理の適正化

#### 問題

`laravel/public/portal-static/build/`配下のビルド生成物がGit管理対象に含まれかけていた。

#### 対応

**`.gitignore`に追加**:
```
/public/portal-static/build
```

**確認結果**:
```bash
$ git check-ignore -v public/portal-static/build/build/index.html
.gitignore:19:/public/portal-static/build       public/portal-static/build/build/index.html

$ git status public/portal-static/build/
nothing to commit, working tree clean
```

**理由**:
- ビルド生成物は`npm run build`等で自動生成される
- リポジトリに含めるべきではない（サイズ肥大化、無駄なコンフリクト発生）
- 同様に`/public/build`も既に.gitignore済み

---

## 技術的な変更内容

### ログファイル命名規則

```bash
# 形式
storage/logs/scheduler-YYYYMMDD.log

# 例
storage/logs/scheduler-20251127.log  # 2025年11月27日
storage/logs/scheduler-20251128.log  # 2025年11月28日
```

### ログローテーションの仕組み

**自動ローテーション**:
- 日付が変わると自動的に新しいファイルに切り替わる
- シェルスクリプトの`$(date '+%Y%m%d')`で動的にファイル名を生成
- 追加の設定やlogrotate不要

**古いログの削除**:
```bash
# 手動削除（30日以上前）
find storage/logs/ -name "scheduler-*.log" -mtime +30 -delete

# 将来: バッチ処理で自動削除予定
```

### 監視・確認方法

#### 本番環境（CloudWatch Logs）

```bash
# リアルタイム監視
aws logs tail /ecs/myteacher-production --follow --region ap-northeast-1

# スケジューラー関連のみ
aws logs tail /ecs/myteacher-production --since 5m --region ap-northeast-1 | grep -i scheduler
```

#### ローカル環境

```bash
# 当日のログをリアルタイム監視
tail -f storage/logs/scheduler-$(date +%Y%m%d).log

# 過去7日間のログファイル一覧
ls -lt storage/logs/scheduler-*.log | head -7

# 特定日のログ確認
cat storage/logs/scheduler-20251127.log
```

---

## メリット

### 1. ログ管理の改善

**変更前の問題**:
- 単一ファイルが無制限に肥大化
- 特定日のログ確認が困難
- ファイルサイズが大きくなるとtailコマンドが遅延

**変更後の利点**:
- ✅ 日別にファイルが分割され、管理が容易
- ✅ 特定日のログ確認が簡単
- ✅ ファイルサイズが制御される（1日分のみ）

### 2. パフォーマンス向上

- 小さいファイルサイズ → ログ検索・表示が高速
- CloudWatch Logsの転送量削減（古いログは転送不要）

### 3. ストレージコスト削減

- 古いログファイルを選択的に削除可能
- 不要なログの蓄積を防止

### 4. 運用効率向上

- トラブルシューティング時に特定日のログを直接開ける
- `ls`コマンドで実行履歴が一目瞭然
- ログローテーション設定が不要（自動切り替え）

---

## トラブルシューティング

### ログファイルが作成されない

**確認**:
```bash
# コンテナ内でスケジューラープロセス確認
ps aux | grep schedule:run

# 権限確認
ls -la storage/logs/
```

**対処**:
```bash
# 権限修正
chown -R www-data:www-data storage/
chmod -R 775 storage/
```

### ログファイルが肥大化する

**原因**: 頻繁なエラー、詳細なデバッグログ

**対処**:
```bash
# ログレベル調整（.env）
LOG_LEVEL=info  # debug → info に変更

# 古いログ削除
find storage/logs/ -name "scheduler-*.log" -mtime +7 -delete
```

### 過去のログが見つからない

**確認**:
```bash
# ログファイル一覧
ls -lh storage/logs/scheduler-*.log

# CloudWatch Logsで確認
aws logs tail /ecs/myteacher-production --since 7d --region ap-northeast-1
```

---

## 運用手順

### 日常運用

1. **ログ確認**:
   ```bash
   # 当日のログ
   tail -100 storage/logs/scheduler-$(date +%Y%m%d).log
   ```

2. **過去7日間のログ確認**:
   ```bash
   ls -lt storage/logs/scheduler-*.log | head -7
   ```

3. **エラー検索**:
   ```bash
   grep -i error storage/logs/scheduler-$(date +%Y%m%d).log
   ```

### 定期メンテナンス

**月次タスク**:
```bash
# 30日以上前のログ削除
find storage/logs/ -name "scheduler-*.log" -mtime +30 -delete

# ディスク使用量確認
du -sh storage/logs/scheduler-*.log
```

### デプロイ時の確認

1. 新タスク起動後、スケジューラーログが正しく出力されているか確認
2. ログファイル名が`scheduler-YYYYMMDD.log`形式であることを確認
3. タイムスタンプが正しく記録されていることを確認

---

## 制約事項・注意点

### 1. タイムゾーン

- ファイル名の日付は**サーバーのタイムゾーン（UTC）**基準
- 日本時間で日付が変わっても、UTC基準で判定される
- 例: 日本時間 2025-11-28 08:00 → ファイル名は`scheduler-20251127.log`（UTC: 2025-11-27 23:00）

### 2. マルチインスタンス環境

- 各ECSタスクが独自のログファイルを持つ
- CloudWatch Logsで全タスクのログを集約して確認
- コンテナ内のログファイルは永続化されない（タスク再起動で消失）

### 3. ログ保持期間

- CloudWatch Logs: 30日間（設定済み）
- コンテナ内ログ: タスクが停止するまで（永続化なし）
- S3へのアーカイブ: 未実装（将来対応予定）

---

## 今後の改善案

### 1. 自動ログ削除バッチ

**目的**: 古いログファイルを自動削除

**実装案**:
```php
// app/Console/Commands/CleanSchedulerLogs.php
$daysToKeep = 30;
$files = glob(storage_path('logs/scheduler-*.log'));
foreach ($files as $file) {
    if (filemtime($file) < now()->subDays($daysToKeep)->timestamp) {
        unlink($file);
    }
}
```

**スケジュール**: 毎月1日 02:00

### 2. S3へのログアーカイブ

**目的**: 長期保存が必要なログをS3に保存

**実装案**:
```bash
# 7日以上前のログをS3にアップロード
find storage/logs/scheduler-*.log -mtime +7 -exec aws s3 cp {} s3://myteacher-logs/scheduler/ \;
```

### 3. ログ圧縮

**目的**: ストレージ容量削減

**実装案**:
```bash
# 前日のログを圧縮
gzip storage/logs/scheduler-$(date -d yesterday +%Y%m%d).log
```

### 4. エラー通知

**目的**: 重大なエラー発生時に即座に通知

**実装案**:
- CloudWatch Logs Insightsでエラーパターンを検知
- SNS経由でSlack/メール通知
- 実装: Terraform + CloudWatch Alarms

---

## 関連ドキュメント

- **要件定義書**: `definitions/batch.md`
- **環境変数定義**: `definitions/environment-variables.md`
- **アーキテクチャガイド**: `.github/copilot-instructions.md`
- **Phase 0.5完了レポート**: `infrastructure/reports/PHASE0.5_COMPLETION_REPORT.md`

---

## 変更履歴

| 日付 | 変更内容 | 担当者 |
|------|---------|--------|
| 2025-11-27 | 初版作成（スケジューラーログ日別化完了） | - |

---

**以上**

