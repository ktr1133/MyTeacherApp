# 月次レポート画面改善提案

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-02 | GitHub Copilot | 初版作成: ユーザー指摘事項の改善提案 |
| 2025-12-02 | GitHub Copilot | メンバー別概況生成機能の設計更新: confirm-dialog使用、トークン消費量コンスト化、テストデータ作成方針追加、タスク傾向分析をOpenAI文書分類に変更、PDF生成を未着手事項化 |

## 概要

月次レポート画面（`/reports/monthly/{year}/{month}`）に関するユーザー指摘事項を整理し、改善方針を提案します。

---

## 指摘事項と対応方針

### 1. デザインの統一（全画面共通）

#### 指摘内容
> 他画面と乖離したデザインとなっている。welcome画面のデザインを踏襲してください。

#### 現状分析
- **welcome.blade.php**: グラデーション背景、ガラスモーフィズム効果、アニメーション多用
- **月次レポート画面**: シンプルなカードデザイン、グラデーション少ない

**主な差異**:
1. 背景: welcomeは`bg-gradient-to-br from-[#F3F3F2] via-white`、月次は単色
2. カード: welcomeは`.glass-card`（backdrop-blur）、月次は`bg-white`
3. アニメーション: welcomeは`.fadeInUp`、`.floating-icon`等、月次は無し
4. グラデーションテキスト: welcomeは`.gradient-text`多用、月次は無し

#### 改善提案

**Phase 1: 基本デザイン統一**
1. **背景グラデーション追加**
   ```html
   <div class="py-12 bg-gradient-to-br from-[#F3F3F2] via-white to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen">
   ```

2. **カードにガラスモーフィズム適用**
   ```html
   <div class="glass-card rounded-3xl border p-8">
       <!-- サマリーカード、AIコメント、グラフ等 -->
   </div>
   ```
   
   CSS追加:
   ```css
   .glass-card {
       background: rgba(255, 255, 255, 0.7);
       backdrop-filter: blur(10px);
       -webkit-backdrop-filter: blur(10px);
       border: 1px solid rgba(255, 255, 255, 0.3);
   }
   
   .dark .glass-card {
       background: rgba(17, 24, 39, 0.7);
       border: 1px solid rgba(255, 255, 255, 0.1);
   }
   ```

3. **アニメーション追加**
   - ページロード時: `.hero-title`, `.hero-subtitle`アニメーション
   - カードホバー: `.feature-card`スタイル適用
   - グラフエリア: `.fade-in`エフェクト

**Phase 2: 詳細デザイン強化**
4. **タイトル・ラベルにグラデーション**
   ```html
   <h2 class="gradient-text text-4xl font-bold">月次レポート</h2>
   ```

5. **装飾要素追加**
   ```html
   <!-- 背景の装飾円 -->
   <div class="absolute top-20 left-10 w-72 h-72 bg-[#59B9C6]/10 rounded-full blur-3xl floating-icon"></div>
   <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl floating-icon"></div>
   ```

**実装難易度**: ⭐⭐☆☆☆（中）  
**影響範囲**: `resources/views/reports/monthly/show.blade.php`, `resources/css/app.css`

---

### 2. AIコメントラベル変更

#### 指摘内容
> コメントラベルが「AI教師からのコメント」となっているが、「アバターからのコメント」と変更してください

#### 対応内容
**変更箇所**: `resources/views/reports/monthly/show.blade.php` L85

**変更前**:
```html
<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
    AI教師からのコメント
</h3>
```

**変更後**:
```html
<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
    アバターからのコメント
</h3>
```

**実装難易度**: ⭐☆☆☆☆（容易）  
**影響範囲**: 1ファイル、1箇所のみ

---

### 3. AIコメント生成プロンプト改善

#### 仕様変更内容
> 前月比が著しく変わったメンバーがいれば、コメントに取り上げるようにしてください。前月比からの変化とは増減ともに指します。減の場合は心配のコメントを、増の場合は称賛のコメントを生成するようにしてください。

#### 実装提案

**Step 1: 前月比変化率の計算強化**

