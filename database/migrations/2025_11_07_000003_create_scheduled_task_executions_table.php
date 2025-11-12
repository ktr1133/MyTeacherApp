<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_task_id')
                ->constrained('scheduled_group_tasks') // テーブル名を明示的に指定
                ->onDelete('cascade');
            
            $table->timestamp('executed_at');
            $table->enum('status', array_keys(config('const.schedule_task_execution_statuses')))->default('success')->comment('実行ステータス');

            // 作成されたタスク情報
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // エラー・スキップ情報
            $table->text('error_message')->nullable();
            $table->text('skip_reason')->nullable();
            
            $table->timestamps();
            
            // インデックス
            $table->index(['scheduled_task_id', 'executed_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_executions');
    }
};