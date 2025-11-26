<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 1.5 並行運用期間の認証メトリクス監視コマンド
 * 
 * 【実行頻度】5分ごと（Cron設定）
 * 【監視項目】
 * - 認証成功率（Breeze / Cognito）
 * - 認証失敗率
 * - ユーザーマッピングエラー
 * - レスポンスタイム
 * 
 * 【アラート】
 * - 認証失敗率 > 5%
 * - マッピングエラー発生
 * 
 * 使用方法:
 *   php artisan auth:monitor-dual-auth
 */
class MonitorDualAuthCommand extends Command
{
    /**
     * コマンド名
     */
    protected $signature = 'auth:monitor-dual-auth 
                            {--period=5 : 監視期間（分）}
                            {--alert : アラート送信を有効化}';

    /**
     * コマンド説明
     */
    protected $description = 'Phase 1.5: Breeze + Cognito並行運用の認証メトリクスを監視';

    /**
     * 認証成功率の警告閾値
     */
    private const SUCCESS_RATE_THRESHOLD = 99.5;

    /**
     * 認証失敗率の警告閾値
     */
    private const FAILURE_RATE_THRESHOLD = 5.0;

    /**
     * コマンド実行
     */
    public function handle(): int
    {
        $period = (int) $this->option('period');
        $alertEnabled = $this->option('alert');

        $this->info("🔍 Phase 1.5 並行運用監視開始（過去{$period}分間）");
        $this->newLine();

        // 1. ログファイルから認証メトリクスを収集
        $metrics = $this->collectAuthMetrics($period);

        // 2. メトリクスを表示
        $this->displayMetrics($metrics);

        // 3. 警告チェック
        $warnings = $this->checkWarnings($metrics);

        // 4. アラート送信
        if ($alertEnabled && !empty($warnings)) {
            $this->sendAlerts($warnings);
        }

        // 5. メトリクスをDBに保存（オプション）
        $this->saveMetrics($metrics);

        $this->newLine();
        $this->info('✅ 監視完了');

        return self::SUCCESS;
    }

