<?php

namespace Tests\Feature\Task;

use App\Models\User;
use App\Models\Task;
use App\Models\Tag;

/**
 * タスク登録機能テスト (Phase 1)
 * 
 * テスト対象: StoreTaskAction
 * 機能: 通常タスクの新規登録
 * 
 * テストケース:
 * - 正常系: 通常タスク登録（必須項目のみ、全項目、タグ付き）
 * - 異常系: バリデーションエラー（必須項目未入力、文字数超過、不正値）
 * - 権限: 未認証ユーザーのアクセス制御
 * 
 * ⚠️ 重要な注意事項:
 * - span指定: TaskFactory はspan=1-30のランダム値を生成するため、
 *   テストデータでは必ず明示的に span=1 を指定すること
 * - due_date形式: span=1(年月日), span=2(年月), span=3(任意文字列)
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
 * ========================================
 * 2.1.1 正常系テストケース
 * ========================================
 */

it('通常タスクを新規登録できる（必須項目のみ）', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
        'span' => config('const.task_spans.short'), // 1: 短期
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success', 'タスクが登録されました。');
    $response->assertSessionHas('avatar_event', config('const.avatar_events.task_created'));

    // DBに登録されていることを確認
    $this->assertDatabaseHas('tasks', [
        'title' => 'テストタスク',
        'user_id' => $this->user->id,
        'span' => 1,
        'is_completed' => false,
        'requires_approval' => false,
        'requires_image' => false,
    ]);

    // タスクが1件だけ作成されたことを確認
    expect(Task::count())->toBe(1);
});

it('通常タスクを新規登録できる（全項目指定）', function () {
    // Arrange
    $dueDate = now()->addDays(7)->format('Y-m-d');
    $data = [
        'title' => 'テストタスク（全項目）',
        'description' => 'これはテスト説明です。詳細な内容を記載します。',
        'span' => config('const.task_spans.mid'), // 2: 中期
        'due_date' => $dueDate,
        'priority' => 4,
        'tags' => ['重要', '急ぎ'],
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success');

    // DBに登録されていることを確認
    $this->assertDatabaseHas('tasks', [
        'title' => 'テストタスク（全項目）',
        'user_id' => $this->user->id,
        'description' => 'これはテスト説明です。詳細な内容を記載します。',
        'span' => 2,
        'priority' => 4,
    ]);

    // タスクを取得してタグを確認
    $task = Task::where('title', 'テストタスク（全項目）')->first();
    expect($task)->not->toBeNull();
    expect($task->tags)->toHaveCount(2);
    expect($task->tags->pluck('name')->toArray())->toMatchArray(['重要', '急ぎ']);
});

it('既存タグを指定してタスク登録できる', function () {
    // Arrange
    $existingTag = Tag::factory()->create([
        'user_id' => $this->user->id,
        'name' => '既存タグ',
    ]);

    $data = [
        'title' => 'タグ付きタスク',
        'span' => 1,
        'tags' => ['既存タグ'], // 既存タグ名を指定
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));

    // タスクとタグの関連を確認
    $task = Task::where('title', 'タグ付きタスク')->first();
    expect($task->tags)->toHaveCount(1);
    expect($task->tags->first()->id)->toBe($existingTag->id);
    expect($task->tags->first()->name)->toBe('既存タグ');

    // タグが新規作成されていないことを確認（既存タグを再利用）
    expect(Tag::count())->toBe(1);
});

it('新規タグを指定してタスク登録できる', function () {
    // Arrange
    $data = [
        'title' => '新規タグ付きタスク',
        'span' => 2,
        'tags' => ['新規タグ1', '新規タグ2', '新規タグ3'],
    ];

    // 事前にタグが存在しないことを確認
    expect(Tag::count())->toBe(0);

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));

    // 新規タグが作成されたことを確認
    expect(Tag::count())->toBe(3);
    $this->assertDatabaseHas('tags', ['name' => '新規タグ1', 'user_id' => $this->user->id]);
    $this->assertDatabaseHas('tags', ['name' => '新規タグ2', 'user_id' => $this->user->id]);
    $this->assertDatabaseHas('tags', ['name' => '新規タグ3', 'user_id' => $this->user->id]);

    // タスクとタグの関連を確認
    $task = Task::where('title', '新規タグ付きタスク')->first();
    expect($task->tags)->toHaveCount(3);
});

