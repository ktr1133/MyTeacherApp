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
            $table->foreignId('task_id')->constrained('tasks')->comment('タスクID');
            $table->foreignId('proposal_id')->constrained('task_proposals')->cascadeOnDelete()->comment('タスク提案ID');

            $table->integer('estimated_effort_minutes')->nullable()->comment('予想所要時間（分）');
            $table->integer('actual_effort_minutes')->nullable()->comment('実際の所要時間（分）');
            
            $table->string('completion_status')->default('pending')->comment('完了ステータス'); // pending, completed, abandoned, partial
            $table->boolean('is_high_quality')->nullable()->comment('高品質フラグ');

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