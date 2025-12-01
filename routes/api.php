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

// レガシーAPI（Sanctum認証 - テスト用に一時的に維持）
Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
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

    Route::post('/tasks/propose', ProposeTaskAction::class)->name('api.tasks.propose');
});
