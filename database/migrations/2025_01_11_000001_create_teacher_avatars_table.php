<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_avatars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
            
            // 生成用シード値
            $table->bigInteger('seed')->comment('アバター生成シード値');
            
            // 外見設定
            $table->enum('sex', array_keys(config('const.avatar_sex_types')))->comment('性別');
            $table->string('hair_color', 50)->comment('髪の色');
            $table->string('hair_style', 50)->nullable()->comment('髪型');
            $table->string('eye_color', 50)->comment('目の色');
            $table->string('clothing', 50)->comment('服装');
            $table->string('accessory', 50)->nullable()->comment('アクセサリー');
            $table->string('body_type', 50)->comment('体型');

            // 性格設定
            $table->string('tone', 50)->comment('口調');
            $table->string('enthusiasm', 50)->comment('熱意');
            $table->string('formality', 50)->comment('フォーマルさ');
            $table->string('humor', 50)->comment('ユーモア');

            // 描画モデル
            $table->string('draw_model_version', 100)->nullable()->comment('描画モデルバージョン');
            $table->boolean('is_transparent')->default(false)->comment('背景透過');
            $table->boolean('is_chibi')->default(false)->comment('ちびキャラ');
            $table->integer('estimated_token_usage')->default(0)->comment('推定使用トークン量');

            // 生成ステータス
            $table->enum('generation_status', array_keys(config('const.avatar_generation_statuses')))->default('pending');
            $table->timestamp('last_generated_at')->nullable();
            
            // 表示設定
            $table->boolean('is_visible')->default(true)->comment('アバター表示ON/OFF');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_avatars');
    }
};