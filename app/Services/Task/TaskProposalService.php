<?php

namespace App\Services\Task;

use App\Models\User;
use App\Models\TaskProposal;
use App\Repositories\Task\TaskProposalRepositoryInterface;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

class TaskProposalService implements TaskProposalServiceInterface
{
    public function __construct(
        private TaskProposalRepositoryInterface $repo,
        private OpenAIService $openAI
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
        // AI へ分解依頼（必要なら isRefinement も渡す）
        $aiRaw = $this->openAI->requestDecomposition(
            $originalText,
            "期間: {$span}" . ($context ? ", {$context}" : ''),
            $isRefinement
        );

        // パース処理を確実に実行
        $proposed = [];
        
        // 1. tasks キーが配列なら使用
        if (isset($aiRaw['tasks']) && is_array($aiRaw['tasks'])) {
            $proposed = $aiRaw['tasks'];
        } 
        // 2. text キーがあればパース
        elseif (isset($aiRaw['text']) && is_string($aiRaw['text'])) {
            $proposed = $this->parseAIResponse($aiRaw['text']);
        } 
        // 3. response キーがあればパース（ログの形式に対応）
        elseif (isset($aiRaw['response']) && is_string($aiRaw['response'])) {
            $proposed = $this->parseAIResponse($aiRaw['response']);
        }
        // 4. 配列全体が文字列ならパース
        elseif (is_string($aiRaw)) {
            $proposed = $this->parseAIResponse($aiRaw);
        }

        return $this->repo->create([
            'user_id' => $user->id,
            'original_task_text' => $originalText,
            'proposal_context' => (string)($context ?? ''),
            'proposed_tasks_json' => $proposed,
            'model_used' => (string)($aiRaw['model'] ?? 'unknown'),
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
                $tasks[] = ['title' => $line];
            }
        }
        
        return $tasks;
    }
}