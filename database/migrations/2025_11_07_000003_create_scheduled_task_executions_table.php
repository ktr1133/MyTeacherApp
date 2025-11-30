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
                ->constrained('scheduled_group_tasks')
                ->onDelete('cascade')
                ->comment('スケジュールタスクID');
            
            $table->timestamp('executed_at')->comment('実行日時');
            $table->enum('status', array_keys(config('const.schedule_task_execution_statuses')))->default('success')->comment('実行ステータス'); // success, failed, skipped

            $table->foreignId('created_task_id')->nullable()->constrained('tasks')->onDelete('set null')->comment('作成されたタスクID');
            $table->foreignId('deleted_task_id')->nullable()->constrained('tasks')->onDelete('set null')->comment('削除されたタスクID');
            
            $table->text('note')->nullable()->comment('備考');
            $table->text('error_message')->nullable()->comment('エラーメッセージ');
            
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