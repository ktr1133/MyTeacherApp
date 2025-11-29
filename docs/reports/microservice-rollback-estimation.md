# マイクロサービス移行取りやめ・モノリス復旧 工数見積書

## 📊 概要

**評価日**: 2025-11-29
**対象**: MyTeacher AIタスク管理プラットフォーム
**現状**: 部分的マイクロサービス移行実装済み
**提案**: モノリス構成への復旧

---

## 🔍 実装済みマイクロサービス要素の現状分析

### 1. Task Service（Node.js マイクロサービス）
**実装状況**: ✅ 75%完了
- Express.js ベースのAPI実装
- Docker化、ECSデプロイ用設定
- GitHub Actions CI/CD パイプライン
- AWS設定（ECS, ECR, CodeDeploy）
- テスト実装（Jest, ESLint）

**削除対象ファイル**: 計37ファイル
```
services/task-service/
├── src/ (15ファイル)
│   ├── index.js, routes/, controllers/, middleware/, utils/
├── tests/ (8ファイル)
├── aws/ (5ファイル)
├── package.json, Dockerfile, .eslintrc, etc. (9ファイル)
.github/workflows/task-service-ci-cd*.yml (3ファイル)
```

### 2. AI Service（AWS SAM/Lambda）
**実装状況**: ✅ 60%完了
- SAM template.yaml（完全実装）
- Lambda関数ハンドラー（4系統）
- 分散トランザクション（Saga Pattern）
- DynamoDB、SQS、Step Functions設定

**削除対象ファイル**: 計22ファイル
```
services/ai-service/
├── template.yaml
├── src/handlers/ (16ファイル)
│   ├── avatar-generation-saga/
│   ├── compensation/
│   ├── orchestrator/
│   ├── propose-task/
└── microservice-*.md, *.js (5ファイル)
```

### 3. モバイルAPIインフラ
**実装状況**: ✅ 40%完了
- API ルート定義（mobile-api-routes.php）
- API Action/Request/Responder実装開始
- Sanctum認証統合
- WebSocket実装（リアルタイム同期）

**削除対象ファイル**: 計12ファイル
```
laravel/app/Http/Actions/Api/ (9ファイル)
laravel/app/Http/Requests/Api/ (2ファイル)
laravel/app/Http/Responders/Api/ (3ファイル)
laravel/app/Services/WebSocket/ (1ファイル)
api-routes/ (1ファイル)
websocket-server.sh (1ファイル)
```

---

## ⚡ 復旧作業項目・工数見積

### Phase 1: ファイル削除・クリーンアップ
**工数**: 0.5人日

**作業内容**:
- マイクロサービス関連ディレクトリ削除
  - `rm -rf services/` (71ファイル削除)
- GitHub Actions ワークフロー削除
  - `task-service-ci-cd*.yml` (3ファイル削除)
- API関連実装削除
  - `laravel/app/Http/Actions/Api/`, `Requests/Api/`, `Responders/Api/` (14ファイル)
- WebSocket実装削除
  - `laravel/app/Services/WebSocket/`
- 設定ファイルクリーンアップ

**リスク**: ❌ 低リスク
- 削除対象はすべて新規追加ファイル
- 既存Laravel機能への影響なし

### Phase 2: Laravel依存関係調整
**工数**: 1.0人日

**作業内容**:
- `AppServiceProvider.php` からAPI関連バインディング削除
- `routes/api.php` からモバイルAPI削除
- 未使用パッケージ削除
  - ReactPHP/Ratchet（WebSocket用）
  - AWS SDK（Lambda統合用）
- `composer.json`, `package.json` 最適化

**リスク**: ⚠️ 中リスク
- 依存関係の意図しない削除による影響
- Laravelサービス解決の破綻

### Phase 3: 設定・環境変数の復旧
**工数**: 0.5人日

**作業内容**:
- `.env` からマイクロサービス設定削除
  - AWS関連（ECS, Lambda, DynamoDB）
  - WebSocket設定
  - API Gateway設定
- `config/` から専用設定削除
- Docker設定の単純化（必要に応じて）

