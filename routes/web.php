<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwaggerController;

// 管理者用 - 認証
use App\Http\Actions\Admin\Auth\AdminLoginAction;

// 管理者用
use App\Http\Actions\Admin\Notification\IndexAdminNotificationAction;
use App\Http\Actions\Admin\Notification\CreateAdminNotificationAction;
use App\Http\Actions\Admin\Notification\StoreAdminNotificationAction;
use App\Http\Actions\Admin\Notification\EditAdminNotificationAction;
use App\Http\Actions\Admin\Notification\UpdateAdminNotificationAction;
use App\Http\Actions\Admin\Notification\DeleteAdminNotificationAction;
use App\Http\Actions\Admin\Portal\IndexMaintenanceAction;
use App\Http\Actions\Admin\Portal\CreateMaintenanceAction;
use App\Http\Actions\Admin\Portal\StoreMaintenanceAction;
use App\Http\Actions\Admin\Portal\EditMaintenanceAction;
use App\Http\Actions\Admin\Portal\UpdateMaintenanceAction;
use App\Http\Actions\Admin\Portal\DeleteMaintenanceAction;
use App\Http\Actions\Admin\Portal\UpdateMaintenanceStatusAction;
use App\Http\Actions\Admin\Portal\IndexContactAction;
use App\Http\Actions\Admin\Portal\ShowContactAction;
use App\Http\Actions\Admin\Portal\UpdateContactStatusAction;
use App\Http\Actions\Admin\Portal\IndexFaqAction;
use App\Http\Actions\Admin\Portal\CreateFaqAction;
use App\Http\Actions\Admin\Portal\StoreFaqAction;
use App\Http\Actions\Admin\Portal\EditFaqAction;
use App\Http\Actions\Admin\Portal\UpdateFaqAction;
use App\Http\Actions\Admin\Portal\DeleteFaqAction;
use App\Http\Actions\Admin\Portal\ToggleFaqPublishedAction;
use App\Http\Actions\Admin\Portal\IndexAppUpdateAction;
use App\Http\Actions\Admin\Portal\CreateAppUpdateAction;
use App\Http\Actions\Admin\Portal\StoreAppUpdateAction;
use App\Http\Actions\Admin\Portal\EditAppUpdateAction;
use App\Http\Actions\Admin\Portal\UpdateAppUpdateAction;
use App\Http\Actions\Admin\Portal\DeleteAppUpdateAction;
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
use App\Http\Actions\Auth\ValidateEmailAction;
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
use App\Http\Actions\Notification\ApproveParentLinkAction;
use App\Http\Actions\Notification\RejectParentLinkAction;
use App\Http\Actions\Profile\EditProfileAction;
use App\Http\Actions\Profile\UpdateProfileAction;
use App\Http\Actions\Profile\DeleteProfileAction;
use App\Http\Actions\Profile\UpdatePasswordWebAction;
use App\Http\Actions\Profile\ShowTimezoneSettingAction;
use App\Http\Actions\Profile\UpdateTimezoneAction;
use App\Http\Actions\Profile\Group\EditGroupAction;
use App\Http\Actions\Profile\Group\UpdateGroupAction;
use App\Http\Actions\Profile\Group\AddMemberAction;
use App\Http\Actions\Profile\Group\UpdateMemberPermissionAction;
use App\Http\Actions\Profile\Group\ToggleMemberThemeAction;
use App\Http\Actions\Profile\Group\TransferGroupMasterAction;
use App\Http\Actions\Profile\Group\RemoveMemberAction;
use App\Http\Actions\Profile\Group\SearchUnlinkedChildrenAction;
use App\Http\Actions\Profile\Group\SendChildLinkRequestAction;
use App\Http\Actions\Profile\Group\LinkChildrenAction;
use App\Http\Actions\Reports\IndexPerformanceAction;
use App\Http\Actions\Reports\ShowMonthlyReportAction;
use App\Http\Actions\Reports\GenerateMemberSummaryAction;
use App\Http\Actions\Reports\DownloadMemberSummaryPdfAction;
use App\Http\Actions\Tags\TagsListAction;
use App\Http\Actions\Tags\StoreTagAction;
use App\Http\Actions\Tags\UpdateTagAction;
use App\Http\Actions\Tags\DestroyTagAction;
use App\Http\Actions\GroupTask\ListGroupTasksAction;
use App\Http\Actions\GroupTask\ShowGroupTaskEditFormAction;
use App\Http\Actions\GroupTask\UpdateGroupTaskAction;
use App\Http\Actions\GroupTask\DestroyGroupTaskAction;
use App\Http\Actions\Tags\TagTaskAction;
use App\Http\Actions\Tags\GetTagTasksAction;
use App\Http\Actions\Tags\AttachTaskToTagAction;
use App\Http\Actions\Tags\DetachTaskFromTagAction;
use App\Http\Actions\Task\AdoptProposalAction;
use App\Http\Actions\Task\ApproveTaskAction;
use App\Http\Actions\Task\BulkCompleteTasksAction;
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
use App\Http\Actions\Task\GetTasksPaginatedAction;
use App\Http\Actions\Token\ApproveTokenPurchaseRequestAction;
use App\Http\Actions\Token\CancelTokenPurchaseRequestAction;
use App\Http\Actions\Token\CreateTokenCheckoutSessionAction;
use App\Http\Actions\Token\HandleStripeWebhookAction;
use App\Http\Actions\Token\IndexPendingTokenPurchaseRequestsAction;
use App\Http\Actions\Token\IndexTokenHistoryAction;
use App\Http\Actions\Token\IndexTokenPurchaseAction;
use App\Http\Actions\Token\ProcessTokenPurchaseAction;
use App\Http\Actions\Token\RejectTokenPurchaseRequestAction;
use App\Http\Actions\Token\ShowPurchaseCancelAction;
use App\Http\Actions\Token\ShowPurchaseSuccessAction;
use App\Http\Actions\Subscription\BillingPortalAction;
use App\Http\Actions\Subscription\CancelSubscriptionAction;
use App\Http\Actions\Subscription\CreateCheckoutSessionAction;
use App\Http\Actions\Subscription\ManageSubscriptionAction;
use App\Http\Actions\Subscription\ShowSubscriptionPlansAction;
use App\Http\Actions\Subscription\SubscriptionCancelAction;
use App\Http\Actions\Subscription\SubscriptionSuccessAction;
use App\Http\Actions\Subscription\UpdateSubscriptionAction;
use App\Http\Actions\Validation\ValidateGroupNameAction;
// ヘルスチェック用
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

