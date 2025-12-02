<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\User;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * 2025年11月分のテスト用タスクデータを生成するシーダー
 * 
 * 対象: testuser (ID: 8) とグループメンバー (IDs: 9-13)
 * - 通常タスク: 各ユーザー月間15-25件
 * - グループタスク: グループ全体で月間10-15件
 */
class November2025TasksSeeder extends Seeder
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
        '資料作成',
        'ミーティング準備',
        'レビュー対応',
    ];

    private array $taskDescriptions = [
        '期限までに完了させる必要があります',
        '詳細は添付資料を参照してください',
        '不明点があれば相談してください',
        '他のメンバーと協力して進めましょう',
        '品質に注意して作業してください',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            $groupMemberIds = [8, 9, 10, 11, 12, 13]; // testuser + 5名
            $tags = Tag::whereIn('name', ['重要', '緊急', '通常', '低優先度'])->get();
            
            if ($tags->isEmpty()) {
                $this->command->warn('タグが見つかりません。先にタグを作成してください。');
                $tags = collect([
                    Tag::firstOrCreate(['name' => '重要', 'user_id' => 8]),
                    Tag::firstOrCreate(['name' => '緊急', 'user_id' => 8]),
                    Tag::firstOrCreate(['name' => '通常', 'user_id' => 8]),
                    Tag::firstOrCreate(['name' => '低優先度', 'user_id' => 8]),
                ]);
            }

            // 1. 各ユーザーの通常タスクを生成
            foreach ($groupMemberIds as $userId) {
                $taskCount = rand(15, 25);
                $this->command->info("ユーザーID {$userId}: 通常タスク {$taskCount}件を生成中...");
                $this->createNormalTasks($userId, $taskCount, $tags);
            }

            // 2. グループタスクを生成（assigned_by: testuser ID:8）
            $groupTaskCount = rand(10, 15);
            $this->command->info("グループタスク {$groupTaskCount}件を生成中...");
            $this->createGroupTasks(8, $groupMemberIds, $groupTaskCount, $tags);

            DB::commit();
            $this->command->info('✅ 2025年11月分のタスクデータ生成が完了しました');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('エラー: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 通常タスクを生成
     */
    private function createNormalTasks(int $userId, int $count, $tags): void
    {
        $november = Carbon::create(2025, 11, 1, 0, 0, 0);
        
        for ($i = 0; $i < $count; $i++) {
            // 11月1日〜30日のランダムな日時
            $createdAt = $november->copy()->addDays(rand(0, 29))->addHours(rand(8, 20))->addMinutes(rand(0, 59));
            $completedAt = null;
            $approvedAt = null;
            $isCompleted = false;
            $approvedByUserId = null;
            
            // 70%の確率で完了
            if (rand(1, 100) <= 70) {
                $isCompleted = true;
                $completedAt = $createdAt->copy()->addHours(rand(1, 48));
                
                // 完了タスクの80%は承認済み
                if (rand(1, 100) <= 80) {
                    $approvedAt = $completedAt->copy()->addHours(rand(1, 24));
                    $approvedByUserId = $userId; // 自己承認
                }
            }
            
            $task = Task::create([
                'title' => $this->taskTitles[array_rand($this->taskTitles)],
                'description' => $this->taskDescriptions[array_rand($this->taskDescriptions)],
                'user_id' => $userId,
                'assigned_by_user_id' => $userId, // 自己割当
                'is_completed' => $isCompleted,
                'priority' => rand(1, 5),
                'requires_approval' => rand(0, 1) === 1,
                'reward' => rand(10, 100) * 10,
                'completed_at' => $completedAt,
                'approved_at' => $approvedAt,
                'approved_by_user_id' => $approvedByUserId,
                'created_at' => $createdAt,
                'updated_at' => $completedAt ?? $createdAt,
            ]);

            // ランダムにタグを1-2個追加
            if (!$tags->isEmpty() && rand(1, 100) <= 60) {
                $task->tags()->attach($tags->random(rand(1, min(2, $tags->count())))->pluck('id'));
            }
        }
    }

    /**
     * グループタスクを生成
     */
    private function createGroupTasks(int $assignedBy, array $memberIds, int $count, $tags): void
    {
        $november = Carbon::create(2025, 11, 1, 0, 0, 0);
        
        for ($i = 0; $i < $count; $i++) {
            $groupTaskId = (string) Str::uuid();
            $createdAt = $november->copy()->addDays(rand(0, 29))->addHours(rand(8, 20))->addMinutes(rand(0, 59));
            
            // グループタスクは2-4名にランダム割当
            $assignedMemberIds = collect($memberIds)
                ->shuffle()
                ->take(rand(2, 4))
                ->values()
                ->all();
            
            foreach ($assignedMemberIds as $memberId) {
                $completedAt = null;
                $approvedAt = null;
                $isCompleted = false;
                $approvedByUserId = null;
                
                // 60%の確率で完了
                if (rand(1, 100) <= 60) {
                    $isCompleted = true;
                    $completedAt = $createdAt->copy()->addHours(rand(2, 72));
                    
                    // 完了タスクの70%は承認済み
                    if (rand(1, 100) <= 70) {
                        $approvedAt = $completedAt->copy()->addHours(rand(1, 48));
                        $approvedByUserId = $assignedBy; // グループマスターが承認
                    }
                }
                
                $task = Task::create([
                    'title' => '[グループ] ' . $this->taskTitles[array_rand($this->taskTitles)],
                    'description' => $this->taskDescriptions[array_rand($this->taskDescriptions)],
                    'user_id' => $memberId,
                    'assigned_by_user_id' => $assignedBy,
                    'group_task_id' => $groupTaskId,
                    'is_completed' => $isCompleted,
                    'priority' => rand(2, 5),
                    'requires_approval' => true,
                    'reward' => rand(20, 200) * 10,
                    'completed_at' => $completedAt,
                    'approved_at' => $approvedAt,
                    'approved_by_user_id' => $approvedByUserId,
                    'created_at' => $createdAt,
                    'updated_at' => $completedAt ?? $createdAt,
                ]);

                // グループタスクには高確率でタグを付与
                if (!$tags->isEmpty() && rand(1, 100) <= 80) {
                    $task->tags()->attach($tags->random(rand(1, min(2, $tags->count())))->pluck('id'));
                }
            }
        }
    }
}
