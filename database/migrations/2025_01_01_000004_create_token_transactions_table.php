<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * トークン取引履歴テーブル
 * トークンの消費・購入・調整履歴を記録
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            
            // トークン所有者（Polymorphic）
            $table->morphs('tokenable');
            
            // 実行ユーザー（個人の場合は自分、グループの場合はメンバー）
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // トランザクション種別
            $table->enum('type', array_keys(config('const.token_transaction_types')))->comment('トランザクション種別');
            
            // トークン量（消費はマイナス、購入はプラス）
            $table->bigInteger('amount');
            
            // 残高（トランザクション後）
            $table->bigInteger('balance_after');
            
            // 消費理由・購入理由
            $table->string('reason')->nullable()->comment('task_decomposition, teacher_avatar等');
            
            // 関連情報（Polymorphic: Task, TaskProposal等）
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            
            // Stripe関連（購入の場合）
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->json('stripe_metadata')->nullable();
            
            // 管理者メモ（admin_adjustの場合）
            $table->text('admin_note')->nullable();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // インデックス
            $table->index(['related_type', 'related_id']);
            $table->index(['tokenable_type', 'tokenable_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_transactions');
    }
};