`MonthlyReportService::generateAIComment()` に変化率計算ロジック追加:

```php
/**
 * メンバー別の前月比変化率を計算
 */
protected function calculateMemberChanges(MonthlyReport $report, MonthlyReport $previousReport): array
{
    $changes = [];
    $threshold = 30; // 30%以上の変化を「著しい変化」とする
    
    foreach ($report->member_task_summary as $userId => $currentSummary) {
        $previousSummary = $previousReport->member_task_summary[$userId] ?? null;
        
        if (!$previousSummary) {
            continue; // 前月データなし
        }
        
        $currentCount = $currentSummary['completed_count'] ?? 0;
        $previousCount = $previousSummary['completed_count'] ?? 0;
        
        if ($previousCount == 0) {
            if ($currentCount > 0) {
                $changes[] = [
                    'user_name' => $currentSummary['user_name'],
                    'type' => 'increase',
                    'change_percentage' => 100,
                    'current' => $currentCount,
                    'previous' => 0,
                ];
            }
            continue;
        }
        
        $changePercentage = round((($currentCount - $previousCount) / $previousCount) * 100);
        
        if (abs($changePercentage) >= $threshold) {
            $changes[] = [
                'user_name' => $currentSummary['user_name'],
                'type' => $changePercentage > 0 ? 'increase' : 'decrease',
                'change_percentage' => $changePercentage,
                'current' => $currentCount,
                'previous' => $previousCount,
            ];
        }
    }
    
    return $changes;
}
```

**Step 2: プロンプトに変化情報を追加**

`buildReportCommentSystemPrompt()` メソッド修正:

```php
protected function buildReportCommentSystemPrompt(array $memberSummary, array $personality, array $memberChanges = []): string
{
    $prompt = "あなたは以下の性格を持つ教師アバターです：\n";
    $prompt .= "- 性別: {$personality['sex']}\n";
    $prompt .= "- 口調: {$personality['tone']}\n";
    $prompt .= "- テンション: {$personality['enthusiasm']}\n";
    $prompt .= "- 丁寧さ: {$personality['formality']}\n";
    $prompt .= "- ユーモア: {$personality['humor']}\n\n";
    
    $prompt .= "以下のグループメンバーの月次実績を、あなたの性格に合わせたしゃべり口調でコメントしてください：\n\n";
    $prompt .= "【メンバー別実績】\n";
    
    foreach ($memberSummary as $member) {
        $prompt .= "- {$member['name']}: 通常タスク{$member['normal_tasks']}件、";
        $prompt .= "グループタスク{$member['group_tasks']}件完了";
        $prompt .= "（前月比{$member['change_sign']}{$member['change_percentage']}%）、";
        $prompt .= "報酬{$member['reward']}円\n";
        
        if (!empty($member['top_tasks'])) {
            $prompt .= "  完了した主なタスク: " . implode('、', $member['top_tasks']) . "\n";
        }
    }
    
    // ★新規追加: 著しい変化があったメンバーの情報
    if (!empty($memberChanges)) {
        $prompt .= "\n【特に注目すべきメンバー】\n";
        foreach ($memberChanges as $change) {
            if ($change['type'] === 'increase') {
                $prompt .= "- {$change['user_name']}: 前月から{$change['change_percentage']}%増加！";
                $prompt .= "（{$change['previous']}件 → {$change['current']}件）素晴らしい成長です。\n";
            } else {
                $prompt .= "- {$change['user_name']}: 前月から{$change['change_percentage']}%減少";
                $prompt .= "（{$change['previous']}件 → {$change['current']}件）心配ですが、優しく励ましてください。\n";
            }
        }
    }
    
    $prompt .= "\nコメントは3-5文程度で、メンバーの頑張りを称賛し、次月への励ましを含めてください。";
    $prompt .= "特に著しい変化があったメンバーには必ず言及してください。";
    
    return $prompt;
}
```

**Step 3: generateAIComment()呼び出し修正**

