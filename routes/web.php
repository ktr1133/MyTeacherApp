<?php

// 管理者用
use App\Http\Actions\Admin\Notification\IndexAdminNotificationAction;
use App\Http\Actions\Admin\Notification\CreateAdminNotificationAction;
use App\Http\Actions\Admin\Notification\StoreAdminNotificationAction;
use App\Http\Actions\Admin\Notification\EditAdminNotificationAction;
use App\Http\Actions\Admin\Notification\UpdateAdminNotificationAction;
use App\Http\Actions\Admin\Notification\DeleteAdminNotificationAction;
use App\Http\Actions\Admin\IndexUserAction;
use App\Http\Actions\Admin\EditUserAction;
use App\Http\Actions\Admin\UpdateUserAction;
use App\Http\Actions\Admin\DeleteUserAction;
use App\Http\Actions\Admin\Payment\IndexPaymentHistoryAction;
use App\Http\Actions\Admin\Token\IndexTokenStatsAction;
use App\Http\Actions\Admin\Token\IndexTokenUsersAction;
use App\Http\Actions\Admin\Token\IndexTokenPackageAction;
use App\Http\Actions\Admin\Token\CreateTokenPackageAction;
use App\Http\Actions\Admin\Token\StoreTokenPackageAction;
use App\Http\Actions\Admin\Token\EditTokenPackageAction;
use App\Http\Actions\Admin\Token\UpdateTokenPackageAction;
use App\Http\Actions\Admin\Token\DeleteTokenPackageAction;
use App\Http\Actions\Admin\Token\UpdateFreeTokenAmountAction;
// 認証用
use App\Http\Actions\Auth\ValidateUsernameAction;
use App\Http\Actions\Auth\ValidatePasswordAction;
use App\Http\Actions\Avatar\CreateTeacherAvatarAction;
use App\Http\Actions\Avatar\EditTeacherAvatarAction;
use App\Http\Actions\Avatar\RegenerateAvatarImageAction;
use App\Http\Actions\Avatar\StoreTeacherAvatarAction;
use App\Http\Actions\Avatar\UpdateTeacherAvatarAction;
use App\Http\Actions\Avatar\GetAvatarCommentAction;
use App\Http\Actions\Avatar\ToggleAvatarVisibilityAction;
use App\Http\Actions\Batch\IndexScheduledTaskAction;
use App\Http\Actions\Batch\CreateScheduledTaskAction;
use App\Http\Actions\Batch\StoreScheduledTaskAction;
use App\Http\Actions\Batch\EditScheduledTaskAction;
use App\Http\Actions\Batch\UpdateScheduledTaskAction;
use App\Http\Actions\Batch\DeleteScheduledTaskAction;
use App\Http\Actions\Batch\PauseScheduledTaskAction;
use App\Http\Actions\Batch\ResumeScheduledTaskAction;
use App\Http\Actions\Batch\ShowExecutionHistoryAction;
use App\Http\Actions\Notification\GetUnreadCountAction;
use App\Http\Actions\Notification\IndexNotificationAction;
use App\Http\Actions\Notification\MarkNotificationAsReadAction;
use App\Http\Actions\Notification\MarkAllNotificationsAsReadAction;
use App\Http\Actions\Notification\ShowNotificationAction;
use App\Http\Actions\Notification\SearchNotificationsAction;
use App\Http\Actions\Notification\SearchResultsNotificationAction;
use App\Http\Actions\Profile\EditProfileAction;
use App\Http\Actions\Profile\UpdateProfileAction;
use App\Http\Actions\Profile\DeleteProfileAction;
use App\Http\Actions\Profile\Group\EditGroupAction;
use App\Http\Actions\Profile\Group\UpdateGroupAction;
use App\Http\Actions\Profile\Group\AddMemberAction;
use App\Http\Actions\Profile\Group\UpdateMemberPermissionAction;
use App\Http\Actions\Profile\Group\ToggleMemberThemeAction;
use App\Http\Actions\Profile\Group\TransferGroupMasterAction;
use App\Http\Actions\Profile\Group\RemoveMemberAction;
use App\Http\Actions\Reports\IndexPerformanceAction;
use App\Http\Actions\Tags\TagsListAction;
use App\Http\Actions\Tags\StoreTagAction;
use App\Http\Actions\Tags\UpdateTagAction;
use App\Http\Actions\Tags\DestroyTagAction;
use App\Http\Actions\Tags\TagTaskAction;
use App\Http\Actions\Tags\GetTagTasksAction;
use App\Http\Actions\Tags\AttachTaskToTagAction;
use App\Http\Actions\Tags\DetachTaskFromTagAction;
use App\Http\Actions\Task\AdoptProposalAction;
use App\Http\Actions\Task\ApproveTaskAction;
use App\Http\Actions\Task\CreateTaskAction;
use App\Http\Actions\Task\DeleteTaskImageAction;
use App\Http\Actions\Task\DestroyTaskAction;
use App\Http\Actions\Task\IndexTaskAction;
use App\Http\Actions\Task\ListPendingApprovalsAction;
use App\Http\Actions\Task\ProposeTaskAction;
use App\Http\Actions\Task\RejectTaskAction;
use App\Http\Actions\Task\RequestApprovalAction;
use App\Http\Actions\Task\SearchTasksAction;
use App\Http\Actions\Task\StoreTaskAction;
use App\Http\Actions\Task\TaskSearchResultsAction;
use App\Http\Actions\Task\ToggleTaskCompletionAction;
use App\Http\Actions\Task\UpdateTaskAction;
use App\Http\Actions\Task\UpdateTaskDescriptionAction;
use App\Http\Actions\Task\UploadTaskImageAction;
use App\Http\Actions\Token\HandleStripeWebhookAction;
use App\Http\Actions\Token\IndexTokenPurchaseAction;
use App\Http\Actions\Token\ProcessTokenPurchaseAction;
use App\Http\Actions\Token\IndexTokenHistoryAction;
use App\Http\Actions\Validation\ValidateGroupNameAction;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| アプリケーションのウェブインターフェースに使用されるルートを定義します。
| 全てのルートは 'web' ミドルウェアグループ内にあり、セッション、CSRF保護などが提供されます。
|
*/
Route::get('/', function () {
    return view('welcome');
});

