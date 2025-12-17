<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 5-2拡張: 保護者招待トークン機能
     * 13歳未満ユーザーの保護者が、同意完了後に自身のアカウントを作成する際、
     * 子アカウントと自動紐付けするための招待トークンを追加。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 保護者招待トークン（64文字、ユニーク、単一使用）
            $table->string('parent_invitation_token', 64)->nullable()->unique()->after('parent_consent_expires_at');
            
            // 招待トークン有効期限（30日）
            $table->timestamp('parent_invitation_expires_at')->nullable()->after('parent_invitation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SQLite互換性のため、インデックスを先に削除
            $table->dropUnique('users_parent_invitation_token_unique');
            $table->dropColumn(['parent_invitation_token', 'parent_invitation_expires_at']);
        });
    }
};