```php
public function generateAIComment(MonthlyReport $report): ?string
{
    // ... 既存のコード ...
    
    // ★前月レポート取得
    $previousMonth = Carbon::createFromFormat('Y-m-d', $report->report_month)->subMonth();
    $previousReport = $this->repository->findByGroupAndMonth(
        $report->group_id, 
        $previousMonth->format('Y-m')
    );
    
    // ★著しい変化の計算
    $memberChanges = [];
    if ($previousReport) {
        $memberChanges = $this->calculateMemberChanges($report, $previousReport);
    }
    
    // ★プロンプト生成（変化情報を追加）
    $systemPrompt = $this->buildReportCommentSystemPrompt($memberSummary, $personality, $memberChanges);
    
    // ... 既存のOpenAI呼び出し ...
}
```

**実装難易度**: ⭐⭐⭐☆☆（中〜高）  
**影響範囲**: `MonthlyReportService.php`（3メソッド追加・修正）

---

### 4. グラフ表示不具合（11月分が非表示）

#### 指摘内容
> 11月分の積み上げ棒グラフが画面に表示できていません。まずはデバッグから始めてください。

#### デバッグ手順

**Step 1: データ確認**
```bash
# 11月分のmonthly_reportsデータ確認
docker exec mtdev-db-1 psql -U postgres -d myteacher -c "
SELECT 
    id, 
    report_month, 
    member_task_summary::jsonb ? '8' as has_user8,
    group_task_summary::jsonb ? '8' as has_group_task,
    jsonb_array_length(member_task_summary::jsonb -> '8' -> 'tasks') as task_count
FROM monthly_reports 
WHERE group_id = 1 AND report_month = '2025-11-01';"

# 過去6ヶ月分のレポート確認
docker exec mtdev-db-1 psql -U postgres -d myteacher -c "
SELECT report_month, id 
FROM monthly_reports 
WHERE group_id = 1 
ORDER BY report_month DESC 
LIMIT 6;"
```

**Step 2: getTrendData()ログ追加**

`MonthlyReportService::getTrendData()` にデバッグログ:

```php
public function getTrendData(Group $group, string $yearMonth, int $months = 6): array
{
    Log::debug('getTrendData called', [
        'group_id' => $group->id,
        'yearMonth' => $yearMonth,
        'months' => $months,
    ]);
    
    $baseDate = Carbon::createFromFormat('Y-m', $yearMonth);
    $labels = [];
    $memberData = [];
    
    // ... 既存のループ ...
    for ($i = $months - 1; $i >= 0; $i--) {
        $targetMonth = $baseDate->copy()->subMonths($i);
        $targetYearMonth = $targetMonth->format('Y-m');
        $labels[] = $targetMonth->format('n月');
        
        $report = $this->repository->findByGroupAndMonth($group->id, $targetYearMonth);
        
        Log::debug('Fetching report for month', [
            'target' => $targetYearMonth,
            'found' => $report !== null,
            'member_count' => $report ? count($report->member_task_summary ?? []) : 0,
        ]);
        
        // ... 残りの処理 ...
    }
    
    Log::debug('getTrendData result', [
        'labels' => $labels,
        'dataset_count' => count($datasets),
        'member_count' => count($memberData),
    ]);
    
    return [
        'labels' => $labels,
        'datasets' => $datasets,
        'members' => array_map(fn($data) => $data['name'], $memberData),
    ];
}
```

**Step 3: Blade側のデバッグ**

`show.blade.php` のJavaScript部分にログ追加:

```javascript
@if(!empty($trendData['datasets']))
const trendData = @json($trendData);

console.log('Trend data loaded:', {
    labels: trendData.labels,
    datasetCount: trendData.datasets.length,
    datasets: trendData.datasets.map(ds => ({
        label: ds.label,
        data: ds.data,
        dataSum: ds.data.reduce((a, b) => a + b, 0)
    }))
});

new Chart(ctx, {
    // ... 既存の設定 ...
});
@else
console.warn('No trend data available');
@endif
```

**実装難易度**: ⭐⭐☆☆☆（デバッグ作業）  
**影響範囲**: `MonthlyReportService.php`, `show.blade.php`

---

### 5. 詳細テーブルのスマホ表示改善

