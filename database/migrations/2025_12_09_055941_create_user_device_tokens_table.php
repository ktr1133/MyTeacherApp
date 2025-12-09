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
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('ユーザーID');
            $table->string('device_token', 255)->unique()->comment('FCMトークン');
            $table->enum('device_type', ['ios', 'android'])->comment('デバイス種別');
            $table->string('device_name', 100)->nullable()->comment('デバイス名（例: iPhone 15 Pro）');
            $table->string('app_version', 20)->nullable()->comment('アプリバージョン（例: 1.0.0）');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamp('last_used_at')->nullable()->comment('最終使用日時');
            $table->timestamps();

            // インデックス
            $table->index(['user_id', 'is_active'], 'idx_user_device_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
