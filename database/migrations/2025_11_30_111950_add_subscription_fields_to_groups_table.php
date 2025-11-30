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
        Schema::table('groups', function (Blueprint $table) {
            // サブスクリプション管理フィールド
            $table->boolean('subscription_active')->default(false)->comment('サブスクリプション有効フラグ');
            $table->string('subscription_plan', 50)->nullable()->comment('サブスクリプションプラン: family, enterprise');
            $table->integer('max_members')->default(6)->comment('最大メンバー数（デフォルト6: 無料枠）');
            $table->integer('max_groups')->default(1)->comment('最大グループ数（将来用）');
            
            // グループタスク制限管理
            $table->integer('free_group_task_limit')->default(3)->comment('グループタスク無料作成回数（月次、管理者調整可能）');
            $table->integer('group_task_count_current_month')->default(0)->comment('当月のグループタスク作成回数');
            $table->timestamp('group_task_count_reset_at')->nullable()->comment('グループタスク作成回数リセット日時（翌月1日）');
            
            // トライアル・レポート設定
            $table->integer('free_trial_days')->default(14)->comment('無料トライアル日数（管理者調整可能）');
            $table->date('report_enabled_until')->nullable()->comment('実績レポート利用可能期限（無料ユーザーは初月末まで）');
            
            // インデックス
            $table->index('subscription_active');
            $table->index('group_task_count_reset_at');
        });
        
        // 既存グループに初期値設定（SQLite/PostgreSQL対応）
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite用: datetime関数を使用
            DB::statement("UPDATE groups SET group_task_count_reset_at = datetime('now', 'start of month', '+1 month') WHERE group_task_count_reset_at IS NULL");
            DB::statement("UPDATE groups SET report_enabled_until = date('now', 'start of month', '+1 month', '-1 day') WHERE report_enabled_until IS NULL AND subscription_active = 0");
        } else {
            // PostgreSQL用: DATE_TRUNC関数を使用
            DB::statement("UPDATE groups SET group_task_count_reset_at = DATE_TRUNC('month', NOW() + INTERVAL '1 month') WHERE group_task_count_reset_at IS NULL");
            DB::statement("UPDATE groups SET report_enabled_until = DATE_TRUNC('month', NOW() + INTERVAL '1 month') - INTERVAL '1 day' WHERE report_enabled_until IS NULL AND subscription_active = FALSE");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex(['subscription_active']);
            $table->dropIndex(['group_task_count_reset_at']);
            
            $table->dropColumn([
                'subscription_active',
                'subscription_plan',
                'max_members',
                'max_groups',
                'free_group_task_limit',
                'group_task_count_current_month',
                'group_task_count_reset_at',
                'free_trial_days',
                'report_enabled_until'
            ]);
        });
    }
};