#### 指摘内容
> 画面幅430の表示状態が貼り付けられた画像になります。視認性が悪く、改善してください。まずは改善の方向性について提案してください。

#### 現状の問題点（予想）
- テーブルが横幅を超えてはみ出す
- 文字が小さく読みにくい
- スクロール操作が必要で煩雑

#### 改善提案（3パターン）

**提案A: カード形式に変換（推奨）**

画面幅768px未満でテーブルをカードレイアウトに切り替え。

**メリット**:
- ✅ 縦スクロールのみで全情報確認可能
- ✅ タップ領域が広く操作しやすい
- ✅ welcomeページのデザインと統一感

**実装イメージ**:
```html
<!-- デスクトップ: テーブル表示 -->
<div class="hidden md:block">
    <table class="min-w-full">
        <!-- 既存のテーブル -->
    </table>
</div>

<!-- モバイル: カード表示 -->
<div class="md:hidden space-y-4">
    @foreach($tasks as $task)
    <div class="glass-card p-4 rounded-lg border">
        <div class="flex items-start justify-between mb-2">
            <h4 class="font-semibold text-gray-900 dark:text-white">
                {{ $task['title'] }}
            </h4>
            <span class="text-sm px-2 py-1 rounded {{ $task['is_group'] ? 'bg-purple-100' : 'bg-blue-100' }}">
                {{ $task['is_group'] ? 'グループ' : '通常' }}
            </span>
        </div>
        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <p>📅 {{ $task['completed_at'] }}</p>
            <p>💰 報酬: {{ $task['reward'] }}円</p>
            @if(!empty($task['tags']))
            <div class="flex flex-wrap gap-1 mt-2">
                @foreach($task['tags'] as $tag)
                <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded-full text-xs">
                    {{ $tag }}
                </span>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
```

**実装難易度**: ⭐⭐⭐☆☆（中）

---

**提案B: アコーディオン形式**

タスクタイトルをクリックすると詳細が展開。

**メリット**:
- ✅ 画面領域を節約
- ✅ 必要な情報のみ表示可能

**デメリット**:
- ❌ ワンタップ増える
- ❌ 全体把握が難しい

**実装難易度**: ⭐⭐⭐⭐☆（中〜高）

---

**提案C: スワイプ式横スクロール**

テーブルを横スクロール可能にし、カラムを優先度順に配置。

**メリット**:
- ✅ テーブル構造を維持
- ✅ 実装が簡単

**デメリット**:
- ❌ 横スクロール操作が必要
- ❌ 全カラムが一目で見えない

**実装難易度**: ⭐⭐☆☆☆（容易）

---

**推奨**: **提案A（カード形式）** - welcomeページのデザインと統一でき、視認性も最高。

---

### 6. メンバー別概況生成機能の追加

#### 仕様変更内容
> 詳細テーブルのヘッダにメンバー別の概況まとめコメントを生成するボタンを追加してください。このボタンを押下すると、トークン消費を警告するモーダルが表示されたあと、待機状態を経て選択しているメンバーの完了したタスク、グループタスクの件数、完了したタスクの傾向、前月比についてコメントを生成し、モーダルにコメントと円グラフを表示する。生成した結果はPDFとしてダウンロード可能とする。PDFにはグラフとコメントのみ記載することとする。このコメント生成にはトークンを消費することとする。

#### 実装提案

**Phase 1: UI実装（ボタン・モーダル）**

**1. ボタン追加**
```html
<!-- task-detail-table.blade.php のヘッダー部 -->
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-4">
        <label for="member-filter">メンバー選択:</label>
        <select id="member-filter" class="form-select rounded-lg border-gray-300 dark:border-gray-600">
            <option value="">全員</option>
            <!-- メンバー選択肢 -->
        </select>
    </div>
    
    <!-- ★新規追加 -->
    <button id="generate-summary-btn" 
            data-member-id=""  {{-- 選択中のメンバーID --}}
            data-token-cost="{{ config('const.token.consumption.member_summary_generation') }}"  {{-- トークン消費量 --}}
            data-token-balance="{{ auth()->user()->token_balance }}"  {{-- 現在の残高 --}}
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4aa5b3] hover:to-purple-700 text-white font-medium rounded-lg transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        概況レポート生成
    </button>
</div>

{{-- 既存のconfirm-dialogコンポーネントを使用 --}}
<x-confirm-dialog />
```

