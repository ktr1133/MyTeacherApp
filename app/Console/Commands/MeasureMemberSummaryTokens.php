<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AI\OpenAIService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * メンバー別概況生成のトークン消費量計測コマンド
 * 
 * テストデータ（TestUserA-D）を使用して、
 * 実際のOpenAI API呼び出しを行い、トークン消費量を計測する。
 */
class MeasureMemberSummaryTokens extends Command
{
    /**
     * コマンドシグネチャ
     *
     * @var string
     */
    protected $signature = 'measure:member-summary-tokens 
                            {userId : 対象ユーザーID}
                            {--iterations=3 : 実行回数（平均を取るため）}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = 'メンバー別概況生成のトークン消費量を計測';

    /**
     * OpenAIサービス
     *
     * @var OpenAIService
     */
    protected OpenAIService $openAIService;

    /**
     * コンストラクタ
     *
     * @param OpenAIService $openAIService
     */
    public function __construct(OpenAIService $openAIService)
    {
        parent::__construct();
        $this->openAIService = $openAIService;
    }

    /**
     * コマンド実行
     *
     * @return int
     */
    public function handle(): int
    {
        $userId = $this->argument('userId');
        $iterations = (int) $this->option('iterations');

        // ユーザー取得
        $user = User::find($userId);
        if (!$user) {
            $this->error("ユーザーID {$userId} が見つかりません");
            return 1;
        }

        $this->info("=== メンバー別概況生成トークン消費量計測 ===");
        $this->info("ユーザー: {$user->name} (ID: {$user->id})");
        $this->info("実行回数: {$iterations}回");
        $this->newLine();

        // 対象月（2025-10: テストデータで最もタスクが多い月）
        $targetMonth = '2025-10';
        $startDate = Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // ユーザーのタスクデータ取得
        $taskData = $this->getUserTaskData($user->id, $startDate, $endDate);
        
        $this->info("【データ概要】");
        $this->info("通常タスク: {$taskData['normal_tasks']['count']}件");
        $this->info("グループタスク: {$taskData['group_tasks']['count']}件");
        $this->info("合計タスク: " . ($taskData['normal_tasks']['count'] + $taskData['group_tasks']['count']) . "件");
        $this->info("報酬合計: {$taskData['group_tasks']['total_reward']}円");
        $this->newLine();

        // タスク傾向分析用のタスクタイトル取得（全件）
        $taskTitles = $this->getTaskTitles($user->id, $startDate, $endDate);
        $this->info("タスクタイトル数: " . count($taskTitles) . "件");
        $this->newLine();

        // トークン消費量を複数回計測
        $tokenUsages = [];
        $bar = $this->output->createProgressBar($iterations);
        $bar->start();

        for ($i = 1; $i <= $iterations; $i++) {
            try {
                // メンバー概況コメント生成（タスクタイトルを含む）
                $result = $this->generateMemberSummaryComment($user, $taskData, $taskTitles);
                $totalTokens = $result['usage']['total_tokens'] ?? 0;

                $tokenUsages[] = $totalTokens;

                $bar->advance();
            } catch (\Exception $e) {
                $bar->advance();
                $this->newLine();
                $this->error("エラー発生（試行{$i}回目）: " . $e->getMessage());
                continue;
            }
        }

        $bar->finish();
        $this->newLine(2);

        // 結果集計
        if (empty($tokenUsages)) {
            $this->error("計測に失敗しました。すべての試行でエラーが発生しました。");
            return 1;
        }

        $this->info("=== 計測結果 ===");
        $this->table(
            ['試行回数', 'トークン消費量'],
            collect($tokenUsages)->map(fn($tokens, $index) => [
                '試行' . ($index + 1),
                number_format($tokens) . ' tokens'
            ])->toArray()
        );

        $avgTokens = round(array_sum($tokenUsages) / count($tokenUsages));
        $minTokens = min($tokenUsages);
        $maxTokens = max($tokenUsages);

        $this->newLine();
        $this->info("平均: " . number_format($avgTokens) . " tokens");
        $this->info("最小: " . number_format($minTokens) . " tokens");
        $this->info("最大: " . number_format($maxTokens) . " tokens");
        $this->newLine();

        // 推奨値の提示（最大値 + 10%の安全マージン）
        $recommendedValue = (int) ceil($maxTokens * 1.1);
        $this->info("【推奨設定値】");
        $this->info("config/const.php の 'token.consumption.member_summary_generation' に設定:");
        $this->info(number_format($recommendedValue) . " tokens（最大値 + 10% マージン）");

        return 0;
    }

    /**
     * ユーザーのタスクデータを取得
     *
     * @param int $userId ユーザーID
     * @param Carbon $startDate 開始日
     * @param Carbon $endDate 終了日
     * @return array タスクデータ
     */
    protected function getUserTaskData(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        // 通常タスク集計
        $normalTasks = DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereNull('group_task_id')
            ->select(
                DB::raw('COUNT(*) as count')
            )
            ->first();

        // グループタスク集計
        $groupTasks = DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereNotNull('group_task_id')
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(reward), 0) as total_reward')
            )
            ->first();

        return [
            'normal_tasks' => [
                'count' => $normalTasks->count ?? 0,
            ],
            'group_tasks' => [
                'count' => $groupTasks->count ?? 0,
                'total_reward' => $groupTasks->total_reward ?? 0,
            ],
        ];
    }

    /**
     * タスクタイトル一覧を取得
     *
     * @param int $userId ユーザーID
     * @param Carbon $startDate 開始日
     * @param Carbon $endDate 終了日
     * @return array タスクタイトル配列
     */
    protected function getTaskTitles(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        return DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->orderBy('completed_at', 'desc')
            ->pluck('title')
            ->toArray();
    }

    /**
     * メンバー概況コメント生成（プロンプト）
     *
     * @param User $user ユーザー
     * @param array $taskData タスクデータ
     * @param array $taskTitles タスクタイトル一覧
     * @return array 生成結果（comment, usage）
     */
    protected function generateMemberSummaryComment(User $user, array $taskData, array $taskTitles): array
    {
        // システムプロンプト
        $systemPrompt = <<<PROMPT
あなたは教師アバターとして、メンバーの月次活動を分析してコメントを生成します。

以下の情報を元に、簡潔で具体的なコメント（200-300文字程度）を生成してください：

- メンバー名: {$user->name}
- 通常タスク完了数: {$taskData['normal_tasks']['count']}件
- グループタスク完了数: {$taskData['group_tasks']['count']}件
- 報酬合計: {$taskData['group_tasks']['total_reward']}円

コメントには以下を含めてください：
1. 完了タスク数への評価
2. 報酬実績への言及
3. タスクの傾向分析（タスクタイトルから）
4. 今後の期待やアドバイス
PROMPT;

        // タスクタイトルを追加
        if (!empty($taskTitles)) {
            $systemPrompt .= "\n\nタスクタイトル例（最近完了分）:";
            foreach (array_slice($taskTitles, 0, 20) as $index => $title) {
                $systemPrompt .= "\n" . ($index + 1) . ". " . $title;
            }
        }

        // ユーザープロンプト
        $userPrompt = "このメンバーの月次活動について、教師として具体的なコメントを生成してください。";

        // OpenAI APIコール
        return $this->openAIService->chat($userPrompt, $systemPrompt, 'gpt-4o-mini');
    }
}
