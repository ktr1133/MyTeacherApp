<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Actions\Task\ProposeTaskAction;

Route::prefix('api')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/user', function () {
            return auth()->user();
        });

        Route::post('/tasks/propose', ProposeTaskAction::class)->name('api.tasks.propose');
    });
});