**JavaScript（トークン警告とconfirm-dialog連携）**
```javascript
document.getElementById('generate-summary-btn')?.addEventListener('click', function() {
    const btn = this;
    const memberId = btn.dataset.memberId;
    const tokenCost = parseInt(btn.dataset.tokenCost);
    const tokenBalance = parseInt(btn.dataset.tokenBalance);
    
    // メンバー未選択チェック
    if (!memberId) {
        alert('メンバーを選択してください');
        return;
    }
    
    // トークン残高不足チェック
    if (tokenBalance < tokenCost) {
        showFlashMessage('error', `トークン残高が不足しています。\n必要: ${tokenCost.toLocaleString()}\n残高: ${tokenBalance.toLocaleString()}`);
        return;
    }
    
    // confirm-dialogで警告表示
    const message = `この機能は約 ${tokenCost.toLocaleString()} トークンを消費します。\n\n現在の残高: ${tokenBalance.toLocaleString()} トークン\n\n実行しますか?`;
    
    window.showConfirmDialog(message, () => {
        // 確認 → 生成処理開始
        generateMemberSummary(memberId);
    });
});
```

**トークン消費量の管理**
- `config/const.php` に `token.consumption.member_summary_generation` として定義
- 初期値: 50,000トークン（暫定値）
- **テスト実施後に実績値から算出して更新**

**トークン消費量の変動要因**
- ✅ **プロンプト（入力）**: タスク件数により変動大
- ✅ **レスポンス（出力）**: コメント生成のため変動中程度
- ユーザー指摘通り、プロンプトのトークン量が主要な変動要因

**テストデータ作成方針**

1ユーザーあたりのタスク量（通常+グループの合計）:
- **最小値**: 0件/日 × 30日 = 0件/月
- **最大値**: 10件/日 × 30日 = 300件/月

テストシナリオ:
```
ユーザーA: 0件/月（未活動）
ユーザーB: 30件/月（1件/日ペース）
ユーザーC: 150件/月（5件/日ペース）
ユーザーD: 300件/月（10件/日ペース - 最大想定）
```

これらのパターンで3回ずつテストを実施し、平均消費トークン量を算出してコンストに反映。

**3. 生成中モーダル**
```html
<!-- モーダル2: 生成中 -->
<div id="generating-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="glass-card rounded-2xl p-8 max-w-md w-full text-center">
            <div class="w-16 h-16 border-4 border-[#59B9C6] border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                レポート生成中...
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                AIがコメントとグラフを作成しています
            </p>
        </div>
    </div>
</div>
```

**4. 結果表示モーダル**
```html
<!-- モーダル3: 結果表示 -->
<div id="summary-result-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="glass-card rounded-2xl p-8 max-w-3xl w-full">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <span id="member-name-display"></span>さんの概況レポート
                </h3>
                <button id="close-result-modal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- AIコメント -->
            <div class="mb-6 p-6 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">アバターからのコメント</h4>
                <p id="ai-comment-display" class="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed"></p>
            </div>
            
            <!-- 円グラフ -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">タスク内訳</h4>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="member-pie-chart"></canvas>
                </div>
            </div>
            
            <!-- 統計サマリー -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-blue-600 dark:text-blue-400 mb-1">通常タスク</p>
                    <p id="normal-task-count" class="text-2xl font-bold text-blue-900 dark:text-blue-100">0</p>
                </div>
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <p class="text-sm text-purple-600 dark:text-purple-400 mb-1">グループタスク</p>
                    <p id="group-task-count" class="text-2xl font-bold text-purple-900 dark:text-purple-100">0</p>
                </div>
                <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                    <p class="text-sm text-amber-600 dark:text-amber-400 mb-1">前月比</p>
                    <p id="change-percentage" class="text-2xl font-bold text-amber-900 dark:text-amber-100">0%</p>
                </div>
            </div>
            
            <!-- アクションボタン -->
            <div class="flex gap-3">
                <button id="download-pdf-btn" 
                        class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-[#59B9C6] to-purple-600 hover:from-[#4aa5b3] hover:to-purple-700 text-white font-medium rounded-lg transition shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDFダウンロード
                </button>
                <button id="close-result-btn" 
                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                    閉じる
                </button>
            </div>
        </div>
    </div>
</div>
```