// ========================================
// ヘルスチェックエンドポイント（冗長構成対応）
// ========================================
Route::get('/health', function () {
    // 一時的に簡易ヘルスチェックに変更（詳細確認のため）
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
    ], 200);
})->name('health');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| アプリケーションのウェブインターフェースに使用されるルートを定義します。
| 全てのルートは 'web' ミドルウェアグループ内にあり、セッション、CSRF保護などが提供されます。
|
*/

// ============================================================
// Swagger UI（API仕様書）
// ============================================================
Route::get('/api-docs', [SwaggerController::class, 'index'])->name('api-docs');
Route::get('/api-docs.yaml', [SwaggerController::class, 'yaml'])->name('api-docs.yaml');

Route::get('/', function () {
    return view('welcome');
});

// =========================================================================
// 法的情報ページ（公開アクセス - 認証不要）
// =========================================================================
Route::get('/privacy-policy', function () {
    return view('legal.privacy-policy');
})->name('privacy-policy');

Route::get('/terms-of-service', function () {
    return view('legal.terms-of-service');
})->name('terms-of-service');

// =========================================================================
// ゲストユーザー向けルート（認証前）
// =========================================================================
Route::middleware(['guest'])->group(function () {
    // ユーザー名の非同期バリデーション
    Route::post('/validate/username', ValidateUsernameAction::class)->name('validate.username');
    
    // メールアドレスの非同期バリデーション
    Route::post('/validate/email', ValidateEmailAction::class)->name('validate.email');
    
    // パスワードの非同期バリデーション
    Route::post('/validate/password', ValidatePasswordAction::class)->name('validate.password');
});

