<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index()->comment('ユーザーID');
            $table->string('ip_address', 45)->nullable()->comment('IPアドレス');
            $table->text('user_agent')->nullable()->comment('ユーザーエージェント');
            $table->text('payload')->comment('セッションデータ');
            $table->integer('last_activity')->index()->comment('最終アクティビティ時刻');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sessions');
    }
};
