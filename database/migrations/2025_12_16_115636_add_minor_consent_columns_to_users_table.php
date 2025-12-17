<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * usersテーブルに未成年者・保護者同意関連カラムを追加
 * 
 * COPPA（児童オンラインプライバシー保護法）対応のため、以下のカラムを追加:
 * - birthdate: 生年月日（年齢判定用）
 * - is_minor: 未成年フラグ（13歳未満の場合true）
 * - parent_email: 保護者のメールアドレス
 * - parent_consent_token: 保護者同意確認用トークン
 * - parent_consented_at: 保護者同意日時
 * - parent_consent_expires_at: 保護者同意有効期限（7日間）
 * 
 * @see /home/ktr/mtdev/definitions/PrivacyPolicyAndTerms.md - COPPA対応
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 生年月日（年齢判定用）
            $table->date('birthdate')->nullable()->after('email');
            
            // 未成年フラグ（13歳未満の場合true）
            $table->boolean('is_minor')->default(false)->after('birthdate');
            
            // 保護者のメールアドレス
            $table->string('parent_email')->nullable()->after('is_minor');
            
            // 保護者同意確認用トークン（64文字）
            $table->string('parent_consent_token', 64)->nullable()->after('parent_email');
            
            // 保護者同意日時
            $table->timestamp('parent_consented_at')->nullable()->after('parent_consent_token');
            
            // 保護者同意有効期限（7日間）
            $table->timestamp('parent_consent_expires_at')->nullable()->after('parent_consented_at');
            
            // インデックス追加（検索高速化）
            $table->index('parent_consent_token');
            $table->index('parent_consented_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // インデックス削除
            $table->dropIndex(['parent_consent_token']);
            $table->dropIndex(['parent_consented_at']);
            
            // カラム削除
            $table->dropColumn([
                'birthdate',
                'is_minor',
                'parent_email',
                'parent_consent_token',
                'parent_consented_at',
                'parent_consent_expires_at',
            ]);
        });
    }
};
