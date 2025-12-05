<?php

use App\Models\Task;
use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;

uses(DatabaseMigrations::class);

/**
 * タスク削除機能テスト (Phase 4)
 * 
 * ⚠️ 重要な注意事項:
 * - ソフトデリート: Task::delete() によるソフトデリートを実装
 *   → assertSoftDeleted() でデリート確認
 * - Storage fake: S3画像削除テストでは Storage::fake('s3') を使用
 *   → Storage::disk('s3')->assertExists() で削除前の存在確認が可能
 * - 画像削除: TaskManagementService が S3 + TaskImage レコードを削除
 */

describe('タスク削除機能', function () {
    
    /**
     * テストの前処理
     */
    beforeEach(function () {
        // テスト用ユーザー作成
        $this->user = User::factory()->create();
        
        // 他ユーザー（権限テスト用）
        $this->otherUser = User::factory()->create();
    });
    
    // ========================================
    // 1. 正常系テスト
    // ========================================
    
    test('自分のタスクを削除できる', function () {
        // タスク作成
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => '削除テストタスク',
        ]);
        
        // 実行
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => $task->id,
        ]);
        
        // 検証
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'タスクを削除しました');
        
        // ソフトデリート確認
        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
        
        // deleted_at が設定されている
        expect(Task::withTrashed()->find($task->id)->deleted_at)->not->toBeNull();
    });
    
    test('タスク削除時に関連タグも削除される', function () {
        // タスク+タグ作成
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $task->tags()->create([
            'name' => 'テストタグ',
            'user_id' => $this->user->id,
        ]);
        
        // 削除前確認
        expect($task->tags()->count())->toBe(1);
        
        // 実行
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => $task->id,
        ]);
        
        // 検証
        $response->assertRedirect(route('dashboard'));
        
        // タグ関連が削除される（task_tagテーブル）
        $this->assertDatabaseMissing('task_tag', [
            'task_id' => $task->id,
        ]);
    });
    
    test('タスク削除時にS3の画像も削除される', function () {
        Storage::fake('s3');
        
        // 画像付きタスク作成
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // 画像ファイルを作成
        $imagePath = "tasks/{$this->user->id}/test-image.jpg";
        Storage::disk('s3')->put($imagePath, 'fake-image-content');
        
        // タスクに画像を関連付け
        $task->images()->create([
            'file_path' => $imagePath,
        ]);
        
        // 削除前確認
        Storage::disk('s3')->assertExists($imagePath);
        
        // 実行
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => $task->id,
        ]);
        
        // 検証
        $response->assertRedirect(route('dashboard'));
        
        // S3から画像が削除される
        Storage::disk('s3')->assertMissing($imagePath);
        
        // task_imagesレコードも削除される
        $this->assertDatabaseMissing('task_images', [
            'task_id' => $task->id,
        ]);
    });
    
    test('複数画像が添付されたタスクを削除すると全画像が削除される', function () {
        Storage::fake('s3');
        
        // タスク作成
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // 3つの画像を作成
        $images = [];
        for ($i = 1; $i <= 3; $i++) {
            $imagePath = "tasks/{$this->user->id}/test-image-{$i}.jpg";
            Storage::disk('s3')->put($imagePath, "fake-content-{$i}");
            
            $task->images()->create([
                'file_path' => $imagePath,
            ]);
            
            $images[] = $imagePath;
        }
        
        // 削除前確認
        foreach ($images as $imagePath) {
            Storage::disk('s3')->assertExists($imagePath);
        }
        
        // 実行
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => $task->id,
        ]);
        
        // 検証
        $response->assertRedirect(route('dashboard'));
        
        // 全画像がS3から削除される
        foreach ($images as $imagePath) {
            Storage::disk('s3')->assertMissing($imagePath);
        }
    });
    
    test('グループタスク削除時に指定したタスクのみ削除される', function () {
        // グループ作成
        $group = Group::factory()->create();
        $this->user->update(['group_id' => $group->id, 'group_edit_flg' => true]);
        
        // 同じgroup_task_idで3つのタスクを作成
        $groupTaskId = (string) \Illuminate\Support\Str::uuid();
        
        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'group_task_id' => $groupTaskId,
            'assigned_by_user_id' => $this->user->id,
        ]);
        
        $member1 = User::factory()->create(['group_id' => $group->id]);
        $task2 = Task::factory()->create([
            'user_id' => $member1->id,
            'group_task_id' => $groupTaskId,
            'assigned_by_user_id' => $this->user->id,
        ]);
        
        $member2 = User::factory()->create(['group_id' => $group->id]);
        $task3 = Task::factory()->create([
            'user_id' => $member2->id,
            'group_task_id' => $groupTaskId,
            'assigned_by_user_id' => $this->user->id,
        ]);
        
        // 実行（1つのタスクを削除）
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => $task1->id,
        ]);
        
        // 検証
        $response->assertRedirect(route('dashboard'));
        
        // 指定したタスクのみソフトデリートされる
        $this->assertSoftDeleted('tasks', ['id' => $task1->id]);
        
        // 他のグループタスクは削除されない
        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $task3->id,
            'deleted_at' => null,
        ]);
        
        // 注: group_task_id単位での一括削除機能は実装されていない
    });
    
    // ========================================
    // 2. 権限テスト
    // ========================================
    
    test('他人のタスクは削除できない（403エラー）', function () {
        // 他ユーザーのタスク作成
        $task = Task::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);
        
        // 実行
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => $task->id,
        ]);
        
        // 検証
        $response->assertStatus(403);
        
        // タスクは削除されていない
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => null,
        ]);
    });
    
    test('未認証ユーザーはタスクを削除できない（302リダイレクト）', function () {
        // タスク作成
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // 実行（未認証）
        $response = $this->delete(route('tasks.destroy'), [
            'task_id' => $task->id,
        ]);
        
        // 検証
        $response->assertStatus(302);
        
        // タスクは削除されていない
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => null,
        ]);
    });
    
    // ========================================
    // 3. 異常系テスト
    // ========================================
    
    test('存在しないタスクIDで削除するとエラー', function () {
        // 実行（存在しないID）
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => 99999,
        ]);
        
        // 検証（500エラーまたは302リダイレクト）
        expect($response->status())->toBeIn([302, 500]);
        
        // エラーメッセージ確認
        if ($response->status() === 302) {
            $response->assertSessionHas('error');
        }
    });
    
    test('既に削除済みのタスクを再削除しようとするとエラー', function () {
        // タスク作成＆削除
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $task->delete(); // ソフトデリート実行
        
        // 実行（削除済みタスクのID）
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => $task->id,
        ]);
        
        // 検証（500エラーまたは302リダイレクト）
        expect($response->status())->toBeIn([302, 500]);
    });
    
    test('task_idが未指定の場合はエラー', function () {
        // 実行（task_id未指定）
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), []);
        
        // 検証
        expect($response->status())->toBeIn([302, 500]);
    });
    
    test('task_idが不正な形式の場合はエラー', function () {
        // 実行（task_idに文字列）
        $response = $this->actingAs($this->user)->delete(route('tasks.destroy'), [
            'task_id' => 'invalid-string',
        ]);
        
        // 検証
        expect($response->status())->toBeIn([302, 500]);
    });
});
