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
        Schema::create('task_executions', function (Blueprint $table) {
            $table->id();
            // タスクが論理削除されても実績は残すため、cascadeOnDeleteは不要（論理削除はDBレベルで追跡しない）
            $table->foreignId('task_id')->constrained('tasks'); 
            $table->foreignId('proposal_id')->constrained('task_proposals')->cascadeOnDelete();

            $table->integer('estimated_effort_minutes')->nullable();
            $table->integer('actual_effort_minutes')->nullable();
            
            $table->string('completion_status')->default('pending'); // completed, abandoned, partial
            $table->boolean('is_high_quality')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_executions');
    }
};