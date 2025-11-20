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
        Schema::create('token_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('ユーザーID');
            
            $table->foreignId('package_id')
                ->constrained('token_packages')
                ->onDelete('cascade')
                ->comment('トークンパッケージID');
            
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->comment('承認状態'); // （pending: 承認待ち, approved: 承認済み, rejected: 却下）
            
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('承認者ID'); // 親ユーザID
            
            $table->timestamp('approved_at')
                ->nullable()
                ->comment('承認日時');
            
            $table->text('rejection_reason')
                ->nullable()
                ->comment('却下理由'); // 親が入力
            
            $table->timestamps();
            
            // インデックス
            $table->index(['user_id', 'status']);
            $table->index('approved_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_purchase_requests');
    }
};