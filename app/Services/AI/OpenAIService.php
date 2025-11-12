<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAIサービス
 * 
 * OpenAI APIとの通信を担当します。
 */
class OpenAIService
{
    public function __construct(
        private ?string $apiKey = null,
        private ?string $baseUrl = null,
        private ?string $model = null,
        private int $timeout = 30
    ) {
        $this->apiKey = $this->apiKey ?? config('services.openai.api_key');
        $this->baseUrl = rtrim($this->baseUrl ?? config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $this->model = $this->model ?? config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * タスク分解リクエスト
     *
     * @param string $title タスクタイトル
     * @param string $context コンテキスト情報
     * @param bool $isRefinement 細分化フラグ（デフォルト: false）
     * @return array ['response' => string, 'usage' => array, 'model' => string]
     */
    public function requestDecomposition(string $title, string $context = '', bool $isRefinement = false): array
    {
        if (!$this->apiKey) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $systemPrompt = $isRefinement
            ? 'あなたはタスク細分化の専門家です。与えられたタスクをより小さく具体的なサブタスクに分解してください。箇条書きで短いタスク名だけを出力してください。'
            : 'あなたはタスク分解の専門家です。与えられたタスクを実行可能な複数のサブタスクに分解してください。箇条書きで短いタスク名だけを出力してください。';

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "元タスク: {$title}\n補足: {$context}"],
            ],
            'temperature' => 0.3,
            'max_tokens' => 500,
        ];

        $res = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        if (!$res->ok()) {
            Log::error('OpenAI API Error', [
                'status' => $res->status(),
                'body' => $res->body(),
            ]);
            throw new \RuntimeException("OpenAI API error: {$res->status()} {$res->body()}");
        }

        $data = $res->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];

        if (!is_string($content) || $content === '') {
            throw new \RuntimeException('OpenAI response has no content.');
        }

        return [
            'response' => $content,
            'usage' => [
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0,
            ],
            'model' => $data['model'] ?? $this->model,
        ];
    }

    /**
     * DALL-E 3で画像生成
     *
     * @param string $prompt プロンプト
     * @param string $size サイズ ('1024x1024', '1792x1024', '1024x1792')
     * @param string $quality 品質 ('standard', 'hd')
     * @return string|null 生成された画像のURL
     */
    public function generateImage(string $prompt, string $size = '1024x1024', string $quality = 'standard'): ?string
    {
        if (!$this->apiKey) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }
        logger()->info('prompt', [$prompt]);

        $payload = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'quality' => $quality,
            'response_format' => 'url',
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60) // 画像生成は時間がかかるため60秒
                ->post("{$this->baseUrl}/images/generations", $payload);

            if (!$response->successful()) {
                Log::error('DALL-E 3 API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException("DALL-E 3 API error: {$response->status()} {$response->body()}");
            }

            $data = $response->json();
            $imageUrl = $data['data'][0]['url'] ?? null;

            if (!$imageUrl) {
                throw new \RuntimeException('No image URL in DALL-E 3 response');
            }

            Log::info('DALL-E 3 API Response', [
                'url' => $imageUrl,
            ]);

            return $imageUrl;

        } catch (\Exception $e) {
            Log::error('DALL-E 3 image generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Chat APIでテキスト生成（汎用メソッド）
     *
     * @param string $prompt プロンプト
     * @param string|null $systemPrompt システムプロンプト
     * @param string $model 使用モデル
     * @return array|null
     */
    public function chat(string $prompt, ?string $systemPrompt = null, string $model = 'gpt-4'): ?array
    {
        if (!$this->apiKey) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $messages = [];
        
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 200,
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if (!$response->successful()) {
                Log::error('Chat API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException("Chat API error: {$response->status()} {$response->body()}");
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $usage = $data['usage'] ?? [];

            if (!$content) {
                throw new \RuntimeException('No content in Chat API response');
            }

            return [
                'content' => $content,
                'usage' => [
                    'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                    'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    'total_tokens' => $usage['total_tokens'] ?? 0,
                ],
                'model' => $data['model'] ?? $model,
            ];

        } catch (\Exception $e) {
            Log::error('Chat API failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}