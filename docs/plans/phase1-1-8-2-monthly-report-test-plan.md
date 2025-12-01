# 月次レポート機能 テスト計画

## Phase 1.1.8-2 実装内容の検証

### 1. データベース構造確認 ✅
- [x] monthly_reportsテーブル存在確認
- [x] ai_commentカラム (TEXT)
- [x] ai_comment_tokens_usedカラム (INT, default: 0)
- [x] group_task_summaryカラム (JSON)

### 2. 構文エラーチェック ✅
- [x] OpenAIService.php
- [x] MonthlyReportService.php
- [x] ShowMonthlyReportAction.php
- [x] その他関連ファイル

### 3. リレーション修正 ✅
- [x] Group::master() リレーション使用に修正
- [x] Null safe operator追加

### 4. 機能テスト（次のステップ）
- [ ] 年月選択UIの動作確認
  - デスクトップ: 年月ドロップダウン
  - モバイル: input[type=month]
  - JavaScript遷移制御
  
- [ ] グラフ表示確認
  - Chart.js読み込み
  - 6ヶ月分のデータ表示
  - メンバー別色分け
  - 積み上げ棒グラフ
  - ダークモード対応
  
- [ ] AIコメント生成確認
  - OpenAI API呼び出し
  - アバター性格反映
  - トークン使用量記録
  - エラー時のグレースフルデグラデーション
  
- [ ] タスク詳細テーブル確認
  - メンバーフィルター
  - 通常タスク表示
  - グループタスク表示
  - タグ表示
  - 報酬表示

### 5. エッジケースのテスト
- [ ] データなし（新規グループ）
- [ ] アバターなし
- [ ] サブスクリプション非アクティブ
- [ ] OpenAI APIエラー
- [ ] 過去のレポートアクセス

### 実装済みコミット
1. cfa7639 - Performance.md Section 13追加
2. 6ac7e12 - データベースマイグレーション
3. 1684d07 - 年月選択UI + グラフ実装
4. 9a5d21f - タスク詳細テーブルコンポーネント化
5. e760107 - AIコメント生成機能実装
6. 5142285 - Group::master()リレーション修正

### 次のアクション
画面アクセスによる統合テスト実施
