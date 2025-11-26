<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Actions\Task\ProposeTaskAction;

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

    // ★ 今後の新規API追加はこちらに
    // Route::get('/tasks', ...)->name('api.v1.tasks.index');
    // Route::post('/tasks', ...)->name('api.v1.tasks.store');
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

// レガシーAPI（Sanctum認証 - Phase 2以降削除予定）
Route::prefix('api')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/user', function () {
            return auth()->user();
        });

        Route::post('/tasks/propose', ProposeTaskAction::class)->name('api.tasks.propose');
    });
});
