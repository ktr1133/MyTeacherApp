<?php

namespace Tests\Feature\Profile\Group;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * グループメンバー追加機能のテスト
 */
class AddMemberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * グループメンバー追加: 全フィールド正常（代理同意含む）
     */
    public function test_member_can_be_added_with_all_fields(): void
    {
        // グループマスター作成
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'newmember',
            'email' => 'newmember@example.com',
            'password' => 'SecureTest#9Xm2',
            'name' => 'New Member',
            'group_edit_flg' => false,
            'privacy_policy_consent' => true,
            'terms_consent' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'member-added');

        $newMember = User::where('username', 'newmember')->first();
        $this->assertNotNull($newMember);
        $this->assertEquals('newmember@example.com', $newMember->email);
        $this->assertEquals('New Member', $newMember->name);
        $this->assertEquals($group->id, $newMember->group_id);
        $this->assertFalse($newMember->group_edit_flg);

        // 代理同意が記録されていることを確認
        $this->assertEquals($master->id, $newMember->created_by_user_id);
        $this->assertEquals($master->id, $newMember->consent_given_by_user_id);
        $this->assertEquals(config('legal.current_versions.privacy_policy'), $newMember->privacy_policy_version);
        $this->assertEquals(config('legal.current_versions.terms_of_service'), $newMember->terms_version);
        $this->assertNotNull($newMember->privacy_policy_agreed_at);
        $this->assertNotNull($newMember->terms_agreed_at);
    }

    /**
     * グループメンバー追加: プライバシーポリシー同意が必須
     */
    public function test_member_requires_privacy_policy_consent(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'newmember',
            'email' => 'newmember@example.com',
            'password' => 'SecureTest#9Xm2',
            'privacy_policy_consent' => false, // 同意なし
            'terms_consent' => true,
        ]);

        $response->assertSessionHasErrors('privacy_policy_consent');
    }

    /**
     * グループメンバー追加: 利用規約同意が必須
     */
    public function test_member_requires_terms_consent(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'newmember',
            'email' => 'newmember@example.com',
            'password' => 'SecureTest#9Xm2',
            'privacy_policy_consent' => true,
            'terms_consent' => false, // 同意なし
        ]);

        $response->assertSessionHasErrors('terms_consent');
    }

    /**
     * グループメンバー追加: 両方の同意が必須
     */
    public function test_member_requires_both_consents(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'newmember',
            'email' => 'newmember@example.com',
            'password' => 'SecureTest#9Xm2',
            'privacy_policy_consent' => false,
            'terms_consent' => false,
        ]);

        $response->assertSessionHasErrors(['privacy_policy_consent', 'terms_consent']);
    }

    /**
     * グループメンバー追加: 代理同意で正しいバージョンが記録される
     */
    public function test_member_records_correct_consent_versions(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'childuser',
            'email' => 'child@example.com',
            'password' => 'SecureTest#9Xm2',
            'name' => 'Child User',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
        ]);

        $response->assertRedirect();

        $child = User::where('username', 'childuser')->first();
        
        // 代理同意の記録を確認
        $this->assertEquals($master->id, $child->created_by_user_id);
        $this->assertEquals($master->id, $child->consent_given_by_user_id);
        
        // バージョン確認
        $this->assertEquals(config('legal.current_versions.privacy_policy'), $child->privacy_policy_version);
        $this->assertEquals(config('legal.current_versions.terms_of_service'), $child->terms_version);
        
        // タイムスタンプ確認
        $this->assertNotNull($child->privacy_policy_agreed_at);
        $this->assertNotNull($child->terms_agreed_at);
        $this->assertNull($child->self_consented_at); // 代理同意なのでNULL
    }

    /**
     * グループメンバー追加: name省略時usernameを使用
     */
    public function test_member_uses_username_when_name_is_empty(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecureTest#9Xm2',
            'name' => '', // 空文字
            'group_edit_flg' => false,
            'privacy_policy_consent' => true,
            'terms_consent' => true,
        ]);

        $response->assertRedirect();

        $newMember = User::where('username', 'newuser')->first();
        $this->assertEquals('newuser', $newMember->name); // usernameが使用される
    }

    /**
     * グループメンバー追加: 編集権限付与
     */
    public function test_member_can_be_added_with_edit_permission(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'editor',
            'email' => 'editor@example.com',
            'password' => 'SecureTest#9Xm2',
            'name' => 'Editor User',
            'group_edit_flg' => true, // 編集権限あり
            'privacy_policy_consent' => true,
            'terms_consent' => true,
        ]);

        $response->assertRedirect();

        $newMember = User::where('username', 'editor')->first();
        $this->assertTrue($newMember->group_edit_flg);
    }

    /**
     * グループメンバー追加: 重複usernameでエラー
     */
    public function test_member_rejects_duplicate_username(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        
        // 既存ユーザー
        User::factory()->create(['username' => 'existing']);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'existing', // 重複
            'email' => 'new@example.com',
            'password' => 'SecureTest#9Xm2',
        ]);

        $response->assertSessionHasErrors('username');
    }

    /**
     * グループメンバー追加: 重複emailでエラー
     */
    public function test_member_rejects_duplicate_email(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        
        // 既存ユーザー
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'newuser',
            'email' => 'existing@example.com', // 重複
            'password' => 'SecureTest#9Xm2',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * グループメンバー追加: 必須フィールドが空でエラー
     */
    public function test_member_requires_username_email_password(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        // usernameが空
        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => '',
            'email' => 'test@example.com',
            'password' => 'SecureTest#9Xm2',
        ]);
        $response->assertSessionHasErrors('username');

        // emailが空
        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'testuser',
            'email' => '',
            'password' => 'SecureTest#9Xm2',
        ]);
        $response->assertSessionHasErrors('email');

        // passwordが空
        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '',
        ]);
        $response->assertSessionHasErrors('password');
    }

    /**
     * グループメンバー追加: 無効なemail形式でエラー
     */
    public function test_member_rejects_invalid_email_format(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'testuser',
            'email' => 'invalid-email', // 無効な形式
            'password' => 'SecureTest#9Xm2',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * グループメンバー追加: 無効なusername形式でエラー（記号含む）
     */
    public function test_member_rejects_invalid_username_format(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);

        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'test@user!', // 無効な文字（@, !）
            'email' => 'test@example.com',
            'password' => 'SecureTest#9Xm2',
        ]);

        $response->assertSessionHasErrors('username');
    }

    /**
     * グループメンバー追加: 権限なしでエラー
     */
    public function test_member_cannot_be_added_without_permission(): void
    {
        $group = Group::create(['name' => 'Test Group']);
        $member = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => false, // 編集権限なし
        ]);

        $response = $this->actingAs($member)->post('/profile/group/member', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecureTest#9Xm2',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
        ]);

        $response->assertForbidden(); // 403
    }

    /**
     * グループメンバー追加: max_members制限でエラー
     */
    public function test_member_cannot_be_added_when_max_members_reached(): void
    {
        // max_members=3のグループを作成
        $group = Group::create([
            'name' => 'Test Group',
            'max_members' => 3,
        ]);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        $group->update(['master_user_id' => $master->id]);

        // 既に3名（上限）のメンバーが存在
        User::factory()->count(2)->create([
            'group_id' => $group->id,
        ]);

        // 4人目を追加しようとする
        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecureTest#9Xm2',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
        ]);

        // 422エラー（制限超過）
        $response->assertStatus(422);
        
        // データベースに追加されていないことを確認
        $this->assertNull(User::where('username', 'newuser')->first());
    }

    /**
     * グループメンバー追加: サブスクリプション加入でmax_members拡張
     */
    public function test_member_can_be_added_with_subscription(): void
    {
        // サブスクリプション加入済みのグループ（max_members=20）
        $group = Group::create([
            'name' => 'Test Group',
            'max_members' => 20,
            'subscription_active' => true,
            'subscription_plan' => 'family',
        ]);
        $master = User::factory()->create([
            'group_id' => $group->id,
            'group_edit_flg' => true,
        ]);
        $group->update(['master_user_id' => $master->id]);

        // 既に6名のメンバーが存在（無料枠超過）
        User::factory()->count(5)->create([
            'group_id' => $group->id,
        ]);

        // 7人目を追加（サブスクリプションにより可能）
        $response = $this->actingAs($master)->post('/profile/group/member', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecureTest#9Xm2',
            'privacy_policy_consent' => true,
            'terms_consent' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'member-added');

        $newMember = User::where('username', 'newuser')->first();
        $this->assertNotNull($newMember);
        $this->assertEquals($group->id, $newMember->group_id);
    }
}
