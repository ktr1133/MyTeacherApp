<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 1: Cognito統合のためのカラム追加
     * - email: メールアドレス（Cognito必須、既存ユーザーには疑似メール自動生成）
     * - name: 表示名（Cognito用、既存ユーザーはusernameをコピー）
     * - cognito_sub: CognitoユーザーのSub（一意識別子）
     * - auth_provider: 認証プロバイダー（'breeze' or 'cognito'）
     * 
     * 並行運用期間中は両方の認証をサポート
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // メールアドレス（Cognito必須）
            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->unique()->after('username');
            }
            
            // 表示名（Cognito用）
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->after('email');
            }
            
            // Cognito Sub（UUID）- ユニークインデックス付き
            if (!Schema::hasColumn('users', 'cognito_sub')) {
                $table->string('cognito_sub', 100)->nullable()->unique()->after('id');
            }
            
            // 認証プロバイダー（デフォルト: breeze）
            if (!Schema::hasColumn('users', 'auth_provider')) {
                $table->enum('auth_provider', ['breeze', 'cognito'])->default('breeze')->after('cognito_sub');
            }
            
            // 最終ログイン日時（既に存在する場合はスキップ）
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('updated_at');
            }
            
            // インデックス追加（auth_providerカラムが存在する場合のみ）
            if (Schema::hasColumn('users', 'auth_provider')) {
                $table->index('auth_provider');
            }
        });
        
        // 既存ユーザーにデフォルト値を設定
        DB::table('users')->whereNull('email')->update([
            'email' => DB::raw("username || '@myteacher.local'"),
            'name' => DB::raw('username'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // インデックス削除（存在する場合のみ）
            if (Schema::hasColumn('users', 'auth_provider')) {
                $table->dropIndex(['auth_provider']);
            }
            
            // カラム削除（存在する場合のみ）
            $columnsToRemove = [];
            if (Schema::hasColumn('users', 'email')) {
                $columnsToRemove[] = 'email';
            }
            if (Schema::hasColumn('users', 'name')) {
                $columnsToRemove[] = 'name';
            }
            if (Schema::hasColumn('users', 'cognito_sub')) {
                $columnsToRemove[] = 'cognito_sub';
            }
            if (Schema::hasColumn('users', 'auth_provider')) {
                $columnsToRemove[] = 'auth_provider';
            }
            // last_login_atは既存カラムのため削除しない
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};
