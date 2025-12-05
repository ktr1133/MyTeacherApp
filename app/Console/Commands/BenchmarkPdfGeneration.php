<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Report\MonthlyReportServiceInterface;
use App\Services\Report\PdfGenerationServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PDF生成のベンチマークコマンド
 * 
 * Chromiumを使用したPDF生成時のインフラ負荷を測定し、
 * トークン換算係数を決定するためのベンチマークツール
 */
class BenchmarkPdfGeneration extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'benchmark:pdf-generation
                            {--user-id= : 対象ユーザーID}
                            {--year-month= : 対象年月（YYYY-MM形式）}
                            {--samples=5 : 測定回数}
                            {--output= : 結果出力ファイルパス（オプション）}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'PDF生成のパフォーマンスベンチマーク（Chromium負荷測定）';

    /**
     * MonthlyReportService
     *
     * @var MonthlyReportServiceInterface
     */
    protected MonthlyReportServiceInterface $reportService;

    /**
     * PdfGenerationService
     *
     * @var PdfGenerationServiceInterface
     */
    protected PdfGenerationServiceInterface $pdfService;

    /**
     * コンストラクタ
     *
     * @param MonthlyReportServiceInterface $reportService
     * @param PdfGenerationServiceInterface $pdfService
     */
    public function __construct(
        MonthlyReportServiceInterface $reportService,
        PdfGenerationServiceInterface $pdfService
    ) {
        parent::__construct();
        $this->reportService = $reportService;
        $this->pdfService = $pdfService;
    }

    /**
     * コマンド実行
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('=== PDF Generation Benchmark ===');
        $this->newLine();

        // パラメータ取得
        $userId = $this->option('user-id');
        $yearMonth = $this->option('year-month') ?? Carbon::now()->format('Y-m');
        $samples = (int) $this->option('samples');
        $outputFile = $this->option('output');

        // ユーザー検証
        if (!$userId) {
            $this->error('--user-id オプションは必須です');
            return 1;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("ユーザーID {$userId} が見つかりません");
            return 1;
        }

        if (!$user->group) {
            $this->error("ユーザーにグループが設定されていません");
            return 1;
        }

        // タスク件数取得
        $taskCount = $this->getTaskCount($userId, $yearMonth);

        $this->info("ユーザー: {$user->email} (ID: {$userId})");
        $this->info("グループ: {$user->group->name}");
        $this->info("対象年月: {$yearMonth}");
        $this->info("完了タスク数: {$taskCount}件");
        $this->info("測定回数: {$samples}回");
        $this->newLine();

        // ベンチマーク実行
        $results = [];
        $progressBar = $this->output->createProgressBar($samples);

        for ($i = 1; $i <= $samples; $i++) {
            $result = $this->runSingleBenchmark($userId, $user->group->id, $yearMonth);
            $results[] = $result;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // 結果集計
        $summary = $this->analyzeBenchmarkResults($results, $taskCount);

        // 結果表示
        $this->displayResults($summary);

        // トークン換算提案
        $this->suggestTokenCost($summary);

        // ファイル出力
        if ($outputFile) {
            $this->saveResults($outputFile, [
                'user_id' => $userId,
                'user_email' => $user->email,
                'year_month' => $yearMonth,
                'task_count' => $taskCount,
                'samples' => $samples,
                'summary' => $summary,
                'raw_results' => $results,
                'timestamp' => now()->toIso8601String(),
            ]);
            $this->info("結果を {$outputFile} に保存しました");
        }

        return 0;
    }

    /**
     * 単一ベンチマーク実行
     *
     * @param int $userId
     * @param int $groupId
     * @param string $yearMonth
     * @return array
     */
    protected function runSingleBenchmark(int $userId, int $groupId, string $yearMonth): array
    {
        // メモリ・時間計測開始
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $peakMemoryBefore = memory_get_peak_usage(true);

        try {
            // === Phase 1: AI生成（OpenAI） ===
            $phase1Start = microtime(true);
            $result = $this->reportService->generateMemberSummary($userId, $groupId, $yearMonth);
            $phase1End = microtime(true);
            $phase1Time = $phase1End - $phase1Start;

            // === Phase 2: PDF生成（Chromium） ===
            $phase2Start = microtime(true);
            $user = \App\Models\User::find($userId);
            
            // PDF生成実行（Chromium起動）
            $pdfBinary = $this->pdfService->generateMemberSummaryPdf(
                $user,
                $yearMonth,
                $result['comment'],
                null  // 円グラフ画像は省略
            );
            
            $phase2End = microtime(true);
            $phase2Time = $phase2End - $phase2Start;

            // 計測終了
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $peakMemoryAfter = memory_get_peak_usage(true);

            return [
                'success' => true,
                'elapsed_time' => $endTime - $startTime,
                'memory_used' => $endMemory - $startMemory,
                'peak_memory' => $peakMemoryAfter - $peakMemoryBefore,
                'tokens_used' => $result['tokens_used'],
                'phase1_time' => $phase1Time,  // AI生成時間
                'phase2_time' => $phase2Time,  // Chromium時間
                'pdf_size' => strlen($pdfBinary),
            ];

        } catch (\Exception $e) {
            $endTime = microtime(true);

            return [
                'success' => false,
                'elapsed_time' => $endTime - $startTime,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ベンチマーク結果を分析
     *
     * @param array $results
     * @param int $taskCount
     * @return array
     */
    protected function analyzeBenchmarkResults(array $results, int $taskCount): array
    {
        $successResults = array_filter($results, fn($r) => $r['success']);

        if (empty($successResults)) {
            return ['error' => 'すべてのベンチマークが失敗しました'];
        }

        $times = array_column($successResults, 'elapsed_time');
        $memories = array_column($successResults, 'memory_used');
        $peaks = array_column($successResults, 'peak_memory');
        $tokens = array_column($successResults, 'tokens_used');
        $phase1Times = array_column($successResults, 'phase1_time');
        $phase2Times = array_column($successResults, 'phase2_time');
        $pdfSizes = array_column($successResults, 'pdf_size');

        return [
            'task_count' => $taskCount,
            'samples' => count($successResults),
            'avg_time' => array_sum($times) / count($times),
            'min_time' => min($times),
            'max_time' => max($times),
            'avg_memory' => array_sum($memories) / count($memories),
            'avg_peak_memory' => array_sum($peaks) / count($peaks),
            'avg_tokens' => array_sum($tokens) / count($tokens),
            'min_tokens' => min($tokens),
            'max_tokens' => max($tokens),
            'avg_phase1_time' => array_sum($phase1Times) / count($phase1Times),
            'avg_phase2_time' => array_sum($phase2Times) / count($phase2Times),
            'avg_pdf_size' => array_sum($pdfSizes) / count($pdfSizes),
        ];
    }

    /**
     * 結果表示
     *
     * @param array $summary
     * @return void
     */
    protected function displayResults(array $summary): void
    {
        if (isset($summary['error'])) {
            $this->error($summary['error']);
            return;
        }

        $this->info('=== ベンチマーク結果 ===');
        $this->newLine();

        $this->table(
            ['指標', '値'],
            [
                ['測定回数', $summary['samples']],
                ['タスク件数', $summary['task_count']],
                ['--- トータル処理 ---', ''],
                ['平均処理時間', number_format($summary['avg_time'], 3) . '秒'],
                ['最小処理時間', number_format($summary['min_time'], 3) . '秒'],
                ['最大処理時間', number_format($summary['max_time'], 3) . '秒'],
                ['平均メモリ使用量', $this->formatBytes($summary['avg_memory'])],
                ['平均ピークメモリ', $this->formatBytes($summary['avg_peak_memory'])],
                ['--- AI生成（OpenAI） ---', ''],
                ['Phase1平均時間', number_format($summary['avg_phase1_time'], 3) . '秒'],
                ['平均OpenAIトークン', number_format($summary['avg_tokens'], 0)],
                ['最小OpenAIトークン', $summary['min_tokens']],
                ['最大OpenAIトークン', $summary['max_tokens']],
                ['--- PDF生成（Chromium） ---', ''],
                ['Phase2平均時間', number_format($summary['avg_phase2_time'], 3) . '秒'],
                ['平均PDFサイズ', $this->formatBytes($summary['avg_pdf_size'])],
            ]
        );
    }

    /**
     * トークン換算を提案
     *
     * @param array $summary
     * @return void
     */
    protected function suggestTokenCost(array $summary): void
    {
        if (isset($summary['error'])) {
            return;
        }

        $this->newLine();
        $this->info('=== トークン換算提案（Chromium負荷） ===');
        $this->newLine();

        // Chromium処理時間（Phase2）のみを基準に換算
        $chromiumTimeSeconds = $summary['avg_phase2_time'];
        $avgMemoryMB = $summary['avg_peak_memory'] / (1024 * 1024);

        // 課金レート: 500,000トークン = 400円
        // Fargate Spot想定: 0.5 vCPU + 1GB = 約$0.003/分 ≈ 0.45円/分 ≈ 0.0075円/秒
        // トークン換算: 0.0075円/秒 ÷ (400円/500,000トークン) = 9.375トークン/秒

        // 簡易計算: Chromium処理時間（秒）× 10トークン/秒（概算係数）
        $suggestedInfraLoad = (int) ceil($chromiumTimeSeconds * 10);

        // 保守的な係数（1.3倍 - メモリ負荷を考慮）
        $conservativeLoad = (int) ceil($suggestedInfraLoad * 1.3);

        $this->line("Chromium平均処理時間: " . number_format($chromiumTimeSeconds, 2) . "秒");
        $this->line("平均ピークメモリ: " . number_format($avgMemoryMB, 2) . "MB");
        $this->line("平均PDFサイズ: " . $this->formatBytes($summary['avg_pdf_size']));
        $this->newLine();

        $this->line("推奨インフラ負荷トークン（基本）: {$suggestedInfraLoad}");
        $this->line("推奨インフラ負荷トークン（保守的 +30%）: {$conservativeLoad}");
        $this->newLine();

        $this->info("config/const.php の設定例:");
        $this->line("'monthly_report' => [");
        $this->line("    'infra_load' => {$conservativeLoad},  // Chromium負荷（測定値ベース）");
        $this->line("],");
        $this->newLine();

        // トータルコスト表示
        $totalCost = $summary['avg_tokens'] + $conservativeLoad;
        $costInYen = ($totalCost / 500000) * 400;

        $this->info("【トータルコスト試算】");
        $this->line("  OpenAIトークン: " . number_format($summary['avg_tokens'], 0));
        $this->line("  + インフラ負荷: {$conservativeLoad}");
        $this->line("  = 合計: " . number_format($totalCost, 0) . "トークン");
        $this->newLine();
        $this->info("円換算: 約" . number_format($costInYen, 2) . "円/回");
        $this->newLine();
        
        // Phase1とPhase2の比率表示
        $phase1Ratio = ($summary['avg_phase1_time'] / $summary['avg_time']) * 100;
        $phase2Ratio = ($summary['avg_phase2_time'] / $summary['avg_time']) * 100;
        
        $this->info("【処理時間比率】");
        $this->line("  Phase1（AI生成）: " . number_format($phase1Ratio, 1) . "%");
        $this->line("  Phase2（Chromium）: " . number_format($phase2Ratio, 1) . "%");
    }

    /**
     * タスク件数取得
     *
     * @param int $userId
     * @param string $yearMonth
     * @return int
     */
    protected function getTaskCount(int $userId, string $yearMonth): int
    {
        $startDate = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * バイト数を人間が読みやすい形式に変換
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 結果をファイルに保存
     *
     * @param string $filePath
     * @param array $data
     * @return void
     */
    protected function saveResults(string $filePath, array $data): void
    {
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
