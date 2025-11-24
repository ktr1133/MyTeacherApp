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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('メンテナンスタイトル');
            $table->text('description')->comment('メンテナンス内容');
            $table->timestamp('scheduled_at')->comment('予定日時');
            $table->timestamp('started_at')->nullable()->comment('開始日時');
            $table->timestamp('completed_at')->nullable()->comment('完了日時');
            $table->string('status', 50)->default('scheduled')->comment('ステータス: scheduled, in_progress, completed, cancelled');
            $table->json('affected_apps')->comment('影響を受けるアプリのリスト');
            $table->unsignedBigInteger('created_by')->comment('作成者のユーザーID');
            $table->timestamps();
            
            $table->index('scheduled_at');
            $table->index('status');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
        
        DB::statement("ALTER TABLE maintenances ADD CONSTRAINT check_status CHECK (status IN ('scheduled', 'in_progress', 'completed', 'cancelled'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
