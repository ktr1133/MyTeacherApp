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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('ユーザーID');
            $table->string('type')->comment('サブスクリプションタイプ');
            $table->string('stripe_id')->unique()->comment('StripeサブスクリプションID');
            $table->string('stripe_status')->comment('Stripeステータス');
            $table->string('stripe_price')->nullable()->comment('Stripe価格ID');
            $table->integer('quantity')->nullable()->comment('数量');
            $table->timestamp('trial_ends_at')->nullable()->comment('トライアル終了日時');
            $table->timestamp('ends_at')->nullable()->comment('サブスク終了日時');
            $table->timestamps();

            $table->index(['user_id', 'stripe_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
