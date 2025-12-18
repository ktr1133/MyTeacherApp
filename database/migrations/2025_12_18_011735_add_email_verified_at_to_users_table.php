<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * usersテーブルにemail_verified_atカラムを追加
 * 
 * 背景:
 * 初期デプロイ時、usersテーブルが手動またはマイグレーションファイル修正前に作成され、
 * email_verified_atカラムが欠落していた。このマイグレーションで不足カラムを追加する。
 * 
 * 参照: docs/reports/2025-12-18-production-database-schema-mismatch-investigation.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // カラムが存在しない場合のみ追加（冪等性確保）
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')
                    ->nullable()
                    ->after('email')
                    ->comment('メール認証日時');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
        });
    }
};