**Phase 2: バックエンド実装**

**1. Action作成**
```php
// App\Http\Actions\Reports\GenerateMemberSummaryAction
public function __invoke(Request $request): JsonResponse
{
    $validated = $request->validate([
        'report_id' => 'required|integer|exists:monthly_reports,id',
        'user_id' => 'required|integer|exists:users,id',
    ]);
    
    $user = $request->user();
    $report = MonthlyReport::findOrFail($validated['report_id']);
    $targetUser = User::findOrFail($validated['user_id']);
    
    // 権限チェック（同一グループのみ）
    if ($user->group_id !== $report->group_id) {
        abort(403);
    }
    
    // トークン残高チェック（コンストから取得）
    $requiredTokens = config('const.token.consumption.member_summary_generation');
    if ($user->token_balance < $requiredTokens) {
        return response()->json([
            'success' => false,
            'error' => 'トークン残高が不足しています',
            'required' => $requiredTokens,
            'balance' => $user->token_balance,
        ], 400);
    }
    
    try {
        // サマリー生成（Service経由）
        $summary = $this->monthlyReportService->generateMemberSummary($report, $targetUser);
        
        // トークン消費
        $this->tokenService->consumeTokens(
            $user,
            $summary['tokens_used'],
            'メンバー別概況レポート生成'
        );
        
        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    } catch (\Exception $e) {
        Log::error('メンバー別概況生成エラー', [
            'user_id' => $user->id,
            'target_user_id' => $targetUser->id,
            'report_id' => $report->id,
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'success' => false,
            'error' => '概況レポート生成中にエラーが発生しました',
        ], 500);
    }
}
```

