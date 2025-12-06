<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Actions\Task\ProposeTaskAction;
use App\Http\Actions\Api\Task\StoreTaskApiAction;
use App\Http\Actions\Api\Task\IndexTaskApiAction;
use App\Http\Actions\Api\Task\UpdateTaskApiAction;
use App\Http\Actions\Api\Task\DestroyTaskApiAction;
use App\Http\Actions\Api\Task\ToggleTaskCompletionApiAction;
use App\Http\Actions\Api\Task\ApproveTaskApiAction;
use App\Http\Actions\Api\Task\RejectTaskApiAction;
use App\Http\Actions\Api\Task\UploadTaskImageApiAction;
use App\Http\Actions\Api\Task\DeleteTaskImageApiAction;
use App\Http\Actions\Api\Task\BulkCompleteTasksApiAction;
use App\Http\Actions\Api\Task\RequestApprovalApiAction;
use App\Http\Actions\Api\Task\ListPendingApprovalsApiAction;
use App\Http\Actions\Api\Task\SearchTasksApiAction;
use App\Http\Actions\Api\Task\GetTasksPaginatedApiAction;
use App\Http\Actions\Token\HandleTokenPurchaseWebhookAction;
use App\Http\Actions\Api\Group\EditGroupApiAction;
use App\Http\Actions\Api\Group\UpdateGroupApiAction;
use App\Http\Actions\Api\Group\AddMemberApiAction;
use App\Http\Actions\Api\Group\UpdateMemberPermissionApiAction;
use App\Http\Actions\Api\Group\ToggleMemberThemeApiAction;
use App\Http\Actions\Api\Group\TransferGroupMasterApiAction;
use App\Http\Actions\Api\Group\RemoveMemberApiAction;
use App\Http\Actions\Api\User\GetCurrentUserApiAction;
use App\Http\Actions\Api\Profile\EditProfileApiAction;
use App\Http\Actions\Api\Profile\UpdateProfileApiAction;
use App\Http\Actions\Api\Profile\DeleteProfileApiAction;
use App\Http\Actions\Api\Profile\ShowTimezoneSettingApiAction;
use App\Http\Actions\Api\Profile\UpdateTimezoneApiAction;
use App\Http\Actions\Api\Tags\TagsListApiAction;
use App\Http\Actions\Api\Tags\StoreTagApiAction;
use App\Http\Actions\Api\Tags\UpdateTagApiAction;
use App\Http\Actions\Api\Tags\DestroyTagApiAction;

// Phase 1.E-1.5.2: Avatar API
use App\Http\Actions\Api\Avatar\StoreTeacherAvatarApiAction;
use App\Http\Actions\Api\Avatar\ShowTeacherAvatarApiAction;
use App\Http\Actions\Api\Avatar\UpdateTeacherAvatarApiAction;
use App\Http\Actions\Api\Avatar\DestroyTeacherAvatarApiAction;
use App\Http\Actions\Api\Avatar\RegenerateAvatarImageApiAction;
use App\Http\Actions\Api\Avatar\ToggleAvatarVisibilityApiAction;
use App\Http\Actions\Api\Avatar\GetAvatarCommentApiAction;

// Phase 1.E-1.5.2: Notification API
use App\Http\Actions\Api\Notification\IndexNotificationApiAction;
use App\Http\Actions\Api\Notification\ShowNotificationApiAction;
use App\Http\Actions\Api\Notification\MarkNotificationAsReadApiAction;
use App\Http\Actions\Api\Notification\MarkAllNotificationsAsReadApiAction;
use App\Http\Actions\Api\Notification\GetUnreadCountApiAction;
use App\Http\Actions\Api\Notification\SearchNotificationsApiAction;

// Phase 1.E-1.5.2: Token API
use App\Http\Actions\Api\Token\GetTokenBalanceApiAction;
use App\Http\Actions\Api\Token\GetTokenHistoryApiAction;
use App\Http\Actions\Api\Token\GetTokenPackagesApiAction;
use App\Http\Actions\Api\Token\CreateCheckoutSessionApiAction;
use App\Http\Actions\Api\Token\ToggleTokenModeApiAction;

// Phase 1.E-1.5.3: Report API
use App\Http\Actions\Api\Report\IndexPerformanceApiAction;
use App\Http\Actions\Api\Report\ShowMonthlyReportApiAction;
use App\Http\Actions\Api\Report\GenerateMemberSummaryApiAction;
use App\Http\Actions\Api\Report\DownloadMemberSummaryPdfApiAction;

