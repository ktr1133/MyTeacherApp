<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Task;
use App\Models\Tag;
use App\Models\TaskImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Task API 統合テスト
 * 
 * Phase 1: 13個のAPI Action全体のテスト
 * 
 * テスト対象:
 * 1. StoreTaskApiAction - タスク作成
 * 2. IndexTaskApiAction - タスク一覧取得
 * 3. UpdateTaskApiAction - タスク更新
 * 4. DestroyTaskApiAction - タスク削除
 * 5. ToggleTaskCompletionApiAction - 完了トグル
 * 6. ApproveTaskApiAction - タスク承認
 * 7. RejectTaskApiAction - タスク却下
 * 8. UploadTaskImageApiAction - 画像アップロード
 * 9. DeleteTaskImageApiAction - 画像削除
 * 10. BulkCompleteTasksApiAction - 一括完了
 * 11. RequestApprovalApiAction - 完了申請
 * 12. ListPendingApprovalsApiAction - 承認待ち一覧
 * 13. SearchTasksApiAction - タスク検索
 */
class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザー作成
        $this->user = User::factory()->create([
            'cognito_sub' => 'cognito-sub-task-test',
            'email' => 'taskuser@test.com',
            'username' => 'taskuser',
            'auth_provider' => 'cognito',
        ]);

        $this->approver = User::factory()->create([
            'cognito_sub' => 'cognito-sub-approver',
            'email' => 'approver@test.com',
            'username' => 'approver',
            'auth_provider' => 'cognito',
        ]);

        // S3ディスクのフェイク設定
        Storage::fake('s3');
    }

    /**
     * @test
     * タスクを作成できること
     */
    public function test_can_create_task(): void
    {
        // Arrange
        $taskData = [
            'title' => 'API テストタスク',
            'description' => 'これはAPIテストです',
            'span' => 3, // config/const.phpの定義に従い3（中期）を指定
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'priority' => 1,
            'reward' => 100,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/tasks', $taskData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'task' => [
                        'id',
                        'title',
                        'description',
                        'span',
                        'due_date',
                        'priority',
                        'reward',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'API テストタスク',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * タスク一覧を取得できること
     */
    public function test_can_retrieve_task_list(): void
    {
        // Arrange
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tasks',
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                    ],
                ],
            ]);
    }

    /**
     * @test
     * デフォルトで未完了タスクのみ取得されること（Web版と動作統一）
     */
    public function test_retrieves_only_pending_tasks_by_default(): void
    {
        // Arrange: 未完了タスク3件、完了タスク2件
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);
        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_completed' => true,
        ]);

        // Act: statusパラメータなしでリクエスト
        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks');

        // Assert: 未完了3件のみ返却される
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(3, $data['tasks']);
        foreach ($data['tasks'] as $task) {
            $this->assertFalse($task['is_completed'], 'デフォルトでは未完了タスクのみ返却される');
        }
    }

    /**
     * @test
     * status=completedで完了タスクのみ取得できること
     */
    public function test_can_retrieve_completed_tasks_with_status_filter(): void
    {
        // Arrange
        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_completed' => true,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?status=completed');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(3, $data['tasks']);
        foreach ($data['tasks'] as $task) {
            $this->assertTrue($task['is_completed'], 'status=completedで完了タスクのみ返却される');
        }
    }

    /**
     * @test
     * status=allで全タスク取得できること
     */
    public function test_can_retrieve_all_tasks_with_status_all(): void
    {
        // Arrange
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);
        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_completed' => true,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?status=all');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(5, $data['tasks'], 'status=allで全タスク（未完了+完了）が返却される');
    }

    /**
     * @test
     * タスクを更新できること
     */
    public function test_can_update_task(): void
    {
        // Arrange
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $updateData = [
            'title' => '更新されたタスク',
            'description' => '更新されました',
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", $updateData);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => '更新されたタスク',
        ]);
    }

    /**
     * @test
     * タスクを削除できること
     */
    public function test_can_delete_task(): void
    {
        // Arrange
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    /**
     * @test
     * タスクの完了状態をトグルできること
     */
    public function test_can_toggle_task_completion_status(): void
    {
        // Arrange
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/toggle");

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'is_completed' => true,
        ]);
    }

    /**
     * @test
     * タスクを一括完了できること
     */
    public function test_can_bulk_complete_tasks(): void
    {
        // Arrange
        $tasks = Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);

        $taskIds = $tasks->pluck('id')->toArray();

        // Act
        $response = $this->actingAs($this->user)
            ->patchJson('/api/tasks/bulk-complete', [
                'task_ids' => $taskIds,
                'is_completed' => true,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'updated_count' => 3,
                    'is_completed' => true,
                ],
            ]);

        foreach ($taskIds as $taskId) {
            $this->assertDatabaseHas('tasks', [
                'id' => $taskId,
                'is_completed' => true,
            ]);
        }
    }

    /**
     * @test
     * タスクを承認できること
     */
    public function test_can_approve_task(): void
    {
        // Arrange
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'assigned_by_user_id' => $this->approver->id,
            'requires_approval' => true,
        ]);

        // Act
        $response = $this->actingAs($this->approver)
            ->postJson("/api/tasks/{$task->id}/approve");

        // Assert
        $response->assertStatus(200);
        
        // データベースから実際の値を取得して確認（タイミング問題を回避）
        $task->refresh();
        expect($task->approved_at)->not->toBeNull();
        expect($task->approved_at->diffInSeconds(now()))->toBeLessThan(2);
    }

    /**
     * @test
     * タスクを却下できること
     */
    public function test_can_reject_task(): void
    {
        // Arrange
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'assigned_by_user_id' => $this->approver->id,
            'requires_approval' => true,
            'is_completed' => true,  // 完了状態にしてから却下
            'completed_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($this->approver)
            ->postJson("/api/tasks/{$task->id}/reject", [
                'reason' => 'テスト却下理由',
            ]);

        // Assert
        $response->assertStatus(200);
        // Rejectedの状態はDBに記録されない（approved_atがnullのまま）
    }

    /**
     * @test
     * タスク画像をアップロードできること
     */
    public function test_can_upload_task_image(): void
    {
        // S3ストレージをフェイク
        Storage::fake('s3');
        
        // VirusScanServiceをモック
        $virusScanMock = \Mockery::mock(\App\Services\Security\VirusScanServiceInterface::class);
        $virusScanMock->shouldReceive('isAvailable')->andReturn(false);
        $this->app->instance(\App\Services\Security\VirusScanServiceInterface::class, $virusScanMock);

        // Arrange
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        // GD拡張がない環境でも動作するように通常のファイルを使用
        $image = UploadedFile::fake()->create('test-image.jpg', 100, 'image/jpeg');

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/images", [
                'image' => $image,
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'image' => [
                        'id',
                        'task_id',
                        'file_path',
                        'url',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('task_images', [
            'task_id' => $task->id,
        ]);
        
        // S3にファイルがアップロードされたことを確認
        Storage::disk('s3')->assertExists($response->json('data.image.file_path'));
    }

    /**
     * @test
     * タスク画像を削除できること
     */
    public function test_can_delete_task_image(): void
    {
        // Arrange
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $taskImage = TaskImage::factory()->create([
            'task_id' => $task->id,
            'file_path' => 'task_approvals/test-image.jpg',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/images/{$taskImage->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('task_images', ['id' => $taskImage->id]);
    }

    /**
     * @test
     * タスク完了申請ができること
     */
    public function test_can_request_task_completion_approval(): void
    {
        // Arrange
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'requires_approval' => true,
            'requires_image' => false, // 画像不要に設定
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/request-approval");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * @test
     * 承認待ち一覧を取得できること
     */
    public function test_can_retrieve_pending_approval_list(): void
    {
        // Arrange
        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'assigned_by_user_id' => $this->approver->id,
            'requires_approval' => true,
        ]);

        // Act
        $response = $this->actingAs($this->approver)
            ->getJson('/api/tasks/approvals/pending');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'approvals',
                    'pagination',
                ],
            ]);
    }

    /**
     * @test
     * タスク検索ができること
     */
    public function test_can_search_tasks(): void
    {
        // Arrange
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => '検索対象タスク1',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => '検索対象タスク2',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => '別のタスク',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/tasks/search', [
                'type' => 'title',
                'operator' => 'or',
                'terms' => ['検索対象'],
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tasks',
                    'search_params',
                ],
            ]);
    }

    /**
     * @test
     * 他人のタスクを更新できないこと
     */
    public function test_cannot_update_other_users_task(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        // Act
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => '不正な更新',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * 他人のタスクを削除できないこと
     */
    public function test_cannot_delete_other_users_task(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}");

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @test
     * 認証なしでAPIアクセスできないこと
     */
    public function test_cannot_access_api_without_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/tasks');

        // Assert
        $response->assertStatus(401);
    }
}
