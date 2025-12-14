<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

/**
 * グループタスク作成上限エラーUI テスト
 * 
 * サブスク未加入ユーザーが規定回数以上のグループタスクを作成しようとした際の
 * エラーレスポンスとモーダル表示のテスト
 * 
 * テスト対象:
 * - グループタスク作成上限チェック
 * - エラーレスポンス形式（upgrade_required フラグ）
 * - エラーメッセージ内容
 */

describe('グループタスク作成上限エラーUI', function () {
    
    beforeEach(function () {
        // テスト用ユーザー作成
        $this->master = User::factory()->create([
            'email' => 'master@example.com',
        ]);
        
        // グループ作成（無料プラン: 上限3件）
        $this->group = Group::factory()->create([
            'master_user_id' => $this->master->id,
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 0,
            'subscription_active' => false, // サブスク未加入
        ]);
        
        // マスターユーザーのグループ情報を設定
        $this->master->update([
            'group_id' => $this->group->id,
            'group_edit_flg' => true,
        ]);
        
        // 一般メンバー
        $this->normalMember = User::factory()->create([
            'email' => 'normal@example.com',
            'group_id' => $this->group->id,
            'group_edit_flg' => false,
        ]);
    });
    
    // ========================================
    // 1. グループタスク作成上限チェック
    // ========================================
    
    test('サブスク未加入ユーザーが上限以内のグループタスクを作成できる', function () {
        // グループタスクカウンターを2に設定（上限3）
        $this->group->update(['group_task_count_current_month' => 2]);
        
        // 実行（3件目 - 上限内）
        $response = $this->actingAs($this->master)->postJson(route('tasks.store'), [
            'title' => 'グループタスク3件目',
            'span' => 1,
            'is_group_task' => true,
            'assigned_user_id' => $this->normalMember->id,
            'reward' => 100,
            'requires_approval' => false,
        ]);
        
        // 検証
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'グループタスクが登録されました。',
        ]);
    });
    
    test('サブスク未加入ユーザーが上限に達するとエラーレスポンスを返す', function () {
        // グループタスクカウンターを上限に設定（3/3）
        $this->group->update(['group_task_count_current_month' => 3]);
        
        // 実行（4件目 - 上限超過）
        $response = $this->actingAs($this->master)->postJson(route('tasks.store'), [
            'title' => 'グループタスク4件目',
            'span' => 1,
            'is_group_task' => true,
            'assigned_user_id' => $this->normalMember->id,
            'reward' => 100,
            'requires_approval' => false,
        ]);
        
        // 検証: HTTPステータス422
        $response->assertStatus(422);
        
        // 検証: エラーレスポンス構造
        $response->assertJsonStructure([
            'message',
            'usage' => [
                'current',
                'limit',
                'remaining',
                'is_unlimited',
                'has_subscription',
                'reset_at',
            ],
            'upgrade_required',
        ]);
        
        // 検証: upgrade_requiredフラグがtrue
        expect($response->json('upgrade_required'))->toBeTrue();
        
        // 検証: エラーメッセージに上限情報が含まれる
        $message = $response->json('message');
        expect($message)->toContain('上限');
        expect($message)->toContain('3件');
        expect($message)->toContain('プレミアムプラン');
    });
    
    test('エラーレスポンスのusage情報が正しい', function () {
        // グループタスクカウンターを上限に設定（3/3）
        $this->group->update(['group_task_count_current_month' => 3]);
        
        // 実行
        $response = $this->actingAs($this->master)->postJson(route('tasks.store'), [
            'title' => 'グループタスク上限超過',
            'span' => 1,
            'is_group_task' => true,
            'assigned_user_id' => $this->normalMember->id,
            'reward' => 100,
        ]);
        
        // 検証: usage情報
        $usage = $response->json('usage');
        expect($usage['current'])->toBe(3);
        expect($usage['limit'])->toBe(3);
        expect($usage['remaining'])->toBe(0);
        expect($usage['is_unlimited'])->toBeFalse();
        expect($usage['has_subscription'])->toBeFalse();
    });
    
    // ========================================
    // 2. サブスクリプション加入ユーザーの挙動
    // ========================================
    
    test('サブスク加入ユーザーは上限を超えてもグループタスクを作成できる', function () {
        // サブスクリプション有効化
        $this->group->update([
            'subscription_active' => true,
            'group_task_count_current_month' => 100, // 上限超過しても問題なし
        ]);
        
        // 実行
        $response = $this->actingAs($this->master)->postJson(route('tasks.store'), [
            'title' => 'サブスク加入ユーザーのグループタスク',
            'span' => 1,
            'is_group_task' => true,
            'assigned_user_id' => $this->normalMember->id,
            'reward' => 100,
        ]);
        
        // 検証
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'グループタスクが登録されました。',
        ]);
    });
    
    // ========================================
    // 3. 通常タスクへの影響確認
    // ========================================
    
    test('グループタスク上限に達しても通常タスクは作成できる', function () {
        // グループタスクカウンターを上限に設定
        $this->group->update(['group_task_count_current_month' => 3]);
        
        // 実行: 通常タスク作成
        $response = $this->actingAs($this->master)->post(route('tasks.store'), [
            'title' => '通常タスク',
            'span' => 1,
            'is_group_task' => false, // 通常タスク
        ]);
        
        // 検証: 通常タスクは成功
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'タスクが登録されました。');
    });
    
    // ========================================
    // 4. エッジケース
    // ========================================
    
    test('月次カウントリセット日を過ぎた場合は上限エラーが発生しない', function () {
        // グループタスクカウンターを上限に設定 + リセット日を過去に設定
        $this->group->update([
            'group_task_count_current_month' => 3,
            'group_task_count_reset_at' => now()->subDay(), // 昨日（リセット対象）
        ]);
        
        // 実行
        $response = $this->actingAs($this->master)->postJson(route('tasks.store'), [
            'title' => 'リセット後のグループタスク',
            'span' => 1,
            'is_group_task' => true,
            'assigned_user_id' => $this->normalMember->id,
            'reward' => 100,
        ]);
        
        // 検証: リセットされるため成功
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'グループタスクが登録されました。',
        ]);
        
        // カウンターが1にリセットされていることを確認
        $this->group->refresh();
        expect($this->group->group_task_count_current_month)->toBe(1);
    });
    
    test('グループ編集権限がないユーザーは上限チェック前に403エラーになる', function () {
        // 編集権限なしユーザー
        $noEditUser = User::factory()->create([
            'group_id' => $this->group->id,
            'group_edit_flg' => false,
        ]);
        
        // 実行
        $response = $this->actingAs($noEditUser)->postJson(route('tasks.store'), [
            'title' => 'グループタスク',
            'span' => 1,
            'is_group_task' => true,
            'assigned_user_id' => $this->normalMember->id,
            'reward' => 100,
        ]);
        
        // 検証: 権限エラーが優先
        $response->assertStatus(403);
    });
    
    // ========================================
    // 5. Web版ブラウザリクエストの挙動
    // ========================================
    
    test('ブラウザリクエスト（非JSON）の場合はリダイレクトでエラー返却', function () {
        // グループタスクカウンターを上限に設定
        $this->group->update(['group_task_count_current_month' => 3]);
        
        // 実行: 通常のPOSTリクエスト（JSON以外）
        $response = $this->actingAs($this->master)->post(route('tasks.store'), [
            'title' => 'グループタスク上限超過',
            'span' => 1,
            'is_group_task' => true,
            'assigned_user_id' => $this->normalMember->id,
            'reward' => 100,
        ]);
        
        // 検証: リダイレクト + エラーメッセージ
        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
        
        $errors = session('errors')->getBag('default');
        expect($errors->has('error'))->toBeTrue();
        expect($errors->first('error'))->toContain('上限');
    });
});