// Phase 1.E-1.5.3: ScheduledTask API
use App\Http\Actions\Api\ScheduledTask\IndexScheduledTaskApiAction;
use App\Http\Actions\Api\ScheduledTask\CreateScheduledTaskApiAction;
use App\Http\Actions\Api\ScheduledTask\StoreScheduledTaskApiAction;
use App\Http\Actions\Api\ScheduledTask\EditScheduledTaskApiAction;
use App\Http\Actions\Api\ScheduledTask\UpdateScheduledTaskApiAction;
use App\Http\Actions\Api\ScheduledTask\DeleteScheduledTaskApiAction;
use App\Http\Actions\Api\ScheduledTask\PauseScheduledTaskApiAction;
use App\Http\Actions\Api\ScheduledTask\ResumeScheduledTaskApiAction;

// ============================================================
// 認証API（モバイルアプリ用 - Sanctum）
// ============================================================
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::prefix('auth')->group(function () {
    // ログイン（username + password → Sanctum token）
    Route::post('/login', [AuthenticatedSessionController::class, 'apiLogin'])->name('api.auth.login');
    
    // ログアウト（Sanctum token削除）
    Route::post('/logout', [AuthenticatedSessionController::class, 'apiLogout'])
        ->middleware('auth:sanctum')
        ->name('api.auth.logout');
    
    // 登録は現在停止中（RegisterAction::store で abort(404)）
    // Route::post('/register', [RegisterAction::class, 'apiStore'])->name('api.auth.register');
});

// ============================================================
// Stripe Webhook（認証不要）
// ============================================================
Route::post('/webhooks/stripe/token-purchase', HandleTokenPurchaseWebhookAction::class)->name('webhooks.stripe.token-purchase');

// ============================================================
// Phase 1.5: 並行運用期間のAPI設定
// ============================================================
// - 既存: Sanctum認証（レガシー）
// - 新規: Cognito JWT認証
// - 並行: dual.auth ミドルウェア（Breeze + Cognito両対応）
// ============================================================

