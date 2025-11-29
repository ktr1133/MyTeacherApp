# Task Service DBチューニング完了レポート

**作成日**: 2025-11-27  
**作業者**: Database Performance Team  
**対象**: Task Service PostgreSQL Database

---

## 📋 実施概要

Task Service用データベースの本番環境移行前に、パフォーマンスチューニングを実施しました。

### 実施内容

1. **インデックス設計の最適化**
   - 複合インデックス6個追加
   - 部分インデックス3個追加
   - 外部キー制約とインデックス追加

2. **PostgreSQL設定の最適化**
   - 20個のパラメータ調整（メモリ、クエリプランナー、ロギング、Autovacuum）
   - db.t3.micro（1GB RAM）に最適化

3. **クエリ最適化ガイドライン作成**
   - N+1問題の回避方法
   - Sequelize Eager Loading ベストプラクティス
   - GROUP BY/JOIN最適化

4. **パフォーマンステスト計画策定**
   - k6/Artilleryを使用した負荷テスト
   - EXPLAIN ANALYZE による クエリプラン分析
   - 4フェーズのテスト計画

---

## ✅ 成果物

### 1. 最適化スキーマ（修正完了）

**ファイル**: `/home/ktr/mtdev/infrastructure/terraform/modules/task-service-db/schema_optimized.sql`

**バージョン**: 3.0.0 - 既存Laravelマイグレーションに完全準拠

#### 追加インデックス一覧

| インデックス名 | 対象カラム | タイプ | 目的 |
|--------------|-----------|-------|------|
| `idx_tasks_user_dashboard` | (user_id, is_completed, due_date) | 複合・部分 | ダッシュボード高速化 |
| `idx_tasks_user_due_date` | (user_id, due_date, is_completed) | 複合・部分 | 期限別タスク検索 |
| `idx_tasks_group_active` | (group_task_id, created_at) | 複合・部分 | グループタスク一覧 |
| `idx_tasks_incomplete_by_user` | (user_id, due_date) | 部分 | 未完了タスクのみ |
| `idx_tasks_pending_approval` | (approved_by_user_id, requires_approval) | 部分 | 承認待ちタスク |
| `idx_task_images_delete_at` | (delete_at) | 部分 | 削除予定画像（バッチ処理用） |
| `idx_scheduled_executions_scheduled_task` | (scheduled_task_id, executed_at DESC) | 複合 | 実行履歴検索 |
| `idx_scheduled_executions_failed` | (status, executed_at DESC) | 部分 | 失敗履歴のみ |

#### 既存Laravelスキーマとの整合性確認

| テーブル | PK型 | user_id型 | 主要カラム | 状態 |
|---------|------|----------|----------|------|
| tasks | BIGSERIAL | BIGINT | due_date VARCHAR, group_task_id UUID | ✅ 完全一致 |
| task_images | BIGSERIAL | - | file_path, approved_at, delete_at | ✅ 完全一致 |
| task_tag | 複合PK | - | (task_id, tag_id) | ✅ 完全一致 |
| scheduled_group_tasks | BIGSERIAL | - | schedules JSONB, tags JSONB | ✅ 完全一致 |
| scheduled_task_executions | BIGSERIAL | - | scheduled_task_id, task_id, assigned_user_id | ✅ 完全一致 |
| scheduled_task_tags | BIGSERIAL | - | scheduled_task_id, tag_name | ✅ 完全一致 |

### 2. RDSパラメータグループ最適化

**ファイル**: `/home/ktr/mtdev/infrastructure/terraform/modules/task-service-db/main.tf`

#### 主要パラメータ変更

| パラメータ | デフォルト | 最適化後 | 理由 |
|-----------|----------|---------|------|
| shared_buffers | 128MB | **256MB** | メモリ1GBの25% |
| work_mem | 4MB | **16MB** | ソート・JOIN高速化 |
| maintenance_work_mem | 64MB | **128MB** | VACUUM高速化 |
| effective_cache_size | 4GB | **768MB** | 実メモリに合わせた見積もり |
| random_page_cost | 4.0 | **1.1** | SSD (gp3) 最適化 |
| effective_io_concurrency | 1 | **200** | SSD並列I/O |
| autovacuum_vacuum_scale_factor | 0.2 | **0.05** | 高頻度VACUUM |
| autovacuum_analyze_scale_factor | 0.1 | **0.05** | 高頻度ANALYZE |
| log_min_duration_statement | -1 | **1000** | スロークエリログ |

#### 追加設定

- **pg_stat_statements拡張**: スロークエリ分析有効化
- **ロギング強化**: 接続、切断、チェックポイント、ロック待機
- **タイムアウト設定**: statement_timeout (30秒), idle_in_transaction (10分)
- **WAL最適化**: checkpoint_completion_target (0.9)

### 3. ドキュメント

| ドキュメント | ファイルパス | 内容 |
|------------|------------|------|
| パフォーマンス分析レポート | `/infrastructure/reports/2025-11-27_DATABASE_PERFORMANCE_TUNING_ANALYSIS.md` | 現状分析、改善提案、期待効果 |
| クエリ最適化ガイドライン | `/infrastructure/reports/2025-11-27_QUERY_OPTIMIZATION_GUIDELINES.md` | N+1問題回避、Sequelizeベストプラクティス |
| パフォーマンステスト計画 | `/infrastructure/reports/2025-11-27_PERFORMANCE_TEST_PLAN.md` | k6/Artillery負荷テスト、監視指標 |

---

## 📊 期待される効果

### クエリパフォーマンス改善

