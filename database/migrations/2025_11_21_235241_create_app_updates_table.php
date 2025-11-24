<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_updates', function (Blueprint $table) {
            $table->id();
            $table->string('app_name', 20)->comment('アプリ名');
            $table->string('version', 50)->comment('バージョン番号');
            $table->string('title')->comment('更新タイトル');
            $table->text('description')->comment('更新内容の説明');
            $table->json('changes')->nullable()->comment('変更内容のリスト');
            $table->timestamp('released_at')->comment('リリース日時');
            $table->boolean('is_major')->default(false)->comment('メジャーアップデートかどうか');
            $table->timestamps();
            
            $table->unique(['app_name', 'version']);
            $table->index('app_name');
            $table->index('released_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_updates');
    }
};
