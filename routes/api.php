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
use App\Http\Actions\Api\Profile\UpdatePasswordApiAction;
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
// モバイルアプリAPI（Sanctum認証）
// Phase 2.B: モバイルアプリ実装（Cognito → Sanctum認証に統一）
// ============================================================
Route::middleware('auth:sanctum')->group(function () {
    
    // ユーザー情報API
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'auth_provider' => 'sanctum',
        ]);
    })->name('api.user');
    Route::get('/user/current', GetCurrentUserApiAction::class)->name('api.user.current');

    // タスクAPI
    Route::prefix('tasks')->group(function () {
        Route::get('/', IndexTaskApiAction::class)->name('api.tasks.index');
        Route::post('/', StoreTaskApiAction::class)->name('api.tasks.store');
        Route::put('/{task}', UpdateTaskApiAction::class)->name('api.tasks.update');
        Route::delete('/{task}', DestroyTaskApiAction::class)->name('api.tasks.destroy');
        Route::patch('/{task}/toggle', ToggleTaskCompletionApiAction::class)->name('api.tasks.toggle');
        
        // 検索・ページネーション
        Route::post('/search', SearchTasksApiAction::class)->name('api.tasks.search');
        Route::get('/paginated', GetTasksPaginatedApiAction::class)->name('api.tasks.paginated');
        
        // 承認フロー
        Route::post('/{task}/approve', ApproveTaskApiAction::class)->name('api.tasks.approve');
        Route::post('/{task}/reject', RejectTaskApiAction::class)->name('api.tasks.reject');
        Route::post('/{task}/request-approval', RequestApprovalApiAction::class)->name('api.tasks.request-approval');
        Route::get('/approvals/pending', ListPendingApprovalsApiAction::class)->name('api.tasks.approvals.pending');
        
        // 一括操作
        Route::patch('/bulk-complete', BulkCompleteTasksApiAction::class)->name('api.tasks.bulk-complete');
        
        // 画像
        Route::post('/{task}/images', UploadTaskImageApiAction::class)->name('api.tasks.images.upload');
        Route::delete('/images/{image}', DeleteTaskImageApiAction::class)->name('api.tasks.images.delete');
        
        // その他
        Route::post('/propose', ProposeTaskAction::class)->name('api.tasks.propose');
    });

    // グループ管理API
    Route::prefix('groups')->group(function () {
        Route::get('/edit', EditGroupApiAction::class)->name('api.groups.edit');
        Route::patch('/', UpdateGroupApiAction::class)->name('api.groups.update');
        Route::post('/members', AddMemberApiAction::class)->name('api.groups.members.add');
        Route::patch('/members/{member}/permission', UpdateMemberPermissionApiAction::class)->name('api.groups.members.permission');
        Route::patch('/members/{member}/theme', ToggleMemberThemeApiAction::class)->name('api.groups.members.theme');
        Route::post('/transfer/{newMaster}', TransferGroupMasterApiAction::class)->name('api.groups.transfer');
        Route::delete('/members/{member}', RemoveMemberApiAction::class)->name('api.groups.members.remove');
    });

    // プロフィール管理API
    Route::prefix('profile')->group(function () {
        Route::get('/edit', EditProfileApiAction::class)->name('api.profile.edit');
        Route::patch('/', UpdateProfileApiAction::class)->name('api.profile.update');
        Route::delete('/', DeleteProfileApiAction::class)->name('api.profile.delete');
        Route::get('/timezone', ShowTimezoneSettingApiAction::class)->name('api.profile.timezone');
        Route::put('/timezone', UpdateTimezoneApiAction::class)->name('api.profile.timezone.update');
        Route::put('/password', UpdatePasswordApiAction::class)->name('api.profile.password');
    });

    // タグ管理API
    Route::prefix('tags')->group(function () {
        Route::get('/', TagsListApiAction::class)->name('api.tags.index');
        Route::post('/', StoreTagApiAction::class)->name('api.tags.store');
        Route::put('/{id}', UpdateTagApiAction::class)->name('api.tags.update');
        Route::delete('/{id}', DestroyTagApiAction::class)->name('api.tags.destroy');
    });

    // アバター管理API
    Route::prefix('avatar')->group(function () {
        Route::post('/', StoreTeacherAvatarApiAction::class)->name('api.avatar.store');
        Route::get('/', ShowTeacherAvatarApiAction::class)->name('api.avatar.show');
        Route::put('/', UpdateTeacherAvatarApiAction::class)->name('api.avatar.update');
        Route::delete('/', DestroyTeacherAvatarApiAction::class)->name('api.avatar.destroy');
        Route::post('/regenerate', RegenerateAvatarImageApiAction::class)->name('api.avatar.regenerate');
        Route::patch('/visibility', ToggleAvatarVisibilityApiAction::class)->name('api.avatar.visibility');
        Route::get('/comment/{event}', GetAvatarCommentApiAction::class)->name('api.avatar.comment');
    });

    // 通知API（モバイルアプリ専用）
    // 注意: Sanctum認証（トークンベース）を使用
    // Web画面用の未読件数エンドポイントは routes/web.php に別途定義（セッション認証）
    // 経緯: 2025-12-07 Phase 2.B-5 Step 2実装時、認証方式の違いにより分離
    Route::prefix('notifications')->group(function () {
        Route::get('/', IndexNotificationApiAction::class)->name('api.notifications.index');
        Route::get('/unread-count', GetUnreadCountApiAction::class)->name('api.notifications.unread-count');
        Route::get('/search', SearchNotificationsApiAction::class)->name('api.notifications.search');
        Route::post('/read-all', MarkAllNotificationsAsReadApiAction::class)->name('api.notifications.read-all');
        Route::get('/{id}', ShowNotificationApiAction::class)->name('api.notifications.show');
        Route::patch('/{id}/read', MarkNotificationAsReadApiAction::class)->name('api.notifications.read');
    });

    // トークン管理API
    Route::prefix('tokens')->group(function () {
        Route::get('/balance', GetTokenBalanceApiAction::class)->name('api.tokens.balance');
        Route::get('/history', GetTokenHistoryApiAction::class)->name('api.tokens.history');
        Route::get('/packages', GetTokenPackagesApiAction::class)->name('api.tokens.packages');
        Route::post('/create-checkout-session', CreateCheckoutSessionApiAction::class)->name('api.tokens.create-checkout-session');
        Route::patch('/toggle-mode', ToggleTokenModeApiAction::class)->name('api.tokens.toggle-mode');
    });

    // レポート・実績API
    Route::prefix('reports')->group(function () {
        Route::get('/performance', IndexPerformanceApiAction::class)->name('api.reports.performance');
        Route::get('/monthly/{year?}/{month?}', ShowMonthlyReportApiAction::class)->name('api.reports.monthly.show');
        Route::post('/monthly/member-summary', GenerateMemberSummaryApiAction::class)->name('api.reports.monthly.member-summary');
        Route::post('/monthly/member-summary/pdf', DownloadMemberSummaryPdfApiAction::class)->name('api.reports.monthly.member-summary.pdf');
    });

    // スケジュールタスクAPI
    Route::prefix('scheduled-tasks')->group(function () {
        Route::get('/', IndexScheduledTaskApiAction::class)->name('api.scheduled-tasks.index');
        Route::get('/create', CreateScheduledTaskApiAction::class)->name('api.scheduled-tasks.create');
        Route::post('/', StoreScheduledTaskApiAction::class)->name('api.scheduled-tasks.store');
        Route::get('/{id}/edit', EditScheduledTaskApiAction::class)->name('api.scheduled-tasks.edit');
        Route::put('/{id}', UpdateScheduledTaskApiAction::class)->name('api.scheduled-tasks.update');
        Route::delete('/{id}', DeleteScheduledTaskApiAction::class)->name('api.scheduled-tasks.destroy');
        Route::post('/{id}/pause', PauseScheduledTaskApiAction::class)->name('api.scheduled-tasks.pause');
        Route::post('/{id}/resume', ResumeScheduledTaskApiAction::class)->name('api.scheduled-tasks.resume');
    });
});
