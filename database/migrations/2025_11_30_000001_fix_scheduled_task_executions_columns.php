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
        Schema::table('scheduled_task_executions', function (Blueprint $table) {
            // 既存のカラムをリネーム・削除
            if (Schema::hasColumn('scheduled_task_executions', 'task_id')) {
                $table->renameColumn('task_id', 'created_task_id');
            }
            
            // 不要なカラムを削除
            if (Schema::hasColumn('scheduled_task_executions', 'assigned_user_id')) {
                $table->dropForeign(['assigned_user_id']);
                $table->dropColumn('assigned_user_id');
            }
            
            if (Schema::hasColumn('scheduled_task_executions', 'skip_reason')) {
                $table->dropColumn('skip_reason');
            }
            
            // 新しいカラムを追加
            if (!Schema::hasColumn('scheduled_task_executions', 'deleted_task_id')) {
                $table->foreignId('deleted_task_id')->nullable()->after('created_task_id')->constrained('tasks')->onDelete('set null')->comment('削除されたタスクID');
            }
            
            if (!Schema::hasColumn('scheduled_task_executions', 'note')) {
                $table->text('note')->nullable()->after('status')->comment('備考');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_task_executions', function (Blueprint $table) {
            if (Schema::hasColumn('scheduled_task_executions', 'created_task_id')) {
                $table->renameColumn('created_task_id', 'task_id');
            }
            
            if (Schema::hasColumn('scheduled_task_executions', 'deleted_task_id')) {
                $table->dropForeign(['deleted_task_id']);
                $table->dropColumn('deleted_task_id');
            }
            
            if (Schema::hasColumn('scheduled_task_executions', 'note')) {
                $table->dropColumn('note');
            }
            
            if (!Schema::hasColumn('scheduled_task_executions', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('割り当てられたユーザーID');
            }
            
            if (!Schema::hasColumn('scheduled_task_executions', 'skip_reason')) {
                $table->text('skip_reason')->nullable()->comment('スキップ理由');
            }
        });
    }
};
