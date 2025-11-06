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
        Schema::create('task_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('original_task_text'); // ユーザーが入力した元のタスク
            $table->text('proposal_context');    // 分割を依頼した際のプロンプト/観点
            $table->jsonb('proposed_tasks_json'); // AIが提案した全分割タスクのJSON配列

            $table->string('model_used');        // 使用したAIモデル名
            $table->jsonb('adopted_proposed_tasks_json')->default(false); // ユーザが採用したタスク群のJSON配列
            $table->boolean('was_adopted')->default(false); // ユーザー

            $table->timestamps();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // 外部キーを削除してからテーブルを削除
            $table->dropForeign(['source_proposal_id']);
        });
        Schema::dropIfExists('task_proposals');
    }
};