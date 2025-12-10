<?php

namespace Tests\Feature\Api\Task;

use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;

/**
 * モバイル版: タスク一覧取得API（IndexTaskApiAction）のテスト
 * 
 * API: GET /api/tasks (Sanctum認証)
 * 
 * テスト対象:
 * - filter=group_templates パラメータによるグループタスクフィルタリング
 * - 通常のタスク一覧取得（フィルタなし）
 */
class IndexTaskApiActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 通常のタスク一覧取得が正常に動作することをテスト
     */
    public function test_通常のタスク一覧を取得できる(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        // 通常タスク（5件）
        Task::factory()->count(5)->create([
            'user_id' => $user->id,
            'group_task_id' => null,
            'assigned_by_user_id' => null,
        ]);
        
        // グループタスク（3件）
        Task::factory()->count(3)->create([
            'user_id' => $user->id,
            'group_task_id' => (string) Str::uuid(),
            'assigned_by_user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tasks' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'due_date',
                            'priority',
                            'is_completed',
                            'group_task_id',
                            'assigned_by_user_id',
                        ],
                    ],
                    'pagination',
                ],
            ]);

        // 全8件（通常5件 + グループ3件）が返却される
        $this->assertCount(8, $response->json('data.tasks'));
    }

    /**
     * filter=group_templates でグループタスクテンプレートのみ取得できることをテスト
     */
    public function test_グループタスクテンプレートのみをフィルタリングできる(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $memberUser = User::factory()->create(); // グループメンバー
        Sanctum::actingAs($user);
        
        // ユーザーがメンバーに割り当てたグループタスクA（3件 - 同じ件名・説明・報酬）
        $groupTaskId1 = (string) Str::uuid();
        Task::factory()->count(3)->create([
            'user_id' => $memberUser->id,  // メンバーのタスク
            'group_task_id' => $groupTaskId1,
            'assigned_by_user_id' => $user->id,  // ログインユーザーが作成
            'title' => 'グループタスクA',
            'description' => '説明A',
            'reward' => 100,
        ]);
        
        // グループタスクB（2件 - 別の件名・説明・報酬）
        $groupTaskId2 = (string) Str::uuid();
        Task::factory()->count(2)->create([
            'user_id' => $memberUser->id,
            'group_task_id' => $groupTaskId2,
            'assigned_by_user_id' => $user->id,
            'title' => 'グループタスクB',
            'description' => '説明B',
            'reward' => 200,
        ]);
        
        // 通常タスク（除外対象）
        Task::factory()->count(2)->create([
            'user_id' => $user->id,
            'group_task_id' => null,
            'assigned_by_user_id' => null,
        ]);
        
        // 他人が作成したグループタスク（除外対象）
        $groupTaskId3 = (string) Str::uuid();
        Task::factory()->create([
            'user_id' => $user->id,
            'group_task_id' => $groupTaskId3,
            'assigned_by_user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/tasks?filter=group_templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tasks',
                    'pagination',
                ],
            ]);

        $data = $response->json('data.tasks');

        // 2件のみ取得される（件名・説明・報酬の組み合わせ単位で一意 - グループタスクA、B）
        $this->assertCount(2, $data);

        // すべてのタスクが条件を満たすことを検証
        $titles = collect($data)->pluck('title')->toArray();
        $this->assertContains('グループタスクA', $titles);
        $this->assertContains('グループタスクB', $titles);
        
        foreach ($data as $task) {
            $this->assertEquals($user->id, $task['assigned_by_user_id'], 'assigned_by_user_idがユーザーIDと一致すること');
            $this->assertNotNull($task['group_task_id'], 'group_task_idがnullでないこと');
        }
    }

    /**
     * filter=group_templates でグループタスクが0件の場合に空配列を返すことをテスト
     */
    public function test_グループタスクテンプレートが存在しない場合は空配列を返す(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        // 通常タスクのみ作成
        Task::factory()->count(5)->create([
            'user_id' => $user->id,
            'group_task_id' => null,
            'assigned_by_user_id' => null,
        ]);

        $response = $this->getJson('/api/tasks?filter=group_templates');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tasks' => [],
                ],
            ]);
    }

    /**
     * filter=group_templates で複数のグループタスクIDが存在する場合のテスト
     */
    public function test_複数のグループタスクIDをフィルタリングできる(): void
    {
        $user = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        Sanctum::actingAs($user);
        
        // グループタスクA（2件 - member1に割り当て、同じ件名・説明・報酬）
        $groupTaskIdA = (string) Str::uuid();
        Task::factory()->count(2)->create([
            'user_id' => $member1->id,
            'group_task_id' => $groupTaskIdA,
            'assigned_by_user_id' => $user->id,
            'title' => 'タスクA',
            'description' => '説明A',
            'reward' => 100,
        ]);
        
        // グループタスクB（3件 - member2に割り当て、同じ件名・説明・報酬）
        $groupTaskIdB = (string) Str::uuid();
        Task::factory()->count(3)->create([
            'user_id' => $member2->id,
            'group_task_id' => $groupTaskIdB,
            'assigned_by_user_id' => $user->id,
            'title' => 'タスクB',
            'description' => '説明B',
            'reward' => 200,
        ]);

        $response = $this->getJson('/api/tasks?filter=group_templates');

        $response->assertStatus(200);

        $data = $response->json('data.tasks');

        // 2件が取得される（件名・説明・報酬の組み合わせ単位で一意 - A、B）
        $this->assertCount(2, $data);

        // 各タスクが条件を満たすことを検証
        $groupTaskIds = collect($data)->pluck('group_task_id')->toArray();
        $this->assertCount(2, array_unique($groupTaskIds), 'group_task_idが一意であること');
        
        foreach ($data as $task) {
            $this->assertEquals($user->id, $task['assigned_by_user_id']);
            $this->assertNotNull($task['group_task_id']);
            $this->assertContains($task['group_task_id'], [$groupTaskIdA, $groupTaskIdB]);
        }
    }

    /**
     * 未認証の場合は401エラーを返すことをテスト
     */
    public function test_未認証の場合は401エラーを返す(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }

    /**
     * filter=group_templates で未認証の場合は401エラーを返すことをテスト
     */
    public function test_グループタスクフィルタ使用時も未認証では401エラーを返す(): void
    {
        $response = $this->getJson('/api/tasks?filter=group_templates');

        $response->assertStatus(401);
    }
}
