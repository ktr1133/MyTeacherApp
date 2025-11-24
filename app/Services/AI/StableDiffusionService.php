<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Stable Diffusion 画像生成サービス（Replicate API）
 */
class StableDiffusionService implements StableDiffusionServiceInterface
{
    /**
     * @deprecated Use AICostService::calculateReplicateCost() instead
     */
    private const FALLBACK_COST_DRAW = 230;

    /**
     * @deprecated Use AICostService::calculateReplicateCost() instead
     */
    private const FALLBACK_COST_TRANSPARENT = 50;

    private string $apiToken;
    private string $drawModelVersion;      // 描画モデル (anything-v4.0)
    private string $transparentModelVersion; // 背景除去モデル (rembg)
    private int $maxPollingAttempts;
    private int $pollingInterval;

    public function __construct()
    {
        $this->apiToken = config('services.replicate.api_token');
        $this->drawModelVersion = config('services.replicate.draw_model_version');
        $this->transparentModelVersion = config('services.replicate.transparent_model_version');
        $this->maxPollingAttempts = config('services.replicate.max_polling_attempts', 60);
        $this->pollingInterval = config('services.replicate.polling_interval', 2);
    }

    /**
     * 画像を生成
     * 
     * @param string $prompt プロンプト
     * @param int $seed シード値（同じ値で同一キャラクター生成）
     * @param array $options オプション
     * @return array|null ['url' => string, 'seed' => int, 'prediction_id' => string, 'token_cost' => int]
     */
    public function generateImage(string $prompt, int $seed, array $options = []): ?array
    {
        try {
            $drawModelVersionConst = config('services.draw_model_versions');
            $drawModelVersion = $drawModelVersionConst[$options['draw_model_version']] ?? $this->drawModelVersion;

            // stable-diffusion-3.5-medium の surprised 表情でぼやけ防止
            $isStableDiffusion35 = ($options['draw_model_version'] ?? '') === 'stable-diffusion-3.5-medium';
            $isSurprised = ($options['expression_type'] ?? '') === 'surprised';
            
            $numInferenceSteps = 50; // デフォルト
            $guidanceScale = 7.5;    // デフォルト
            
            if ($isStableDiffusion35 && $isSurprised) {
                $numInferenceSteps = 90;  // 驚き表情のぼやけ防止（推論ステップ増加）
                $guidanceScale = 8.5;      // ガイダンス強化で明瞭に
            }
            
            Log::info('[StableDiffusion] Generating image', [
                'const' => $drawModelVersionConst,
                'model_version' => $options['draw_model_version'] ?? 'default',
                'model_id' => $drawModelVersion,
                'seed' => $seed,
                'expression_type' => $options['expression_type'] ?? 'unknown',
                'num_inference_steps' => $numInferenceSteps,
                'guidance_scale' => $guidanceScale,
                'prompt' => $prompt,
            ]);


            // リクエスト送信
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Token {$this->apiToken}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.replicate.com/v1/predictions', [
                    'version' => $drawModelVersion,
                    'input' => array_merge([
                        'prompt' => $prompt,
                        'seed' => $seed,
                        'width' => 512,
                        'height' => 512,
                        'num_outputs' => 1,
                        'guidance_scale' => $guidanceScale,
                        'num_inference_steps' => $numInferenceSteps,
                        'negative_prompt' => implode(', ', [
                            // NSFW 対策を追加
                            'nsfw',
                            'explicit',
                            'nude',
                            'sexual',
                            'adult content',
                            'inappropriate',
                            // 複数人物の排除
                            'multiple people',
                            '2girls',
                            '2boys',
                            'multiple characters',
                            'group',
                            'crowd',
                            'duo',
                            'couple',
                            // 表情ネガティブ
                            '(expressionless:1.3)',
                            '(blank face:1.2)',
                            'same expression',
                            // 品質低下の排除
                            'lowres',
                            'bad anatomy',
                            'bad hands',
                            'bad face',
                            'bad eyes',
                            'bad proportions',
                            'text',
                            'error',
                            'missing fingers',
                            'extra digit',
                            'fewer digits',
                            'cropped',
                            'worst quality',
                            'low quality',
                            'normal quality',
                            'jpeg artifacts',
                            'signature',
                            'watermark',
                            'username',
                            'blurry',
                            'ugly',
                            'deformed',
                            // 背景の排除
                            'complex background',
                            'detailed background',
                            'cluttered background',
                            'messy background',
                            'busy background',
                            'outdoor',
                            'scenery',
                        ]),
                    ], $options),
                ]);

