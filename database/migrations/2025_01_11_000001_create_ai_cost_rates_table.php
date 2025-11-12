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
        Schema::create('ai_cost_rates', function (Blueprint $table) {
            $table->id();
            
            // サービス種別
            $table->string('service_type', 50)->comment('dalle3, chat_gpt4等');
            
            // サービス詳細（画像サイズ、品質、入出力種別など）
            $table->string('service_detail', 100)->nullable()->comment('1024x1024_standard, input, output等');
            
            // 単位あたりの実際のコスト（USD）
            $table->decimal('unit_cost_usd', 10, 6)->comment('1画像 or 1,000トークンあたりのUSDコスト');
            
            // トークン換算レート（単位あたり）
            $table->integer('token_conversion_rate')->comment('単位あたりのトークン消費量');
            
            // 有効/無効
            $table->boolean('is_active')->default(true);
            
            // 適用開始日
            $table->timestamp('effective_from')->nullable();
            
            // メモ
            $table->text('note')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['service_type', 'service_detail'], 'idx_service');
            $table->index(['is_active', 'effective_from'], 'idx_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_cost_rates');
    }
};