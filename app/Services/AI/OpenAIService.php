<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAIサービス
 * 
 * OpenAI APIとの通信を担当します。
 */
class OpenAIService implements OpenAIServiceInterface
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

        $user = Auth::user();
        if ($user->useChildTheme()) {
            $systemPrompt = $isRefinement
                ? 'あなたはタスク細分化の専門家です。与えられたタスクをより小さく具体的なサブタスクに分解してください。箇条書きでタスク名だけを出力してください。ただし、出力するタスク名は小学生程度の子どもに分かるようにしてください。'
                : 'あなたはタスク分解の専門家です。与えられたタスクを実行可能な複数のサブタスクに分解してください。箇条書きで短いタスク名だけを出力してください。ただし、出力するタスク名は小学生程度の子どもに分かるようにしてください。';
        } else {
            $systemPrompt = $isRefinement
                ? 'あなたはタスク細分化の専門家です。与えられたタスクをより小さく具体的なサブタスクに分解してください。箇条書きで短いタスク名だけを出力してください。'
                : 'あなたはタスク分解の専門家です。与えられたタスクを実行可能な複数のサブタスクに分解してください。箇条書きで短いタスク名だけを出力してください。';
        }

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
                'total_tokens' => $usage['prompt_tokens'] + $usage['completion_tokens'] * config('const.openai_prompt_completion_ratio') ?? 0, // OpenAIの入力プロンプトと出力プロンプトの比率で重みづけ
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
            'max_tokens' => 600,  // 日本語で300-400文字程度のコメント生成に対応
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

    /**
     * 月次レポート用のAIコメント生成
     *
     * @param array $reportData レポートデータ
     * @param array|null $avatarPersonality アバター性格情報 ['tone', 'enthusiasm', 'formality', 'humor']
     * @param array $memberChanges メンバー変化情報
     * @param string $userTheme ユーザーテーマ ('adult' or 'child')
     * @return array ['comment' => string, 'usage' => array]
     */
    public function generateMonthlyReportComment(array $reportData, ?array $avatarPersonality = null, array $memberChanges = [], string $userTheme = 'child'): array
    {
        if (!$this->apiKey) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        // アバター性格に基づいたシステムプロンプト生成（変化情報を含める）
        $systemPrompt = $this->buildReportCommentSystemPrompt($avatarPersonality, $memberChanges, $userTheme);
        
        // レポートデータを要約
        $normalTaskCount = 0;
        $groupTaskCount = 0;
        $totalReward = 0;
        $memberCount = 0;
        
        if (isset($reportData['member_task_summary'])) {
            $memberCount = count($reportData['member_task_summary']);
            foreach ($reportData['member_task_summary'] as $member) {
                $normalTaskCount += $member['completed_count'] ?? 0;
            }
        }
        
        if (isset($reportData['group_task_summary'])) {
            foreach ($reportData['group_task_summary'] as $member) {
                $groupTaskCount += $member['completed_count'] ?? 0;
                $totalReward += $member['reward'] ?? 0;
            }
        }
        
        $previousNormalCount = $reportData['normal_task_count_previous_month'] ?? 0;
        $previousGroupCount = $reportData['group_task_count_previous_month'] ?? 0;
        $previousReward = $reportData['reward_previous_month'] ?? 0;
        
        // ユーザープロンプト作成
        $userPrompt = <<<PROMPT
            今月の実績:
            - メンバー数: {$memberCount}人
            - 通常タスク完了数: {$normalTaskCount}件 (前月: {$previousNormalCount}件)
            - グループタスク完了数: {$groupTaskCount}件 (前月: {$previousGroupCount}件)
            - 獲得報酬: {$totalReward}ポイント (前月: {$previousReward}ポイント)

            上記の実績を踏まえ、グループメンバーへの励ましや改善提案を含む短いコメントを生成してください。
            PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 600,  // 日本語で300-400文字程度のコメント生成に対応
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if (!$response->successful()) {
                Log::error('OpenAI Monthly Report Comment API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException("OpenAI API error: {$response->status()}");
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $usage = $data['usage'] ?? [];

            if (!$content) {
                throw new \RuntimeException('No content in OpenAI response');
            }

            return [
                'comment' => trim($content),
                'usage' => [
                    'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                    'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    'total_tokens' => $usage['total_tokens'] ?? 0,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Monthly report comment generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * アバター性格に基づいたシステムプロンプト構築
     *
     * @param array|null $personality アバター性格情報
     * @param array $memberChanges メンバー変化情報
     * @param string $userTheme ユーザーテーマ ('adult' or 'child')
     * @return string
     */
    protected function buildReportCommentSystemPrompt(?array $personality, array $memberChanges = [], string $userTheme = 'child'): string
    {
        // テーマに応じた基本プロンプト
        if ($userTheme === 'adult') {
            $basePrompt = "あなたは教師アバターとして、グループの月次タスク実績レポートにコメントを付けます。丁寧語・敬語を使用し、大人に対して適切な言葉遣いでコメントしてください。";
        } else {
            $basePrompt = "あなたは教師アバターとして、グループの月次タスク実績レポートにコメントを付けます。子どもに分かりやすく、親しみやすい口調でコメントしてください。";
        }
        
        // メンバー変化情報をプロンプトに追加
        $changesSection = '';
        if (!empty($memberChanges)) {
            $changesSection = "\n\n【特に注目すべきメンバー】\n";
            foreach ($memberChanges as $change) {
                $userName = $change['user_name'];
                $percentage = abs($change['change_percentage']);
                $current = $change['current'];
                $previous = $change['previous'];
                
                if ($change['type'] === 'increase') {
                    // 増加の場合は称賛
                    $changesSection .= "- {$userName}さん: 前月{$previous}件→今月{$current}件（{$percentage}%増加）！素晴らしい成長です。この調子を称賛し、さらなる励みになる言葉をかけてあげてください。\n";
                } else {
                    // 減少の場合は心配と励まし
                    $changesSection .= "- {$userName}さん: 前月{$previous}件→今月{$current}件（{$percentage}%減少）。心配な状況ですが、優しく励まし、前向きな言葉をかけてサポートしてください。\n";
                }
            }
            $changesSection .= "\n※上記のメンバーの変化を必ずコメントに反映させ、具体的に名前を挙げて言及してください。";
        }
        
        if (!$personality) {
            return $basePrompt . $changesSection . "\n\n励ましや建設的なアドバイスを含む、150文字程度の短いコメントを日本語で生成してください。";
        }
        
        // 性格特性に応じたプロンプト調整
        $toneMap = [
            'friendly' => '親しみやすく温かい口調で',
            'professional' => '丁寧でプロフェッショナルな口調で',
            'casual' => 'カジュアルで気さくな口調で',
            'strict' => '厳しくも愛情のある口調で',
        ];
        
        $enthusiasmMap = [
            'high' => '熱意を込めて',
            'moderate' => '落ち着いて',
            'low' => '冷静に',
        ];
        
        $formalityMap = [
            'formal' => '敬語を使い',
            'neutral' => '適度な丁寧さで',
            'informal' => 'フレンドリーな言葉遣いで',
        ];
        
        $humorMap = [
            'high' => 'ユーモアを交えて',
            'moderate' => '時々軽い冗談を入れつつ',
            'none' => '真面目に',
        ];
        
        $tone = $toneMap[$personality['tone'] ?? 'friendly'] ?? '親しみやすく';
        $enthusiasm = $enthusiasmMap[$personality['enthusiasm'] ?? 'moderate'] ?? '落ち着いて';
        $formality = $formalityMap[$personality['formality'] ?? 'neutral'] ?? '適度な丁寧さで';
        $humor = $humorMap[$personality['humor'] ?? 'moderate'] ?? '時々軽い冗談を入れつつ';
        
        return "{$basePrompt}{$changesSection}\n\n{$tone}、{$enthusiasm}、{$formality}、{$humor}、励ましや建設的なアドバイスを含む150文字程度の短いコメントを日本語で生成してください。";
    }
}
