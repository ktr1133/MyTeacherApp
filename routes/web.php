<?php

use App\Http\Actions\Profile\EditProfileAction;
use App\Http\Actions\Profile\UpdateProfileAction;
use App\Http\Actions\Profile\DeleteProfileAction;
use App\Http\Actions\Profile\Group\EditGroupAction;
use App\Http\Actions\Profile\Group\UpdateGroupAction;
use App\Http\Actions\Profile\Group\AddMemberAction;
use App\Http\Actions\Profile\Group\UpdateMemberPermissionAction;
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
use App\Http\Actions\Task\CreateTaskAction;
use App\Http\Actions\Task\DestroyTaskAction;
use App\Http\Actions\Task\IndexTaskAction;
use App\Http\Actions\Task\ProposeTaskAction;
use App\Http\Actions\Task\SearchTasksAction;
use App\Http\Actions\Task\StoreTaskAction;
use App\Http\Actions\Task\UpdateTaskAction;
use App\Http\Actions\Task\ApproveTaskAction;
use App\Http\Actions\Task\RejectTaskAction;
use App\Http\Actions\Task\RequestApprovalAction;
use App\Http\Actions\Task\ListPendingApprovalsAction;
use App\Http\Actions\Task\UploadTaskImageAction;
use App\Http\Actions\Task\DeleteTaskImageAction;

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
// 認証済みユーザー向けルート
// =========================================================================

Route::middleware(['auth'])->group(function () {

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

    // タスク承認関連
    Route::post('/tasks/{task}/request-approval', RequestApprovalAction::class)->name('tasks.request-approval');
    Route::post('/tasks/{task}/approve', ApproveTaskAction::class)->name('tasks.approve');
    Route::post('/tasks/{task}/reject', RejectTaskAction::class)->name('tasks.reject');
    Route::get('/tasks/pending-approvals', ListPendingApprovalsAction::class)->name('tasks.pending-approvals');

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
        Route::post('/group', UpdateGroupAction::class)->name('group.update');
        Route::post('/group/member', AddMemberAction::class)->name('group.member.add');
        Route::patch('/group/member/{member}', UpdateMemberPermissionAction::class)->name('group.member.permission');
        Route::post('/group/transfer/{newMaster}', TransferGroupMasterAction::class)->name('group.master.transfer');
        Route::delete('/group/member/{member}', RemoveMemberAction::class)->name('group.member.remove');
    });

    // --- その他のタスク操作 (更新、削除など) ---
    // 例: タスクの完了状態トグル
    Route::patch('/tasks/{task}/toggle', App\Http\Actions\Task\ToggleTaskCompletionAction::class)->name('tasks.toggle');
});

// =========================================================================
// 認証関連ルート (Breeze)
// =========================================================================

require __DIR__.'/auth.php'; // Breezeが生成するログイン/ログアウト/新規登録ルート