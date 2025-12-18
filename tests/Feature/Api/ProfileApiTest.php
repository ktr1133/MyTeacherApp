<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('api: profile information can be updated', function () {
    $user = User::factory()->create([
        'username' => 'test_user_update',
        'email' => 'old@example.com',
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/profile', [
        'username' => 'testuser123',
        'name' => 'Test User',
        'email' => 'new@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'email' => 'new@example.com',
                'name' => 'Test User',
            ],
        ]);

    $user->refresh();
    $this->assertSame('new@example.com', $user->email);
    $this->assertSame('Test User', $user->name);
});

test('api: parent email is updated for children when parent changes email', function () {
    // 親ユーザー作成
    $parent = User::factory()->create([
        'username' => 'test_parent',
        'email' => 'parent@example.com',
    ]);

    // 子ユーザー作成
    $child1 = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    $child2 = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    // 他の親を持つ子ユーザー
    $otherParent = User::factory()->create([
        'username' => 'test_other_parent',
        'email' => 'other@example.com',
    ]);
    $otherChild = User::factory()->create([
        'parent_user_id' => $otherParent->id,
        'parent_email' => 'other@example.com',
    ]);

    Sanctum::actingAs($parent);

    // 親ユーザーのメールアドレスを変更
    $response = $this->patchJson('/api/profile', [
        'username' => $parent->username,
        'email' => 'new-parent@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'email' => 'new-parent@example.com',
            ],
        ]);

    // 親ユーザーのメールアドレスが更新されたことを確認
    $parent->refresh();
    $this->assertSame('new-parent@example.com', $parent->email);

    // 子ユーザーのparent_emailが更新されたことを確認
    $child1->refresh();
    $child2->refresh();
    $this->assertSame('new-parent@example.com', $child1->parent_email);
    $this->assertSame('new-parent@example.com', $child2->parent_email);

    // 他の親を持つ子ユーザーは影響を受けないことを確認
    $otherChild->refresh();
    $this->assertSame('other@example.com', $otherChild->parent_email);
});

test('api: parent email is not updated for children when parent email unchanged', function () {
    // 親ユーザー作成
    $parent = User::factory()->create([
        'username' => 'test_parent_unchanged',
        'email' => 'parent@example.com',
        'name' => 'Original Name',
    ]);

    // 子ユーザー作成
    $child = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    Sanctum::actingAs($parent);

    // 親ユーザーのメールアドレスを変更せず、名前だけ変更
    $response = $this->patchJson('/api/profile', [
        'name' => 'Updated Name',
        'email' => 'parent@example.com', // 同じメールアドレス
    ]);

    $response->assertStatus(200);

    // 親ユーザーの名前が更新されたことを確認
    $parent->refresh();
    $this->assertSame('Updated Name', $parent->name);

    // 子ユーザーのparent_emailは変更なし
    $child->refresh();
    $this->assertSame('parent@example.com', $child->parent_email);
});

test('api: theme can be updated', function () {
    $user = User::factory()->create([
        'username' => 'test_user_theme',
        'theme' => 'adult',
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/profile', [
        'theme' => 'child',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'theme' => 'child',
            ],
        ]);

    $user->refresh();
    $this->assertSame('child', $user->theme);
});
