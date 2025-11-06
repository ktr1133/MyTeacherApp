<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

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

    public function requestDecomposition(string $title, string $context = ''): string
    {
        if (!$this->apiKey) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'あなたはタスク分解の専門家です。箇条書きで短いタスク名だけを出力してください。'],
                ['role' => 'user', 'content' => "元タスク: {$title}\n補足: {$context}"],
            ],
            'temperature' => 0.3,
        ];

        $res = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        if (!$res->ok()) {
            throw new \RuntimeException("OpenAI API error: {$res->status()} {$res->body()}");
        }

        $data = $res->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || $content === '') {
            throw new \RuntimeException('OpenAI response has no content.');
        }
        return $content;
    }
}