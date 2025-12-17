<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 作成者・同意者トラッキング
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('id')->comment('作成者のユーザーID（親が子を作成した場合）');
            $table->unsignedBigInteger('consent_given_by_user_id')->nullable()->after('created_by_user_id')->comment('同意者のユーザーID（代理同意の場合は親のID）');
            
            // 同意バージョン管理
            $table->string('privacy_policy_version', 20)->nullable()->after('parent_consent_expires_at')->comment('同意したプライバシーポリシーのバージョン');
            $table->string('terms_version', 20)->nullable()->after('privacy_policy_version')->comment('同意した利用規約のバージョン');
            
            // 同意日時
            $table->timestamp('privacy_policy_agreed_at')->nullable()->after('terms_version')->comment('プライバシーポリシー同意日時');
            $table->timestamp('terms_agreed_at')->nullable()->after('privacy_policy_agreed_at')->comment('利用規約同意日時');
            $table->timestamp('self_consented_at')->nullable()->after('terms_agreed_at')->comment('本人同意日時（13歳到達時の再同意）');
            
            // 外部キー制約
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('consent_given_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            // インデックス
            $table->index('privacy_policy_version', 'idx_privacy_policy_version');
            $table->index('terms_version', 'idx_terms_version');
            $table->index('consent_given_by_user_id', 'idx_consent_given_by');
            $table->index('self_consented_at', 'idx_self_consented_at');
        });
        
        // 既存ユーザーを同意済みに設定（開発者追加ユーザーのみ）
        DB::table('users')->update([
            'privacy_policy_version' => config('legal.current_versions.privacy_policy', '1.0.0'),
            'terms_version' => config('legal.current_versions.terms_of_service', '1.0.0'),
            'privacy_policy_agreed_at' => now(),
            'terms_agreed_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 外部キー削除
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['consent_given_by_user_id']);
            
            // インデックス削除
            $table->dropIndex('idx_privacy_policy_version');
            $table->dropIndex('idx_terms_version');
            $table->dropIndex('idx_consent_given_by');
            $table->dropIndex('idx_self_consented_at');
            
            // カラム削除
            $table->dropColumn([
                'created_by_user_id',
                'consent_given_by_user_id',
                'privacy_policy_version',
                'terms_version',
                'privacy_policy_agreed_at',
                'terms_agreed_at',
                'self_consented_at',
            ]);
        });
    }
};
