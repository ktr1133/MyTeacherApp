<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * アプリ内通知テーブル
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // 通知先ユーザー
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // 通知種別
            $table->enum('type', [
                'token_low',           // トークン残高低下
                'token_depleted',      // トークン残高ゼロ
                'payment_success',     // 課金成功
                'payment_failed',      // 課金失敗
                'group_task_created',  // グループタスク作成（将来）
                'group_task_assigned', // グループタスク割当（将来）
            ]);
            
            // 通知内容
            $table->string('title');
            $table->text('message');
            
            // 追加データ（JSON）
            $table->json('data')->nullable();
            
            // アクション
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            
            // 既読フラグ
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            $table->timestamps();
            
            // インデックス
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};