<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\View;

uses(DatabaseMigrations::class);

/**
 * グループタスク作成上限エラーモーダル UI/E2E テスト
 * 
 * Web版のモーダル表示とJavaScript動作のテスト
 * 
 * テスト対象:
 * - モーダルHTMLの存在確認
 * - エラーレスポンスからのモーダル表示トリガー
 * - JavaScript（GroupTaskLimitModal）の初期化
 * - サブスク管理画面へのリンク
 */

describe('グループタスク作成上限エラーモーダル - Web版UI', function () {
    
    beforeEach(function () {
        // テスト用ユーザー作成
        $this->master = User::factory()->create([
            'email' => 'master@example.com',
        ]);
        
        // グループ作成（無料プラン）
        $this->group = Group::factory()->create([
            'master_user_id' => $this->master->id,
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 0,
            'subscription_active' => false,
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
    // 1. モーダルHTMLの存在確認
    // ========================================
    
    test('ダッシュボードにグループタスク上限エラーモーダルが含まれる', function () {
        // ダッシュボードを表示
        $response = $this->actingAs($this->master)->get(route('dashboard'));
        
        $response->assertStatus(200);
        
        // モーダルDOMが存在することを確認
        $response->assertSee('id="group-task-limit-modal"', false);
        $response->assertSee('グループタスク作成上限', false);
        $response->assertSee('サブスク管理画面へ', false);
    });
    
    test('グループ編集権限がないユーザーにはモーダルが表示されない', function () {
        // 編集権限なしユーザー
        $noEditUser = User::factory()->create([
            'group_id' => $this->group->id,
            'group_edit_flg' => false,
        ]);
        
        // ダッシュボードを表示
        $response = $this->actingAs($noEditUser)->get(route('dashboard'));
        
        $response->assertStatus(200);
        
        // モーダルDOMが存在しないことを確認
        $response->assertDontSee('id="group-task-limit-modal"', false);
    });
    
    // ========================================
    // 2. モーダルコンポーネントの表示内容確認
    // ========================================
    
    test('モーダルコンポーネントに必要な要素が含まれる', function () {
        // モーダルコンポーネントを直接レンダリング
        $html = View::make('components.group-task-limit-modal')->render();
        
        // 構造確認
        expect($html)->toContain('id="group-task-limit-modal"');
        expect($html)->toContain('グループタスク作成上限');
        expect($html)->toContain('id="group-task-limit-message"');
        expect($html)->toContain('サブスクリプションで制限解除');
        expect($html)->toContain('グループタスクを無制限に作成');
        expect($html)->toContain('月額 ¥500〜');
        expect($html)->toContain('GroupTaskLimitModal.hide()');
        // route()は実際のURLに展開されるため、URLの一部をチェック
        expect($html)->toContain('href=');
        expect($html)->toContain('サブスク管理画面へ');
    });
    
    test('モーダルにJavaScript制御スクリプトが含まれる', function () {
        $html = View::make('components.group-task-limit-modal')->render();
        
        // JavaScript確認
        expect($html)->toContain('const GroupTaskLimitModal');
        expect($html)->toContain('show(message = \'\')');
        expect($html)->toContain('hide()');
        expect($html)->toContain('getElementById(\'group-task-limit-modal\')');
    });
    
    // ========================================
    // 3. エラーレスポンスとモーダル表示の統合確認
    // ========================================
    
    test('グループタスク作成上限エラーレスポンスに必要な情報が含まれる', function () {
        // グループタスクカウンターを上限に設定
        $this->group->update(['group_task_count_current_month' => 3]);
        
        // グループタスク作成リクエスト（JSON）
        $response = $this->actingAs($this->master)->postJson(route('tasks.store'), [
            'title' => 'グループタスク上限超過',
            'span' => 1,
            'is_group_task' => true,
            'assigned_user_id' => $this->normalMember->id,
            'reward' => 100,
        ]);
        
        $response->assertStatus(422);
        
        // レスポンスJSONの確認
        $json = $response->json();
        expect($json)->toHaveKey('message');
        expect($json)->toHaveKey('upgrade_required');
        expect($json)->toHaveKey('usage');
        
        // upgrade_requiredフラグの確認
        expect($json['upgrade_required'])->toBeTrue();
        
        // メッセージ確認（モーダルに表示される内容）
        expect($json['message'])->toContain('上限');
        expect($json['message'])->toContain('3件');
    });
    
    // ========================================
    // 4. サブスク管理画面へのリンク確認
    // ========================================
    
    test('モーダル内のサブスク管理画面リンクが正しい', function () {
        // モーダルコンポーネントをレンダリング
        $html = View::make('components.group-task-limit-modal')->render();
        
        // サブスク管理画面へのリンクを確認
        $subscriptionRoute = route('subscriptions.index');
        expect($html)->toContain('href="' . $subscriptionRoute . '"');
    });
    
    // ========================================
    // 5. モーダルスタイル・アニメーション確認
    // ========================================
    
    test('モーダルにトランジションCSSが含まれる', function () {
        $html = View::make('components.group-task-limit-modal')->render();
        
        // CSS確認
        expect($html)->toContain('transition: opacity');
        expect($html)->toContain('transform: translateY');
        expect($html)->toContain('modal-content');
        expect($html)->toContain('modal-overlay');
    });
    
    test('モーダルのグラデーション設定が正しい', function () {
        $html = View::make('components.group-task-limit-modal')->render();
        
        // グラデーション確認（紫→ピンク）
        expect($html)->toContain('from-purple-600');
        expect($html)->toContain('to-pink-600');
    });
    
    // ========================================
    // 6. キーボード操作（ESCキー）確認
    // ========================================
    
    test('モーダルにESCキーハンドラが含まれる', function () {
        $html = View::make('components.group-task-limit-modal')->render();
        
        // ESCキーハンドラ確認
        expect($html)->toContain('document.addEventListener(\'keydown\'');
        expect($html)->toContain('e.key === \'Escape\'');
        expect($html)->toContain('this.hide()');
    });
    
    // ========================================
    // 7. アクセシビリティ確認
    // ========================================
    
    test('モーダルにARIA属性が含まれる', function () {
        $html = View::make('components.group-task-limit-modal')->render();
        
        // ARIA属性確認
        expect($html)->toContain('role="dialog"');
        expect($html)->toContain('aria-modal="true"');
        expect($html)->toContain('aria-labelledby="group-task-limit-title"');
    });
    
    // ========================================
    // 8. ダークモード対応確認
    // ========================================
    
    test('モーダルにダークモードスタイルが含まれる', function () {
        $html = View::make('components.group-task-limit-modal')->render();
        
        // ダークモードクラス確認
        expect($html)->toContain('dark:bg-gray-800');
        expect($html)->toContain('dark:text-white');
        expect($html)->toContain('dark:text-gray-300');
    });
});