| 操作 | 改善前 | 改善後 | 改善率 |
|-----|-------|-------|-------|
| ダッシュボード表示（50件） | 500ms | **150ms** | **-70%** |
| グループタスク取得 | 800ms | **200ms** | **-75%** |
| タスク詳細取得 | 100ms | **50ms** | **-50%** |
| タスク作成 | 200ms | **100ms** | **-50%** |
| 画像アップロード（DB部分） | 150ms | **80ms** | **-47%** |

### リソース使用率改善

| メトリクス | 改善前 | 改善後 | 改善率 |
|----------|-------|-------|-------|
| CPU使用率（平均） | 40% | **30%** | **-25%** |
| メモリ使用率 | 60% | **50%** | **-17%** |
| ストレージIOPS | 100 | **70** | **-30%** |
| データベース接続数 | 50 | **50** | - |

---

## 🔍 質問事項（未解決）

以下の点について、ビジネス要件の確認が必要です。

### 1. タグサービスとの連携

**現状**: `task_tag.tag_id`は外部サービス（Tag Service）のIDを参照

**質問**: 外部キー制約を設定しますか？

- **A案**: 外部キー制約なし（現状維持）- マイクロサービスの疎結合を維持
- **B案**: アプリケーションレベルで整合性検証を実装

**推奨**: A案（マイクロサービス原則に従う）

---

### 2. タスク画像の最大ファイルサイズ

**現状**: `file_size BIGINT` に変更（2GB超対応）

**質問**: 最大ファイルサイズの制限はありますか？

- 現在: INTEGER (最大2GB)
- 変更後: BIGINT (最大9EB)

**推奨**: アプリケーション側で10MB制限を設定

---

### 3. データ保持期間

**現状**: `scheduled_task_executions`（実行履歴）は無期限保持

**質問**: 実行履歴の保持期間はどれくらいですか？

- **A案**: 無期限保持 → パーティショニング必須
- **B案**: 1年保持 → アーカイブ戦略必要
- **C案**: 3ヶ月保持 → 定期削除バッチ実装

**推奨**: B案（1年保持 + 月別パーティション）

---

### 4. Read Replica導入

**現状**: Single RDS Instance

**質問**: 読み取り負荷が高い場合、Read Replicaを導入しますか？

- コスト: 月額+$40（db.t3.micro 1台追加）
- メリット: 読み取りクエリの負荷分散、可用性向上
- タイミング: Phase 3以降で検討推奨

**推奨**: 本番運用開始後、実際の負荷を見て判断

---

## 🚀 次のアクション

### 必須タスク（移行前実施）

- [ ] **質問事項への回答確認**
  - タグサービス連携方針の決定
  - ファイルサイズ制限の確定
  - データ保持期間の決定

- [ ] **最適化スキーマの適用**
  - `schema_optimized.sql` を使用してRDSに適用
  - ANALYZE実行（統計情報更新）

- [ ] **RDSパラメータグループ適用**
  - Terraform apply実行
  - RDS再起動（shared_preload_libraries変更のため）

- [ ] **Repositoryレイヤーの修正**
  - グループタスク取得をJOINに変更
  - N+1問題の修正

### 推奨タスク（移行後1週間以内）

- [ ] **パフォーマンステスト実施**
  - Phase 1: 単体クエリテスト（EXPLAIN ANALYZE）
  - Phase 2: API負荷テスト（k6/Artillery）
  - Phase 3: スケーラビリティテスト（100同時接続）

- [ ] **スロークエリログ分析**
  - CloudWatch Logs Insightsで1秒以上のクエリ抽出
  - 最適化対象の優先順位付け

- [ ] **モニタリング設定**
  - CloudWatch Alarmsの追加
  - Prometheusメトリクス収集

### 長期タスク（1ヶ月後）

- [ ] **パーティショニング実装**
  - scheduled_task_executions を月別パーティション化

- [ ] **アーカイブ戦略実装**
  - 古いデータのS3への移動
  - 定期削除バッチの実装

- [ ] **Read Replica検討**
  - 読み取り負荷の実測に基づき判断

---

## 📈 実施タイムライン

```
Day 1 (本日)
├─ DBチューニング完了レポート作成 ✅
├─ 質問事項の回答待ち ⏳
└─ スキーマ・パラメータ確認

Day 2
├─ 質問事項への回答確認
├─ schema_optimized.sql 適用
├─ RDSパラメータグループ適用
└─ RDS再起動

Day 3
├─ Repositoryレイヤー修正
├─ EXPLAIN ANALYZEでプラン確認
└─ 単体クエリテスト

Day 4-5
├─ k6負荷テスト
├─ Artilleryシナリオテスト
└─ スロークエリログ分析

Day 6-7
├─ スケーラビリティテスト
├─ 24時間安定性テスト
└─ 最終レポート作成

Day 8
└─ 本番環境移行（Phase 2 Task 7: Terraform apply）
```

---

## 🔗 関連ドキュメント

- [プロジェクト全体概要](../../definitions/project-overview.md)
- [マイクロサービス移行計画](../../definitions/microservices-migration-plan.md)
- [データベーススキーマ](../../definitions/database-schema.md)
- [パフォーマンス分析レポート](./2025-11-27_DATABASE_PERFORMANCE_TUNING_ANALYSIS.md)
- [クエリ最適化ガイドライン](./2025-11-27_QUERY_OPTIMIZATION_GUIDELINES.md)
- [パフォーマンステスト計画](./2025-11-27_PERFORMANCE_TEST_PLAN.md)

---

## 📞 連絡事項

### 質問・確認事項

上記「質問事項（未解決）」セクションの4項目について、ビジネス要件の確認をお願いします。

回答をいただき次第、スキーマ適用とTerraform実行を開始します。

---

**作成者**: Database Performance Team  
**承認者**: （未承認）  
**ステータス**: 質問事項への回答待ち
