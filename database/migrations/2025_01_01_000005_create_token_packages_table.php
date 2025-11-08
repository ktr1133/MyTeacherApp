<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * トークン商品マスタテーブル
 * 販売するトークンパッケージを管理
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_packages', function (Blueprint $table) {
            $table->id();
            
            // 商品情報
            $table->string('name')->comment('商品名: 1Mトークン等');
            $table->text('description')->nullable();
            $table->bigInteger('token_amount')->comment('トークン数');
            $table->integer('price')->comment('価格（円）');
            
            // Stripe商品ID
            $table->string('stripe_price_id')->nullable()->index();
            $table->string('stripe_product_id')->nullable();
            
            // 表示順序・有効フラグ
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            
            // 買い切りプラン対応（将来用）
            $table->boolean('is_subscription')->default(false)->comment('サブスクか買い切りか');
            $table->json('features')->nullable()->comment('プラン特徴リスト');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_packages');
    }
};