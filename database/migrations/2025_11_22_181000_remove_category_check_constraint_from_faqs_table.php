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
        // FAQsテーブルのcategory CHECK制約を削除
        DB::statement('ALTER TABLE faqs DROP CONSTRAINT IF EXISTS check_category');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ロールバック時はCHECK制約を再追加
        DB::statement("ALTER TABLE faqs ADD CONSTRAINT check_category CHECK (category IN ('getting_started', 'tasks', 'group_tasks', 'ai_features', 'avatars', 'tokens', 'account', 'troubleshooting', 'other'))");
    }
};