            if (!$response->successful()) {
                Log::error('[StableDiffusion] API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $predictionId = $data['id'] ?? null;

            if (!$predictionId) {
                Log::error('[StableDiffusion] No prediction ID', [
                    'response' => $data,
                ]);
                return null;
            }

            Log::info('[StableDiffusion] Prediction created', [
                'prediction_id' => $predictionId,
            ]);

            // ポーリングで完了待機
            $result = $this->waitForCompletion($predictionId, 'draw');

            return $result;

        } catch (\Exception $e) {
            Log::error('[StableDiffusion] Generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * 背景を除去して透過PNGを生成（rembg使用）
     * 
     * @param string $imageUrl 元画像URL
     * @return array|null ['url' => string, 'prediction_id' => string, 'token_cost' => int]
     */
    public function removeBackground(string $imageUrl): ?array
    {
        try {
            Log::info('[StableDiffusion] Removing background', [
                'image_url' => $imageUrl,
            ]);

            // リクエスト送信
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Token {$this->apiToken}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.replicate.com/v1/predictions', [
                    'version' => $this->transparentModelVersion,
                    'input' => [
                        'image' => $imageUrl,
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('[StableDiffusion] Background removal request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $predictionId = $data['id'] ?? null;

            if (!$predictionId) {
                Log::error('[StableDiffusion] No prediction ID for background removal', [
                    'response' => $data,
                ]);
                return null;
            }

            Log::info('[StableDiffusion] Background removal started', [
                'prediction_id' => $predictionId,
            ]);

            // ポーリングで完了待機
            $result = $this->waitForCompletion($predictionId, 'transparent');

            return $result;

        } catch (\Exception $e) {
            Log::error('[StableDiffusion] Background removal failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * 同一キャラクターの複数ポーズを生成
     * 
     * @param string $basePrompt ベースプロンプト
     * @param array $poses ポーズのリスト ['full_body' => 'standing pose', 'bust' => 'portrait']
     * @param int|null $seed シード値
     * @return array ['full_body' => [...], 'bust' => [...]]
     */
    public function generateCharacterPoses(string $basePrompt, array $poses, ?int $seed = null): array
    {
        $results = [];

        Log::info('[StableDiffusion] Generating character with multiple poses', [
            'seed' => $seed,
            'poses' => array_keys($poses),
            'base_prompt' => $basePrompt,
        ]);

        foreach ($poses as $type => $poseDescription) {
            $fullPrompt = "{$basePrompt}, {$poseDescription}";
            
            Log::info('[StableDiffusion] Generating pose', [
                'type' => $type,
                'prompt_preview' => substr($fullPrompt, 0, 100) . '...',
            ]);

            $result = $this->generateImage($fullPrompt, $seed);

            if ($result) {
                $results[$type] = $result;
                
                Log::info('[StableDiffusion] Pose generated successfully', [
                    'type' => $type,
                    'url' => $result['url'],
                ]);
            } else {
                Log::error('[StableDiffusion] Failed to generate pose', [
                    'type' => $type,
                    'seed' => $seed,
                ]);
            }
        }

        return $results;
    }

    /**
     * ポーリングで完了待機
     * 
     * @param string $predictionId
     * @param string $type 'draw' or 'transparent'
     * @return array|null ['url' => string, 'prediction_id' => string]
     */
    private function waitForCompletion(string $predictionId, string $type): ?array
    {
        $attempt = 0;

        while ($attempt < $this->maxPollingAttempts) {
            sleep($this->pollingInterval);

            try {
                $statusResponse = Http::withHeaders([
                    'Authorization' => "Token {$this->apiToken}",
                ])->get("https://api.replicate.com/v1/predictions/{$predictionId}");

                if (!$statusResponse->successful()) {
                    Log::error('[StableDiffusion] Status check failed', [
                        'prediction_id' => $predictionId,
                        'status' => $statusResponse->status(),
                    ]);
                    return null;
                }

                $status = $statusResponse->json();

                if ($status['status'] === 'succeeded') {
                    $output = $status['output'] ?? null;
                    $url = is_array($output) ? $output[0] : $output;

                    if (!$url) {
                        Log::error('[StableDiffusion] No output URL', [
                            'prediction_id' => $predictionId,
                            'output' => $output,
                        ]);
                        return null;
                    }

                    Log::info('[StableDiffusion] Generation completed', [
                        'prediction_id' => $predictionId,
                        'type' => $type,
                        'url' => $url,
                    ]);

                    return [
                        'url' => $url,
                        'prediction_id' => $predictionId,
                    ];
                }

                if ($status['status'] === 'failed' || $status['status'] === 'canceled') {
                    Log::error('[StableDiffusion] Generation failed or canceled', [
                        'prediction_id' => $predictionId,
                        'status' => $status['status'],
                        'error' => $status['error'] ?? 'Unknown error',
                    ]);
                    return null;
                }

                $attempt++;
            } catch (\Exception $e) {
                Log::error('[StableDiffusion] Polling error', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);
                $attempt++;
            }
        }

        Log::error('[StableDiffusion] Timeout', [
            'type' => $type,
            'prediction_id' => $predictionId,
        ]);

        return null;
    }
}