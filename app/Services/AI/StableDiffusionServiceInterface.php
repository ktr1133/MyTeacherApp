<?php

namespace App\Services\AI;

/**
 * Stable Diffusion 画像生成サービスのインターフェース
 */
interface StableDiffusionServiceInterface
{
    /**
     * 画像を生成
     * 
     * @param string $prompt プロンプト
     * @param int $seed シード値
     * @param array $options オプション
     * @return array|null ['url' => string, 'seed' => int, 'prediction_id' => string, 'token_cost' => int]
     */
    public function generateImage(string $prompt, int $seed, array $options = []): ?array;

    /**
     * 背景を除去して透過PNGを生成
     * 
     * @param string $imageUrl 元画像URL
     * @return array|null ['url' => string, 'prediction_id' => string, 'token_cost' => int]
     */
    public function removeBackground(string $imageUrl): ?array;

    /**
     * 同一キャラクターの複数ポーズを生成
     * 
     * @param string $basePrompt ベースプロンプト
     * @param array $poses ポーズのリスト
     * @param int|null $seed シード値
     * @return array
     */
    public function generateCharacterPoses(string $basePrompt, array $poses, ?int $seed = null): array;
}