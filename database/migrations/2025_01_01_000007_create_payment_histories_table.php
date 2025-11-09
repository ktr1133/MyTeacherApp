<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 課金履歴テーブル（1年間保持）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            
            // 課金者（Polymorphic: User or Group）
            $table->morphs('payable');
            
            // Stripe情報
            $table->string('stripe_payment_intent_id')->unique()->index();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            
            // 課金内容
            $table->foreignId('token_package_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('amount')->comment('支払額（円）');
            $table->bigInteger('token_amount')->comment('購入トークン数');
            
            // ステータス
            $table->enum('status', [
                'pending',    // 処理中
                'succeeded',  // 成功
                'failed',     // 失敗
                'refunded',   // 返金済み
            ])->default('pending');
            
            // 支払い方法
            $table->string('payment_method_type')->nullable();
            $table->string('payment_method_last4', 4)->nullable();
            
            // メタデータ
            $table->json('stripe_metadata')->nullable();
            $table->text('failure_message')->nullable();
            
            // 返金情報
            $table->integer('refund_amount')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // インデックス
            $table->index(['payable_type', 'payable_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};