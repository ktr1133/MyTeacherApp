<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\User;
use App\Models\Tag;
use App\Models\Group;
use App\Services\Token\TokenServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * メンバー別概況生成機能のトークン消費量計測用テストデータ生成
 * 
 * 4パターンのユーザーに対して過去6ヶ月分のタスクを生成:
 * - UserA: 0件/月（未活動）
 * - UserB: 30件/月（1件/日ペース）
 * - UserC: 150件/月（5件/日ペース）
 * - UserD: 300件/月（10件/日ペース - 最大想定）
 * 
 * グループID: 1（既存のtestuserのグループ）
 * 対象期間: 2025年6月～2025年11月（6ヶ月）
 */
class MemberSummaryTestDataSeeder extends Seeder
{
    private array $taskTitles = [
        '数学の宿題を完了する',
        '英語のエッセイを書く',
        '理科の実験レポートを提出',
        '社会科のプレゼン準備',
        '音楽の練習をする',
        '体育の準備運動',
        '美術作品を制作する',
        '国語の読書感想文',
        '家庭科の調理実習準備',
        'プログラミング課題',
        'データベース設計',
        'Webアプリケーション開発',
        'UI/UXデザイン',
        'テスト仕様書作成',
        'コードレビュー',
        '資料作成',
        'ミーティング準備',
        'レビュー対応',
        'バグ修正',
        '機能実装',
        'ドキュメント更新',
        '進捗報告書作成',
        'メールチェック',
        '顧客対応',
        'プロジェクト管理',
    ];

    private array $taskDescriptions = [
        '期限までに完了させる必要があります',
        '詳細は添付資料を参照してください',
        '不明点があれば相談してください',
        '他のメンバーと協力して進めましょう',
        '品質に注意して作業してください',
    ];

    private array $tagNames = [
        '重要', '緊急', '学習', '課題', 'プロジェクト', '業務', '日常', 'レビュー', '開発'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('メンバー別概況生成機能テストデータ作成開始...');

        // TokenServiceを取得
        $tokenService = app(TokenServiceInterface::class);

        // Group 1を取得
        $group = Group::findOrFail(1);
        $this->command->info("グループID: {$group->id} - {$group->name}");

        // 既存ユーザーを確認（testuser）
        $masterUser = User::where('group_id', $group->id)->where('id', 8)->first();
        if (!$masterUser) {
            $this->command->error('testuser (ID: 8) が見つかりません。');
            return;
        }

        // テストユーザー4パターンを作成または取得
        $users = $this->createTestUsers($group, $tokenService);

        // 過去6ヶ月分のタスクを生成（タグも各ユーザー用に作成）
        $this->generateTasks($users);

        $this->command->info('テストデータ作成完了！');
        $this->command->newLine();
        $this->command->info('次のステップ:');
        $this->command->info('1. php artisan reports:generate-monthly-all で月次レポートを生成');
        $this->command->info('2. 各ユーザーでメンバー別概況生成を3回ずつ実行');
        $this->command->info('3. トークン消費量を記録して平均値を算出');
    }

    /**
     * テストユーザー4パターンを作成
     */
    private function createTestUsers(Group $group, TokenServiceInterface $tokenService): array
    {
        $patterns = [
            ['email' => 'test-user-a@example.com', 'name' => 'TestUserA', 'tasks_per_month' => 0],
            ['email' => 'test-user-b@example.com', 'name' => 'TestUserB', 'tasks_per_month' => 30],
            ['email' => 'test-user-c@example.com', 'name' => 'TestUserC', 'tasks_per_month' => 150],
            ['email' => 'test-user-d@example.com', 'name' => 'TestUserD', 'tasks_per_month' => 300],
        ];

        $users = [];

        foreach ($patterns as $pattern) {
            $user = User::firstOrCreate(
                ['email' => $pattern['email']],
                [
                    'username' => $pattern['name'],
                    'password' => bcrypt('password'),
                    'group_id' => $group->id,
                ]
            );

            // トークン残高を確保（TokenService経由）
            $balance = $tokenService->getOrCreateBalance(User::class, $user->id);
            $currentBalance = $balance->balance_amount;

            // 残高が不足している場合は追加付与
            if ($currentBalance < 10000000) {
                $tokenService->grantTokens(
                    $user,
                    10000000 - $currentBalance,
                    'テストデータ用トークン付与'
                );
                $this->command->comment("  トークン付与: " . number_format(10000000 - $currentBalance) . " tokens");
            }

            $user->tasks_per_month = $pattern['tasks_per_month'];
            $users[] = $user;

            $this->command->info("ユーザー作成/取得: {$user->username} (ID: {$user->id}) - {$pattern['tasks_per_month']}件/月");
        }

        return $users;
    }

