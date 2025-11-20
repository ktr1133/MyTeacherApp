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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // 外部キー用カラム
            $table->unsignedBigInteger('source_proposal_id')->nullable();
            $table->unsignedBigInteger('assigned_by_user_id')->nullable(); // タスクを割り当てたユーザーID
            $table->unsignedBigInteger('approved_by_user_id')->nullable(); // 承認者のユーザーID

            // タスク基本情報
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('due_date')->nullable();
            $table->integer('span')->nullable();
            $table->smallInteger('priority')->default(3);

            // グループタスク関連のカラム
            $table->uuid('group_task_id')->nullable()->index(); // グループタスク共通識別子（UUID）
            $table->integer('reward')->nullable(); // 報酬額
            $table->boolean('requires_approval')->default(false); // 承認が必要かどうか
            $table->boolean('requires_image')->default(false); // 画像必須フラグ
            $table->timestamp('approved_at')->nullable(); // 承認日時

            // 完了状態
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 外部キー制約
            $table->foreign('source_proposal_id')
                  ->references('id')
                  ->on('task_proposals')
                  ->onDelete('set null');
                  
            $table->foreign('assigned_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('approved_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};