it('既存タグと新規タグを混在させてタスク登録できる', function () {
    // Arrange
    Tag::factory()->create([
        'user_id' => $this->user->id,
        'name' => '既存タグ',
    ]);

    $data = [
        'title' => '混在タグタスク',
        'span' => 1,
        'tags' => ['既存タグ', '新規タグA', '新規タグB'],
    ];

    // 事前に1件のタグが存在
    expect(Tag::count())->toBe(1);

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));

    // タグが3件になっていることを確認（既存1 + 新規2）
    expect(Tag::count())->toBe(3);

    // タスクに3つのタグが関連付けられていることを確認
    $task = Task::where('title', '混在タグタスク')->first();
    expect($task->tags)->toHaveCount(3);
});

/**
 * ========================================
 * 2.1.2 異常系テストケース
 * ========================================
 */

it('タイトル未入力でバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => '', // 空文字
        'span' => 1,
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('title');
    $response->assertRedirect(); // バリデーションエラーで元のページに戻る

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

it('タイトル255文字超過でバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => str_repeat('あ', 256), // 256文字
        'span' => 1,
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('title');

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

it('期間(span)未入力でバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
        // span を指定しない
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('span');

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

it('期間(span)に不正値を指定するとバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
        'span' => 4, // 1,2,3以外の不正値
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('span');

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

it('優先度範囲外（0）でバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
        'span' => 1,
        'priority' => 0, // 1-5の範囲外
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('priority');

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

it('優先度範囲外（6）でバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
        'span' => 1,
        'priority' => 6, // 1-5の範囲外
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('priority');

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

it('タグが配列でない場合にバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
        'span' => 1,
        'tags' => 'タグ1', // 配列ではなく文字列
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('tags');

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

it('タグが50文字超過でバリデーションエラーになる', function () {
    // Arrange
    $data = [
        'title' => 'テストタスク',
        'span' => 1,
        'tags' => [str_repeat('あ', 51)], // 51文字
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertSessionHasErrors('tags.0');

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

/**
 * ========================================
 * 2.1.3 権限・認証テストケース
 * ========================================
 */

it('未認証ユーザーはタスクを作成できない', function () {
    // Arrange
    auth()->logout(); // ログアウト

    $data = [
        'title' => 'テストタスク',
        'span' => 1,
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('login')); // ログイン画面へリダイレクト

    // タスクが作成されていないことを確認
    expect(Task::count())->toBe(0);
});

/**
 * ========================================
 * 追加テスト: エッジケース
 * ========================================
 */

it('優先度未指定の場合、デフォルト値が設定される', function () {
    // Arrange
    $data = [
        'title' => 'デフォルト優先度タスク',
        'span' => 1,
        // priority を指定しない
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));

    // DBにデフォルト優先度（3）で登録されていることを確認
    $this->assertDatabaseHas('tasks', [
        'title' => 'デフォルト優先度タスク',
        'user_id' => $this->user->id,
        'priority' => 3, // デフォルト値
    ]);
});

it('説明文が空でもタスクを作成できる', function () {
    // Arrange
    $data = [
        'title' => '説明なしタスク',
        'span' => 1,
        'description' => null, // 説明文なし
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));

    // DBに登録されていることを確認
    $this->assertDatabaseHas('tasks', [
        'title' => '説明なしタスク',
        'user_id' => $this->user->id,
        'description' => null,
    ]);
});

it('期限が未来の日付でタスクを作成できる', function () {
    // Arrange
    $futureDate = now()->addDays(30)->format('Y-m-d');
    $data = [
        'title' => '未来の期限タスク',
        'span' => 3,
        'due_date' => $futureDate,
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));

    // DBに登録されていることを確認
    $task = Task::where('title', '未来の期限タスク')->first();
    expect($task)->not->toBeNull();
    expect($task->due_date)->not->toBeNull();
});

it('複数のタスクを連続して作成できる', function () {
    // Arrange & Act
    for ($i = 1; $i <= 5; $i++) {
        $response = $this->post(route('tasks.store'), [
            'title' => "連続タスク{$i}",
            'span' => 1,
        ]);
        $response->assertRedirect(route('dashboard'));
    }

    // Assert
    expect(Task::count())->toBe(5);
    
    // 各タスクが正しく作成されていることを確認
    for ($i = 1; $i <= 5; $i++) {
        $this->assertDatabaseHas('tasks', [
            'title' => "連続タスク{$i}",
            'user_id' => $this->user->id,
        ]);
    }
});

it('タグが空配列の場合、タスクをタグなしで作成できる', function () {
    // Arrange
    $data = [
        'title' => 'タグなしタスク',
        'span' => 1,
        'tags' => [], // 空配列
    ];

    // Act
    $response = $this->post(route('tasks.store'), $data);

    // Assert
    $response->assertRedirect(route('dashboard'));

    // タスクが作成され、タグがないことを確認
    $task = Task::where('title', 'タグなしタスク')->first();
    expect($task)->not->toBeNull();
    expect($task->tags)->toHaveCount(0);
});
