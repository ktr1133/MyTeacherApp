<?php

namespace App\Services\Task;

use App\Models\User;
use App\Models\TaskProposal;
use App\Repositories\Task\TaskProposalRepositoryInterface;
use App\Services\AI\OpenAIServiceInterface;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Support\Facades\Log;

class TaskProposalService implements TaskProposalServiceInterface
{
    public function __construct(
        private TaskProposalRepositoryInterface $repo,
        private OpenAIServiceInterface $openAI,
        private TokenServiceInterface $tokenService
    ) {}

    /**
     * @inheritDoc
     */
    public function createProposal(
        User $user,
        string $originalText,
        string $span,
        ?string $context,
        bool $isRefinement
    ): TaskProposal {
        // 推定トークン数をチェック（安全マージンを考慮して1000トークンと仮定）
        $estimatedTokens = 1000;
        
        if (!$this->tokenService->checkBalance($user, $estimatedTokens)) {
            throw new \RuntimeException('トークン残高が不足しています。トークンを購入してください。');
        }

        // AI へ分解依頼（必要なら isRefinement も渡す）
        // OpenAIService は ['response' => string, 'usage' => array, 'model' => string] を返す
        $aiResult = $this->openAI->requestDecomposition(
            $originalText,
            "期間: {$span}" . ($context ? ", {$context}" : ''),
            $isRefinement
        );

        // 実際に使用したトークン数を取得
        $usage = $aiResult['usage'] ?? [
            'prompt_tokens'     => 0,
            'completion_tokens' => 0,
            'total_tokens'      => 0,
        ];
        $actualTokens = $usage['total_tokens'];

        // トークンを消費（インフラ負荷を加味）
        $totalTokenCost = $this->tokenService->calcUsedTokens('task_decomposition', $actualTokens);
        
        $consumed = $this->tokenService->consumeTokens(
            $user,
            $totalTokenCost,
            "タスク提案生成: {$originalText}",
        );

        if (!$consumed) {
            Log::warning('Failed to consume tokens after AI request', [
                'user_id' => $user->id,
                'tokens' => $actualTokens,
                'original_text' => $originalText,
            ]);
            // トークン消費に失敗してもAI結果は保存する（整合性のため）
        }

        // パース処理を確実に実行
        $proposed = [];
        
        // 1. response キーが文字列ならパース（OpenAIServiceの新仕様）
        if (isset($aiResult['response']) && is_string($aiResult['response'])) {
            $proposed = $this->parseAIResponse($aiResult['response']);
        }
        // 2. tasks キーが配列なら使用（後方互換性）
        elseif (isset($aiResult['tasks']) && is_array($aiResult['tasks'])) {
            $proposed = $aiResult['tasks'];
        } 
        // 3. text キーがあればパース（後方互換性）
        elseif (isset($aiResult['text']) && is_string($aiResult['text'])) {
            $proposed = $this->parseAIResponse($aiResult['text']);
        }
        // 4. 配列全体が文字列ならパース（フォールバック）
        elseif (is_string($aiResult)) {
            $proposed = $this->parseAIResponse($aiResult);
        }

        // タスク提案が空の場合はエラー
        if (empty($proposed)) {
            Log::error('AI returned empty task list', [
                'user_id' => $user->id,
                'original_text' => $originalText,
                'ai_result' => $aiResult,
            ]);
            throw new \RuntimeException('AIからタスク提案を取得できませんでした。');
        }

        // 提案をDBに保存
        return $this->repo->create([
            'user_id' => $user->id,
            'original_task_text' => $originalText,
            'proposal_context' => (string)($context ?? ''),
            'proposed_tasks_json' => $proposed,
            'model_used' => $aiResult['model'] ?? 'unknown',
            'prompt_tokens' => $usage['prompt_tokens'],
            'completion_tokens' => $usage['completion_tokens'],
            'total_tokens' => $usage['total_tokens'],
            'was_adopted' => false,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function markAsAdopted(int $proposalId, array $taskIds): void
    {
        $this->repo->markAsAdopted($proposalId, $taskIds);
    }

    /**
     * AIの応答を簡易的にパースしてタスク配列に変換する。
     *
     * @param string $response AIからの応答テキスト
     * @return array パースされたタスク配列
     */
    private function parseAIResponse(string $response): array
    {
        if (empty(trim($response))) {
            return [];
        }

        $lines = preg_split("/\r?\n/", trim($response));
        $tasks = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            
            // "- タスク名" や "1. タスク名" の形式に対応
            if (preg_match('/^(?:[-*•]|\d+\.)\s*(.+)$/', $line, $m)) {
                $title = trim($m[1]);
                if ($title !== '') {
                    $tasks[] = ['title' => $title];
                }
            } elseif ($line !== '') {
                // 箇条書き記号がない場合もタスクとして認識
                $tasks[] = ['title' => $line];
            }
        }
        
        return $tasks;
    }
}