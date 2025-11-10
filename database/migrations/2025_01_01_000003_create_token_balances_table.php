<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * トークン残高管理テーブル
 * ユーザーまたはグループのトークン残高を管理
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_balances', function (Blueprint $table) {
            $table->id();
            
            // トークン所有者（Polymorphic: User or Group）
            $table->morphs('tokenable');
            
            // トークン残高
            $table->bigInteger('balance')->default(0)->comment('総残高');
            $table->bigInteger('free_balance')->default(0)->comment('無料枠残高');
            $table->bigInteger('paid_balance')->default(0)->comment('有料購入分残高');
            
            // 無料枠リセット日（月次）
            $table->timestamp('free_balance_reset_at')->nullable()->comment('無料枠リセット日時');
            
            // 統計情報
            $table->bigInteger('total_consumed')->default(0)->comment('累計消費量');
            $table->bigInteger('monthly_consumed')->default(0)->comment('月次消費量');
            $table->timestamp('monthly_consumed_reset_at')->nullable()->comment('月次消費量リセット日時');

            $table->timestamp('deleted_at')->nullable(); // 削除予定日時
            
            $table->timestamps();
            $table->softDeletes();
            
            // インデックス
            $table->unique(['tokenable_type', 'tokenable_id']);
            $table->index('balance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_balances');
    }
};