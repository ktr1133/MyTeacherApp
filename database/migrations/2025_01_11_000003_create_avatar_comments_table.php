<?php
// filepath: /home/ktr/mtdev/laravel/database/migrations/2025_01_11_000003_create_avatar_comments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatar_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_avatar_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', array_keys(config('const.avatar_events')))->comment('アバターイベント種別');
            $table->text('comment_text')->comment('アバターコメント');
            $table->timestamps();
            
            $table->unique(['teacher_avatar_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_comments');
    }
};