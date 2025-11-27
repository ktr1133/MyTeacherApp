<?php

namespace App\Services\AI;

use App\Models\AICostRate;
use App\Models\User;
use App\Repositories\AI\AICostRateRepositoryInterface;
use App\Repositories\AI\AIUsageLogRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * AI コストサービス
 */
class AICostService implements AICostServiceInterface
{
    public function __construct(
        private AICostRateRepositoryInterface $costRateRepository,
        private AIUsageLogRepositoryInterface $usageLogRepository
    ) {}

    /**
     * 画像生成コストをトークンに換算
     *
     * @param string $size サイズ (例: '1024x1024')
     * @param string $quality 品質 (例: 'standard', 'hd')
     * @param string $model モデル名 (例: 'dalle3')
     * @param int $count 枚数
     * @return int トークンコスト
     */
    public function calculateImageCost(string $size, string $quality, string $model = 'dalle3', int $count = 1): int
    {
        $detail = "{$size}_{$quality}";
        $rate = $this->costRateRepository->getActiveRate($model, $detail);

        if (!$rate) {
            Log::warning("No cost rate found for DALL-E 3: {$detail}");
            // デフォルト値を返す
            return 20000 * $count;
        }

        return $rate->token_conversion_rate * $count;
    }

    /**
     * Chat APIコストをトークンに換算
     *
     * @param int $inputTokens 入力トークン数
     * @param int $outputTokens 出力トークン数
     * @param string $model モデル名 (例: 'gpt-4', 'gpt-3.5-turbo')
     * @return int トークンコスト
     */
    public function calculateChatCost(int $inputTokens, int $outputTokens, string $model = 'gpt-4'): int
    {
        $inputRate = $this->costRateRepository->getActiveRate($model, 'input');
        $outputRate = $this->costRateRepository->getActiveRate($model, 'output');

        if (!$inputRate || !$outputRate) {
            Log::warning("No cost rate found for Chat API: {$model}");
            // デフォルト値（GPT-4）
            $inputCost = ceil(($inputTokens / 1000) * 30);
            $outputCost = ceil(($outputTokens / 1000) * 60);
            return $inputCost + $outputCost;
        }

        // 1,000トークンあたりのレートで計算
        $inputCost = ceil(($inputTokens / 1000) * $inputRate->token_conversion_rate);
        $outputCost = ceil(($outputTokens / 1000) * $outputRate->token_conversion_rate);
        logger()->info('コメント作成のトークンコスト計算', [
            'inputRate' => $inputRate,
            'outputRate' => $outputRate,
            'inputTokens' => $inputTokens,
            'outputTokens' => $outputTokens,
            'inputCost' => $inputCost,
            'outputCost' => $outputCost,
            'totalCost' => $inputCost + $outputCost,
        ]);
        return $inputCost + $outputCost;
    }

    /**
     * AI使用ログを記録
     *
     * @param User $user
     * @param string $usableType
     * @param int $usableId
     * @param string $serviceType
     * @param string|null $serviceDetail
     * @param float $unitsUsed
     * @param int $tokenCost
     * @param array|null $requestData
     * @param array|null $responseData
     * @return void
     */
    public function logUsage(
        User $user,
        string $usableType,
        int $usableId,
        string $serviceType,
        ?string $serviceDetail,
        float $unitsUsed,
        int $tokenCost,
        ?array $requestData = null,
        ?array $responseData = null
    ): void {
        $rate = $this->costRateRepository->getActiveRate($serviceType, $serviceDetail);
        $costUsd = $rate ? ($unitsUsed * $rate->unit_cost_usd) : 0;

        $this->usageLogRepository->create([
            'usable_type' => $usableType,
            'usable_id' => $usableId,
            'user_id' => $user->id,
            'service_type' => $serviceType,
            'service_detail' => $serviceDetail,
            'units_used' => $unitsUsed,
            'cost_usd' => $costUsd,
            'token_cost' => $tokenCost,
            'cost_rate_id' => $rate?->id,
            'request_data' => $requestData,
            'response_data' => $responseData,
        ]);
    }

    /**
     * アバター生成の総コストを計算
     *
     * @param int $avatarId
     * @return array ['total_token_cost' => int, 'details' => array]
     */
    public function calculateAvatarTotalCost(int $avatarId): array
    {
        $logs = $this->usageLogRepository->getByUsable(\App\Models\TeacherAvatar::class, $avatarId);

        $details = $logs->groupBy('service_type')->map(function ($items, $serviceType) {
            return [
                'service_type' => $serviceType,
                'total_units' => $items->sum('units_used'),
                'total_cost_usd' => $items->sum('cost_usd'),
                'total_token_cost' => $items->sum('token_cost'),
            ];
        })->values();

        return [
            'total_token_cost' => $logs->sum('token_cost'),
            'details' => $details->toArray(),
        ];
    }

    /**
     * Replicate モデルのコストを計算（画像生成系）
     *
     * @param string $serviceType サービスタイプ（例: 'anything-v4.0', 'rembg'）
     * @param string|null $imageSize 画像サイズ（例: '512x512', null）
     * @param int $count 枚数
     * @return int トークンコスト
     */
    public function calculateReplicateCost(string $serviceType, ?string $imageSize = null, int $count = 1): int
    {
        try {
            // レート取得
            $rate = AICostRate::active()
                ->forService($serviceType, $imageSize)
                ->first();

            if (!$rate) {
                Log::warning('[AICostService] Rate not found, using fallback', [
                    'service_type' => $serviceType,
                    'image_size' => $imageSize,
                ]);
                
                // フォールバック（デフォルト値）
                return $this->getFallbackReplicateCost($serviceType, $imageSize) * $count;
            }

            $tokenCost = $rate->token_conversion_rate * $count;

            Log::info('[AICostService] Replicate cost calculated', [
                'service_type' => $serviceType,
                'image_size' => $imageSize,
                'count' => $count,
                'unit_cost' => $rate->token_conversion_rate,
                'total_cost' => $tokenCost,
            ]);

            return $tokenCost;

        } catch (\Exception $e) {
            Log::error('[AICostService] Replicate cost calculation failed', [
                'service_type' => $serviceType,
                'image_size' => $imageSize,
                'error' => $e->getMessage(),
            ]);

            // 例外時もフォールバック
            return $this->getFallbackReplicateCost($serviceType, $imageSize) * $count;
        }
    }

    /**
     * Replicate コストのフォールバック値を取得
     * 
     * Replicate公式レートに基づく値（DB障害時用）
     *
     * @param string $serviceType
     * @param string|null $imageSize
     * @return int
     */
    private function getFallbackReplicateCost(string $serviceType, ?string $imageSize): int
    {
        // ハードコードのデフォルト値（DB障害時用、Replicate公式レート）
        return match ($serviceType) {
            'anything-v4.0' => match ($imageSize) {
                '512x512' => 5000,
                '768x768' => 7500,
                '1024x1024' => 10000,
                default => 5000,
            },
            'animagine-xl-3.1' => match ($imageSize) {
                '512x512' => 2000,
                default => 2000,
            },
            'stable-diffusion-3.5-medium' => match ($imageSize) {
                '512x512' => 23000,
                default => 23000,
            },
            'rembg' => 50,
            default => 0,
        };
    }
}