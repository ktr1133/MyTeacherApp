<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * usersテーブルにparent_user_idカラムを追加
 * 
 * Phase 5-2: 親子アカウント紐付け機能対応
 * 
 * 親子関係を直接記録するための外部キーを追加:
 * - parent_user_id: 保護者のユーザーID（NULL許可）
 * 
 * 使用シナリオ:
 * 1. 招待リンク経由登録: 保護者登録時に子アカウントの parent_user_id を自動設定
 * 2. 紐付けリクエスト承認: 子が承認すると parent_user_id が設定される
 * 3. 未紐付け子検索: parent_user_id IS NULL で検索
 * 
 * @see /home/ktr/mtdev/definitions/ParentChildLinking.md - Phase 5-2仕様
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 保護者のユーザーID（外部キー）
            $table->unsignedBigInteger('parent_user_id')->nullable()->after('parent_email');
            
            // 外部キー制約: users.id への参照（保護者削除時はNULLに設定）
            $table->foreign('parent_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // インデックス追加（検索高速化: whereNull('parent_user_id')等）
            $table->index('parent_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 外部キー制約削除
            $table->dropForeign(['parent_user_id']);
            
            // インデックス削除
            $table->dropIndex(['parent_user_id']);
            
            // カラム削除
            $table->dropColumn('parent_user_id');
        });
    }
};
