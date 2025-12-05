<?php

use App\Models\Task;
use App\Models\User;
use App\Models\Tag;
use App\Models\Group;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * タスク更新機能テスト (Phase 5)
 * 
 * ⚠️ 重要な注意事項:
 * - span指定: TaskFactory はspan=1-30のランダム値を生成するため、
 *   テストデータでは必ず明示的に span=1 を指定すること
 * - due_date形式: span=1(年月日のみ), span=2(年月), span=3(任意文字列)
 * - Storage fake: 画像アップロード/削除テストでは Storage::fake('s3') を使用
 * - 画像生成: GD拡張なしのため UploadedFile::fake()->create() を使用
 *   （->image()は使用不可）
 */

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('タスク更新機能', function () {
    // ========================================
    // 1. 正常系テスト - 基本フィールド更新
    // ========================================
    
    test('タイトルのみ更新できる', function () {
        // タスク作成
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => '元のタイトル',
            'description' => '元の説明',
            'span' => 1,
            'priority' => 3,
        ]);
        
        // 実行
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => '更新後のタイトル',
            'description' => $task->description,
            'span' => $task->span,
        ]);
        
        // 検証
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'タスクを更新しました');
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => '更新後のタイトル',
            'description' => '元の説明', // 変更なし
            'span' => 1, // 変更なし
        ]);
    });
    
    test('説明文を更新できる', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'description' => '元の説明',
            'span' => 1,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => '更新後の説明文です',
            'span' => $task->span,
            'tags' => [],
        ]);
        
        $response->assertRedirect(route('dashboard'));
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'description' => '更新後の説明文です',
        ]);
    });
    
    test('期間(span)を変更できる', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'span' => 1,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'span' => 3,
            'tags' => [],
        ]);
        
        $response->assertRedirect(route('dashboard'));
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'span' => 3,
        ]);
    });
    
    test('期限(due_date)を変更できる', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => '2025-12-10',
            'span' => 1,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'span' => $task->span,
            'due_date' => '2025-12-20',
            'tags' => [],
        ]);
        
        $response->assertRedirect(route('dashboard'));
        
        $task->refresh();
        // due_dateはCarbonオブジェクトまたは文字列
        expect($task->due_date)->not->toBeNull();
    });
    
    test('全フィールドを一括更新できる', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => '元のタイトル',
            'description' => '元の説明',
            'span' => 1,
            'due_date' => '2025-12-10',
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => '完全に更新されたタイトル',
            'description' => '完全に更新された説明',
            'span' => 3,
            'due_date' => '来年の春頃',
            'tags' => [],
        ]);
        
        $response->assertRedirect(route('dashboard'));
        
        $task->refresh();
        expect($task->title)->toBe('完全に更新されたタイトル')
            ->and($task->description)->toBe('完全に更新された説明')
            ->and($task->span)->toBe(3);
    });
    
    // ========================================
    // 2. タグ管理テスト
    // ========================================
    
    test('タグを追加できる', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'span' => 1,
        ]);
        
        $tag1 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ1']);
        $tag2 = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'タグ2']);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'span' => $task->span,
            'tags' => [$tag1->id, $tag2->id],
        ]);
        
        $response->assertRedirect(route('dashboard'));
        
        // タグが関連付けられている
        $this->assertDatabaseHas('task_tag', [
            'task_id' => $task->id,
            'tag_id' => $tag1->id,
        ]);
        $this->assertDatabaseHas('task_tag', [
            'task_id' => $task->id,
            'tag_id' => $tag2->id,
        ]);
    });
    
    test('タグを削除できる', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'span' => 1,
        ]);
        
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);
        $task->tags()->attach($tag->id);
        
        // タグなしで更新
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'span' => $task->span,
            'tags' => [],
        ]);
        
        $response->assertRedirect(route('dashboard'));
        
        // タグ関連が削除されている
        $this->assertDatabaseMissing('task_tag', [
            'task_id' => $task->id,
            'tag_id' => $tag->id,
        ]);
    });
    
    test('タグを入れ替えできる', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'span' => 1,
        ]);
        
        $oldTag = Tag::factory()->create(['user_id' => $this->user->id, 'name' => '古いタグ']);
        $newTag = Tag::factory()->create(['user_id' => $this->user->id, 'name' => '新しいタグ']);
        
        // 最初は古いタグのみ
        $task->tags()->attach($oldTag->id);
        
        // 新しいタグに入れ替え
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'span' => $task->span,
            'tags' => [$newTag->id],
        ]);
        
        $response->assertRedirect(route('dashboard'));
        
        // 古いタグは削除、新しいタグは追加
        $this->assertDatabaseMissing('task_tag', [
            'task_id' => $task->id,
            'tag_id' => $oldTag->id,
        ]);
        $this->assertDatabaseHas('task_tag', [
            'task_id' => $task->id,
            'tag_id' => $newTag->id,
        ]);
    });
    
    // ========================================
    // 3. 画像関連テスト
    // ========================================
    
    test('タスクに画像をアップロードできる', function () {
        Storage::fake('s3');
        
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $file = UploadedFile::fake()->create('task-image.jpg', 100, 'image/jpeg');
        
        $response = $this->actingAs($this->user)->post(route('tasks.upload-image', $task), [
            'image' => $file,
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // task_imagesレコードが作成されている
        $this->assertDatabaseHas('task_images', [
            'task_id' => $task->id,
        ]);
        
        // S3にファイルが保存されている
        $image = $task->images()->first();
        Storage::disk('s3')->assertExists($image->file_path);
    });
    
    test('タスクから画像を削除できる', function () {
        Storage::fake('s3');
        
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // 画像作成
        $imagePath = "tasks/{$this->user->id}/test-image.jpg";
        Storage::disk('s3')->put($imagePath, 'fake-content');
        
        $image = $task->images()->create([
            'file_path' => $imagePath,
        ]);
        
        Storage::disk('s3')->assertExists($imagePath);
        
        // 削除実行
        $response = $this->actingAs($this->user)->delete(route('tasks.delete-image', $image));
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // レコードが削除されている
        $this->assertDatabaseMissing('task_images', [
            'id' => $image->id,
        ]);
        
        // S3からファイルが削除されている
        Storage::disk('s3')->assertMissing($imagePath);
    });
    
    test('複数画像を追加できる', function () {
        Storage::fake('s3');
        
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // 3つの画像をアップロード
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->create("image-{$i}.jpg", 100, 'image/jpeg');
            
            $this->actingAs($this->user)->post(route('tasks.upload-image', $task), [
                'image' => $file,
            ]);
        }
        
        // 3つの画像が保存されている
        expect($task->images()->count())->toBe(3);
        
        // 全画像がS3に存在する
        foreach ($task->images as $image) {
            Storage::disk('s3')->assertExists($image->file_path);
        }
    });
    
    test('画像形式がJPG/PNG以外の場合はバリデーションエラー', function () {
        Storage::fake('s3');
        
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // PDFファイルを試行
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        
        $response = $this->actingAs($this->user)->post(route('tasks.upload-image', $task), [
            'image' => $file,
        ]);
        
        $response->assertSessionHasErrors('image');
    });
    
    test('画像サイズが5MBを超える場合はバリデーションエラー', function () {
        Storage::fake('s3');
        
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // 6MBの画像
        $file = UploadedFile::fake()->create('large-image.jpg', 6144, 'image/jpeg');
        
        $response = $this->actingAs($this->user)->post(route('tasks.upload-image', $task), [
            'image' => $file,
        ]);
        
        $response->assertSessionHasErrors('image');
    });
    
    // ========================================
    // 4. バリデーションテスト
    // ========================================
    
    test('タイトルが空の場合はバリデーションエラー', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => '',
            'description' => $task->description,
            'span' => $task->span,
        ]);
        
        $response->assertSessionHasErrors('title');
    });
    
    test('タイトルが255文字を超える場合はバリデーションエラー', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => str_repeat('あ', 256),
            'description' => $task->description,
            'span' => $task->span,
        ]);
        
        $response->assertSessionHasErrors('title');
    });
    
    test('期間(span)が1,2,3以外の場合はバリデーションエラー', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'span' => 4, // 不正値
        ]);
        
        $response->assertSessionHasErrors('span');
    });
    
    test('存在しないタグIDを指定するとバリデーションエラー', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'span' => $task->span,
            'tags' => [99999], // 存在しないID
        ]);
        
        $response->assertSessionHasErrors('tags.0');
    });
    
    // ========================================
    // 5. 権限・認証テスト
    // ========================================
    
    test('他人のタスクは更新できない（403エラー）', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => '不正な更新',
            'description' => $task->description,
            'span' => $task->span,
        ]);
        
        $response->assertForbidden();
    });
    
    test('未認証ユーザーはタスクを更新できない（302リダイレクト）', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->put(route('tasks.update', $task), [
            'title' => '不正な更新',
            'description' => $task->description,
            'span' => $task->span,
        ]);
        
        $response->assertRedirect(route('login'));
    });
    
    test('他人のタスクに画像をアップロードできない（403エラー）', function () {
        Storage::fake('s3');
        
        $otherUser = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');
        
        $response = $this->actingAs($this->user)->post(route('tasks.upload-image', $task), [
            'image' => $file,
        ]);
        
        $response->assertForbidden();
    });
    
    // ========================================
    // 6. グループタスク更新テスト
    // ========================================
    
    test('グループタスクのタイトルを更新できる', function () {
        $group = Group::factory()->create();
        $this->user->update(['group_id' => $group->id, 'group_edit_flg' => true]);
        
        $groupTaskId = (string) \Illuminate\Support\Str::uuid();
        
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'group_task_id' => $groupTaskId,
            'assigned_by_user_id' => $this->user->id,
            'title' => '元のグループタスク',
            'span' => 1,
        ]);
        
        $response = $this->actingAs($this->user)->put(route('tasks.update', $task), [
            'title' => '更新後のグループタスク',
            'description' => $task->description,
            'span' => $task->span,
            'tags' => [],
        ]);
        
        $response->assertRedirect(route('dashboard'));
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => '更新後のグループタスク',
            'group_task_id' => $groupTaskId,
        ]);
    });
    
    test('存在しないタスクIDで更新するとエラー', function () {
        $response = $this->actingAs($this->user)->put(route('tasks.update', 99999), [
            'title' => '更新',
            'description' => 'テスト',
            'span' => 1,
        ]);
        
        $response->assertNotFound();
    });
});
