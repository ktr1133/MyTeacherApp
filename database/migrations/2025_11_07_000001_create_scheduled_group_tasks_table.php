<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_group_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // タスク情報
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('requires_image')->default(false);
            $table->integer('reward')->default(0);
            
            // 担当者設定
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('auto_assign')->default(false)->comment('ランダム割り当て');
            
            // スケジュール設定（JSON配列）
            $table->json('schedules')->comment('スケジュール設定の配列');
            
            // 有効期限設定
            $table->integer('due_duration_days')->nullable()->comment('作成日から何日後が期限か');
            $table->integer('due_duration_hours')->nullable()->comment('作成日から何時間後が期限か');
            
            // 期間設定
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            // 祝日設定
            $table->boolean('skip_holidays')->default(false)->comment('祝日をスキップ');
            $table->boolean('move_to_next_business_day')->default(false)->comment('祝日の場合翌営業日に実行');
            
            // 前回タスク未完了時の処理
            $table->boolean('delete_incomplete_previous')->default(true)->comment('前回タスク未完了時に削除');
            
            // タグ
            $table->json('tags')->nullable()->comment('タグの配列');
            
            // 状態管理
            $table->boolean('is_active')->default(true);
            $table->timestamp('paused_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // インデックス
            $table->index(['group_id', 'is_active']);
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_group_tasks');
    }
};