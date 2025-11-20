<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UsersテーブルにStripe関連フィールドとトークン管理モードを追加
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Stripe関連 (Laravel Cashier)
            $table->string('stripe_id')->nullable()->index()->after('remember_token')->comment('Stripe顧客ID');
            $table->string('pm_type')->nullable()->after('stripe_id')->comment('支払い方法タイプ');
            $table->string('pm_last_four', 4)->nullable()->after('pm_type')->comment('支払い方法の下4桁');
            $table->timestamp('trial_ends_at')->nullable()->after('pm_last_four')->comment('トライアル終了日時');
            
            // トークン管理モード
            $table->enum('token_mode', ['individual', 'group'])
                ->default('individual')
                ->after('trial_ends_at')
                ->comment('individual: 個人課金, group: グループ課金');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
                'token_mode',
            ]);
        });
    }
};