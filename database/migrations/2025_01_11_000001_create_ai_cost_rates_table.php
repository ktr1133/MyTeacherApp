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
            $table->string('service_type', 50)->comment('AIサービス名');
            // サービス詳細（画像サイズ、品質、入出力種別など）
            $table->string('service_detail', 100)->nullable()->comment('1024x1024_standard, input, output等');
            // 画像サイズ
            $table->string('image_size', 20)->nullable()->comment('画像サイズ');
            // 単位あたりの実際のコスト（USD）
            $table->decimal('unit_cost_usd', 10, 6)->comment('単位あたりのUSDコスト');
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
            
            $table->index(['service_type', 'is_active'], 'idx_service');
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