// Cognito JWT認証専用ルート（新規API向け）
Route::prefix('v1')->middleware(['cognito'])->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'id' => $request->user()->id ?? null,
            'email' => $request->cognito_email,
            'cognito_sub' => $request->cognito_sub,
            'auth_provider' => 'cognito',
        ]);
    })->name('api.v1.user');

    // タスクAPI（モバイルアプリ向け）
    Route::get('/tasks', IndexTaskApiAction::class)->name('api.v1.tasks.index');
    Route::post('/tasks', StoreTaskApiAction::class)->name('api.v1.tasks.store');
    Route::put('/tasks/{task}', UpdateTaskApiAction::class)->name('api.v1.tasks.update');
    Route::delete('/tasks/{task}', DestroyTaskApiAction::class)->name('api.v1.tasks.destroy');
    Route::patch('/tasks/{task}/toggle', ToggleTaskCompletionApiAction::class)->name('api.v1.tasks.toggle');
    
    // タスク承認API（グループタスク）
    Route::post('/tasks/{task}/approve', ApproveTaskApiAction::class)->name('api.v1.tasks.approve');
    Route::post('/tasks/{task}/reject', RejectTaskApiAction::class)->name('api.v1.tasks.reject');
    
    // タスク画像API
    Route::post('/tasks/{task}/images', UploadTaskImageApiAction::class)->name('api.v1.tasks.images.upload');
    Route::delete('/task-images/{image}', DeleteTaskImageApiAction::class)->name('api.v1.tasks.images.delete');

    // タスク一括完了/未完了
    Route::patch('/tasks/bulk-complete', BulkCompleteTasksApiAction::class)->name('api.v1.tasks.bulk-complete');

    // タスク完了申請
    Route::post('/tasks/{task}/request-approval', RequestApprovalApiAction::class)->name('api.v1.tasks.request-approval');

    // 承認待ち一覧取得
    Route::get('/approvals/pending', ListPendingApprovalsApiAction::class)->name('api.v1.approvals.pending');

    // タスク検索
    Route::post('/tasks/search', SearchTasksApiAction::class)->name('api.v1.tasks.search');
    
    // タスク一覧（無限スクロール用・ページネーション付き）
    Route::get('/tasks/paginated', GetTasksPaginatedApiAction::class)->name('api.v1.tasks.paginated');

    // グループ管理API
    Route::get('/groups/edit', EditGroupApiAction::class)->name('api.v1.groups.edit');
    Route::patch('/groups', UpdateGroupApiAction::class)->name('api.v1.groups.update');
    Route::post('/groups/members', AddMemberApiAction::class)->name('api.v1.groups.members.add');
    Route::patch('/groups/members/{member}/permission', UpdateMemberPermissionApiAction::class)->name('api.v1.groups.members.permission');
    Route::patch('/groups/members/{member}/theme', ToggleMemberThemeApiAction::class)->name('api.v1.groups.members.theme');
    Route::post('/groups/transfer/{newMaster}', TransferGroupMasterApiAction::class)->name('api.v1.groups.transfer');
    Route::delete('/groups/members/{member}', RemoveMemberApiAction::class)->name('api.v1.groups.members.remove');

    // ユーザー情報API
    Route::get('/user/current', GetCurrentUserApiAction::class)->name('api.v1.user.current');

    // プロフィール管理API
    Route::get('/profile/edit', EditProfileApiAction::class)->name('api.v1.profile.edit');
    Route::patch('/profile', UpdateProfileApiAction::class)->name('api.v1.profile.update');
    Route::delete('/profile', DeleteProfileApiAction::class)->name('api.v1.profile.delete');
    Route::get('/profile/timezone', ShowTimezoneSettingApiAction::class)->name('api.v1.profile.timezone');
    Route::put('/profile/timezone', UpdateTimezoneApiAction::class)->name('api.v1.profile.timezone.update');

    // タグ管理API
    Route::get('/tags', TagsListApiAction::class)->name('api.v1.tags.index');
    Route::post('/tags', StoreTagApiAction::class)->name('api.v1.tags.store');
    Route::put('/tags/{id}', UpdateTagApiAction::class)->name('api.v1.tags.update');
    Route::delete('/tags/{id}', DestroyTagApiAction::class)->name('api.v1.tags.destroy');

    // ============================================================
    // Phase 1.E-1.5.2: 中優先度API（アバター、通知、トークン管理）
    // ============================================================

    // アバター管理API
    Route::post('/avatar', StoreTeacherAvatarApiAction::class)->name('api.v1.avatar.store');
    Route::get('/avatar', ShowTeacherAvatarApiAction::class)->name('api.v1.avatar.show');
    Route::put('/avatar', UpdateTeacherAvatarApiAction::class)->name('api.v1.avatar.update');
    Route::delete('/avatar', DestroyTeacherAvatarApiAction::class)->name('api.v1.avatar.destroy');
    Route::post('/avatar/regenerate', RegenerateAvatarImageApiAction::class)->name('api.v1.avatar.regenerate');
    Route::patch('/avatar/visibility', ToggleAvatarVisibilityApiAction::class)->name('api.v1.avatar.visibility');
    Route::get('/avatar/comment/{event}', GetAvatarCommentApiAction::class)->name('api.v1.avatar.comment');

    // 通知API
    Route::get('/notifications', IndexNotificationApiAction::class)->name('api.v1.notifications.index');
    Route::get('/notifications/unread-count', GetUnreadCountApiAction::class)->name('api.v1.notifications.unread-count');
    Route::get('/notifications/search', SearchNotificationsApiAction::class)->name('api.v1.notifications.search');
    Route::post('/notifications/read-all', MarkAllNotificationsAsReadApiAction::class)->name('api.v1.notifications.read-all');
    Route::get('/notifications/{id}', ShowNotificationApiAction::class)->name('api.v1.notifications.show');
    Route::patch('/notifications/{id}/read', MarkNotificationAsReadApiAction::class)->name('api.v1.notifications.read');

    // トークン管理API
    Route::get('/tokens/balance', GetTokenBalanceApiAction::class)->name('api.v1.tokens.balance');
    Route::get('/tokens/history', GetTokenHistoryApiAction::class)->name('api.v1.tokens.history');
    Route::get('/tokens/packages', GetTokenPackagesApiAction::class)->name('api.v1.tokens.packages');
    Route::post('/tokens/create-checkout-session', CreateCheckoutSessionApiAction::class)->name('api.v1.tokens.create-checkout-session');
    Route::patch('/tokens/toggle-mode', ToggleTokenModeApiAction::class)->name('api.v1.tokens.toggle-mode');

    // ============================================================
    // Phase 1.E-1.5.3: 低優先度API（レポート・実績、スケジュールタスク）
    // ============================================================

    // レポート・実績API
    Route::get('/reports/performance', IndexPerformanceApiAction::class)->name('api.v1.reports.performance');
    Route::get('/reports/monthly/{year?}/{month?}', ShowMonthlyReportApiAction::class)->name('api.v1.reports.monthly.show');
    Route::post('/reports/monthly/member-summary', GenerateMemberSummaryApiAction::class)->name('api.v1.reports.monthly.member-summary');
    Route::post('/reports/monthly/member-summary/pdf', DownloadMemberSummaryPdfApiAction::class)->name('api.v1.reports.monthly.member-summary.pdf');

    // スケジュールタスクAPI
    Route::get('/scheduled-tasks', IndexScheduledTaskApiAction::class)->name('api.v1.scheduled-tasks.index');
    Route::get('/scheduled-tasks/create', CreateScheduledTaskApiAction::class)->name('api.v1.scheduled-tasks.create');
    Route::post('/scheduled-tasks', StoreScheduledTaskApiAction::class)->name('api.v1.scheduled-tasks.store');
    Route::get('/scheduled-tasks/{id}/edit', EditScheduledTaskApiAction::class)->name('api.v1.scheduled-tasks.edit');
    Route::put('/scheduled-tasks/{id}', UpdateScheduledTaskApiAction::class)->name('api.v1.scheduled-tasks.update');
    Route::delete('/scheduled-tasks/{id}', DeleteScheduledTaskApiAction::class)->name('api.v1.scheduled-tasks.destroy');
    Route::post('/scheduled-tasks/{id}/pause', PauseScheduledTaskApiAction::class)->name('api.v1.scheduled-tasks.pause');
    Route::post('/scheduled-tasks/{id}/resume', ResumeScheduledTaskApiAction::class)->name('api.v1.scheduled-tasks.resume');
});

