<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFreeTokenSettingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('free_token_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('amount')->default(10000)->comment('無料トークン数');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_token_settings');
    }
}