// =========================================================================
// ゲストユーザー向けルート（認証前）
// =========================================================================
Route::middleware(['guest'])->group(function () {
    // ユーザー名の非同期バリデーション
    Route::post('/validate/username', ValidateUsernameAction::class)->name('validate.username');
    
    // パスワードの非同期バリデーション
    Route::post('/validate/password', ValidatePasswordAction::class)->name('validate.password');
});

// =========================================================================
// 認証済みユーザー向けルート
// =========================================================================
Route::middleware(['auth'])->group(function () {
    // グループ名非同期バリデーション
    Route::post('/validate/group-name', ValidateGroupNameAction::class)->name('validate.group-name');
    
    // メンバー追加時非同期のバリデーション
    Route::post('/validate/member-username', ValidateUsernameAction::class)->name('validate.member.username');
    Route::post('/validate/member-password', ValidatePasswordAction::class)->name('validate.member.password');

    // --- メインメニュー画面 (タスク一覧) ---
    Route::get('/dashboard', IndexTaskAction::class)->middleware(['verified'])->name('dashboard');

    // --- タスク画面 ---
    Route::get('/tasks/create', CreateTaskAction::class)->name('tasks.create');
    Route::post('/tasks', StoreTaskAction::class)->name('tasks.store');
    Route::post('/tasks/propose', ProposeTaskAction::class)->name('tasks.propose');
    Route::post('/tasks/adopt', AdoptProposalAction::class)->name('tasks.adopt');
    Route::put('/tasks/{task}', UpdateTaskAction::class)->name('tasks.update');
    Route::delete('/tasks/destroy', DestroyTaskAction::class)->name('tasks.destroy');
    Route::get('/tasks/search', SearchTasksAction::class)->name('tasks.search');
    Route::get('/tasks/search/results', TaskSearchResultsAction::class)->name('tasks.search.results');
    Route::patch('/tasks/{task}/toggle', ToggleTaskCompletionAction::class)->name('tasks.toggle');

    // タスク承認関連
    Route::post('/tasks/{task}/request-approval', RequestApprovalAction::class)->name('tasks.request-approval');
    Route::post('/tasks/{task}/approve', ApproveTaskAction::class)->name('tasks.approve');
    Route::post('/tasks/{task}/reject', RejectTaskAction::class)->name('tasks.reject');
    Route::get('/tasks/pending-approvals', ListPendingApprovalsAction::class)->name('tasks.pending-approvals');
    Route::patch('/tasks/{task}/update-description', UpdateTaskDescriptionAction::class)->name('tasks.update-description');

    // 画像アップロード
    Route::post('/tasks/{task}/upload-image', UploadTaskImageAction::class)->name('tasks.upload-image');
    Route::delete('/tasks/images/{image}', DeleteTaskImageAction::class)->name('tasks.delete-image');

    // --- タグ画面 ---
    Route::get('/tags/list', TagsListAction::class)->name('tags.list');
    Route::post('/tags/store', StoreTagAction::class)->name('tags.store');
    Route::put('/tags/update/{id}', UpdateTagAction::class)->name('tags.update');
    Route::delete('/tags/destroy/{id}', DestroyTagAction::class)->name('tags.destroy');

    // --- タグのタスク管理API (統合アクション) ---
    Route::prefix('tags/{tag}')->name('tags.')->group(function () {
        Route::get('tasks', [TagTaskAction::class, 'index'])->name('tasks');
        Route::post('tasks/attach', [TagTaskAction::class, 'attach'])->name('tasks.attach');
        Route::delete('tasks/detach', [TagTaskAction::class, 'detach'])->name('tasks.detach');
    });

    // --- 実績 ---
    Route::get('/reports/performance', IndexPerformanceAction::class)->name('reports.performance');

    // --- アカウント管理画面 ---
    Route::prefix('/profile')->group(function () {
        Route::get('/edit', EditProfileAction::class)->name('profile.edit');
        Route::patch('/update', UpdateProfileAction::class)->name('profile.update');
        Route::delete('/delete', DeleteProfileAction::class)->name('profile.destroy');
        // --- グループ管理 ---
        Route::get('/group', EditGroupAction::class)->name('group.edit');
        Route::patch('/group', UpdateGroupAction::class)->name('group.update');
        Route::post('/group/member', AddMemberAction::class)->name('group.member.add');
        Route::patch('/group/member/{member}/permission', UpdateMemberPermissionAction::class)->name('group.member.permission');
        Route::patch('/group/member/{member}/theme', ToggleMemberThemeAction::class)->name('group.member.theme');
        Route::post('/group/transfer/{newMaster}', TransferGroupMasterAction::class)->name('group.master.transfer');
        Route::delete('/group/member/{member}', RemoveMemberAction::class)->name('group.member.remove');
    });

    // ========================================
    // スケジュールタスク管理（Batch）
    // ========================================
    Route::prefix('batch/scheduled-tasks')->name('batch.scheduled-tasks.')->group(function () {        
        // 一覧
        Route::get('/', IndexScheduledTaskAction::class)->name('index');
        // 作成画面
        Route::get('/create', CreateScheduledTaskAction::class)->name('create');
        // 作成処理
        Route::post('/', StoreScheduledTaskAction::class)->name('store');
        // 編集画面
        Route::get('/{id}/edit', EditScheduledTaskAction::class)->name('edit');
        // 更新処理
        Route::put('/{id}', UpdateScheduledTaskAction::class)->name('update');
        // 削除処理
        Route::delete('/{id}', DeleteScheduledTaskAction::class)->name('destroy');
        // 一時停止
        Route::post('/{id}/pause', PauseScheduledTaskAction::class)->name('pause');
        // 再開
        Route::post('/{id}/resume', ResumeScheduledTaskAction::class)->name('resume');
        // 実行履歴
        Route::get('/{id}/history', ShowExecutionHistoryAction::class)->name('history');
    });

    // ========================================
    // トークン関連
    // ========================================
    // トークン購入
    Route::get('/tokens/purchase', IndexTokenPurchaseAction::class)->name('tokens.purchase');
    Route::post('/tokens/purchase', ProcessTokenPurchaseAction::class)->name('tokens.purchase.process');
    // トークン履歴
    Route::get('/tokens/history', IndexTokenHistoryAction::class)->name('tokens.history');    
    // 通知
    Route::get('/notifications', IndexNotificationAction::class)->name('notifications.index');
    Route::post('/notifications/{notification}/read', MarkNotificationAsReadAction::class)->name('notifications.read');
    Route::post('/notifications/read-all', MarkAllNotificationsAsReadAction::class)->name('notifications.read-all');

    // ========================================
    // アバター管理
    // ========================================
    // アバター作成（初回のみ）
    Route::get('/avatars/create', CreateTeacherAvatarAction::class)->name('avatars.create');
    Route::post('/avatars', StoreTeacherAvatarAction::class)->name('avatars.store');
    // アバター編集
    Route::get('/avatars/edit', EditTeacherAvatarAction::class)->name('avatars.edit');
    Route::put('/avatars/update', UpdateTeacherAvatarAction::class)->name('avatars.update');
    // 画像再生成
    Route::post('/avatars/regenerate', RegenerateAvatarImageAction::class)->name('avatars.regenerate');
    // コメント取得API
    Route::get('/avatars/comment/{eventType}', GetAvatarCommentAction::class)->name('avatars.comment');
    // 表示/非表示切替
    Route::post('/avatars/toggle-visibility', ToggleAvatarVisibilityAction::class)->name('avatars.toggle-visibility');

    // ========================================
    // お知らせ管理
    // ========================================
    Route::prefix('notification')->name('notification.')->group(function () {
        Route::get('/', IndexNotificationAction::class)->name('index');
        Route::get('/{notification}', ShowNotificationAction::class)->name('show');
        Route::post('/{notification}/read', MarkNotificationAsReadAction::class)->name('read');
        Route::post('/read-all', MarkAllNotificationsAsReadAction::class)->name('read-all');
        Route::get('/search/api', SearchNotificationsAction::class)->name('search.api');
        Route::get('/search/results', SearchResultsNotificationAction::class)->name('search.results');
    });

    // ========================================
    // 通知 API（非同期）
    // ========================================
    Route::get('/api/notifications/unread-count', GetUnreadCountAction::class)->name('api.notifications.unread-count');
});

