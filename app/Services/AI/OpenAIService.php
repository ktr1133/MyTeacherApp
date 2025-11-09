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

        Log::info('OpenAI API Request', [
            'model' => $this->model,
            'title' => $title,
            'context' => $context,
            'is_refinement' => $isRefinement,
        ]);

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

        Log::info('OpenAI API Response', [
            'model' => $data['model'] ?? 'unknown',
            'usage' => $usage,
            'content_length' => strlen($content),
        ]);

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
}