    /**
     * 過去6ヶ月分のタスクを生成
     */
    private function generateTasks(array $users): void
    {
        $months = [
            '2025-06' => ['start' => '2025-06-01', 'end' => '2025-06-30'],
            '2025-07' => ['start' => '2025-07-01', 'end' => '2025-07-31'],
            '2025-08' => ['start' => '2025-08-01', 'end' => '2025-08-31'],
            '2025-09' => ['start' => '2025-09-01', 'end' => '2025-09-30'],
            '2025-10' => ['start' => '2025-10-01', 'end' => '2025-10-31'],
            '2025-11' => ['start' => '2025-11-01', 'end' => '2025-11-30'],
        ];

        foreach ($users as $user) {
            $this->command->info("ユーザー {$user->username} のタスク生成開始...");

            // ユーザー専用のタグを作成
            $tags = $this->createTagsForUser($user);

            foreach ($months as $monthKey => $period) {
                $taskCount = $user->tasks_per_month;

                if ($taskCount == 0) {
                    continue; // UserA: タスクなし
                }

                // 通常タスク: 70%
                $normalTaskCount = (int)($taskCount * 0.7);
                // グループタスク: 30%
                $groupTaskCount = $taskCount - $normalTaskCount;

                // 通常タスク生成
                $this->generateNormalTasks($user, $period, $normalTaskCount, $tags);

                // グループタスク生成
                $this->generateGroupTasks($user, $period, $groupTaskCount, $tags);

                $this->command->comment("  {$monthKey}: 通常{$normalTaskCount}件 + グループ{$groupTaskCount}件 = 合計{$taskCount}件");
            }
        }
    }

    /**
     * ユーザー専用のタグを作成
     */
    private function createTagsForUser(User $user): array
    {
        $tags = [];

        foreach ($this->tagNames as $tagName) {
            $tag = Tag::firstOrCreate(
                ['user_id' => $user->id, 'name' => $tagName]
            );
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * 通常タスクを生成
     */
    private function generateNormalTasks(User $user, array $period, int $count, array $tags): void
    {
        $start = Carbon::parse($period['start']);
        $end = Carbon::parse($period['end']);
        $days = $end->diffInDays($start);

        for ($i = 0; $i < $count; $i++) {
            // ランダムな日付を生成
            $completedAt = $start->copy()->addDays(rand(0, $days))->setTime(rand(9, 18), rand(0, 59));

            $task = Task::create([
                'title' => $this->taskTitles[array_rand($this->taskTitles)],
                'description' => $this->taskDescriptions[array_rand($this->taskDescriptions)],
                'user_id' => $user->id,
                'assigned_by_user_id' => $user->id,
                'is_completed' => true,
                'priority' => rand(1, 5),
                'reward' => 0, // 通常タスクは報酬なし
                'completed_at' => $completedAt,
                'approved_at' => $completedAt->copy()->addMinutes(rand(5, 30)),
                'created_at' => $completedAt->copy()->subDays(rand(1, 3)),
                'updated_at' => $completedAt,
            ]);

            // ランダムにタグを2-3個付与
            $randomTags = collect($tags)->random(rand(2, min(3, count($tags))));
            $task->tags()->attach($randomTags);
        }
    }

    /**
     * グループタスクを生成
     */
    private function generateGroupTasks(User $user, array $period, int $count, array $tags): void
    {
        $start = Carbon::parse($period['start']);
        $end = Carbon::parse($period['end']);
        $days = $end->diffInDays($start);

        for ($i = 0; $i < $count; $i++) {
            $completedAt = $start->copy()->addDays(rand(0, $days))->setTime(rand(9, 18), rand(0, 59));

            $task = Task::create([
                'title' => $this->taskTitles[array_rand($this->taskTitles)],
                'description' => $this->taskDescriptions[array_rand($this->taskDescriptions)],
                'user_id' => $user->id,
                'assigned_by_user_id' => $user->id, // 自己割当（簡易版）
                'group_task_id' => (string) Str::uuid(),
                'is_completed' => true,
                'priority' => rand(1, 5),
                'reward' => rand(100, 1000), // グループタスクは報酬あり
                'completed_at' => $completedAt,
                'approved_at' => $completedAt->copy()->addMinutes(rand(5, 30)),
                'created_at' => $completedAt->copy()->subDays(rand(1, 3)),
                'updated_at' => $completedAt,
            ]);

            // ランダムにタグを2-3個付与
            $randomTags = collect($tags)->random(rand(2, min(3, count($tags))));
            $task->tags()->attach($randomTags);
        }
    }
}