**フラッシュメッセージ表示（JavaScript側）**
```javascript
function generateMemberSummary(memberId) {
    // 生成中モーダル表示
    showGeneratingModal();
    
    fetch('/reports/monthly/member-summary', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            report_id: document.querySelector('[data-report-id]').dataset.reportId,
            user_id: memberId,
        }),
    })
    .then(response => response.json())
    .then(data => {
        hideGeneratingModal();
        
        if (data.success) {
            // 結果表示モーダル
            showResultModal(data.data);
        } else {
            // エラーメッセージ（flash-messageと同じデザイン）
            showFlashMessage('error', data.error);
        }
    })
    .catch(error => {
        hideGeneratingModal();
        showFlashMessage('error', 'ネットワークエラーが発生しました');
    });
}

/**
 * フラッシュメッセージ表示（flash-message.blade.phpと同じデザイン）
 */
function showFlashMessage(type, message) {
    const colors = {
        success: { bg: 'bg-green-50', border: 'border-green-500', text: 'text-green-800' },
        error: { bg: 'bg-red-50', border: 'border-red-500', text: 'text-red-800' },
        warning: { bg: 'bg-yellow-50', border: 'border-yellow-500', text: 'text-yellow-800' },
        info: { bg: 'bg-blue-50', border: 'border-blue-500', text: 'text-blue-800' },
    };
    
    const color = colors[type] || colors.info;
    
    const flashDiv = document.createElement('div');
    flashDiv.className = `fixed top-4 right-4 z-50 max-w-sm w-full ${color.bg} ${color.border} ${color.text} border-l-4 p-4 rounded-lg shadow-lg`;
    flashDiv.innerHTML = `
        <div class="flex items-start">
            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 flex-shrink-0">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(flashDiv);
    
    // 5秒後に自動削除
    setTimeout(() => flashDiv.remove(), 5000);
}
```

**2. Service実装**
```php
// MonthlyReportService::generateMemberSummary()
public function generateMemberSummary(MonthlyReport $report, User $targetUser): array
{
    // メンバーのタスク集計
    $normalTasks = $report->member_task_summary[$targetUser->id] ?? [];
    $groupTasks = $report->group_task_summary[$targetUser->id] ?? [];
    
    $normalCount = $normalTasks['completed_count'] ?? 0;
    $groupCount = $groupTasks['completed_count'] ?? 0;
    
    // 前月比計算
    $previousMonth = Carbon::createFromFormat('Y-m-d', $report->report_month)->subMonth();
    $previousReport = $this->repository->findByGroupAndMonth(
        $report->group_id,
        $previousMonth->format('Y-m')
    );
    
    $previousCount = 0;
    if ($previousReport) {
        $prevNormal = $previousReport->member_task_summary[$targetUser->id]['completed_count'] ?? 0;
        $prevGroup = $previousReport->group_task_summary[$targetUser->id]['completed_count'] ?? 0;
        $previousCount = $prevNormal + $prevGroup;
    }
    
    $currentCount = $normalCount + $groupCount;
    $changePercentage = $previousCount > 0 
        ? round((($currentCount - $previousCount) / $previousCount) * 100) 
        : ($currentCount > 0 ? 100 : 0);
    
    // タスク傾向分析（OpenAI文書分類）
    $taskTitles = [];
    foreach (array_merge($normalTasks['tasks'] ?? [], $groupTasks['tasks'] ?? []) as $task) {
        $taskTitles[] = $task['title'];
    }
    
    // OpenAIで文書分類
    $topCategory = null;
    if (!empty($taskTitles)) {
        $topCategory = $this->classifyTaskTitles($taskTitles);
    }
    
    // AIコメント生成
    $comment = $this->generateMemberComment($targetUser, [
        'normal_count' => $normalCount,
        'group_count' => $groupCount,
        'change_percentage' => $changePercentage,
        'top_category' => $topCategory,  // タグ → カテゴリに変更
        'avatar' => $report->group->master->teacher_avatar ?? null,
    ]);
    
    return [
        'member_name' => $targetUser->username,
        'normal_task_count' => $normalCount,
        'group_task_count' => $groupCount,
        'total_count' => $currentCount,
        'previous_count' => $previousCount,
        'change_percentage' => $changePercentage,
        'top_category' => $topCategory,  // タグ → カテゴリに変更
        'ai_comment' => $comment['comment'],
        'tokens_used' => $comment['tokens_used'],
        'chart_data' => [
            'labels' => ['通常タスク', 'グループタスク'],
            'data' => [$normalCount, $groupCount],
        ],
    ];
}

/**
 * タスク件名をOpenAIで文書分類
 * 
 * @param array $taskTitles タスク件名の配列
 * @return string 最も傾向の強いカテゴリ
 */
protected function classifyTaskTitles(array $taskTitles): string
{
    $titlesText = implode("\n", $taskTitles);
    
    $prompt = <<<EOT
以下のタスク件名を分析し、最も傾向の強いカテゴリを1つだけ返してください。

タスク件名:
{$titlesText}

カテゴリ例: 学習、業務、プロジェクト、日常作業、コミュニケーション、その他

最も傾向の強いカテゴリのみを回答してください（説明不要）。
EOT;
    
    $result = $this->openAIService->requestCompletion($prompt, [
        'max_tokens' => 50,
        'temperature' => 0.3,
    ]);
    
    return trim($result['content'] ?? 'その他');
}
```

**Phase 3: PDF生成（未着手事項）**

**実装タイミング**: 別タスクでまとめて着手

**理由**:
- PDF生成処理は複数の機能（月次レポート、メンバー別概況等）で共通利用予定
- PDFテンプレートの共通デザイン設計から始める必要がある
- dompdf統合、Chart.js画像化、Base64エンコード等の基盤整備が必要

**実装予定内容**:

1. **ライブラリ導入**: `barryvdh/laravel-dompdf`
2. **共通PDFテンプレート作成**: ヘッダー、フッター、スタイル定義
3. **Chart.js画像化処理**: canvas.toDataURL()でBase64変換
4. **メンバー別概況PDF**: グラフとコメントのみ記載
5. **ダウンロードエンドポイント追加**: `/reports/monthly/member-summary/{reportId}/{userId}/pdf`

**暫定対応**:
- モーダル内の「PDFダウンロード」ボタンは非表示またはdisabled状態
- 結果表示モーダルのみ実装（コメント、グラフ、統計サマリー）

**TODO（別タスク）**:
```
[ ] dompdfライブラリ導入
[ ] PDF共通テンプレート設計・実装
[ ] Chart.js画像化ユーティリティ作成
[ ] メンバー別概況PDF生成Action実装
[ ] PDFダウンロードルート追加
[ ] PDFダウンロードボタン有効化
```

**参考実装（将来用）**:

```php
use Barryvdh\DomPDF\Facade\Pdf;

