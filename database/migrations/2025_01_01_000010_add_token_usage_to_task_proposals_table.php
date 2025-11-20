<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_proposals', function (Blueprint $table) {
            $table->integer('prompt_tokens')->default(0)->after('model_used')->comment('プロンプトトークン数');
            $table->integer('completion_tokens')->default(0)->after('prompt_tokens')->comment('完了トークン数');
            $table->integer('total_tokens')->default(0)->after('completion_tokens')->comment('総トークン数');
        });
    }

    public function down(): void
    {
        Schema::table('task_proposals', function (Blueprint $table) {
            $table->dropColumn(['prompt_tokens', 'completion_tokens', 'total_tokens']);
        });
    }
};