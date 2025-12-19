<?php

namespace Tests\Feature\Task;

use App\Models\User;
use App\Models\Task;

/**
 * タスク登録機能テスト (Phase 1 - 簡易版)
 * 
 * テスト対象: StoreTaskAction
 * 機能: 通常タスクの新規登録（基本機能のみ）
 */

beforeEach(function () {
    // テストユーザー作成
    $this->user = User::factory()->create([
        'email' => 'testuser@example.com',
        'username' => 'testuser',
        'name' => 'Test User',
    ]);
    $this->actingAs($this->user);
});

/**
 * 正常系: 最小限のテスト
 */
it('通常タスクを新規登録できる（必須項目のみ）', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
        'span' => 1, // 短期
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success');

    // DBに登録されていることを確認
    $this->assertDatabaseHas('tasks', [
        'title' => 'テストタスク',
        'user_id' => $this->user->id,
        'span' => 1,
    ]);

    expect(Task::count())->toBe(1);
});

it('説明文を含めてタスクを作成できる', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク2',
        'description' => 'これはテスト説明です',
        'span' => 3, // config/const.phpの定義に従い3（中期）を指定
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('tasks', [
        'title' => 'テストタスク2',
        'description' => 'これはテスト説明です',
        'user_id' => $this->user->id,
    ]);
});

/**
 * 異常系: バリデーションエラー
 */
it('タイトル未入力でバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => '',
        'span' => 1,
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('title');

    expect(Task::count())->toBe(0);
});

it('期間(span)未入力でバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('span');

    expect(Task::count())->toBe(0);
});

/**
 * 権限: 未認証ユーザー
 */
it('未認証ユーザーはタスクを作成できない', function () {
    // Arrange
    auth()->logout();

    $data = [
        'title' => 'テストタスク',
        'span' => 1,
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('login'));

    expect(Task::count())->toBe(0);
});
