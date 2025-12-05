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
        Schema::table('tasks', function (Blueprint $table) {
            // タスク一覧画面用の複合インデックス（最頻出クエリ: user_id + is_completed + due_date + priority）
            // WHERE user_id = ? AND is_completed = false ORDER BY due_date, priority
            $table->index(['user_id', 'is_completed', 'due_date', 'priority'], 'idx_tasks_user_incomplete_due');
            
            // ソフトデリート対応（削除済みタスクを除外するクエリ用）
            // WHERE user_id = ? AND deleted_at IS NULL
            $table->index(['user_id', 'deleted_at'], 'idx_tasks_user_deleted');
            
            // グループタスク検索用（グループIDとユーザーIDの組み合わせ）
            // WHERE group_task_id = ? AND user_id = ?
            $table->index(['group_task_id', 'user_id'], 'idx_tasks_group_user');
            
            // 完了タスク検索用（実績画面等で使用）
            // WHERE user_id = ? AND is_completed = true ORDER BY completed_at
            $table->index(['user_id', 'is_completed', 'completed_at'], 'idx_tasks_user_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // インデックス削除（逆順で実行）
            $table->dropIndex('idx_tasks_user_completed');
            $table->dropIndex('idx_tasks_group_user');
            $table->dropIndex('idx_tasks_user_deleted');
            $table->dropIndex('idx_tasks_user_incomplete_due');
        });
    }
};
