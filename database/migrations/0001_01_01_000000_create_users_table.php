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
            $table->string('name')->unique()->comment('グループ名'); // グループ名
            $table->unsignedBigInteger('master_user_id')->nullable()->comment('マスターのユーザーID'); // グループマスターのユーザーID
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique()->comment('ユーザー名'); // ログインIDとして使用
            $table->unsignedBigInteger('group_id')->nullable()->comment('グループID'); // グループID
            $table->boolean('group_edit_flg')->default(false)->comment('グループ編集フラグ'); // グループ編集フラグ
            $table->enum('theme', ['adult', 'child'])->default('adult')->comment('デザインテーマ');
            $table->string('password')->comment('パスワード');
            $table->boolean('requires_purchase_approval')->default(true)->comment('トークン購入承認');
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
        // 1. まず groups テーブルの外部キー制約を削除
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['master_user_id']);
        });

        // 2. 次に users テーブルを削除（group_id の外部キー制約も一緒に削除される）
        Schema::dropIfExists('users');

        // 3. 最後に groups テーブルを削除
        Schema::dropIfExists('groups');
    }
};