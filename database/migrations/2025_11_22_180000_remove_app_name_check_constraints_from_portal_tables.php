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
        // FAQsテーブルのapp_name CHECK制約を削除し、カラムをVARCHAR(20)に変更
        DB::statement('ALTER TABLE faqs DROP CONSTRAINT IF EXISTS check_app_name');
        DB::statement('ALTER TABLE faqs ALTER COLUMN app_name TYPE VARCHAR(20)');
        
        // FAQsテーブルのcategory CHECK制約を削除
        DB::statement('ALTER TABLE faqs DROP CONSTRAINT IF EXISTS check_category');
        
        // app_updatesテーブルのapp_name CHECK制約を削除し、カラムをVARCHAR(20)に変更
        DB::statement('ALTER TABLE app_updates DROP CONSTRAINT IF EXISTS check_app_name');
        DB::statement('ALTER TABLE app_updates ALTER COLUMN app_name TYPE VARCHAR(20)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ロールバック時はCHECK制約を再追加
        DB::statement("ALTER TABLE faqs ADD CONSTRAINT check_app_name CHECK (app_name IN ('MyTeacher', 'KeepItSimple'))");
        DB::statement("ALTER TABLE faqs ADD CONSTRAINT check_category CHECK (category IN ('getting_started', 'tasks', 'group_tasks', 'ai_features', 'avatars', 'tokens', 'account', 'troubleshooting', 'other'))");
        DB::statement("ALTER TABLE app_updates ADD CONSTRAINT check_app_name CHECK (app_name IN ('MyTeacher', 'KeepItSimple'))");
    }
};