// Breeze + Cognito 並行運用ルート（Phase 1.5 期間限定）
Route::prefix('v1/dual')->middleware(['dual.auth'])->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'auth_provider' => $user->auth_provider ?? 'breeze',
            'cognito_sub' => $user->cognito_sub,
            'authenticated_via' => $request->has('cognito_sub') ? 'cognito' : 'breeze',
        ]);
    })->name('api.v1.dual.user');

    // ★ 既存APIを段階的に移行する際はこちらに
    // Route::get('/tasks', ...)->name('api.v1.dual.tasks.index');
});

// レガシーAPI（Web認証 - ダッシュボード用）
// 注意: routes/api.phpは自動的に/apiプレフィックスが付くため、prefix不要
Route::middleware(['auth'])->group(function () {
    Route::get('/user', function () {
        return auth()->user();
    });

    // タスクAPI（テスト用 - Phase 2で削除予定）
    Route::get('/tasks', IndexTaskApiAction::class)->name('api.tasks.index');
    Route::post('/tasks', StoreTaskApiAction::class)->name('api.tasks.store');
    Route::put('/tasks/{task}', UpdateTaskApiAction::class)->name('api.tasks.update');
    Route::delete('/tasks/{task}', DestroyTaskApiAction::class)->name('api.tasks.destroy');
    Route::patch('/tasks/{task}/toggle', ToggleTaskCompletionApiAction::class)->name('api.tasks.toggle');
    
    // タスク承認API
    Route::post('/tasks/{task}/approve', ApproveTaskApiAction::class)->name('api.tasks.approve');
    Route::post('/tasks/{task}/reject', RejectTaskApiAction::class)->name('api.tasks.reject');
    
    // タスク画像API
    Route::post('/tasks/{task}/images', UploadTaskImageApiAction::class)->name('api.tasks.images.upload');
    Route::delete('/task-images/{image}', DeleteTaskImageApiAction::class)->name('api.tasks.images.delete');
    
    // タスク一括完了/未完了
    Route::patch('/tasks/bulk-complete', BulkCompleteTasksApiAction::class)->name('api.tasks.bulk-complete');
    
    // タスク完了申請
    Route::post('/tasks/{task}/request-approval', RequestApprovalApiAction::class)->name('api.tasks.request-approval');
    
    // 承認待ち一覧取得
    Route::get('/approvals/pending', ListPendingApprovalsApiAction::class)->name('api.approvals.pending');
    
    // タスク検索
    Route::post('/tasks/search', SearchTasksApiAction::class)->name('api.tasks.search');
    
    // タスク一覧（無限スクロール用・ページネーション付き）
    Route::get('/tasks/paginated', GetTasksPaginatedApiAction::class)->name('api.tasks.paginated');

    Route::post('/tasks/propose', ProposeTaskAction::class)->name('api.tasks.propose');
});