    /**
     * 認証メトリクスを収集
     * 
     * @param int $period 監視期間（分）
     * @return array メトリクス配列
     */
    private function collectAuthMetrics(int $period): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            $this->warn("ログファイルが見つかりません: {$logFile}");
            return $this->getEmptyMetrics();
        }

        // 過去N分のログを解析
        $cutoffTime = now()->subMinutes($period);
        
        $breezeSuccess = 0;
        $cognitoSuccess = 0;
        $authFailure = 0;
        $mappingError = 0;
        $totalRequests = 0;

        // ログファイルを読み込み（効率化のため tail 使用）
        $lines = $this->getRecentLogLines($logFile, $period);

        foreach ($lines as $line) {
            // タイムスタンプ抽出
            if (!$this->isWithinPeriod($line, $cutoffTime)) {
                continue;
            }

            $totalRequests++;

            // Breeze認証成功
            if (str_contains($line, 'DualAuth: Breeze session authenticated')) {
                $breezeSuccess++;
            }

            // Cognito認証成功
            if (str_contains($line, 'DualAuth: Cognito JWT authenticated')) {
                $cognitoSuccess++;
            }

            // 認証失敗
            if (str_contains($line, 'DualAuth: Authentication failed')) {
                $authFailure++;
            }

            // ユーザーマッピングエラー
            if (str_contains($line, 'Cognito user not found in database')) {
                $mappingError++;
            }
        }

        $totalSuccess = $breezeSuccess + $cognitoSuccess;
        $totalAuth = $totalSuccess + $authFailure;

        return [
            'period' => $period,
            'total_requests' => $totalRequests,
            'total_auth_requests' => $totalAuth,
            'breeze_success' => $breezeSuccess,
            'cognito_success' => $cognitoSuccess,
            'total_success' => $totalSuccess,
            'auth_failure' => $authFailure,
            'mapping_error' => $mappingError,
            'success_rate' => $totalAuth > 0 ? ($totalSuccess / $totalAuth) * 100 : 0,
            'failure_rate' => $totalAuth > 0 ? ($authFailure / $totalAuth) * 100 : 0,
            'breeze_ratio' => $totalSuccess > 0 ? ($breezeSuccess / $totalSuccess) * 100 : 0,
            'cognito_ratio' => $totalSuccess > 0 ? ($cognitoSuccess / $totalSuccess) * 100 : 0,
        ];
    }

    /**
     * メトリクスを表示
     * 
     * @param array $metrics メトリクス配列
     */
    private function displayMetrics(array $metrics): void
    {
        $this->table(
            ['メトリクス', '値', 'ステータス'],
            [
                ['総リクエスト数', $metrics['total_requests'], ''],
                ['認証リクエスト数', $metrics['total_auth_requests'], ''],
                ['', '', ''],
                ['Breeze 認証成功', $metrics['breeze_success'], $this->getStatusIcon($metrics['breeze_success'] > 0)],
                ['Cognito 認証成功', $metrics['cognito_success'], $this->getStatusIcon($metrics['cognito_success'] > 0)],
                ['認証失敗', $metrics['auth_failure'], $this->getStatusIcon($metrics['auth_failure'] == 0)],
                ['マッピングエラー', $metrics['mapping_error'], $this->getStatusIcon($metrics['mapping_error'] == 0)],
                ['', '', ''],
                ['認証成功率', sprintf('%.2f%%', $metrics['success_rate']), $this->getStatusIcon($metrics['success_rate'] >= self::SUCCESS_RATE_THRESHOLD)],
                ['認証失敗率', sprintf('%.2f%%', $metrics['failure_rate']), $this->getStatusIcon($metrics['failure_rate'] < self::FAILURE_RATE_THRESHOLD)],
                ['', '', ''],
                ['Breeze 利用率', sprintf('%.2f%%', $metrics['breeze_ratio']), ''],
                ['Cognito 利用率', sprintf('%.2f%%', $metrics['cognito_ratio']), ''],
            ]
        );
    }

    /**
     * 警告チェック
     * 
     * @param array $metrics メトリクス配列
     * @return array 警告配列
     */
    private function checkWarnings(array $metrics): array
    {
        $warnings = [];

        // 認証成功率が低い
        if ($metrics['success_rate'] < self::SUCCESS_RATE_THRESHOLD && $metrics['total_auth_requests'] > 0) {
            $warnings[] = [
                'level' => 'CRITICAL',
                'message' => sprintf(
                    '認証成功率が閾値を下回っています: %.2f%% < %.2f%%',
                    $metrics['success_rate'],
                    self::SUCCESS_RATE_THRESHOLD
                ),
            ];
        }

        // 認証失敗率が高い
        if ($metrics['failure_rate'] > self::FAILURE_RATE_THRESHOLD && $metrics['total_auth_requests'] > 0) {
            $warnings[] = [
                'level' => 'WARNING',
                'message' => sprintf(
                    '認証失敗率が閾値を超えています: %.2f%% > %.2f%%',
                    $metrics['failure_rate'],
                    self::FAILURE_RATE_THRESHOLD
                ),
            ];
        }

        // マッピングエラーが発生
        if ($metrics['mapping_error'] > 0) {
            $warnings[] = [
                'level' => 'WARNING',
                'message' => sprintf(
                    'Cognitoユーザーのマッピングエラーが%d件発生しています（移行漏れの可能性）',
                    $metrics['mapping_error']
                ),
            ];
        }

        // 警告表示
        if (!empty($warnings)) {
            $this->newLine();
            $this->warn('⚠️  警告が検出されました:');
            foreach ($warnings as $warning) {
                $this->warn("[{$warning['level']}] {$warning['message']}");
            }
        } else {
            $this->newLine();
            $this->info('✅ 問題は検出されませんでした。');
        }

        return $warnings;
    }

    /**
     * アラート送信
     * 
     * @param array $warnings 警告配列
     */
    private function sendAlerts(array $warnings): void
    {
        foreach ($warnings as $warning) {
            Log::channel('slack')->warning($warning['message'], [
                'level' => $warning['level'],
                'phase' => 'Phase 1.5',
                'component' => 'DualAuthMiddleware',
            ]);
        }

        $this->info('📧 アラートを送信しました。');
    }

    /**
     * メトリクスをDBに保存
     * 
     * @param array $metrics メトリクス配列
     */
    private function saveMetrics(array $metrics): void
    {
        try {
            DB::table('auth_metrics')->insert([
                'timestamp' => now(),
                'period_minutes' => $metrics['period'],
                'total_requests' => $metrics['total_requests'],
                'breeze_success' => $metrics['breeze_success'],
                'cognito_success' => $metrics['cognito_success'],
                'auth_failure' => $metrics['auth_failure'],
                'mapping_error' => $metrics['mapping_error'],
                'success_rate' => $metrics['success_rate'],
                'failure_rate' => $metrics['failure_rate'],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // テーブルが存在しない場合はスキップ
            $this->warn('メトリクスの保存をスキップしました（auth_metricsテーブルが存在しません）');
        }
    }

    /**
     * 最近のログ行を取得
     * 
     * @param string $logFile ログファイルパス
     * @param int $period 期間（分）
     * @return array ログ行配列
     */
    private function getRecentLogLines(string $logFile, int $period): array
    {
        // tailコマンドで最新1000行を取得（効率化）
        $lines = [];
        $lineCount = min(1000, $period * 100); // 期間に応じて行数調整

        exec("tail -n {$lineCount} " . escapeshellarg($logFile), $lines);

        return $lines;
    }

    /**
     * ログ行が期間内かチェック
     * 
     * @param string $line ログ行
     * @param \Carbon\Carbon $cutoffTime カットオフ時刻
     * @return bool 期間内ならtrue
     */
    private function isWithinPeriod(string $line, $cutoffTime): bool
    {
        // Laravel ログフォーマット: [2025-11-26 12:34:56] ...
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            try {
                $timestamp = \Carbon\Carbon::parse($matches[1]);
                return $timestamp->gte($cutoffTime);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * ステータスアイコンを取得
     * 
     * @param bool $isOk 正常ならtrue
     * @return string アイコン
     */
    private function getStatusIcon(bool $isOk): string
    {
        return $isOk ? '✅' : '❌';
    }

    /**
     * 空のメトリクスを取得
     * 
     * @return array 空のメトリクス
     */
    private function getEmptyMetrics(): array
    {
        return [
            'period' => 0,
            'total_requests' => 0,
            'total_auth_requests' => 0,
            'breeze_success' => 0,
            'cognito_success' => 0,
            'total_success' => 0,
            'auth_failure' => 0,
            'mapping_error' => 0,
            'success_rate' => 0,
            'failure_rate' => 0,
            'breeze_ratio' => 0,
            'cognito_ratio' => 0,
        ];
    }
}
