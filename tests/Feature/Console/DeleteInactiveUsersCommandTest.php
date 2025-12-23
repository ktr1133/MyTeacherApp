<?php

use App\Models\User;
use App\Models\Task;
use App\Models\TokenTransaction;
use App\Models\UserNotification;
use App\Models\TeacherAvatar;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DeleteInactiveUsersCommand Integration Test
 * 
 * 90日経過ユーザー削除バッチのテスト
 * 
 * 削除対象:
 * - usersテーブル（物理削除）
 * - 関連データ（tasks, token_transactions, notifications等）
 * - S3オブジェクト（avatars/, task_approvals/）
 * 
 * @see /home/ktr/mtdev/app/Console/Commands/DeleteInactiveUsersCommand.php
 * @see /home/ktr/mtdev/definitions/PrivacyPolicyAndTerms.md - 4.2 削除バッチ処理
 */

beforeEach(function () {
    // S3のfake設定
    Storage::fake('s3');
});

describe('batch:delete-inactive-users コマンド', function () {
    it('90日経過ユーザーが物理削除される', function () {
        // Arrange: 91日前に論理削除されたユーザー
        $oldUser = User::factory()->create([
            'username' => 'old_user',
            'email' => 'old@example.com',
        ]);
        $oldUserId = $oldUser->id;
        $oldUser->delete();
        $oldUser->deleted_at = now()->subDays(91);
        $oldUser->save();

        // Arrange: 89日前に論理削除されたユーザー（削除されない）
        $recentUser = User::factory()->create([
            'username' => 'recent_user',
            'email' => 'recent@example.com',
        ]);
        $recentUserId = $recentUser->id;
        $recentUser->delete();
        $recentUser->deleted_at = now()->subDays(89);
        $recentUser->save();

        // Arrange: アクティブなユーザー（削除されない）
        $activeUser = User::factory()->create([
            'username' => 'active_user',
            'email' => 'active@example.com',
        ]);
        $activeUserId = $activeUser->id;

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: 91日前のユーザーは物理削除
        expect(User::withTrashed()->find($oldUserId))->toBeNull();
        $this->assertDatabaseMissing('users', ['id' => $oldUserId]);

        // Assert: 89日前のユーザーは論理削除状態で残る
        expect(User::withTrashed()->find($recentUserId))->not->toBeNull();
        expect(User::withTrashed()->find($recentUserId)->trashed())->toBeTrue();

        // Assert: アクティブなユーザーは残る
        expect(User::find($activeUserId))->not->toBeNull();
        expect(User::find($activeUserId)->trashed())->toBeFalse();
    });

    it('関連データ（タスク）も物理削除される', function () {
        // Arrange: ユーザーとタスクを作成
        $user = User::factory()->create();
        $task1 = Task::factory()->create(['user_id' => $user->id, 'title' => 'Task 1']);
        $task2 = Task::factory()->create(['user_id' => $user->id, 'title' => 'Task 2']);

        $userId = $user->id;
        $task1Id = $task1->id;
        $task2Id = $task2->id;

        // Arrange: 91日前に論理削除
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: ユーザーが物理削除
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Assert: タスクも物理削除（カスケード削除またはバッチ内で削除）
        $this->assertDatabaseMissing('tasks', ['id' => $task1Id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task2Id]);
    });

    it('関連データ（トークン取引履歴）も物理削除される', function () {
        // Arrange: ユーザーとトークン取引を作成
        $user = User::factory()->create();
        
        $transaction1 = TokenTransaction::factory()->create([
            'user_id' => $user->id,
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $user->id,
            'type' => 'consume',
            'amount' => -10000,
            'balance_after' => 990000,
            'reason' => 'AI機能: タスク分解',
        ]);

        $transaction2 = TokenTransaction::factory()->create([
            'user_id' => $user->id,
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $user->id,
            'type' => 'purchase',
            'amount' => 50000,
            'balance_after' => 1040000,
            'reason' => 'トークン購入',
        ]);

        $userId = $user->id;
        $transaction1Id = $transaction1->id;
        $transaction2Id = $transaction2->id;

        // Arrange: 91日前に論理削除
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: ユーザーが物理削除
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Assert: トークン取引も物理削除
        $this->assertDatabaseMissing('token_transactions', ['id' => $transaction1Id]);
        $this->assertDatabaseMissing('token_transactions', ['id' => $transaction2Id]);
    });

    it('関連データ（ユーザー通知）も物理削除される', function () {
        // Arrange: ユーザーと通知を作成
        $user = User::factory()->create();
        $notification1 = UserNotification::factory()->create(['user_id' => $user->id]);
        $notification2 = UserNotification::factory()->create(['user_id' => $user->id]);

        $userId = $user->id;
        $notification1Id = $notification1->id;
        $notification2Id = $notification2->id;

        // Arrange: 91日前に論理削除
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: ユーザーが物理削除
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Assert: 通知も物理削除
        $this->assertDatabaseMissing('user_notifications', ['id' => $notification1Id]);
        $this->assertDatabaseMissing('user_notifications', ['id' => $notification2Id]);
    });

    it('S3オブジェクト（avatars）が削除される', function () {
        // Arrange: ユーザーとS3ファイルを作成
        $user = User::factory()->create();
        $userId = $user->id;

        // S3にアバター画像を作成
        Storage::disk('s3')->put("avatars/{$userId}/profile.png", 'dummy_image_content');
        Storage::disk('s3')->put("avatars/{$userId}/smile_bust.png", 'dummy_image_content');
        Storage::disk('s3')->put("avatars/{$userId}/angry_full.png", 'dummy_image_content');

        // Arrange: 91日前に論理削除
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: ユーザーが物理削除
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Assert: S3のアバターディレクトリが削除されている
        Storage::disk('s3')->assertMissing("avatars/{$userId}/profile.png");
        Storage::disk('s3')->assertMissing("avatars/{$userId}/smile_bust.png");
        Storage::disk('s3')->assertMissing("avatars/{$userId}/angry_full.png");
    });

    it('S3オブジェクト（task_approvals）が削除される', function () {
        // Arrange: ユーザーとS3ファイルを作成
        $user = User::factory()->create();
        $userId = $user->id;

        // S3にタスク承認画像を作成
        Storage::disk('s3')->put("task_approvals/{$userId}/approval_001.jpg", 'dummy_image_content');
        Storage::disk('s3')->put("task_approvals/{$userId}/approval_002.jpg", 'dummy_image_content');

        // Arrange: 91日前に論理削除
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: ユーザーが物理削除
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Assert: S3のタスク承認ディレクトリが削除されている
        Storage::disk('s3')->assertMissing("task_approvals/{$userId}/approval_001.jpg");
        Storage::disk('s3')->assertMissing("task_approvals/{$userId}/approval_002.jpg");
    });

    it('複数ユーザーを一括削除できる', function () {
        // Arrange: 複数の91日経過ユーザーを作成
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = User::factory()->create([
                'username' => "user_{$i}",
                'email' => "user{$i}@example.com",
            ]);
            $user->delete();
            $user->deleted_at = now()->subDays(91);
            $user->save();
            $users[] = $user->id;
        }

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: 全ユーザーが物理削除
        foreach ($users as $userId) {
            $this->assertDatabaseMissing('users', ['id' => $userId]);
        }
    });

    it('Dry runモードでは削除されない', function () {
        // Arrange: 91日前に論理削除されたユーザー
        $user = User::factory()->create([
            'username' => 'dry_run_user',
            'email' => 'dryrun@example.com',
        ]);
        $userId = $user->id;
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: Dry runモードでバッチ実行
        Artisan::call('batch:delete-inactive-users', ['--dry-run' => true]);

        // Assert: ユーザーは論理削除状態で残る（物理削除されない）
        expect(User::withTrashed()->find($userId))->not->toBeNull();
        expect(User::withTrashed()->find($userId)->trashed())->toBeTrue();
    });

    it('--daysオプションで削除対象日数を変更できる', function () {
        // Arrange: 31日前に論理削除されたユーザー
        $user = User::factory()->create([
            'username' => 'custom_days_user',
            'email' => 'customdays@example.com',
        ]);
        $userId = $user->id;
        $user->delete();
        $user->deleted_at = now()->subDays(31);
        $user->save();

        // Act: 30日で削除設定してバッチ実行
        Artisan::call('batch:delete-inactive-users', ['--days' => 30]);

        // Assert: ユーザーが物理削除（31日前なので削除対象）
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    });

    it('--daysオプションで削除対象外のユーザーは残る', function () {
        // Arrange: 29日前に論理削除されたユーザー
        $user = User::factory()->create([
            'username' => 'not_expired_user',
            'email' => 'notexpired@example.com',
        ]);
        $userId = $user->id;
        $user->delete();
        $user->deleted_at = now()->subDays(29);
        $user->save();

        // Act: 30日で削除設定してバッチ実行
        Artisan::call('batch:delete-inactive-users', ['--days' => 30]);

        // Assert: ユーザーは論理削除状態で残る（29日前なので削除対象外）
        expect(User::withTrashed()->find($userId))->not->toBeNull();
        expect(User::withTrashed()->find($userId)->trashed())->toBeTrue();
    });

    it('削除対象ユーザーがいない場合もエラーにならない', function () {
        // Arrange: 削除対象なし（アクティブユーザーのみ）
        User::factory()->create(['username' => 'active1', 'email' => 'active1@example.com']);
        User::factory()->create(['username' => 'active2', 'email' => 'active2@example.com']);

        // Act: バッチ実行
        $exitCode = Artisan::call('batch:delete-inactive-users');

        // Assert: 正常終了
        expect($exitCode)->toBe(0);
    });

    it('トークン残高（token_balances）も削除される', function () {
        // Arrange: ユーザーとトークン残高を作成
        $user = User::factory()->create();
        $userId = $user->id;

        // トークン残高を作成
        DB::table('token_balances')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $userId,
            'balance' => 500000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Arrange: 91日前に論理削除
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: ユーザーが物理削除
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Assert: トークン残高も削除（ポリモーフィックリレーション）
        $this->assertDatabaseMissing('token_balances', [
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $userId,
        ]);
    });

    it('アバター（teacher_avatars）も削除される', function () {
        // Arrange: ユーザーとアバターを作成
        $user = User::factory()->create();
        $userId = $user->id;

        // アバターを作成（実際のスキーマに合わせる）
        DB::table('teacher_avatars')->insert([
            'user_id' => $userId,
            'seed' => 12345,
            'sex' => 'female',
            'hair_color' => 'black',
            'eye_color' => 'brown',
            'clothing' => 'casual',
            'body_type' => 'normal',
            'tone' => 'friendly',
            'enthusiasm' => 'medium',
            'formality' => 'casual',
            'humor' => 'moderate',
            'is_transparent' => false,
            'generation_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Arrange: 91日前に論理削除
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: ユーザーが物理削除
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Assert: アバターも削除
        $this->assertDatabaseMissing('teacher_avatars', ['user_id' => $userId]);
    });

    it('複数の関連データが一括で削除される（統合テスト）', function () {
        // Arrange: ユーザーと全関連データを作成
        $user = User::factory()->create();
        $userId = $user->id;

        // タスク
        $task = Task::factory()->create(['user_id' => $userId]);
        $taskId = $task->id;

        // トークン取引
        $transaction = TokenTransaction::factory()->create([
            'user_id' => $userId,
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $userId,
            'type' => 'consume',
            'amount' => -5000,
            'balance_after' => 995000,
            'reason' => 'AI機能: タスク分解',
        ]);
        $transactionId = $transaction->id;

        // 通知
        $notification = UserNotification::factory()->create(['user_id' => $userId]);
        $notificationId = $notification->id;

        // トークン残高
        DB::table('token_balances')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $userId,
            'balance' => 1000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // アバター
        DB::table('teacher_avatars')->insert([
            'user_id' => $userId,
            'seed' => 99999,
            'sex' => 'male',
            'hair_color' => 'brown',
            'eye_color' => 'blue',
            'clothing' => 'formal',
            'body_type' => 'athletic',
            'tone' => 'serious',
            'enthusiasm' => 'high',
            'formality' => 'formal',
            'humor' => 'low',
            'is_transparent' => true,
            'generation_status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // S3ファイル
        Storage::disk('s3')->put("avatars/{$userId}/test.png", 'dummy');
        Storage::disk('s3')->put("task_approvals/{$userId}/test.jpg", 'dummy');

        // Arrange: 91日前に論理削除
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        Artisan::call('batch:delete-inactive-users');

        // Assert: ユーザーが物理削除
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Assert: 全関連データが削除
        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
        $this->assertDatabaseMissing('token_transactions', ['id' => $transactionId]);
        $this->assertDatabaseMissing('user_notifications', ['id' => $notificationId]);
        $this->assertDatabaseMissing('token_balances', [
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => $userId,
        ]);
        $this->assertDatabaseMissing('teacher_avatars', ['user_id' => $userId]);

        // Assert: S3ファイルが削除
        Storage::disk('s3')->assertMissing("avatars/{$userId}/test.png");
        Storage::disk('s3')->assertMissing("task_approvals/{$userId}/test.jpg");
    });

    it('コマンドが正常終了コードを返す', function () {
        // Arrange: 91日前に論理削除されたユーザー
        $user = User::factory()->create();
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: バッチ実行
        $exitCode = Artisan::call('batch:delete-inactive-users');

        // Assert: 正常終了（exit code 0）
        expect($exitCode)->toBe(0);
    });

    it('Dry runモードでも正常終了コードを返す', function () {
        // Arrange: 91日前に論理削除されたユーザー
        $user = User::factory()->create();
        $user->delete();
        $user->deleted_at = now()->subDays(91);
        $user->save();

        // Act: Dry runモードでバッチ実行
        $exitCode = Artisan::call('batch:delete-inactive-users', ['--dry-run' => true]);

        // Assert: 正常終了（exit code 0）
        expect($exitCode)->toBe(0);
    });
});