// ========================================
// 管理者専用ルート
// ========================================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // ユーザー管理
    Route::get('/users', IndexUserAction::class)->name('users.index');
    Route::get('/users/{user}/edit', EditUserAction::class)->name('users.edit');
    Route::put('/users/{user}', UpdateUserAction::class)->name('users.update');
    Route::delete('/users/{user}', DeleteUserAction::class)->name('users.destroy');
    
    // トークン統計
    Route::get('/token-stats', IndexTokenStatsAction::class)->name('token-stats');
    
    // ユーザー別トークン
    Route::get('/token-users', IndexTokenUsersAction::class)->name('token-users');
    
    // 課金履歴
    Route::get('/payments', IndexPaymentHistoryAction::class)->name('payment-history');

    // トークンパッケージ設定
    Route::get('/token-packages', IndexTokenPackageAction::class)->name('token-packages');
    Route::get('/token-packages/create', CreateTokenPackageAction::class)->name('token-packages-create');
    Route::post('/token-packages', StoreTokenPackageAction::class)->name('token-packages-store');
    Route::get('/token-packages/{package}/edit', EditTokenPackageAction::class)->name('token-packages-edit');
    Route::put('/token-packages/{package}', UpdateTokenPackageAction::class)->name('token-packages-update');
    Route::delete('/token-packages/{package}', DeleteTokenPackageAction::class)->name('token-packages-delete');
    Route::post('/token-packages/free-token-update', UpdateFreeTokenAmountAction::class)->name('token-packages.free-token-update');

    // 管理者通知管理
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', IndexAdminNotificationAction::class)->name('index');
        Route::get('/create', CreateAdminNotificationAction::class)->name('create');
        Route::post('/', StoreAdminNotificationAction::class)->name('store');
        Route::get('/{notification}/edit', EditAdminNotificationAction::class)->name('edit');
        Route::put('/{notification}', UpdateAdminNotificationAction::class)->name('update');
        Route::delete('/{notification}', DeleteAdminNotificationAction::class)->name('destroy');
    });
});

// ========================================
// Stripe Webhook
// ========================================
Route::post(
    '/stripe/webhook',
    \App\Http\Actions\Token\HandleStripeWebhookAction::class
)->name('stripe.webhook');

// =========================================================================
// 認証関連ルート (Breeze)
// =========================================================================

require __DIR__.'/auth.php'; // Breezeが生成するログイン/ログアウト/新規登録ルート