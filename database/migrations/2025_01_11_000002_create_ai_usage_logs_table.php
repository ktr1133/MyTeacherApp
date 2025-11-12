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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            
            // 関連モデル（TeacherAvatar, Task等）
            $table->string('usable_type');
            $table->unsignedBigInteger('usable_id');
            
            // ユーザー
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // サービス種別
            $table->string('service_type', 50);
            $table->string('service_detail', 100)->nullable();
            
            // 使用量
            $table->decimal('units_used', 10, 2)->comment('画像枚数 or トークン数（千単位）');
            
            // コスト
            $table->decimal('cost_usd', 10, 6)->comment('実際のUSDコスト');
            $table->integer('token_cost')->comment('トークン換算コスト');
            
            // 換算レートID（参照用）
            $table->foreignId('cost_rate_id')->nullable()->constrained('ai_cost_rates')->onDelete('set null');
            
            // リクエスト・レスポンス情報
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['usable_type', 'usable_id'], 'idx_usable');
            $table->index(['user_id', 'created_at'], 'idx_user');
            $table->index('service_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};