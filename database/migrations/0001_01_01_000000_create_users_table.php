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
            $table->string('name')->unique()->comment('グループ名');
            $table->unsignedBigInteger('master_user_id')->nullable()->comment('グループ管理者のユーザーID');
            
            // Stripe関連フィールド (Laravel Cashier - グループ課金用)
            $table->string('stripe_id')->nullable()->index()->comment('Stripe顧客ID');
            $table->string('pm_type')->nullable()->comment('支払い方法タイプ');
            $table->string('pm_last_four', 4)->nullable()->comment('支払い方法の下4桁');
            $table->timestamp('trial_ends_at')->nullable()->comment('トライアル終了日時');
            
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique()->comment('ユーザー名');
            $table->timestamp('last_login_at')->nullable()->comment('最終ログイン日時');
            $table->unsignedBigInteger('group_id')->nullable()->comment('グループID');
            $table->boolean('group_edit_flg')->default(false)->comment('グループ編集フラグ');
            $table->boolean('is_admin')->default(false)->comment('管理者フラグ');
            $table->enum('theme', ['adult', 'child'])->default('adult')->comment('デザインテーマ');
            $table->string('timezone', 50)->default('Asia/Tokyo')->comment('ユーザーのタイムゾーン（IANA形式）');
            $table->string('password')->comment('パスワード');
            $table->boolean('requires_purchase_approval')->default(true)->comment('トークン購入承認');
            $table->rememberToken();
            
            // Stripe関連フィールド (Laravel Cashier)
            $table->string('stripe_id')->nullable()->index()->comment('Stripe顧客ID');
            $table->string('pm_type')->nullable()->comment('支払い方法タイプ');
            $table->string('pm_last_four', 4)->nullable()->comment('支払い方法の下4桁');
            $table->timestamp('trial_ends_at')->nullable()->comment('トライアル終了日時');
            
            // トークン管理モード
            $table->enum('token_mode', ['individual', 'group'])
                ->default('individual')
                ->comment('individual: 個人課金, group: グループ課金');
            
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