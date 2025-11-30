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
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->comment('グループID');
            $table->date('report_month')->comment('レポート対象月（YYYY-MM-01形式）');
            $table->timestamp('generated_at')->nullable()->comment('レポート生成日時');
            
            // メンバー別通常タスク集計（JSON）
            $table->json('member_task_summary')->nullable()->comment('メンバー別タスク集計 {user_id: {completed_count, tasks: [{title, completed_at}]}}');
            
            // グループタスク集計
            $table->integer('group_task_completed_count')->default(0)->comment('グループタスク完了件数');
            $table->integer('group_task_total_reward')->default(0)->comment('グループタスク獲得報酬合計');
            $table->json('group_task_details')->nullable()->comment('グループタスク完了内訳 [{task_id, title, reward, completed_at}]');
            
            // 前月比
            $table->integer('normal_task_count_previous_month')->default(0)->comment('前月の通常タスク完了件数');
            $table->integer('group_task_count_previous_month')->default(0)->comment('前月のグループタスク完了件数');
            $table->integer('reward_previous_month')->default(0)->comment('前月の獲得報酬');
            
            // レポートファイル
            $table->string('pdf_path')->nullable()->comment('PDFファイルパス（S3）');
            
            $table->timestamps();
            
            // 外部キー・インデックス
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->unique(['group_id', 'report_month']);
            $table->index('report_month');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
