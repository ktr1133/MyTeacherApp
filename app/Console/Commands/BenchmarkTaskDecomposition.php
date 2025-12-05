<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AI\OpenAIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * タスク分解のインフラ負荷測定コマンド
 * 
 * 用途: OpenAI API呼び出し・JSON解析等のインフラ処理時間を測定
 */
class BenchmarkTaskDecomposition extends Command
{
    protected $signature = 'benchmark:task-decomposition
                            {user_id : ユーザーID}
                            {--samples=5 : 測定サンプル数}
                            {--task-title=プロジェクト企画書作成 : テストタスクタイトル}';

    protected $description = 'タスク分解のインフラ負荷を測定';

    /**
     * テスト用タスク例
     */
    private const TEST_TASKS = [
        '新規Webサイトのデザイン作成',
        'データベース設計と実装',
        '社内研修プログラムの企画',
        '四半期レポートの作成と提出',
        '新商品のマーケティング戦略立案',
    ];

    public function handle(OpenAIService $openAIService): int
    {
        $userId = $this->argument('user_id');
        $samples = (int) $this->option('samples');
        $taskTitle = $this->option('task-title');

        $user = User::find($userId);
        if (!$user) {
            $this->error("ユーザーID {$userId} が見つかりません");
            return 1;
        }

        // 認証コンテキスト設定
        \Illuminate\Support\Facades\Auth::setUser($user);
        
        // ログ出力を最小限に（エラー時のみ）
        Log::getLogger()->pushHandler(new \Monolog\Handler\NullHandler());

        $this->info("=== タスク分解インフラ負荷測定 ===");
        $this->info("ユーザー: {$user->name} (ID: {$user->id})");
        $this->info("サンプル数: {$samples}");
        $this->newLine();

        $results = [];

        for ($i = 1; $i <= $samples; $i++) {
            // バリエーションをつけるため、テストタスクをローテーション
            $currentTask = $i === 1 ? $taskTitle : self::TEST_TASKS[($i - 1) % count(self::TEST_TASKS)];
            
            $this->info("--- サンプル {$i}/{$samples} ---");
            $this->info("テストタスク: {$currentTask}");
            
            try {
                $result = $this->measureSingleDecomposition($user, $openAIService, $currentTask);
                $results[] = $result;
                
                $this->info("OpenAI API呼び出し時間: {$result['api_time']}秒");
                $this->info("JSON解析時間: {$result['parse_time']}秒");
                $this->info("DB保存時間: {$result['db_time']}秒");
                $this->info("合計インフラ時間: {$result['total_infra_time']}秒");
                $this->info("OpenAIトークン消費: {$result['openai_tokens']}トークン");
                $this->newLine();
                
                // 次のサンプルまで待機（API rate limit対策）
                if ($i < $samples) {
                    sleep(1);
                }
            } catch (\Exception $e) {
                $this->error("測定失敗: {$e->getMessage()}");
                Log::error('Benchmark task decomposition failed', [
                    'sample' => $i,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($results)) {
            $this->error('測定データが取得できませんでした');
            return 1;
        }

        // 統計分析
        $this->displayStatistics($results);

        return 0;
    }

    /**
     * 1回のタスク分解を測定
     */
    private function measureSingleDecomposition(
        User $user,
        OpenAIService $openAIService,
        string $taskTitle
    ): array {
        $context = "期間: 2, 重要度: 高";
        
        // Phase 1: OpenAI API呼び出し
        $apiStart = microtime(true);
        $aiResult = $openAIService->requestDecomposition($taskTitle, $context, false);
        $apiTime = microtime(true) - $apiStart;

        $usage = $aiResult['usage'] ?? [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];
        $openaiTokens = $usage['total_tokens'];

        // Phase 2: JSON解析
        $parseStart = microtime(true);
        $response = $aiResult['response'] ?? '';
        $proposed = $this->parseAIResponse($response);
        $parseTime = microtime(true) - $parseStart;

        if (empty($proposed)) {
            throw new \RuntimeException('AI応答の解析に失敗しました');
        }

        // Phase 3: DB保存（シミュレーション）
        $dbStart = microtime(true);
        DB::table('task_proposals')->insert([
            'user_id' => $user->id,
            'original_task_text' => $taskTitle,
            'proposal_context' => $context,  // NOT NULL制約対応
            'proposed_tasks_json' => json_encode($proposed),
            'model_used' => $aiResult['model'] ?? 'gpt-4',
            'prompt_tokens' => $usage['prompt_tokens'],
            'completion_tokens' => $usage['completion_tokens'],
            'total_tokens' => $openaiTokens,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lastId = DB::getPdo()->lastInsertId();
        
        // 後片付け
        DB::table('task_proposals')->where('id', $lastId)->delete();
        
        $dbTime = microtime(true) - $dbStart;

        $totalInfraTime = $apiTime + $parseTime + $dbTime;

        return [
            'api_time' => round($apiTime, 2),
            'parse_time' => round($parseTime, 4),
            'db_time' => round($dbTime, 4),
            'total_infra_time' => round($totalInfraTime, 2),
            'openai_tokens' => $openaiTokens,
        ];
    }

    /**
     * AI応答を解析（TaskProposalServiceと同じロジック）
     */
    private function parseAIResponse(string $response): array
    {
        $lines = explode("\n", $response);
        $tasks = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            // 番号付きリスト形式（1. 〜、- 〜など）
            if (preg_match('/^[\d\-\*\+]\.\s*(.+)$/', $line, $matches)) {
                $tasks[] = ['title' => trim($matches[1])];
            } elseif (preg_match('/^[\-\*\+]\s+(.+)$/', $line, $matches)) {
                $tasks[] = ['title' => trim($matches[1])];
            }
        }

        return $tasks;
    }

    /**
     * 統計情報を表示
     */
    private function displayStatistics(array $results): void
    {
        $this->info("=== 統計分析 ===");

        $apiTimes = array_column($results, 'api_time');
        $parseTimes = array_column($results, 'parse_time');
        $dbTimes = array_column($results, 'db_time');
        $totalInfraTimes = array_column($results, 'total_infra_time');
        $openaiTokens = array_column($results, 'openai_tokens');

        $this->table(
            ['指標', '平均', '最小', '最大', '標準偏差'],
            [
                ['OpenAI API時間（秒）', $this->avg($apiTimes), min($apiTimes), max($apiTimes), $this->stdDev($apiTimes)],
                ['JSON解析時間（秒）', $this->avg($parseTimes), min($parseTimes), max($parseTimes), $this->stdDev($parseTimes)],
                ['DB保存時間（秒）', $this->avg($dbTimes), min($dbTimes), max($dbTimes), $this->stdDev($dbTimes)],
                ['合計インフラ時間（秒）', $this->avg($totalInfraTimes), min($totalInfraTimes), max($totalInfraTimes), $this->stdDev($totalInfraTimes)],
                ['OpenAIトークン消費', $this->avg($openaiTokens), min($openaiTokens), max($openaiTokens), $this->stdDev($openaiTokens)],
            ]
        );

        $this->newLine();

        // トークン推奨値算出
        $avgInfraTime = $this->avg($totalInfraTimes);
        $avgOpenaiTokens = $this->avg($openaiTokens);
        
        // Fargate課金モデルに基づく試算
        // 想定: 0.25 vCPU, 0.5 GB メモリ, Fargate Spot
        $cpuCostPerSecond = 0.012144 / 3600;
        $memCostPerSecond = 0.001334 / 3600 * 0.5;
        $totalCostPerSecond = $cpuCostPerSecond + $memCostPerSecond;
        
        $infraCostUsd = $avgInfraTime * $totalCostPerSecond;
        $infraCostJpy = $infraCostUsd * 150;
        
        // トークン換算（500,000トークン = 400円）
        $recommendedTokens = ceil(($infraCostJpy / 400) * 500000);

        $this->info("【推奨設定値】");
        $this->info("インフラ負荷: {$recommendedTokens}トークン");
        $this->info("  └ 根拠: 平均インフラ時間 {$avgInfraTime}秒 × Fargate Spot料金");
        $this->info("  └ Fargateコスト試算: " . number_format($infraCostJpy, 4) . "円");
        $this->newLine();

        $this->info("【1リクエストあたりのトータルコスト】");
        $totalPerRequest = $avgOpenaiTokens + $recommendedTokens;
        $this->info("OpenAIトークン: " . round($avgOpenaiTokens) . "トークン");
        $this->info("インフラ負荷: {$recommendedTokens}トークン");
        $this->info("合計: {$totalPerRequest}トークン");
        $this->info("円換算: " . number_format(($totalPerRequest / 500000) * 400, 2) . "円");
        $this->newLine();

        $this->info("【設定例】");
        $this->line("'task_decomposition' => [");
        $this->line("    'infra_load' => {$recommendedTokens},  // 1リクエストあたり（測定値ベース）");
        $this->line("],");
    }

    private function avg(array $values): float
    {
        return round(array_sum($values) / count($values), 2);
    }

    private function stdDev(array $values): float
    {
        $avg = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($v) => ($v - $avg) ** 2, $values)) / count($values);
        return round(sqrt($variance), 2);
    }
}
