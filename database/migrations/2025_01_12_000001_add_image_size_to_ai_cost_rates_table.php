<?php
// filepath: /home/ktr/mtdev/laravel/database/migrations/2025_01_12_000001_add_image_size_to_ai_cost_rates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_cost_rates テーブルに image_size カラムを追加
 */
class AddImageSizeToAiCostRatesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_cost_rates', function (Blueprint $table) {
            $table->string('image_size', 20)->nullable()->after('service_detail')->comment('画像サイズ (例: 512x512, 1024x1024)');
            
            // インデックス追加（検索最適化）
            $table->index(['service_type', 'image_size', 'is_active'], 'idx_service_image_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_cost_rates', function (Blueprint $table) {
            $table->dropIndex('idx_service_image_active');
            $table->dropColumn('image_size');
        });
    }
}