**リスク**: ❌ 低リスク
- 設定値削除のみ、機能追加なし

### Phase 4: テスト・ドキュメント修正
**工数**: 1.0人日

**作業内容**:
- マイクロサービス関連テスト削除
- CI/CD設定の単純化
- `README.md`, ドキュメント修正
- Git履歴の整理（必要に応じて）

**リスク**: ❌ 低リスク
- ドキュメント更新のみ

### Phase 5: 検証・動作確認
**工数**: 1.0人日

**作業内容**:
- Laravel単体動作確認
- 既存機能の回帰テスト
- データベース整合性確認
- パフォーマンス確認

**リスク**: ⚠️ 中リスク
- 予期しない機能影響の発見

---

## 📈 工数サマリー

| Phase | 作業内容 | 工数 | リスク |
|-------|----------|------|--------|
| 1 | ファイル削除・クリーンアップ | **0.5人日** | ❌ 低 |
| 2 | Laravel依存関係調整 | **1.0人日** | ⚠️ 中 |
| 3 | 設定・環境変数復旧 | **0.5人日** | ❌ 低 |
| 4 | テスト・ドキュメント修正 | **1.0人日** | ❌ 低 |
| 5 | 検証・動作確認 | **1.0人日** | ⚠️ 中 |

### 合計工数: **4.0人日** (約1週間)

---

## 🎯 復旧後のメリット

### ✅ 即座に得られる利益
1. **運用複雑性の大幅削減**
   - AWS インフラ管理不要（ECS, Lambda, DynamoDB等）
   - CI/CD パイプラインの簡素化
   - デプロイプロセスの単純化

2. **開発効率の向上**
   - 分散システムデバッグの回避
   - データ整合性管理の簡略化
   - トランザクション境界の明確化

3. **コスト削減**
   - AWS マネージドサービス費用削減
   - インフラ監視・管理工数削減
   - 複数環境維持コスト削減

### ✅ 長期的な利益
1. **技術的負債の軽減**
   - アーキテクチャの統一
   - チーム学習コストの削減
   - ライブラリ・フレームワーク依存の統一

2. **スケーラビリティの確保**
   - Laravel標準機能での拡張
   - 必要時の再マイクロサービス化が容易
   - 段階的な分散化戦略の採用可能

---

## ⚠️ 注意事項・リスク

### 高リスク要素
1. **依存関係の複雑な絡み合い**
   - 一部機能が無意識にマイクロサービス前提で実装されている可能性
   - Sanctum認証の設定変更が必要な場合

2. **データ移行の必要性**
   - DynamoDB等に保存済みデータがある場合の移行
   - ただし、実装段階のため影響は限定的

### 推奨対応策
1. **段階的削除**
   - 一度にすべて削除せず、段階的に実行
   - 各段階での動作確認を徹底

2. **バックアップ戦略**
   - 復旧作業前にGitタグでスナップショット作成
   - `git tag microservice-implementation-20251129`

3. **詳細テスト計画**
   - 既存ユーザーワークフローの完全確認
   - 特にAI機能、トークンシステムの動作検証

---

## 🚀 推奨実行タイミング

**最適実行期間**: 即座〜1週間以内

**理由**:
- マイクロサービス実装が中途段階（75%未満）
- 本格運用前のため、ユーザー影響最小
- 年末までに安定したモノリス構成に戻せる
- 将来のマイクロサービス化も、より慎重な計画で実施可能

**実行推奨順序**:
1. Gitスナップショット作成
2. Phase 1-3 (環境準備): 2日
3. Phase 4-5 (検証): 3日
4. 本格運用移行判定

---

## 📝 結論

マイクロサービス移行の取りやめは**実現可能かつ低リスク**です。

- **工数**: 4人日（1週間）
- **コスト**: 削除作業のみ、追加実装なし
- **リスク**: 中程度（十分な検証で回避可能）
- **メリット**: 運用複雑性大幅削減、開発効率向上

現在の実装段階であれば、モノリス復旧は最適な選択肢といえます。