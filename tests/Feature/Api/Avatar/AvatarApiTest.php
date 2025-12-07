<?php

namespace Tests\Feature\Api\Avatar;

use App\Models\User;
use App\Models\TeacherAvatar;
use App\Models\AvatarImage;
use App\Jobs\GenerateAvatarImagesJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Avatar API 統合テスト
 * 
 * Phase 1.E-1.5.2: アバター管理API（7 Actions）
 * 
 * テスト対象:
 * 1. StoreTeacherAvatarApiAction - アバター作成
 * 2. ShowTeacherAvatarApiAction - アバター情報取得
 * 3. UpdateTeacherAvatarApiAction - アバター更新
 * 4. DestroyTeacherAvatarApiAction - アバター削除
 * 5. RegenerateAvatarImageApiAction - 画像再生成
 * 6. ToggleAvatarVisibilityApiAction - 表示設定切替
 * 7. GetAvatarCommentApiAction - イベントコメント取得
 */
class AvatarApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザー作成
        $this->user = User::factory()->create([
            'cognito_sub' => 'cognito-sub-avatar-test',
            'email' => 'avataruser@test.com',
            'username' => 'avataruser',
            'auth_provider' => 'cognito',
        ]);
    }

    /**
     * @test
     * アバターを作成できること
     */
    public function test_can_create_avatar(): void
    {
        // Arrange
        Queue::fake();

        $avatarData = [
            'sex' => 'female',
            'hair_color' => 'black',
            'hair_style' => 'long',
            'eye_color' => 'brown',
            'clothing' => 'casual',
            'accessory' => 'glasses',
            'body_type' => 'average',
            'tone' => 'friendly',
            'enthusiasm' => 'high',
            'formality' => 'casual',
            'humor' => 'high',
            'draw_model_version' => 'anything-v4.0',
            'is_transparent' => true,
            'is_chibi' => false,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/avatar', $avatarData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'avatar' => [
                        'id',
                        'user_id',
                        'seed',
                        'sex',
                        'hair_color',
                        'eye_color',
                        'generation_status',
                        'is_visible',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('teacher_avatars', [
            'user_id' => $this->user->id,
            'sex' => 'female',
            'hair_color' => 'black',
            'generation_status' => 'pending',
        ]);

        Queue::assertPushed(GenerateAvatarImagesJob::class);
    }

    /**
     * @test
     * アバター情報を取得できること
     */
    public function test_can_get_avatar(): void
    {
        // Arrange
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
            'generation_status' => 'completed',
        ]);

        AvatarImage::factory()->create([
            'teacher_avatar_id' => $avatar->id,
            'image_type' => 'full_body',
            'expression_type' => 'happy',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/avatar');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'avatar' => [
                        'id' => $avatar->id,
                        'user_id' => $this->user->id,
                        'generation_status' => 'completed',
                    ],
                ],
            ]);
    }

    /**
     * @test
     * アバター未作成時は404を返すこと
     */
    public function test_returns_404_when_avatar_not_found(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/avatar');

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                
                'message' => 'アバターが見つかりません。',
            ]);
    }

    /**
     * @test
     * アバターを更新できること
     */
    public function test_can_update_avatar(): void
    {
        // Arrange
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
            'hair_color' => 'black',
            'tone' => 'friendly',
        ]);

        $updateData = [
            'hair_color' => 'brown',
            'tone' => 'intellectual',
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->putJson('/api/avatar', $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'アバター設定を更新しました。',
            ]);

        $this->assertDatabaseHas('teacher_avatars', [
            'id' => $avatar->id,
            'user_id' => $this->user->id,
            'hair_color' => 'brown',
            'tone' => 'intellectual',
        ]);
    }

    /**
     * @test
     * アバター画像を再生成できること
     */
    public function test_can_regenerate_avatar_images(): void
    {
        // Arrange
        Queue::fake();

        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
            'generation_status' => 'completed',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/avatar/regenerate');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'アバター画像の再生成を開始しました。完了には数分かかります。',
            ]);

        $this->assertDatabaseHas('teacher_avatars', [
            'id' => $avatar->id,
            'generation_status' => 'pending',
        ]);

        Queue::assertPushed(GenerateAvatarImagesJob::class);
    }

    /**
     * @test
     * アバターを削除できること
     */
    public function test_can_delete_avatar(): void
    {
        // Arrange
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/avatar');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'アバターを削除しました。',
            ]);

        $this->assertDatabaseMissing('teacher_avatars', [
            'id' => $avatar->id,
        ]);
    }

    /**
     * @test
     * アバター表示設定を切り替えできること（ON→OFF）
     */
    public function test_can_toggle_avatar_visibility_on_to_off(): void
    {
        // Arrange
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
            'is_visible' => true,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->patchJson('/api/avatar/visibility');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'アバター表示をOFFにしました。',
            ]);

        $this->assertDatabaseHas('teacher_avatars', [
            'id' => $avatar->id,
            'is_visible' => false,
        ]);
    }

    /**
     * @test
     * アバター表示設定を切り替えできること（OFF→ON）
     */
    public function test_can_toggle_avatar_visibility_off_to_on(): void
    {
        // Arrange
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
            'is_visible' => false,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->patchJson('/api/avatar/visibility');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'アバター表示をONにしました。',
            ]);

        $this->assertDatabaseHas('teacher_avatars', [
            'id' => $avatar->id,
            'is_visible' => true,
        ]);
    }

    /**
     * @test
     * イベント向けコメントを取得できること
     */
    public function test_can_get_avatar_comment_for_event(): void
    {
        // Arrange
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
            'is_visible' => true,
            'generation_status' => 'completed',
        ]);

        AvatarImage::factory()->create([
            'teacher_avatar_id' => $avatar->id,
            'image_type' => 'full_body',
            'expression_type' => 'happy',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/avatar/comment/task_created');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'comment',
                    'image_url',
                ],
            ]);
    }

    /**
     * @test
     * 無効なイベントタイプの場合400エラーを返すこと
     */
    public function test_returns_400_for_invalid_event_type(): void
    {
        // Arrange
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
            'is_visible' => true,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/avatar/comment/invalid_event');

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                
                'message' => '無効なイベントタイプです。',
            ]);
    }

    /**
     * @test
     * アバター非表示の場合は空のコメントを返すこと
     */
    public function test_returns_empty_comment_when_avatar_is_not_visible(): void
    {
        // Arrange
        $avatar = TeacherAvatar::factory()->create([
            'user_id' => $this->user->id,
            'is_visible' => false,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/avatar/comment/task_created');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'comment' => '',
                    'image_url' => null,
                ],
            ]);
    }
}
