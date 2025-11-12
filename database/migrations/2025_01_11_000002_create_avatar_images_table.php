<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatar_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_avatar_id')->constrained()->onDelete('cascade');
            $table->enum('image_type', array_keys(config('const.avatar_image_types')))->comment('アバター画像種別');
            $table->enum('expression_type', array_keys(config('const.avatar_expressions')))->comment('アバター表情種別');
            $table->string('s3_path');
            $table->string('s3_url');
            $table->timestamps();
            
            $table->unique(['teacher_avatar_id', 'image_type', 'expression_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_images');
    }
};