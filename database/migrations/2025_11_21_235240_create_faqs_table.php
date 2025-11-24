<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->comment('FAQカテゴリ');
            $table->string('app_name', 20)->comment('対象アプリ名');
            $table->text('question')->comment('質問');
            $table->text('answer')->comment('回答');
            $table->integer('display_order')->default(0)->comment('表示順序');
            $table->boolean('is_published')->default(true)->comment('公開フラグ');
            $table->timestamps();
            
            $table->index('category');
            $table->index('app_name');
            $table->index('is_published');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