// =========================================================================
// 保護者同意（13歳未満新規登録） - 認証不要
// =========================================================================
Route::get('/parent-consent/{token}', [\App\Http\Actions\Legal\ParentConsentAction::class, 'show'])->name('legal.parent-consent');
Route::post('/parent-consent/{token}', [\App\Http\Actions\Legal\ParentConsentAction::class, 'store'])->name('legal.parent-consent.store');

// 保護者同意完了画面（招待リンク表示）
Route::get('/parent-consent-complete/{token}', function (string $token) {
    // トークンからユーザーを取得（セッションがない場合のフォールバック）
    if (!session('child_user')) {
        $user = \App\Models\User::where('parent_invitation_token', $token)
            ->where('is_minor', true)
            ->whereNotNull('parent_consented_at')
            ->first();
        
        if ($user) {
            session(['child_user' => $user]);
        }
    }
    
    return view('legal.parent-consent-complete');
})->name('legal.parent-consent-complete');

// =========================================================================
// 認証済みユーザー向けルート
// =========================================================================
Route::middleware(['auth'])->group(function () {
    // グループ名非同期バリデーション
    Route::post('/validate/group-name', ValidateGroupNameAction::class)->name('validate.group-name');
    
    // メンバー追加時非同期のバリデーション
    Route::post('/validate/member-username', ValidateUsernameAction::class)->name('validate.member.username');
    Route::post('/validate/member-email', ValidateEmailAction::class)->name('validate.member.email');
    Route::post('/validate/member-password', ValidatePasswordAction::class)->name('validate.member.password');

    // =========================================================================
    // 法的同意管理（Phase 6C: 再同意プロセス）
    // =========================================================================
    Route::get('/legal/reconsent', \App\Http\Actions\Legal\ShowReconsentAction::class)->name('legal.reconsent');
    Route::post('/legal/reconsent', \App\Http\Actions\Legal\ReconsentAction::class)->name('legal.reconsent.submit');

    // =========================================================================
    // 本人同意（Phase 6D: 13歳到達時の本人再同意）
    // =========================================================================
    Route::get('/legal/self-consent', \App\Http\Actions\Legal\ShowSelfConsentAction::class)->name('legal.self-consent');
    Route::post('/legal/self-consent', \App\Http\Actions\Legal\SelfConsentAction::class)->name('legal.self-consent.submit');

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
    Route::post('/tasks/bulk-complete', BulkCompleteTasksAction::class)->name('tasks.bulk-complete');

    // タスク無限スクロール用ページネーションAPI（Web版 - セッション認証）
    Route::get('/tasks/paginated', GetTasksPaginatedAction::class)->name('tasks.paginated');

    // タスク承認関連
    Route::post('/tasks/{task}/request-approval', RequestApprovalAction::class)->name('tasks.request-approval');
    Route::post('/tasks/{task}/approve', ApproveTaskAction::class)->name('tasks.approve');
    Route::post('/tasks/{task}/reject', RejectTaskAction::class)->name('tasks.reject');
    Route::get('/tasks/pending-approvals', ListPendingApprovalsAction::class)->name('tasks.pending-approvals');
    Route::patch('/tasks/{task}/update-description', UpdateTaskDescriptionAction::class)->name('tasks.update-description');

    // 画像アップロード
    Route::post('/tasks/{task}/upload-image', UploadTaskImageAction::class)->name('tasks.upload-image');
    Route::delete('/tasks/images/{image}', DeleteTaskImageAction::class)->name('tasks.delete-image');

    // --- グループタスク管理 ---
    Route::get('/group-tasks', ListGroupTasksAction::class)->name('group-tasks.index');
    Route::get('/group-tasks/{group_task_id}/edit', ShowGroupTaskEditFormAction::class)->name('group-tasks.edit');
    Route::put('/group-tasks/{group_task_id}', UpdateGroupTaskAction::class)->name('group-tasks.update');
    Route::delete('/group-tasks/{group_task_id}', DestroyGroupTaskAction::class)->name('group-tasks.destroy');

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
    Route::get('/reports/monthly/{year?}/{month?}', ShowMonthlyReportAction::class)->name('reports.monthly.show');
    Route::post('/reports/monthly/member-summary', GenerateMemberSummaryAction::class)->name('reports.monthly.member-summary');
    Route::post('/reports/monthly/member-summary/pdf', DownloadMemberSummaryPdfAction::class)->name('reports.monthly.member-summary.pdf');

    // --- アカウント管理画面 ---
    Route::prefix('/profile')->group(function () {
        Route::get('/edit', EditProfileAction::class)->name('profile.edit');
        Route::match(['patch', 'post'], '/update', UpdateProfileAction::class)->name('profile.update');
        Route::delete('/delete', DeleteProfileAction::class)->name('profile.destroy');
        
        // パスワード更新（Web版 - Ajax用）
        Route::put('/password', UpdatePasswordWebAction::class)->name('profile.password.update');
        
        // タイムゾーン設定
        Route::get('/timezone', ShowTimezoneSettingAction::class)->name('profile.timezone');
        Route::put('/timezone', UpdateTimezoneAction::class)->name('profile.timezone.update');
        
        // --- グループ管理 ---
        Route::get('/group', EditGroupAction::class)->name('group.edit');
        Route::patch('/group', UpdateGroupAction::class)->name('group.update');
        Route::post('/group/member', AddMemberAction::class)->name('group.member.add');
        Route::patch('/group/member/{member}/permission', UpdateMemberPermissionAction::class)->name('group.member.permission');
        Route::patch('/group/member/{member}/theme', ToggleMemberThemeAction::class)->name('group.member.theme');
        Route::post('/group/transfer/{newMaster}', TransferGroupMasterAction::class)->name('group.master.transfer');
        Route::delete('/group/member/{member}', RemoveMemberAction::class)->name('group.member.remove');
        
        // --- Phase 5-2拡張: 未紐付け子アカウント検索・紐付けリクエスト ---
        Route::post('/group/search-children', SearchUnlinkedChildrenAction::class)->name('profile.group.search-children');
        Route::post('/group/send-link-request', SendChildLinkRequestAction::class)->name('profile.group.send-link-request');
        Route::post('/group/link-children', LinkChildrenAction::class)->name('profile.group.link-children');
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
    
    // Stripe Checkout（都度決済）
    Route::post('/tokens/purchase/checkout', CreateTokenCheckoutSessionAction::class)->name('tokens.purchase.checkout');
    Route::get('/tokens/purchase/success', ShowPurchaseSuccessAction::class)->name('tokens.purchase.success');
    Route::get('/tokens/purchase/cancel', ShowPurchaseCancelAction::class)->name('tokens.purchase.cancel');
    
    // トークン購入リクエスト承認・却下・取り下げ
    Route::post('/tokens/requests/{purchaseRequest}/approve', ApproveTokenPurchaseRequestAction::class)->name('tokens.requests.approve');
    Route::post('/tokens/requests/{purchaseRequest}/reject', RejectTokenPurchaseRequestAction::class)->name('tokens.requests.reject');
    Route::delete('/tokens/requests/{purchaseRequest}/cancel', CancelTokenPurchaseRequestAction::class)->name('tokens.requests.cancel');
    // トークン購入承認待ち一覧（親用）
    Route::get('/tokens/pending-approvals', IndexPendingTokenPurchaseRequestsAction::class)->name('tokens.pending-approvals');
    // トークン履歴
    Route::get('/tokens/history', IndexTokenHistoryAction::class)->name('tokens.history');

    // ========================================
    // サブスクリプション管理
    // ========================================
    // サブスクリプション管理
    // ========================================
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        // プラン選択画面
        Route::get('/', ShowSubscriptionPlansAction::class)->name('index');
        // Checkout Session作成 & Stripe決済ページへリダイレクト
        Route::post('/checkout', CreateCheckoutSessionAction::class)->name('checkout');
        // Stripe決済後の成功画面
        Route::get('/success', SubscriptionSuccessAction::class)->name('success');
        // Stripe決済キャンセル画面
        Route::get('/cancel', SubscriptionCancelAction::class)->name('cancel');
        
        // サブスクリプション管理画面
        Route::get('/manage', ManageSubscriptionAction::class)->name('manage');
        // プラン変更
        Route::post('/update', UpdateSubscriptionAction::class)->name('update');
        // サブスクリプションキャンセル
        Route::post('/cancel-subscription', CancelSubscriptionAction::class)->name('cancel.subscription');
        // Billing Portal へリダイレクト
        Route::get('/billing-portal', BillingPortalAction::class)->name('billing-portal');
    });

    // ========================================   
    // 通知
    // ========================================
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
        
        // 親子紐付け承認・拒否
        Route::post('/{notification}/approve-parent-link', ApproveParentLinkAction::class)->name('approve-parent-link');
        Route::post('/{notification}/reject-parent-link', RejectParentLinkAction::class)->name('reject-parent-link');
    });

    // ========================================
    // 通知 API（非同期・Web画面専用）
    // ========================================
    // 注意: このエンドポイントはWeb画面のポーリング用（セッション認証）
    // モバイルアプリ用は routes/api.php に別途定義（Sanctum認証: /api/notifications/unread-count）
    // 経緯: 2025-12-07 Phase 2.B-5 Step 2実装時、認証方式の違いにより分離
    // - Web: セッション認証 + CSRF保護 (/notifications/unread-count)
    // - Mobile: Sanctum認証（トークンベース） (/api/notifications/unread-count)
    Route::get('/notifications/unread-count', GetUnreadCountAction::class)->name('web.notifications.unread-count');
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

    // ポータルサイト管理
    Route::prefix('portal')->name('portal.')->group(function () {
        // メンテナンス情報管理
        Route::prefix('maintenances')->name('maintenances.')->group(function () {
            Route::get('/', IndexMaintenanceAction::class)->name('index');
            Route::get('/create', CreateMaintenanceAction::class)->name('create');
            Route::post('/', StoreMaintenanceAction::class)->name('store');
            Route::get('/{maintenance}/edit', EditMaintenanceAction::class)->name('edit');
            Route::put('/{maintenance}', UpdateMaintenanceAction::class)->name('update');
            Route::delete('/{maintenance}', DeleteMaintenanceAction::class)->name('destroy');
            Route::patch('/{maintenance}/status', UpdateMaintenanceStatusAction::class)->name('status.update');
        });

        // お問い合わせ管理
        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/', IndexContactAction::class)->name('index');
            Route::get('/{contact}', ShowContactAction::class)->name('show');
            Route::patch('/{contact}/status', UpdateContactStatusAction::class)->name('update-status');
        });

        // FAQ管理
        Route::prefix('faqs')->name('faqs.')->group(function () {
            Route::get('/', IndexFaqAction::class)->name('index');
            Route::get('/create', CreateFaqAction::class)->name('create');
            Route::post('/', StoreFaqAction::class)->name('store');
            Route::get('/{faq}/edit', EditFaqAction::class)->name('edit');
            Route::put('/{faq}', UpdateFaqAction::class)->name('update');
            Route::delete('/{faq}', DeleteFaqAction::class)->name('destroy');
            Route::patch('/{faq}/toggle-published', ToggleFaqPublishedAction::class)->name('toggle-published');
        });

        // アプリ更新履歴管理
        Route::prefix('updates')->name('updates.')->group(function () {
            Route::get('/', IndexAppUpdateAction::class)->name('index');
            Route::get('/create', CreateAppUpdateAction::class)->name('create');
            Route::post('/', StoreAppUpdateAction::class)->name('store');
            Route::get('/{update}/edit', EditAppUpdateAction::class)->name('edit');
            Route::put('/{update}', UpdateAppUpdateAction::class)->name('update');
            Route::delete('/{update}', DeleteAppUpdateAction::class)->name('destroy');
        });
    });
});

