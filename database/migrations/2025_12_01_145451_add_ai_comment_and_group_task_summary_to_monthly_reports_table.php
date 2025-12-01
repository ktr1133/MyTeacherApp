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
        Schema::table('monthly_reports', function (Blueprint $table) {
            // AIコメント関連カラム追加
            $table->text('ai_comment')->nullable()->after('group_task_details')->comment('OpenAI生成コメント');
            $table->integer('ai_comment_tokens_used')->default(0)->after('ai_comment')->comment('消費トークン数');
            
            // グループタスクサマリー追加（メンバー別集計）
            $table->json('group_task_summary')->nullable()->after('ai_comment_tokens_used')->comment('メンバー別グループタスク集計 {user_id: {name, completed_count, reward, tasks: [{title, reward, completed_at, tags}]}}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropColumn(['ai_comment', 'ai_comment_tokens_used', 'group_task_summary']);
        });
    }
};
