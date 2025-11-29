<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ログイン試行とアカウントロック管理テーブル
 * 
 * Stripe要件対応: 不正ログイン対策・アカウントロック機能
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index()->comment('試行されたメールアドレス');
            $table->string('ip_address', 45)->comment('接続元IPアドレス');
            $table->boolean('successful')->default(false)->comment('成功/失敗');
            $table->string('user_agent', 500)->nullable()->comment('User-Agent');
            $table->text('error_message')->nullable()->comment('エラーメッセージ（失敗時）');
            $table->timestamp('attempted_at')->useCurrent()->comment('試行日時');
            
            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('is_admin')->comment('アカウントロック状態');
            $table->timestamp('locked_at')->nullable()->after('is_locked')->comment('ロック日時');
            $table->string('locked_reason', 500)->nullable()->after('locked_at')->comment('ロック理由');
            $table->integer('failed_login_attempts')->default(0)->after('locked_reason')->comment('連続ログイン失敗回数');
            $table->timestamp('last_failed_login_at')->nullable()->after('failed_login_attempts')->comment('最終失敗日時');
            
            // 二要素認証関連
            $table->boolean('two_factor_enabled')->default(false)->after('last_failed_login_at')->comment('2FA有効フラグ');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled')->comment('2FA秘密鍵（暗号化）');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret')->comment('2FAリカバリーコード（JSON）');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes')->comment('2FA確認日時');
            
            // IP制限関連
            $table->json('allowed_ips')->nullable()->after('two_factor_confirmed_at')->comment('許可IPアドレスリスト（JSON）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_locked',
                'locked_at',
                'locked_reason',
                'failed_login_attempts',
                'last_failed_login_at',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'allowed_ips',
            ]);
        });
        
        Schema::dropIfExists('login_attempts');
    }
};