// ========================================
// Stripe Webhook
// ========================================
Route::post(
    '/stripe/webhook',
    \App\Http\Actions\Token\HandleStripeWebhookAction::class
)->name('stripe.webhook');

// ========================================
// ポータルサイト（全ユーザーアクセス可能）
// ========================================
Route::prefix('portal')->name('portal.')->group(function () {
    // ポータルトップ
    Route::get('/', \App\Http\Actions\Portal\ShowPortalHomeAction::class)->name('home');
    
    // メンテナンス情報
    Route::get('/maintenance', \App\Http\Actions\Portal\ShowMaintenanceAction::class)->name('maintenance');
    
    // お問い合わせ
    Route::get('/contact', \App\Http\Actions\Portal\ShowContactAction::class)->name('contact');
    Route::post('/contact', \App\Http\Actions\Portal\StoreContactAction::class)->name('contact.store')->middleware('throttle:10,1');
    
    // FAQ
    Route::get('/faq', \App\Http\Actions\Portal\ShowFaqAction::class)->name('faq');
    
    // 更新履歴
    Route::get('/updates', \App\Http\Actions\Portal\ShowUpdatesAction::class)->name('updates');
    
    // 使い方ガイド
    Route::prefix('guide')->name('guide.')->group(function () {
        Route::get('/', \App\Http\Actions\Portal\Guide\ShowGuideIndexAction::class)->name('index');
        Route::get('/getting-started', \App\Http\Actions\Portal\Guide\ShowGettingStartedAction::class)->name('getting-started');
    });
    
    // 機能紹介
    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/', \App\Http\Actions\Portal\Features\ShowFeaturesIndexAction::class)->name('index');
        Route::get('/ai-decomposition', \App\Http\Actions\Portal\Features\ShowAiDecompositionAction::class)->name('ai-decomposition');
        Route::get('/avatar', \App\Http\Actions\Portal\Features\ShowAvatarAction::class)->name('avatar');
        Route::get('/group-tasks', \App\Http\Actions\Portal\Features\ShowGroupTasksAction::class)->name('group-tasks');
        Route::get('/auto-schedule', \App\Http\Actions\Portal\Features\ShowAutoScheduleAction::class)->name('auto-schedule');
        Route::get('/monthly-report', \App\Http\Actions\Portal\Features\ShowMonthlyReportAction::class)->name('monthly-report');
        Route::get('/pricing', \App\Http\Actions\Portal\Features\ShowPricingAction::class)->name('pricing');
    });
});

// =========================================================================
// 管理者認証ルート (Stripe要件対応)
// =========================================================================

Route::prefix('admin')->name('admin.')->group(function () {
    // ログイン（認証不要）
    Route::get('login', [AdminLoginAction::class, 'create'])->name('login.form');
    Route::post('login', AdminLoginAction::class)->name('login');
    
    // 管理者エリア（認証必須 + IP制限 + Basic認証）
    Route::middleware(['auth', 'admin', \App\Http\Middleware\AdminIpRestriction::class])->group(function () {
        Route::get('dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');
        
        // 既存の管理者ルートをここに移動予定
    });
});

// =========================================================================
// 認証関連ルート (Breeze)
// =========================================================================

require __DIR__.'/auth.php'; // Breezeが生成するログイン/ログアウト/新規登録ルート