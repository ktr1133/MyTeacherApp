<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GroupsテーブルにStripe関連フィールドを追加
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            // Stripe関連 (グループ課金用)
            $table->string('stripe_id')->nullable()->index()->after('master_user_id')->comment('Stripe顧客ID');
            $table->string('pm_type')->nullable()->after('stripe_id')->comment('支払い方法タイプ');
            $table->string('pm_last_four', 4)->nullable()->after('pm_type')->comment('支払い方法の下4桁');
            $table->timestamp('trial_ends_at')->nullable()->after('pm_last_four')->comment('トライアル終了日時');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
            ]);
        });
    }
};