// GenerateMemberSummaryAction に追加
public function downloadPdf(Request $request, int $reportId, int $userId): Response
{
    $summary = $this->monthlyReportService->generateMemberSummary(...);
    
    $pdf = Pdf::loadView('reports.monthly.member-summary-pdf', [
        'summary' => $summary,
        'report' => $report,
        'member' => $targetUser,
    ]);
    
    return $pdf->download("member-summary-{$targetUser->username}-{$report->report_month}.pdf");
}
```

**PDF用Bladeテンプレート**:
```html
<!-- resources/views/reports/monthly/member-summary-pdf.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $member->username }}さんの概況レポート</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .comment-box { padding: 20px; background: #f3f4f6; border-radius: 8px; margin-bottom: 30px; }
        .chart { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $member->username }}さんの概況レポート</h1>
        <p>{{ $report->report_month }}</p>
    </div>
    
    <div class="comment-box">
        <h2>アバターからのコメント</h2>
        <p>{{ $summary['ai_comment'] }}</p>
    </div>
    
    <div class="chart">
        <h2>タスク内訳</h2>
        <!-- Chart.jsをPHPのGD/Imagickで画像化してbase64埋め込み -->
        <img src="data:image/png;base64,{{ $summary['chart_image_base64'] }}" />
    </div>
    
    <div class="stats">
        <p>通常タスク: {{ $summary['normal_task_count'] }}件</p>
        <p>グループタスク: {{ $summary['group_task_count'] }}件</p>
        <p>前月比: {{ $summary['change_percentage'] }}%</p>
    </div>
</body>
</html>
```

**実装難易度**: ⭐⭐⭐⭐⭐（高）  
**影響範囲**: 
- Action: 1新規 + 1ルート追加
- Service: 2メソッド追加
- Blade: 2ファイル（モーダル、PDF）
- JavaScript: イベントハンドラ、Chart.js統合
- PDF: dompdf統合、画像生成

---

## 実装優先度

| 項目 | 優先度 | 難易度 | 実装時間 |
|------|--------|--------|---------|
| 2. AIコメントラベル変更 | 🔥 高 | ⭐ | 5分 |
| 4. グラフ表示デバッグ | 🔥 高 | ⭐⭐ | 1-2時間 |
| 3. AIコメントプロンプト改善 | 🔶 中 | ⭐⭐⭐ | 2-3時間 |
| 1. デザイン統一 | 🔶 中 | ⭐⭐ | 3-4時間 |
| 5. スマホ表示改善 | 🔶 中 | ⭐⭐⭐ | 2-3時間 |
| 6. メンバー別概況生成 | 🔵 低 | ⭐⭐⭐⭐⭐ | 8-10時間 |

---

## 次のステップ

1. **即座に実施**: AIコメントラベル変更（5分）
2. **最優先**: グラフ表示デバッグ（ログ追加→原因特定→修正）
3. **Phase 1実装**: デザイン統一 + スマホ表示改善（1日）
4. **Phase 2実装**: AIコメントプロンプト改善（半日）
5. **Phase 3実装**: メンバー別概況生成（2日）

---

## 備考

- 各改善項目は独立しており、順不同で実装可能
- メンバー別概況生成は大規模機能のため、別途詳細設計書作成を推奨
- PDF生成にはサーバー側のImageMagick/GDインストールが必要（Chart.jsの画像化のため）
