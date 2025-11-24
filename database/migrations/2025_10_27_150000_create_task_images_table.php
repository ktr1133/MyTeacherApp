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
        Schema::create('task_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->comment('タスクID');
            $table->string('file_path')->comment('画像ファイルパス');
            $table->timestamp('approved_at')->nullable()->comment('承認日時');
            $table->timestamp('delete_at')->nullable()->comment('削除予定日時（承認後3日）');
            $table->timestamps();
            
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_images');
    }
};