<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ユーザー通知中間テーブルのマイグレーション
 * 
 * 通知テンプレートとユーザーの多対多リレーションを管理。
 * ユーザーごとの既読状態を保存する。
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
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            
            // ユーザー
            $table->foreignId('user_id')
                ->comment('通知を受け取るユーザーのID')
                ->constrained()
                ->cascadeOnDelete();
            
            // 通知テンプレート
            $table->foreignId('notification_template_id')
                ->comment('通知テンプレートのID')
                ->constrained()
                ->cascadeOnDelete();
            
            // 既読フラグ
            $table->boolean('is_read')
                ->default(false)
                ->comment('既読フラグ'); // false=未読, true=既読
            $table->timestamp('read_at')
                ->nullable()
                ->comment('既読日時');
            
            $table->timestamps();
            
            // インデックス
            $table->unique(['user_id', 'notification_template_id'], 'idx_user_template_unique');
            $table->index(['user_id', 'is_read', 'created_at'], 'idx_user_read_created');
        });
    }

    /**
     * マイグレーションのロールバック
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};