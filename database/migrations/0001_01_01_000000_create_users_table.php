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
        // groups テーブルを先に作成
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // グループ名
            $table->unsignedBigInteger('master_user_id')->nullable(); // グループマスターのユーザーID
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique(); // ログインIDとして使用
            $table->unsignedBigInteger('group_id')->nullable(); // グループID
            $table->boolean('group_edit_flg')->default(false); // グループ編集フラグ
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            
            // 外部キー制約
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('set null');
        });

        // groups テーブルに外部キー制約を追加
        Schema::table('groups', function (Blueprint $table) {
            $table->foreign('master_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('groups');
    }
};