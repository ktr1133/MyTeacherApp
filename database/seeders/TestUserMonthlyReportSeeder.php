<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * testユーザーの月次レポートテスト用データ作成Seeder
 * 
 * 2025年9月、10月、11月の完了タスクデータを作成します。
 * - testユーザーのグループと3名のメンバー
 * - 各月の通常タスクとグループタスク（完了済み）
 * - タグ付きのグループタスク
 */
class TestUserMonthlyReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info("=== TestUserMonthlyReportSeeder 開始 ===\n");
            
            // 1. testユーザーを取得または作成
            $testUser = DB::table('users')->where('username', 'testuser')->first();
            
            if (!$testUser) {
                $this->command->error('testuserが存在しません。まずtestuserを作成してください。');
                return;
            }
            
            $testUserId = $testUser->id;
            
            // 2. グループの取得または作成
            $group = DB::table('groups')->where('master_user_id', $testUserId)->first();
            
            if (!$group) {
                // グループを新規作成
                $groupId = DB::table('groups')->insertGetId([
                    'name' => 'testuser家族',
                    'master_user_id' => $testUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // testユーザーをグループに所属させ、編集権限を付与
                DB::table('users')->where('id', $testUserId)->update([
                    'group_id' => $groupId,
                    'group_edit_flg' => true,  // 編集権限を付与
                    'updated_at' => now(),
                ]);
                
                $this->command->info("グループを作成しました: testuser家族 (ID: {$groupId})");
            } else {
                $groupId = $group->id;
                
                // 既存グループの場合も編集権限を確認・付与
                $currentUser = DB::table('users')->where('id', $testUserId)->first();
                if (!$currentUser->group_edit_flg) {
                    DB::table('users')->where('id', $testUserId)->update([
                        'group_edit_flg' => true,
                        'updated_at' => now(),
                    ]);
                    $this->command->info("testuserに編集権限を付与しました");
                }
                
                $this->command->info("既存のグループを使用します: {$group->name} (ID: {$groupId})");
            }
            
            // 3. メンバーを取得または作成（testuser含めて4名）
            $members = DB::table('users')->where('group_id', $groupId)->pluck('id')->toArray();
            
            if (count($members) < 4) {
                $membersToCreate = 4 - count($members);
                for ($i = 1; $i <= $membersToCreate; $i++) {
                    $memberUsername = "testmember{$i}";
                    
                    // 既存チェック
                    $existingMember = DB::table('users')->where('username', $memberUsername)->first();
                    
                    if (!$existingMember) {
                        $memberId = DB::table('users')->insertGetId([
                            'username' => $memberUsername,
                            'name' => "テストメンバー{$i}",
                            'email' => "testmember{$i}@example.com",
                            'password' => bcrypt('password'),
                            'group_id' => $groupId,
                            'group_edit_flg' => false,  // メンバーは編集権限なし
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $members[] = $memberId;
                        $this->command->info("メンバーを作成: {$memberUsername} (ID: {$memberId})");
                    } else {
                        // 既存メンバーをグループに追加（編集権限なし）
                        DB::table('users')->where('id', $existingMember->id)->update([
                            'group_id' => $groupId,
                            'group_edit_flg' => false,
                            'updated_at' => now(),
                        ]);
                        $members[] = $existingMember->id;
                        $this->command->info("既存メンバーをグループに追加: {$memberUsername}");
                    }
                }
            } else {
                $this->command->info("既存のメンバーを使用します（" . count($members) . "名）");
            }
            
            // 4. 既存のテストデータを削除（2025年9-11月のタスクのみ）
            $this->cleanupExistingData($members);
            
            // 5. タグの取得または作成（testuserが作成者）
            $tags = $this->getOrCreateTags($groupId, $testUserId);
            
            // 6. 各月のタスクデータを作成
            $this->createMonthlyTasks($groupId, $members, $tags, '2025-09');
            $this->createMonthlyTasks($groupId, $members, $tags, '2025-10');
            $this->createMonthlyTasks($groupId, $members, $tags, '2025-11');
            
            $this->command->info("\n✅ テストデータの作成が完了しました！");
            $this->command->info("グループID: {$groupId}");
            $this->command->info("メンバー数: " . count($members));
        });
    }
    
    /**
     * 既存のテストデータを削除
     * 
     * @param array $members メンバーIDの配列
     */
    protected function cleanupExistingData(array $members): void
    {
        $this->command->info("\n既存のテストデータをクリーンアップ中...");
        
        // 2025年9-11月のタスクを削除（created_atまたはapproved_atが範囲内）
        $deletedTasks = DB::table('tasks')
            ->whereIn('user_id', $members)
            ->where(function($query) {
                $query->whereBetween('created_at', ['2025-09-01', '2025-11-30 23:59:59'])
                      ->orWhereBetween('approved_at', ['2025-09-01', '2025-11-30 23:59:59'])
                      ->orWhereBetween('completed_at', ['2025-09-01', '2025-11-30 23:59:59']);
            })
            ->delete();
        
        $this->command->info("  削除したタスク: {$deletedTasks}件");
    }
    
    /**
     * タグを取得または作成
     * 
     * @param int $groupId
     * @param int $userId タグ作成者のユーザーID
     * @return array タグID配列
     */
    protected function getOrCreateTags(int $groupId, int $userId): array
    {
        $tagNames = ['掃除', '料理', '宿題', 'お手伝い', '整理整頓'];
        $tagIds = [];
        
        foreach ($tagNames as $name) {
            $tag = DB::table('tags')->where('name', $name)->where('user_id', $userId)->first();
            
            if (!$tag) {
                $tagId = DB::table('tags')->insertGetId([
                    'name' => $name,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $tagIds[] = $tagId;
            } else {
                $tagIds[] = $tag->id;
            }
        }
        
        return $tagIds;
    }
    
    /**
     * 月次タスクデータを作成
     * 
     * @param int $groupId
     * @param array $members メンバーIDの配列
     * @param array $tags タグIDの配列
     * @param string $yearMonth YYYY-MM形式
     */
    protected function createMonthlyTasks(int $groupId, array $members, array $tags, string $yearMonth): void
    {
        $this->command->info("\n{$yearMonth} のタスクデータを作成中...");
        
        $startDate = Carbon::parse("{$yearMonth}-01");
        $endDate = $startDate->copy()->endOfMonth();
        
        $normalTaskCount = 0;
        $groupTaskCount = 0;
        
        // 各メンバーに通常タスクを作成（月に15-25個）
        foreach ($members as $memberId) {
            $taskCount = rand(15, 25);
            
            for ($i = 1; $i <= $taskCount; $i++) {
                $completedAt = $startDate->copy()->addDays(rand(0, $startDate->diffInDays($endDate)));
                
                DB::table('tasks')->insert([
                    'user_id' => $memberId,
                    'title' => "通常タスク{$i}",
                    'description' => "{$yearMonth}のテスト用通常タスクです。",
                    'span' => 1, // short
                    'priority' => rand(1, 5),
                    'is_completed' => true,
                    'completed_at' => $completedAt,
                    'due_date' => $completedAt->copy()->addDay(),
                    'requires_approval' => false,
                    'requires_image' => false,
                    'created_at' => $completedAt->copy()->subDays(rand(1, 3)),
                    'updated_at' => $completedAt,
                ]);
                
                $normalTaskCount++;
            }
        }
        
        // グループタスクを作成（月に10-15グループ、各グループ3-4タスク）
        $groupCount = rand(10, 15);
        
        for ($g = 1; $g <= $groupCount; $g++) {
            $groupTaskId = (string) Str::uuid();
            $completedAt = $startDate->copy()->addDays(rand(0, $startDate->diffInDays($endDate)));
            $reward = rand(20, 200) * 10; // 200-2000トークン
            
            // このグループの担当者を選択（3-4名）
            $assignedMembers = collect($members)->random(rand(3, 4))->toArray();
            $assignedBy = $members[0]; // testユーザーが割り当て
            
            foreach ($assignedMembers as $memberId) {
                $taskId = DB::table('tasks')->insertGetId([
                    'user_id' => $memberId,
                    'title' => "グループタスク{$g}",
                    'description' => "{$yearMonth}のテスト用グループタスクです。",
                    'span' => 1, // short
                    'priority' => rand(2, 5),
                    'is_completed' => true,
                    'completed_at' => $completedAt,
                    'due_date' => $completedAt->copy()->addDay(),
                    'requires_approval' => true,
                    'requires_image' => rand(0, 1) === 1,
                    'reward' => $reward,
                    'group_task_id' => $groupTaskId,
                    'assigned_by_user_id' => $assignedBy,
                    'approved_at' => $completedAt,
                    'approved_by_user_id' => $assignedBy,
                    'created_at' => $completedAt->copy()->subDays(rand(1, 3)),
                    'updated_at' => $completedAt,
                ]);
                
                // タグを付与（80%の確率）
                if (rand(1, 100) <= 80 && !empty($tags)) {
                    $taskTags = collect($tags)->random(rand(1, min(2, count($tags))))->toArray();
                    foreach ($taskTags as $tagId) {
                        DB::table('task_tag')->insert([
                            'task_id' => $taskId,
                            'tag_id' => $tagId,
                        ]);
                    }
                }
                
                $groupTaskCount++;
            }
        }
        
        $this->command->info("  ✅ 通常タスク: {$normalTaskCount}件");
        $this->command->info("  ✅ グループタスク: {$groupTaskCount}件");
    }
}
