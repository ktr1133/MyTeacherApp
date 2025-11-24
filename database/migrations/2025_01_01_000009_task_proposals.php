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

            $table->string('original_task_text')->comment('ユーザーが入力した元のタスク');
            $table->text('proposal_context')->comment('分割を依頼した際のプロンプト/観点');
            $table->jsonb('proposed_tasks_json')->comment('AIが提案した全分割タスクのJSON配列');

            $table->string('model_used')->comment('使用したAIモデル名');
            
            // トークン使用量
            $table->integer('prompt_tokens')->default(0)->comment('プロンプトトークン数');
            $table->integer('completion_tokens')->default(0)->comment('完了トークン数');
            $table->integer('total_tokens')->default(0)->comment('総トークン数');
            
            $table->jsonb('adopted_proposed_tasks_json')->nullable()->comment('ユーザが採用したタスク群のJSON配列');
            $table->boolean('was_adopted')->default(false)->comment('ユーザーが採用したか');

            $table->timestamps();
        });        
    }

    /**t
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_proposals');
    }
};