<?php

namespace Tests\Feature\Task;

use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 無限スクロール（ページネーション）機能のテスト
 * 
 * 注: /api/tasks/paginated エンドポイントが未実装のため全テストスキップ
 */
class InfiniteScrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('ページネーションAPI (/api/tasks/paginated) が未実装');
    }

    /**
     * ページネーションAPIが正常に動作することをテスト
     */
    public function test_ページネーションAPIが正常に動作する(): void
    {
        $user = User::factory()->create();
        
        // 100件のタスクを作成
        Task::factory()->count(100)->create([
            'user_id' => $user->id,
            'is_completed' => false,
            'span' => 1,
        ]);

        // 1ページ目取得（50件）
        $response = $this->actingAs($user)->getJson('/api/tasks/paginated?page=1&per_page=50');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tasks',
                    'pagination' => [
                        'current_page',
                        'next_page',
                        'has_more',
                        'per_page',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'pagination' => [
                        'current_page' => 1,
                        'next_page' => 2,
                        'has_more' => true,
                        'per_page' => 50,
                    ],
                ],
            ]);

        $this->assertCount(50, $response->json('data.tasks'));
    }

    /**
     * 2ページ目が正常に取得できることをテスト
     */
    public function test_2ページ目が正常に取得できる(): void
    {
        $user = User::factory()->create();
        
        // 100件のタスクを作成
        Task::factory()->count(100)->create([
            'user_id' => $user->id,
            'is_completed' => false,
            'span' => 1,
        ]);

        // 2ページ目取得
        $response = $this->actingAs($user)->getJson('/api/tasks/paginated?page=2&per_page=50');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pagination' => [
                        'current_page' => 2,
                        'next_page' => 3,
                        'has_more' => false, // 100件なので3ページ目はない
                        'per_page' => 50,
                    ],
                ],
            ]);

        $this->assertCount(50, $response->json('data.tasks'));
    }

    /**
     * データが存在しない場合の動作テスト
     */
    public function test_データが存在しない場合に空配列を返す(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/tasks/paginated?page=1&per_page=50');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tasks' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'next_page' => 2,
                        'has_more' => false,
                        'per_page' => 50,
                    ],
                ],
            ]);
    }

    /**
     * per_pageパラメータのバリデーションテスト
     */
    public function test_per_pageが範囲外の場合にエラーを返す(): void
    {
        $user = User::factory()->create();

        // 101件は範囲外
        $response = $this->actingAs($user)->getJson('/api/tasks/paginated?page=1&per_page=101');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => '1ページあたりの件数は1〜100の範囲で指定してください。',
            ]);
    }

    /**
     * ページ番号のバリデーションテスト
     */
    public function test_ページ番号が0以下の場合にエラーを返す(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/tasks/paginated?page=0&per_page=50');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'ページ番号は1以上を指定してください。',
            ]);
    }

    /**
     * 未認証ユーザーがアクセスできないことをテスト
     */
    public function test_未認証ユーザーはアクセスできない(): void
    {
        $response = $this->getJson('/api/tasks/paginated?page=1&per_page=50');

        $response->assertStatus(401);
    }

    /**
     * フィルター付きでページネーションが動作することをテスト
     */
    public function test_フィルター付きでページネーションが動作する(): void
    {
        $user = User::factory()->create();
        
        // 優先度1のタスクを30件、優先度3のタスクを30件作成
        Task::factory()->count(30)->create([
            'user_id' => $user->id,
            'is_completed' => false,
            'priority' => 1,
            'span' => 1,
        ]);
        
        Task::factory()->count(30)->create([
            'user_id' => $user->id,
            'is_completed' => false,
            'priority' => 3,
            'span' => 1,
        ]);

        // 優先度1でフィルタリング
        $response = $this->actingAs($user)->getJson('/api/tasks/paginated?page=1&per_page=20&priority=1');

        $response->assertStatus(200);
        
        $tasks = $response->json('data.tasks');
        $this->assertCount(20, $tasks);
        
        // 全て優先度1であることを確認
        foreach ($tasks as $task) {
            $this->assertEquals(1, $task['priority']);
        }
    }

    /**
     * 完了済みタスクは除外されることをテスト
     */
    public function test_完了済みタスクは除外される(): void
    {
        $user = User::factory()->create();
        
        // 未完了タスク50件
        Task::factory()->count(50)->create([
            'user_id' => $user->id,
            'is_completed' => false,
            'span' => 1,
        ]);
        
        // 完了済みタスク50件
        Task::factory()->count(50)->create([
            'user_id' => $user->id,
            'is_completed' => true,
            'span' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/tasks/paginated?page=1&per_page=50');

        $response->assertStatus(200);
        
        // 未完了タスクのみ50件取得
        $tasks = $response->json('data.tasks');
        $this->assertCount(50, $tasks);
        
        // 全て未完了であることを確認
        foreach ($tasks as $task) {
            $this->assertFalse($task['is_completed']);
        }
    }

    /**
     * タスクデータ構造の確認
     */
    public function test_タスクデータ構造が正しい(): void
    {
        $user = User::factory()->create();
        
        Task::factory()->create([
            'user_id' => $user->id,
            'is_completed' => false,
            'span' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/tasks/paginated?page=1&per_page=50');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'tasks' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'due_date',
                            'span',
                            'priority',
                            'is_completed',
                            'completed_at',
                            'group_task_id',
                            'reward',
                            'requires_approval',
                            'requires_image',
                            'approved_at',
                            'created_at',
                            'updated_at',
                            'tags',
                            'images',
                        ],
                    ],
                ],
            ]);
    }
}
