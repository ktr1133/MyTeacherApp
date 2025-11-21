<?php

namespace App\Jobs;

use App\Models\TeacherAvatar;
use App\Services\AI\OpenAIService;
use App\Services\AI\StableDiffusionServiceInterface;
use App\Services\AI\AICostServiceInterface;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * 教師アバター生成ジョブ（anything-v4.0 + rembg使用）
 * NSFW対策版
 */
class GenerateAvatarImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * フォールバック用定数（非推奨だが安全のため残す）
     * 
     * @deprecated Use AICostService::calculateReplicateCost() instead
     */
    private const FALLBACK_COST_DRAW = 230;
    private const FALLBACK_COST_TRANSPARENT = 50;

    /**
     * リトライ設定
     */
    private const MAX_GENERATION_RETRIES = 3; // 画像生成の最大リトライ回数
    private const RETRY_DELAY_SECONDS = 3;    // リトライ間隔（秒）

    /**
     * ポーズ定義（拡張可能）
     */
    private const POSE_DEFINITIONS = [
        'full_body' => [
            'description' => 'full body standing pose, showing entire body from head to toe, centered composition, vertical orientation, simple pose',
            'enabled' => true,
            'expressions' => ['normal'], // 通常は全身は通常表情のみ（ちび時は全表情）
        ],
        'bust' => [
            'description' => 'upper body portrait from shoulders up, close-up view, face clearly visible, detailed facial features, centered',
            'enabled' => true,
            'expressions' => ['normal', 'happy', 'sad', 'angry', 'surprised'], // バストアップは全表情
        ],
    ];

    /**
     * 表情プロンプトマッピング（NSFW対策版）
     */
    private const EXPRESSION_PROMPTS = [
        'normal' => '(neutral expression:1.2), calm face, relaxed eyebrows, cheerful eyes, slight smile, gentle mood, peaceful look',
        'happy' => '(happy expression:1.3), bright smile, joyful face, smiling eyes, cheerful mood, positive emotion, delighted look',
        'sad' => '(sad expression:1.2), melancholic face, gentle downcast eyes, slight frown, thoughtful expression, soft eyebrows, pensive look',
        'angry' => '(serious expression:1.2), stern face, determined look, focused eyebrows, firm mouth, strong gaze, resolute expression',
        'surprised' => '(surprised expression:1.3), shocked face, wide open eyes, raised eyebrows, open mouth, astonished look, amazed expression',
    ];

    /**
     * NSFW トリガーワード（削除対象）
     */
    private const NSFW_TRIGGER_WORDS = [
        'tears in eyes',
        'tears',
        'crying',
        'weeping',
        'sobbing',
        'intense eyes',
        'sharp gaze',
        'aggressive',
        'passionate',
        'fierce',
        'violent',
    ];

    /**
     * マイルドな代替表現（NSFW回避用）
     */
    private const MILD_EXPRESSIONS = [
        'sad' => 'gentle sad expression, soft melancholic look, thoughtful gaze, quiet sadness',
        'angry' => 'serious expression, determined face, focused look, firm expression',
    ];

    protected int $avatarId;

    public function __construct(int $avatarId)
    {
        $this->avatarId = $avatarId;
    }

    /**
     * ジョブ実行
     */
    public function handle(
        StableDiffusionServiceInterface $sdService,
        OpenAIService $openAIService,
        AICostServiceInterface $aiCostService,
        TokenServiceInterface $tokenService,
        NotificationServiceInterface $notificationService
    ): void {
        $avatar = TeacherAvatar::find($this->avatarId);

        if (!$avatar) {
            Log::error('[GenerateAvatarImages] Avatar not found', [
                'avatar_id' => $this->avatarId,
            ]);
            return;
        }

        try {
            $avatar->update(['generation_status' => 'generating']);
            
            Log::info('[GenerateAvatarImages] Started with NSFW protection', [
                'avatar_id' => $avatar->id,
                'max_retries' => self::MAX_GENERATION_RETRIES,
            ]);

            $totalTokenCost = 0;
            $aiUsageDetails = [];

            // ===================================
            // 画像生成
            // ===================================
            
            $basePrompt = $this->buildBasePrompt($avatar);
            $seed = $avatar->seed;

            Log::info('[GenerateAvatarImages] Generating with seed', [
                'avatar_id' => $avatar->id,
                'seed' => $seed,
                'base_prompt' => $basePrompt,
            ]);
            
            // 有効なポーズのみ抽出
            // ちびキャラの場合はバストアップをスキップ、全身の表情を拡張
            $enabledPoses = [];
            foreach (self::POSE_DEFINITIONS as $key => $pose) {
                if (!$pose['enabled']) {
                    continue;
                }
                
                // ちびキャラの場合
                if ($avatar->is_chibi) {
                    if ($key === 'bust') {
                        continue; // バストアップをスキップ
                    }
                    if ($key === 'full_body') {
                        // 全身の表情を全て生成
                        $pose['expressions'] = ['normal', 'happy', 'sad', 'angry', 'surprised'];
                    }
                }
                
                $enabledPoses[$key] = $pose;
            }
            
            Log::info('[GenerateAvatarImages] Pose filtering', [
                'is_chibi' => $avatar->is_chibi,
                'enabled_poses' => array_keys($enabledPoses),
                'full_body_expressions' => $enabledPoses['full_body']['expressions'] ?? [],
            ]);
            
            // 各ポーズ × 各表情で画像生成
            foreach ($enabledPoses as $poseType => $poseConfig) {
                $poseDescription = $poseConfig['description'];
                $expressions = $poseConfig['expressions'];
                
                Log::info('[GenerateAvatarImages] Processing pose type', [
                    'pose_type' => $poseType,
                    'expressions_count' => count($expressions),
                ]);
                
                foreach ($expressions as $expressionType) {
                    $expressionPrompt = self::EXPRESSION_PROMPTS[$expressionType] ?? '';
                    
                    $fullPrompt = $this->buildFullPrompt($basePrompt, $poseDescription, $expressionPrompt);
                    
                    Log::info('[GenerateAvatarImages] Generating image', [
                        'pose_type' => $poseType,
                        'expression_type' => $expressionType,
                        'prompt_preview' => substr($fullPrompt, 0, 200) . '...',
                    ]);
                    
                    // リトライ機構付き画像生成
                    $generatedData = $this->generateImageWithRetry(
                        $sdService,
                        $avatar,
                        $fullPrompt,
                        $seed,
                        $expressionType,
                        $poseType
                    );
                    
                    // 生成失敗時はフォールバック処理
                    if (!$generatedData || !isset($generatedData['url'])) {
                        Log::warning('[GenerateAvatarImages] Image generation completely failed, using fallback', [
                            'pose_type' => $poseType,
                            'expression_type' => $expressionType,
                        ]);
                        
                        $this->handleNSFWError($avatar, $poseType, $expressionType);
                        continue;
                    }

                    Log::info('[GenerateAvatarImages] Image generated successfully', [
                        'pose_type' => $poseType,
                        'expression_type' => $expressionType,
                        'url' => $generatedData['url'],
                    ]);
                    
                    // コスト計算（AICostService を使用）
                    $model = $avatar->draw_model_version ?? 'anything-v4.0';
                    $imageSize = config('const.image_size.width') . 'x' . config('const.image_size.height');
                    $generationCost = $aiCostService->calculateReplicateCost($model, $imageSize, 1);

                    // 背景除去
                    $removalCost = 0;
                    if ($avatar->is_transparent) {
                        $transparentData = $sdService->removeBackground($generatedData['url']);
                        
                        if (!$transparentData || !isset($transparentData['url'])) {
                            Log::error('[GenerateAvatarImages] Background removal failed', [
                                'pose_type' => $poseType,
                                'expression_type' => $expressionType,
                            ]);
                            continue;
                        }
                        
                        // コスト計算（背景除去）
                        $removalCost = $aiCostService->calculateReplicateCost('rembg', null, 1);
                    }

                    // アップロードURLの決定
                    $uploadUrl = $avatar->is_transparent && isset($transparentData['url'])
                        ? $transparentData['url']
                        : $generatedData['url'];

                    // S3にアップロード
                    $s3Path = $this->uploadToS3(
                        $uploadUrl, 
                        $avatar->user_id, 
                        $poseType,
                        $expressionType
                    );
                    $baseUrl = rtrim(config('filesystems.disks.s3.url'), '/');
                    $s3Url = $baseUrl . '/' . $s3Path;
                    
                    // データベースに保存
                    $avatar->images()->updateOrCreate(
                        [
                            'image_type' => $poseType,
                            'expression_type' => $expressionType,
                        ],
                        [
                            's3_path' => $s3Path,
                            's3_url' => $s3Url,
                        ]
                    );
                    
                    Log::info('[GenerateAvatarImages] Image saved', [
                        'pose_type' => $poseType,
                        'expression_type' => $expressionType,
                        's3_path' => $s3Path,
                    ]);
                    
                    // コスト計算
                    $imageTotalCost = $generationCost + $removalCost;
                    $totalTokenCost += $imageTotalCost;
                    
                    $aiUsageDetails[] = [
                        'type' => sprintf('%s画像（%s）（anything-v4.0 + rembg）', 
                            $poseType === 'full_body' ? '全身' : 'バストアップ',
                            $expressionType
                        ),
                        'token_cost' => $imageTotalCost,
                        'breakdown' => [
                            'generation' => $generationCost,
                            'background_removal' => $removalCost,
                        ],
                        'seed' => $seed,
                        'pose_type' => $poseType,
                        'expression_type' => $expressionType,
                    ];
                    
                    // ログ記録（生成）
                    $aiCostService->logUsage(
                        $avatar->user,
                        TeacherAvatar::class,
                        $avatar->id,
                        'anything-v4.0',
                        "{$poseType}_{$expressionType}",
                        1.0,
                        $generationCost,
                        [
                            'prompt' => $fullPrompt,
                            'seed' => $seed,
                            'image_size' => '512x512',
                            'prediction_id' => $generatedData['prediction_id'] ?? null,
                        ],
                        [
                            'url' => $generatedData['url'],
                            's3_path' => $s3Path,
                        ]
                    );
                    
                    // ログ記録（背景除去）
                    if ($avatar->is_transparent && isset($transparentData['url'])) {
                        $aiCostService->logUsage(
                            $avatar->user,
                            TeacherAvatar::class,
                            $avatar->id,
                            'rembg',
                            "{$poseType}_{$expressionType}_transparent",
                            1.0,
                            $removalCost,
                            [
                                'original_url' => $generatedData['url'],
                                'prediction_id' => $transparentData['prediction_id'] ?? null,
                            ],
                            [
                                'transparent_url' => $transparentData['url'],
                                's3_path' => $s3Path,
                            ]
                        );
                    }
                }
            }
            
            // ===================================
            // コメント生成（GPT-4使用）
            // ===================================
            
            $commentTokenCost = $this->generateComments($openAIService, $aiCostService, $avatar);
            
            Log::info('[GenerateAvatarImages] Comment generation completed', [
                'avatar_id' => $avatar->id,
                'comment_token_cost' => $commentTokenCost,
            ]);
            
            $totalTokenCost += $commentTokenCost;

            $aiUsageDetails[] = [
                'type' => 'コメント生成（GPT-4）',
                'token_cost' => $commentTokenCost,
            ];

            // ===================================
            // トークンコスト記録
            // ===================================
            
            $tokenService->recordAICost(
                $avatar->user,
                $totalTokenCost,
                'アバター生成コスト',
                $avatar,
                $aiUsageDetails
            );

            // // 事前見積もり精算
            // $estimatedCost = $avatar->estimated_token_usage ?? 0;
            // $tokenService->settleTokenConsumption(
            //     $avatar->user,
            //     $estimatedCost,
            //     $totalTokenCost,
            //     'アバター生成精算',
            //     $avatar
            // );

            // トークン消費
            $tokenService->consumeTokens(
                $avatar->user,
                $totalTokenCost,
                'アバター画像生成',
                $avatar
            );

            // ステータス更新
            $avatar->update([
                'generation_status' => 'completed',
                'last_generated_at' => now(),
            ]);
            // 生成結果
            $result = true;
        } catch (\Exception $e) {
            Log::error('[GenerateAvatarImages] Error', [
                'avatar_id' => $avatar->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ステータスを失敗に更新
            $avatar->update(['generation_status' => 'failed']);
            // 生成結果
            $result = false;
        }

        $tile = $result ? 'アバター画像の生成が完了しました' : 'アバター画像の生成に失敗しました。';
        $msg = $this->buildNotificationMessage($avatar, $totalTokenCost, $result);
        $notificationService->sendNotification(
            $avatar->user->id,
            $avatar->user->id,
            config('const.notification_types.avatar_generated'),
            $tile,
            $msg
        );
    }

    /**
     * 画像生成（リトライ対応版）
     * 
     * NSFW検出時に自動的にプロンプトを調整して再試行
     */
    private function generateImageWithRetry(
        StableDiffusionServiceInterface $sdService,
        TeacherAvatar $avatar,
        string $fullPrompt,
        int $seed,
        string $expressionType,
        string $poseType,
        int $maxRetries = self::MAX_GENERATION_RETRIES
    ): ?array {
        $attempt = 0;
        $currentPrompt = $fullPrompt;
        
        while ($attempt < $maxRetries) {
            try {
                Log::info('[GenerateImageWithRetry] Attempt', [
                    'expression_type' => $expressionType,
                    'pose_type' => $poseType,
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries,
                    'prompt_preview' => substr($currentPrompt, 0, 150) . '...',
                ]);

                // オプション設定
                $options = [
                    'draw_model_version' => $avatar->draw_model_version,
                    'expression_type' => $expressionType, // 表情タイプを渡す
                ];
                $result = $sdService->generateImage($currentPrompt, $seed, $options);
                
                if ($result && isset($result['url'])) {
                    Log::info('[GenerateImageWithRetry] Success', [
                        'expression_type' => $expressionType,
                        'pose_type' => $poseType,
                        'attempt' => $attempt + 1,
                        'used_mild_prompt' => $attempt > 0,
                    ]);
                    return $result;
                }
                
                // 生成失敗（NSFW の可能性）
                Log::warning('[GenerateImageWithRetry] Generation failed, likely NSFW detected', [
                    'expression_type' => $expressionType,
                    'pose_type' => $poseType,
                    'attempt' => $attempt + 1,
                ]);
                
                // 次の試行でマイルドなプロンプトを使用
                if ($attempt < $maxRetries - 1) {
                    $currentPrompt = $this->createMildPrompt($currentPrompt, $expressionType, $attempt + 1);
                    
                    Log::info('[GenerateImageWithRetry] Retrying with milder prompt', [
                        'expression_type' => $expressionType,
                        'pose_type' => $poseType,
                        'retry_level' => $attempt + 1,
                    ]);
                    
                    sleep(self::RETRY_DELAY_SECONDS);
                }
                
            } catch (\Exception $e) {
                Log::error('[GenerateImageWithRetry] Error', [
                    'expression_type' => $expressionType,
                    'pose_type' => $poseType,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                ]);
            }
            
            $attempt++;
        }
        
        Log::error('[GenerateImageWithRetry] All retries exhausted', [
            'expression_type' => $expressionType,
            'pose_type' => $poseType,
            'max_retries' => $maxRetries,
        ]);
        
        return null;
    }

    /**
     * マイルドなプロンプトを生成（NSFW回避用）
     * 
     * リトライ回数に応じて段階的にマイルドにする
     */
    private function createMildPrompt(string $originalPrompt, string $expressionType, int $retryLevel): string
    {
        $mildPrompt = $originalPrompt;
        
        // レベル1: NSFW トリガーワードを削除
        if ($retryLevel >= 1) {
            $mildPrompt = str_ireplace(self::NSFW_TRIGGER_WORDS, '', $mildPrompt);
            
            Log::debug('[CreateMildPrompt] Level 1: Removed trigger words', [
                'expression_type' => $expressionType,
                'removed_words' => array_filter(self::NSFW_TRIGGER_WORDS, function($word) use ($originalPrompt) {
                    return stripos($originalPrompt, $word) !== false;
                }),
            ]);
        }
        
        // レベル2: 強調値を下げる (1.5 → 1.2, 1.4 → 1.1, etc.)
        if ($retryLevel >= 2) {
            $mildPrompt = preg_replace_callback(
                '/:(\d\.\d+)/',
                function($matches) {
                    $value = floatval($matches[1]);
                    $newValue = max(1.0, $value - 0.3); // 0.3 下げる（最小1.0）
                    return ':' . number_format($newValue, 1);
                },
                $mildPrompt
            );
            
            Log::debug('[CreateMildPrompt] Level 2: Reduced emphasis values', [
                'expression_type' => $expressionType,
            ]);
        }
        
        // レベル3: 表情タイプ別の安全な代替表現に完全置換
        if ($retryLevel >= 3 && isset(self::MILD_EXPRESSIONS[$expressionType])) {
            // 表情関連の記述を安全な表現で置き換え
            $basePromptParts = explode(', ', $mildPrompt);
            $filteredParts = array_filter($basePromptParts, function($part) {
                // 表情に関する記述を除外
                return !preg_match('/(expression|face|eyes|eyebrows|mouth|gaze|look)/i', $part);
            });
            
            // 安全な表情表現を先頭に追加
            array_unshift($filteredParts, self::MILD_EXPRESSIONS[$expressionType]);
            
            $mildPrompt = implode(', ', $filteredParts);
            
            Log::debug('[CreateMildPrompt] Level 3: Replaced with safe expression', [
                'expression_type' => $expressionType,
                'safe_phrase' => self::MILD_EXPRESSIONS[$expressionType],
            ]);
        }
        
        // 余分なカンマやスペースをクリーンアップ
        $mildPrompt = preg_replace('/,\s*,/', ',', $mildPrompt);
        $mildPrompt = preg_replace('/\s+/', ' ', $mildPrompt);
        $mildPrompt = trim($mildPrompt, ', ');
        
        Log::info('[CreateMildPrompt] Generated mild prompt', [
            'expression_type' => $expressionType,
            'retry_level' => $retryLevel,
            'original_length' => strlen($originalPrompt),
            'mild_length' => strlen($mildPrompt),
            'mild_prompt_preview' => substr($mildPrompt, 0, 200) . '...',
        ]);
        
        return $mildPrompt;
    }

    /**
     * NSFW エラー時のフォールバック処理
     * 
     * normal 表情の画像を複製して使用
     */
    private function handleNSFWError(
        TeacherAvatar $avatar,
        string $poseType,
        string $expressionType
    ): void {
        Log::warning('[HandleNSFWError] Creating fallback image', [
            'avatar_id' => $avatar->id,
            'pose_type' => $poseType,
            'expression_type' => $expressionType,
        ]);

        // normal 表情の画像を取得
        $normalImage = $avatar->images()
            ->where('image_type', $poseType)
            ->where('expression_type', 'normal')
            ->first();

        if ($normalImage) {
            // normal 表情を複製
            $avatar->images()->updateOrCreate(
                [
                    'image_type' => $poseType,
                    'expression_type' => $expressionType,
                ],
                [
                    's3_path' => $normalImage->s3_path,
                    's3_url' => $normalImage->s3_url,
                ]
            );

            Log::info('[HandleNSFWError] Used normal expression as fallback', [
                'avatar_id' => $avatar->id,
                'pose_type' => $poseType,
                'expression_type' => $expressionType,
                'fallback_from' => 'normal',
                's3_path' => $normalImage->s3_path,
            ]);
        } else {
            // normal 画像もない場合はログのみ
            Log::error('[HandleNSFWError] No normal image available for fallback', [
                'avatar_id' => $avatar->id,
                'pose_type' => $poseType,
                'expression_type' => $expressionType,
            ]);
        }
    }

    /**
     * ベースプロンプト生成
     */
    private function buildBasePrompt(TeacherAvatar $avatar): string
    {
        // テーマ判定（adult = teacher / child = supporter）
        $isChildTheme = $avatar->user->theme === 'child';
        $role = $isChildTheme ? 'supporter' : 'teacher';
        
        // ちびキャラ判定
        $isChibi = $avatar->is_chibi ?? false;
        
        $appearanceMap = [
            'sex' => [
                'male' => 'male',
                'female' => 'female',
                'other' => 'androgynous',
            ],
            'hair_style' => [
                'short' => 'short hair',
                'middle' => 'medium hair',
                'long' => 'long hair',
            ],
            'hair_color' => [
                'black' => 'black hair',
                'brown' => 'brown hair',
                'blonde' => 'blonde hair',
                'silver' => 'silver hair',
                'red' => 'red hair',
            ],
            'eye_color' => [
                'brown' => 'brown eyes',
                'blue' => 'blue eyes',
                'green' => 'green eyes',
                'gray' => 'gray eyes',
                'purple' => 'purple eyes',
            ],
            'clothing' => [
                'suit' => 'wearing professional business suit',
                'casual' => 'wearing casual clothes',
                'kimono' => 'wearing traditional kimono',
                'robe' => 'wearing academic robe',
                'dress' => 'wearing elegant dress',
            ],
            'accessory' => [
                'glasses' => 'wearing glasses',
                'hat' => 'wearing hat',
                'tie' => 'wearing necktie',
                '' => '',
            ],
            'body_type' => [
                'average' => 'average build',
                'slim' => 'slim build',
                'sturdy' => 'sturdy build',
            ],
        ];

        $parts = [
            $appearanceMap['sex'][$avatar->sex] ?? 'person',
            $appearanceMap['hair_style'][$avatar->hair_style] ?? '',
            $appearanceMap['hair_color'][$avatar->hair_color] ?? '',
            $appearanceMap['eye_color'][$avatar->eye_color] ?? '',
            $appearanceMap['clothing'][$avatar->clothing] ?? '',
            $avatar->accessory ? ($appearanceMap['accessory'][$avatar->accessory] ?? '') : '',
            $appearanceMap['body_type'][$avatar->body_type] ?? '',
        ];

        $parts = array_filter($parts);
        
        // 子ども向けテーマの場合は応援要素を追加
        $additionalTraits = $isChildTheme ? ', bright sparkling eyes, cheerful energetic atmosphere, friendly encouraging smile' : '';
        
        // ちびキャラの場合はデフォルメ要素を追加
        $chibiTraits = $isChibi ? ', chibi character, cute deformed proportions, super deformed style, kawaii small body, big head ratio 1:3, simplified features, adorable tiny hands and feet' : '';

        return sprintf(
            '1person, solo character, anime style %s character ID %d, clothing colors that harmonize with hair color, %s%s%s',
            $role,
            $avatar->seed,
            implode(', ', $parts),
            $additionalTraits,
            $chibiTraits
        );
    }

    /**
     * フルプロンプト生成（ベース + ポーズ + 表情 + 品質タグ）
     */
    private function buildFullPrompt(string $basePrompt, string $poseDescription, string $expressionPrompt): string
    {
        return implode(', ', array_filter([
            // 1. 表情（最優先）
            $expressionPrompt,
            
            // 2. 1人のみ
            '1person',
            'solo',
            'single character only',
            
            // 3. キャラクター特徴
            $basePrompt,
            
            // 4. ポーズ
            $poseDescription,
            
            // 5. 品質タグ
            'high quality',
            'masterpiece',
            'best quality',
            '(detailed face:1.2)',
            '(clear facial features:1.2)',
            'sharp focus',
            'professional illustration',
            
            // 6. 背景
            'plain white background',
            'simple background',
            'studio lighting',
        ]));
    }

    /**
     * 画像をS3にアップロード
     */
    private function uploadToS3(
        string $imageUrl, 
        int $userId, 
        string $poseType,
        string $expressionType
    ): string {
        try {
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                throw new \RuntimeException("Failed to download image from: {$imageUrl}");
            }

            $imageContent = $response->body();

            if (empty($imageContent)) {
                throw new \RuntimeException("Downloaded image is empty from: {$imageUrl}");
            }

            $filename = sprintf('%s_%s_%s.png', $poseType, $expressionType, time());
            $path = "avatars/{$userId}/{$filename}";

            $uploaded = Storage::disk('s3')->put($path, $imageContent, [
                'visibility' => 'public',
                'ContentType' => 'image/png',
            ]);

            if (!$uploaded) {
                throw new \RuntimeException("Failed to upload image to S3: {$path}");
            }

            Log::info('[UploadToS3] Success', [
                'path' => $path,
                'size' => strlen($imageContent),
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error('[UploadToS3] Failed', [
                'image_url' => $imageUrl,
                'user_id' => $userId,
                'pose_type' => $poseType,
                'expression_type' => $expressionType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * コメント生成
     */
    private function generateComments(
        OpenAIService $openAIService,
        AICostServiceInterface $aiCostService,
        TeacherAvatar $avatar
    ): int {
        $eventTypes = array_keys(config('const.avatar_events'));

        $totalTokenCost = 0;

        foreach ($eventTypes as $eventType) {
            $prompt = $this->buildCommentPrompt($avatar, $eventType);

            try {
                $response = $openAIService->requestDecomposition($prompt, '', false);

                if ($response && isset($response['response'])) {
                    $comment = $avatar->comments()->updateOrCreate(
                        [
                            'event_type' => $eventType,
                        ],
                        [
                            'comment_text' => trim($response['response']),
                        ]
                    );

                    Log::info('[GenerateComments] Comment saved', [
                        'avatar_id' => $avatar->id,
                        'event_type' => $eventType,
                        'comment_id' => $comment->id,
                        'was_recently_created' => $comment->wasRecentlyCreated,
                        'comment_text_preview' => mb_substr($comment->comment_text, 0, 30) . '...',
                    ]);

                    $inputTokens = $response['usage']['prompt_tokens'] ?? 0;
                    $outputTokens = $response['usage']['completion_tokens'] ?? 0;
                    $commentCost = $aiCostService->calculateChatCost($inputTokens, $outputTokens, 'gpt-4');
                    $totalTokenCost += $commentCost;

                    $aiCostService->logUsage(
                        $avatar->user,
                        TeacherAvatar::class,
                        $avatar->id,
                        'gpt-4',
                        $eventType,
                        ($inputTokens + $outputTokens) / 1000,
                        $commentCost,
                        ['event_type' => $eventType, 'prompt' => $prompt],
                        [
                            'content' => $response['response'],
                            'input_tokens' => $inputTokens,
                            'output_tokens' => $outputTokens,
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error('[GenerateComments] Failed', [
                    'event_type' => $eventType,
                    'error' => $e->getMessage(),
                ]);

                $this->createDefaultComment($avatar, $eventType);
            }
        }

        return $totalTokenCost;
    }

    /**
     * コメントプロンプト生成
     */
    private function buildCommentPrompt(TeacherAvatar $avatar, string $eventType): string
    {
        $personalityDesc = $this->getPersonalityDescription($avatar);

        $eventDescriptions = config('const.avatar_event_scene');

        $eventDesc = $eventDescriptions[$eventType] ?? 'ユーザーが何かアクションを起こしたとき';

        // ユーザが子どもである場合は子ども向けのプロンプトを生成
        $user = $avatar->user;
        if (($user && $user->useChildTheme())) {
            $avatarCharacter = "子どもを励まし応援するサポートアバター";
            $eventDesc .= '（子ども向け）';
        } else {
            $avatarCharacter = "教師アバター";
        }

        return <<<PROMPT
            あなたは以下の性格を持つ{$avatarCharacter}です：
            - 口調: {$personalityDesc['tone']}
            - 熱意: {$personalityDesc['enthusiasm']}
            - 丁寧さ: {$personalityDesc['formality']}
            - ユーモア: {$personalityDesc['humor']}

            シチュエーション: {$eventDesc}

            この性格に合った、短い応援コメント（日本語、50文字以内）を1つだけ生成してください。
            余計な説明や前置きは不要です。コメント本文のみを出力してください。
            PROMPT;
    }

    /**
     * 性格説明を取得
     */
    private function getPersonalityDescription(TeacherAvatar $avatar): array
    {
        $map = [
            'tone' => [
                'gentle' => '優しく温かい',
                'strict' => '厳しく真面目',
                'friendly' => 'フレンドリーで親しみやすい',
                'intellectual' => '知的で論理的',
            ],
            'enthusiasm' => [
                'high' => '熱意が高く積極的',
                'normal' => '程よく落ち着いている',
                'modest' => '控えめで冷静',
            ],
            'formality' => [
                'polite' => '丁寧で礼儀正しい',
                'casual' => 'カジュアルで気さく',
                'formal' => 'フォーマルで格式高い',
            ],
            'humor' => [
                'high' => 'ユーモアがあり面白い',
                'normal' => '真面目で堅実',
                'low' => '機知に富んでいる',
            ],
        ];

        return [
            'tone' => $map['tone'][$avatar->tone] ?? '優しく温かい',
            'enthusiasm' => $map['enthusiasm'][$avatar->enthusiasm] ?? '程よく落ち着いている',
            'formality' => $map['formality'][$avatar->formality] ?? '丁寧で礼儀正しい',
            'humor' => $map['humor'][$avatar->humor] ?? '真面目で堅実',
        ];
    }

    /**
     * デフォルトコメントを作成
     */
    private function createDefaultComment(TeacherAvatar $avatar, string $eventType): void
    {
        $defaults = [
            'task_created'                => '新しいタスクですね。一緒に頑張りましょう！',
            'task_updated'                => 'タスクが更新されましたね。引き続き頑張りましょう！',
            'task_completed'              => 'よく頑張りましたね。素晴らしいです！',
            'task_breakdown'              => 'タスクを分解して、少しずつ進めていきましょう。',
            'task_breakdown_refine'       => 'もう一度見直しましょうか。どうしますか？',
            'group_task_created'          => 'グループタスクですね。協力して進めましょう。',
            'group_task_updated'          => 'グループタスクが更新されました。確認しましょう。',
            'login'                       => 'おかえりなさい！今日も頑張りましょう。',
            'logout'                      => 'お疲れ様でした。また明日お会いしましょう。',
            'login_gap'                   => 'お久しぶりですね。無理せず進めていきましょう。',
            'token_purchased'             => 'ありがとうございます。引き続きサポートします！',
            'performance_personal_viewed' => '素晴らしい実績ですね。努力が実っていますよ。',
            'performance_group_viewed'    => 'グループメンバーの頑張りが見えますね。',
            'tag_created'                 => '新しいタグを作成しましたね。整理が大切です。',
            'tag_updated'                 => 'タグを更新しましたね。',
            'tag_deleted'                 => 'タグを削除しましたね。すっきりしましたか？',
            'group_created'               => '新しいグループですね。おめでとうございます！',
            'group_edited'                => 'グループを編集しましたね。より良くなりますよ。',
            'group_deleted'               => 'グループを削除しましたね。お疲れ様でした。',
        ];

        $commentText = $defaults[$eventType] ?? '頑張りましょう！';

        $comment = $avatar->comments()->updateOrCreate(
            [
                'event_type' => $eventType,
            ],
            [
                'comment_text' => $commentText,
            ]
        );

        Log::info('[DefaultComment] Created', [
            'avatar_id' => $avatar->id,
            'event_type' => $eventType,
            'comment_id' => $comment->id,
            'was_recently_created' => $comment->wasRecentlyCreated,
        ]);
    }

    /**
     * 通知メッセージ生成
     *
     * @param TeacherAvatar $avatar
     * @param int $totalTokenCost
     * @param bool $result
     */
    private function buildNotificationMessage(TeacherAvatar $avatar, int $totalTokenCost, bool $result): string
    {
        // 使用したモデルを取得
        $model_name = $avatar->draw_model_version ?? 'default model';

        if ($result) {
            return sprintf(
                "アバター画像の生成が完了しました！\n使用モデル: %s\n合計トークンコスト: %dトークン\n教師アバターページで新しいアバターを確認してください。",
                $model_name,
                $totalTokenCost
            );
        } else {
            return sprintf(
                "アバター画像の生成に失敗しました。\n使用モデル: %s\n合計トークンコスト: %dトークン\n再度お試しください。",
                $model_name,
                $totalTokenCost
            );
        }
    }
}