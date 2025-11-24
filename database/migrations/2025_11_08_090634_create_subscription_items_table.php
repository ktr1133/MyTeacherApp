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
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->comment('サブスクリプションID');
            $table->string('stripe_id')->unique()->comment('StripeサブスクリプションアイテムID');
            $table->string('stripe_product')->comment('Stripe商品ID');
            $table->string('stripe_price')->comment('Stripe価格ID');
            $table->string('meter_id')->nullable()->comment('メーターID');
            $table->string('meter_event_name')->nullable()->comment('メーターイベント名');
            $table->integer('quantity')->nullable()->comment('数量');
            $table->timestamps();

            $table->index(['subscription_id', 'stripe_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_items');
    }
};
