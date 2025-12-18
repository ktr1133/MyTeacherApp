<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile/edit');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile/update', [
            'username' => 'testuser123',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile/edit');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile/update', [
            'username' => 'testuser456',
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile/edit');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile/delete', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertTrue($user->fresh()->trashed());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile/edit')
        ->delete('/profile/delete', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile/edit');

    $this->assertNotNull($user->fresh());
});

test('parent email is updated for children when parent changes email', function () {
    // 親ユーザー作成
    $parent = User::factory()->create([
        'username' => 'parent_user',
        'email' => 'parent@example.com',
    ]);

    // 子ユーザー作成（parent_user_id、parent_emailを設定）
    $child1 = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    $child2 = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    // 他の親を持つ子ユーザー（更新されないことを確認）
    $otherParent = User::factory()->create([
        'username' => 'other_parent',
        'email' => 'other@example.com',
    ]);
    $otherChild = User::factory()->create([
        'parent_user_id' => $otherParent->id,
        'parent_email' => 'other@example.com',
    ]);

    // 親ユーザーのメールアドレスを変更
    $response = $this
        ->actingAs($parent)
        ->patch('/profile/update', [
            'username' => 'parent_user',
            'name' => $parent->name,
            'email' => 'new-parent@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile/edit');

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

test('parent email is not updated for children when parent email unchanged', function () {
    // 親ユーザー作成
    $parent = User::factory()->create([
        'username' => 'parent_user',
        'email' => 'parent@example.com',
    ]);

    // 子ユーザー作成
    $child = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    // 親ユーザーのメールアドレスを変更せず、名前だけ変更
    $response = $this
        ->actingAs($parent)
        ->patch('/profile/update', [
            'username' => 'parent_user',
            'name' => 'Updated Name',
            'email' => 'parent@example.com', // 同じメールアドレス
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile/edit');

    // 親ユーザーの名前が更新されたことを確認
    $parent->refresh();
    $this->assertSame('Updated Name', $parent->name);

    // 子ユーザーのparent_emailは変更なし（元のまま）
    $child->refresh();
    $this->assertSame('parent@example.com', $child->parent_email);
});
