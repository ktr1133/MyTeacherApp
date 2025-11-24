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
        Schema::create('scheduled_task_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_task_id')->constrained('scheduled_group_tasks')->onDelete('cascade')->comment('スケジュールタスクID');
            $table->string('tag_name')->comment('タグ名');
            $table->timestamps();
            
            $table->unique(['scheduled_task_id', 'tag_name']);
            $table->index('scheduled_task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_tags');
    }
};