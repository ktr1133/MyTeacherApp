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
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->string('year_month', 7); // YYYY-MM形式
            $table->json('data'); // レポートデータ（統計・グラフデータ）
            $table->string('pdf_path')->nullable(); // PDF保存パス
            $table->timestamps();
            
            // ユニーク制約: グループ×年月で1レコードのみ
            $table->unique(['group_id', 'year_month']);
            
            // インデックス: 検索高速化
            $table->index(['group_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
