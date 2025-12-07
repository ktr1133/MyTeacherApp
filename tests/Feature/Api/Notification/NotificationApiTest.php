<?php

namespace Tests\Feature\Api\Notification;

use App\Models\User;
use App\Models\UserNotification;
use App\Models\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notification API 統合テスト
 * 
 * Phase 1.E-1.5.2: 通知管理API（6 Actions）
 * 
 * テスト対象:
 * 1. IndexNotificationApiAction - 通知一覧取得
 * 2. ShowNotificationApiAction - 通知詳細取得
 * 3. MarkNotificationAsReadApiAction - 通知既読化
 * 4. MarkAllNotificationsAsReadApiAction - 全通知既読化
 * 5. GetUnreadCountApiAction - 未読件数取得
 * 6. SearchNotificationsApiAction - 通知検索
 */
class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザー作成
        $this->user = User::factory()->create([
            'cognito_sub' => 'cognito-sub-notification-test',
            'email' => 'notificationuser@test.com',
            'username' => 'notificationuser',
            'auth_provider' => 'cognito',
        ]);
    }

    /**
     * @test
     * 通知一覧を取得できること
     */
    public function test_can_get_notifications_list(): void
    {
        // Arrange
        $templates = NotificationTemplate::factory()->count(3)->create();

        foreach ($templates as $template) {
            UserNotification::factory()->create([
                'user_id' => $this->user->id,
                'notification_template_id' => $template->id,
                'is_read' => false,
            ]);
        }

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'notifications',
                    'unread_count',
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'last_page',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 3,
                ],
            ]);
    }

    /**
     * @test
     * 通知詳細を取得できること
     */
    public function test_can_get_notification_detail(): void
    {
        // Arrange
        $template = NotificationTemplate::factory()->create([
            'title' => 'テスト通知',
        ]);

        $notification = UserNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_template_id' => $template->id,
            'is_read' => false,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/notifications/{$notification->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'notification' => [
                        'id' => $notification->id,
                        'user_id' => $this->user->id,
                        'is_read' => true, // 自動既読化
                    ],
                ],
            ]);

        // 既読化されていることを確認
        $this->assertDatabaseHas('user_notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    /**
     * @test
     * 他ユーザーの通知は取得できないこと
     */
    public function test_cannot_get_other_users_notification(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $template = NotificationTemplate::factory()->create();
        
        $notification = UserNotification::factory()->create([
            'user_id' => $otherUser->id,
            'notification_template_id' => $template->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/notifications/{$notification->id}");

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                
                'message' => '通知が見つかりません。',
            ]);
    }

    /**
     * @test
     * 通知を既読化できること
     */
    public function test_can_mark_notification_as_read(): void
    {
        // Arrange
        $template = NotificationTemplate::factory()->create();
        
        $notification = UserNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_template_id' => $template->id,
            'is_read' => false,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '通知を既読にしました。',
            ]);

        $this->assertDatabaseHas('user_notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    /**
     * @test
     * 全通知を既読化できること
     */
    public function test_can_mark_all_notifications_as_read(): void
    {
        // Arrange
        $templates = NotificationTemplate::factory()->count(5)->create();
        
        foreach ($templates as $template) {
            UserNotification::factory()->create([
                'user_id' => $this->user->id,
                'notification_template_id' => $template->id,
                'is_read' => false,
            ]);
        }

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/read-all');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'marked_count' => 5,
                ],
            ]);

        $unreadCount = UserNotification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    /**
     * @test
     * 未読通知件数を取得できること
     */
    public function test_can_get_unread_count(): void
    {
        // Arrange
        $unreadTemplates = NotificationTemplate::factory()->count(3)->create();
        $readTemplates = NotificationTemplate::factory()->count(2)->create();
        
        foreach ($unreadTemplates as $template) {
            UserNotification::factory()->create([
                'user_id' => $this->user->id,
                'notification_template_id' => $template->id,
                'is_read' => false,
            ]);
        }

        foreach ($readTemplates as $template) {
            UserNotification::factory()->create([
                'user_id' => $this->user->id,
                'notification_template_id' => $template->id,
                'is_read' => true,
            ]);
        }

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread-count');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 3,
                ],
            ]);
    }

    /**
     * @test
     * AND検索で通知を検索できること
     */
    public function test_can_search_notifications_with_and_operator(): void
    {
        // Arrange
        $template1 = NotificationTemplate::factory()->create([
            'title' => 'タスク完了通知',
        ]);

        $template2 = NotificationTemplate::factory()->create([
            'title' => 'グループ招待',
        ]);

        UserNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_template_id' => $template1->id,
        ]);

        UserNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_template_id' => $template2->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/search?keywords=タスク,完了&operator=AND');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'notifications',
                    'search_params' => [
                        'terms',
                        'operator',
                    ],
                    'pagination',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'search_params' => [
                        'terms' => ['タスク', '完了'],
                        'operator' => 'AND',
                    ],
                ],
            ]);
    }

    /**
     * @test
     * OR検索で通知を検索できること
     */
    public function test_can_search_notifications_with_or_operator(): void
    {
        // Arrange
        $template1 = NotificationTemplate::factory()->create([
            'title' => 'タスク完了通知',
        ]);

        $template2 = NotificationTemplate::factory()->create([
            'title' => 'グループ招待',
        ]);

        UserNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_template_id' => $template1->id,
        ]);

        UserNotification::factory()->create([
            'user_id' => $this->user->id,
            'notification_template_id' => $template2->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/search?keywords=タスク,グループ&operator=OR');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'search_params' => [
                        'terms' => ['タスク', 'グループ'],
                        'operator' => 'OR',
                    ],
                ],
            ]);
    }

    /**
     * @test
     * 検索キーワードがない場合400エラーを返すこと
     */
    public function test_returns_400_when_search_keywords_are_empty(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/search?keywords=&operator=AND');

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                
                'message' => '検索キーワードを指定してください。',
            ]);
    }

    /**
     * @test
     * 無効な演算子の場合400エラーを返すこと
     */
    public function test_returns_400_when_operator_is_invalid(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/search?keywords=タスク&operator=INVALID');

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                
                'message' => '演算子はANDまたはORを指定してください。',
            ]);
    }
}
