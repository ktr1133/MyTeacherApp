<?php

namespace App\Services\AI;

use App\Models\User;

/**
 * AI コストサービスインターフェース
 */
interface AICostServiceInterface
{
    /**
     * 画像生成コストをトークンに換算
     *
     * @param string $size サイズ (例: '1024x1024')
     * @param string $quality 品質 (例: 'standard', 'hd')
     * @param int $count 枚数
     * @return int トークンコスト
     */
    public function calculateImageCost(string $size, string $quality, int $count = 1): int;

    /**
     * Chat APIコストをトークンに換算
     *
     * @param int $inputTokens 入力トークン数
     * @param int $outputTokens 出力トークン数
     * @param string $model モデル名 (例: 'gpt-4', 'gpt-3.5-turbo')
     * @return int トークンコスト
     */
    public function calculateChatCost(int $inputTokens, int $outputTokens, string $model = 'gpt-4'): int;

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
    ): void;

    /**
     * アバター生成の総コストを計算
     *
     * @param int $avatarId
     * @return array ['total_token_cost' => int, 'details' => array]
     */
    public function calculateAvatarTotalCost(int $avatarId): array;

    /**
     * Replicate モデルのコストを計算
     * 
     * @param string $serviceType
     * @param string|null $imageSize
     * @param int $count
     * @return int トークンコスト
     */
    public function calculateReplicateCost(string $serviceType, ?string $imageSize = null, int $count = 1): int;
}