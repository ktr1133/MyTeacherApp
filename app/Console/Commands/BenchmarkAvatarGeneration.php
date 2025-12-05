<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\TeacherAvatar;
use App\Services\AI\StableDiffusionServiceInterface;
use App\Services\AI\AICostServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * アバター画像生成のインフラ負荷測定コマンド
 * 
 * 用途: Replicate API呼び出し・S3アップロード等のインフラ処理時間を測定
 */
class BenchmarkAvatarGeneration extends Command
{
    protected $signature = 'benchmark:avatar-generation
                            {user_id : ユーザーID}
                            {--samples=5 : 測定サンプル数}
                            {--skip-images : 画像生成をスキップ（テスト用）}';

    protected $description = 'アバター画像生成のインフラ負荷を測定';

    public function handle(
        StableDiffusionServiceInterface $sdService,
        AICostServiceInterface $aiCostService
    ): int {
        $userId = $this->argument('user_id');
        $samples = (int) $this->option('samples');
        $skipImages = $this->option('skip-images');

        $user = User::find($userId);
        if (!$user) {
            $this->error("ユーザーID {$userId} が見つかりません");
            return 1;
        }
        
        // ログ出力を最小限に（エラー時のみ）
        Log::getLogger()->pushHandler(new \Monolog\Handler\NullHandler());

        $this->info("=== アバター画像生成インフラ負荷測定 ===");
        $this->info("ユーザー: {$user->name} (ID: {$user->id})");
        $this->info("サンプル数: {$samples}");
        $this->newLine();

        $results = [];

        for ($i = 1; $i <= $samples; $i++) {
            $this->info("--- サンプル {$i}/{$samples} ---");
            
            try {
                $result = $this->measureSingleGeneration($user, $sdService, $aiCostService, $skipImages);
                $results[] = $result;
                
                $this->info("API呼び出し時間: {$result['api_time']}秒");
                $this->info("S3アップロード時間: {$result['upload_time']}秒");
                $this->info("DB保存時間: {$result['db_time']}秒");
                $this->info("合計インフラ時間: {$result['total_infra_time']}秒");
                $this->info("Replicateコスト: {$result['replicate_cost']}トークン");
                $this->newLine();
                
                // 次のサンプルまで待機（API rate limit対策）
                if ($i < $samples) {
                    sleep(2);
                }
            } catch (\Exception $e) {
                $this->error("測定失敗: {$e->getMessage()}");
                Log::error('Benchmark avatar generation failed', [
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
     * 1回のアバター生成を測定（簡易版: 1画像のみ）
     */
    private function measureSingleGeneration(
        User $user,
        StableDiffusionServiceInterface $sdService,
        AICostServiceInterface $aiCostService,
        bool $skipImages
    ): array {
        $seed = random_int(1, 2147483647);
        $prompt = "1girl, teacher, portrait, simple background, anime style";
        
        $apiTime = 0;
        $uploadTime = 0;
        $dbTime = 0;
        $replicateCost = 0;

        if (!$skipImages) {
            // Phase 1: API呼び出し（画像生成）
            $apiStart = microtime(true);
            $generatedData = $sdService->generateImage($prompt, $seed, [
                'width' => 512,
                'height' => 512,
                'num_outputs' => 1,
            ]);
            $apiTime = microtime(true) - $apiStart;

            if (!$generatedData || !isset($generatedData['url'])) {
                throw new \RuntimeException('画像生成に失敗しました');
            }

            // コスト計算
            $replicateCost = $aiCostService->calculateReplicateCost('anything-v4.0', '512x512', 1);

            // Phase 2: S3アップロード
            $uploadStart = microtime(true);
            $imageContent = file_get_contents($generatedData['url']);
            $filename = "benchmark/avatar_{$seed}.png";
            Storage::disk('s3')->put($filename, $imageContent, 'public');
            $uploadTime = microtime(true) - $uploadStart;

            // 後片付け
            Storage::disk('s3')->delete($filename);
        } else {
            // テストモード: ダミー値
            $apiTime = 0.5;
            $uploadTime = 0.2;
            $replicateCost = 230;
        }

        // Phase 3: DB保存（シミュレーション）
        $dbStart = microtime(true);
        DB::table('teacher_avatars')
            ->where('user_id', $user->id)
            ->update(['updated_at' => now()]);
        $dbTime = microtime(true) - $dbStart;

        $totalInfraTime = $apiTime + $uploadTime + $dbTime;

        return [
            'api_time' => round($apiTime, 2),
            'upload_time' => round($uploadTime, 2),
            'db_time' => round($dbTime, 2),
            'total_infra_time' => round($totalInfraTime, 2),
            'replicate_cost' => $replicateCost,
        ];
    }

    /**
     * 統計情報を表示
     */
    private function displayStatistics(array $results): void
    {
        $this->info("=== 統計分析 ===");

        $apiTimes = array_column($results, 'api_time');
        $uploadTimes = array_column($results, 'upload_time');
        $dbTimes = array_column($results, 'db_time');
        $totalInfraTimes = array_column($results, 'total_infra_time');
        $replicateCosts = array_column($results, 'replicate_cost');

        $this->table(
            ['指標', '平均', '最小', '最大', '標準偏差'],
            [
                ['API呼び出し時間（秒）', $this->avg($apiTimes), min($apiTimes), max($apiTimes), $this->stdDev($apiTimes)],
                ['S3アップロード時間（秒）', $this->avg($uploadTimes), min($uploadTimes), max($uploadTimes), $this->stdDev($uploadTimes)],
                ['DB保存時間（秒）', $this->avg($dbTimes), min($dbTimes), max($dbTimes), $this->stdDev($dbTimes)],
                ['合計インフラ時間（秒）', $this->avg($totalInfraTimes), min($totalInfraTimes), max($totalInfraTimes), $this->stdDev($totalInfraTimes)],
                ['Replicateコスト（トークン）', $this->avg($replicateCosts), min($replicateCosts), max($replicateCosts), $this->stdDev($replicateCosts)],
            ]
        );

        $this->newLine();

        // トークン推奨値算出
        $avgInfraTime = $this->avg($totalInfraTimes);
        $avgReplicateCost = $this->avg($replicateCosts);
        
        // Fargate課金モデルに基づく試算
        // 想定: 0.25 vCPU, 0.5 GB メモリ, Fargate Spot
        // vCPU: $0.04048/時間 × 70%割引 = $0.012144/時間
        // メモリ: $0.004445/時間/GB × 70%割引 = $0.001334/時間/GB
        $cpuCostPerSecond = 0.012144 / 3600;
        $memCostPerSecond = 0.001334 / 3600 * 0.5; // 0.5GB
        $totalCostPerSecond = $cpuCostPerSecond + $memCostPerSecond;
        
        $infraCostUsd = $avgInfraTime * $totalCostPerSecond;
        $infraCostJpy = $infraCostUsd * 150; // $1 = 150円想定
        
        // トークン換算（500,000トークン = 400円）
        $recommendedTokens = ceil(($infraCostJpy / 400) * 500000);

        $this->info("【推奨設定値】");
        $this->info("インフラ負荷: {$recommendedTokens}トークン");
        $this->info("  └ 根拠: 平均インフラ時間 {$avgInfraTime}秒 × Fargate Spot料金");
        $this->info("  └ Fargateコスト試算: " . number_format($infraCostJpy, 4) . "円");
        $this->newLine();

        $this->info("【1アバター（8画像）あたりの想定コスト】");
        $totalPerAvatar = $avgReplicateCost * 8 + $recommendedTokens * 8;
        $this->info("Replicateコスト: " . ($avgReplicateCost * 8) . "トークン");
        $this->info("インフラ負荷: " . ($recommendedTokens * 8) . "トークン");
        $this->info("合計: {$totalPerAvatar}トークン");
        $this->info("円換算: " . number_format(($totalPerAvatar / 500000) * 400, 2) . "円");
        $this->newLine();

        $this->info("【設定例】");
        $this->line("'avatar_generation' => [");
        $this->line("    'infra_load' => {$recommendedTokens},  // 1画像あたり（測定値ベース）");
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
