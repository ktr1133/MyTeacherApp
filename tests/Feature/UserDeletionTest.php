<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Services\User\UserDeletionServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ユーザー削除機能テスト
 * 
 * - 通常ユーザー削除
 * - グループマスター削除制限
 * - グループマスター+グループ全体削除
 * - サブスクリプション解約
 */
class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected UserDeletionServiceInterface $userDeletionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userDeletionService = app(UserDeletionServiceInterface::class);
    }

    /**
     * 通常ユーザー（グループ非所属）の削除
     */
    public function test_regular_user_can_be_deleted(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
        ]);
        $userId = $user->id;

        // Act
        $this->userDeletionService->deleteUser($user);

        // Assert
        $this->assertSoftDeleted('users', ['id' => $userId]);
    }

    /**
     * グループメンバー（非マスター）の削除
     */
    public function test_group_member_can_be_deleted(): void
    {
        // Arrange
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $member = User::factory()->create(['group_id' => $group->id]);
        
        $group->master_user_id = $master->id;
        $group->save();

        $memberId = $member->id;
        $masterId = $master->id;

        // Act
        $this->userDeletionService->deleteUser($member);

        // Assert
        $this->assertSoftDeleted('users', ['id' => $memberId]);
        $this->assertDatabaseHas('users', ['id' => $masterId, 'deleted_at' => null]);
    }

    /**
     * グループマスターは通常の削除操作で例外がスローされる
     */
    public function test_group_master_cannot_be_deleted_with_regular_method(): void
    {
        // Arrange
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $group->master_user_id = $master->id;
        $group->save();

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('グループマスターは単独で削除できません');
        $this->userDeletionService->deleteUser($master);
    }

    /**
     * グループマスターステータス取得（サブスクリプションなし）
     */
    public function test_get_group_master_status_without_subscription(): void
    {
        // Arrange
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $group->master_user_id = $master->id;
        $group->save();

        User::factory()->count(2)->create(['group_id' => $group->id]);

        // Act
        $status = $this->userDeletionService->getGroupMasterStatus($master);

        // Assert
        $this->assertFalse($status['has_subscription']);
        $this->assertNull($status['plan']);
        $this->assertEquals(3, $status['members_count']); // master + 2 members
    }

    /**
     * グループマスター+グループ全体削除（サブスクリプションなし）
     */
    public function test_delete_group_master_and_group_without_subscription(): void
    {
        // Arrange
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $group->master_user_id = $master->id;
        $group->save();

        $members = User::factory()->count(2)->create(['group_id' => $group->id]);

        $masterId = $master->id;
        $groupId = $group->id;
        $memberIds = $members->pluck('id')->toArray();

        // Act
        $this->userDeletionService->deleteGroupMasterAndGroup($master);

        // Assert
        $this->assertSoftDeleted('users', ['id' => $masterId]);

        foreach ($memberIds as $memberId) {
            $this->assertSoftDeleted('users', ['id' => $memberId]);
        }

        $this->assertSoftDeleted('groups', ['id' => $groupId]);
    }

    /**
     * isGroupMaster判定テスト（マスター）
     */
    public function test_is_group_master_returns_true_for_master(): void
    {
        // Arrange
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $group->master_user_id = $master->id;
        $group->save();

        // Act & Assert
        $this->assertTrue($this->userDeletionService->isGroupMaster($master));
    }

    /**
     * isGroupMaster判定テスト（非マスター）
     */
    public function test_is_group_master_returns_false_for_member(): void
    {
        // Arrange
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $member = User::factory()->create(['group_id' => $group->id]);
        $group->master_user_id = $master->id;
        $group->save();

        // Act & Assert
        $this->assertFalse($this->userDeletionService->isGroupMaster($member));
    }

    /**
     * isGroupMaster判定テスト（グループ非所属）
     */
    public function test_is_group_master_returns_false_for_non_group_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->assertFalse($this->userDeletionService->isGroupMaster($user));
    }
}
