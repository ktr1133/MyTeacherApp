<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 旧通知テーブルの削除マイグレーション
 * 
 * 中間テーブル方式への移行に伴い、既存の notifications テーブルを削除。
 * ※ 実行前に既存データのバックアップを取ること！
 * 
 * @package App\Database\Migrations
 */
return new class extends Migration
{
    /**
     * マイグレーション実行
     *
     * @return void
     */
    public function up(): void
    {
        // 既存データのバックアップが必要な場合は、事前に実施すること
        Schema::dropIfExists('notifications');
    }

    /**
     * マイグレーションのロールバック
     *
     * @return void
     */
    public function down(): void
    {
        // ロールバック時は元の構造で再作成
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
};