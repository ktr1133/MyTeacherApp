<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 承認不要のグループタスクで完了済みだがapproved_atがnullのデータを修正
     * 
     * 対象条件:
     * - group_task_id IS NOT NULL (グループタスク)
     * - requires_approval = false (承認不要)
     * - is_completed = true (完了済み)
     * - approved_at IS NULL (承認日時が未設定)
     * 
     * 変更内容:
     * - approved_at = completed_at の値をコピー
     * - approved_by_user_id = user_id の値をコピー（申請者自身を承認者として記録）
     */
    public function up(): void
    {
        // 対象データ件数を確認
        $targetCount = DB::table('tasks')
            ->whereNotNull('group_task_id')
            ->where('requires_approval', false)
            ->where('is_completed', true)
            ->whereNull('approved_at')
            ->whereNotNull('completed_at')  // completed_atがnullの場合は除外（異常データ）
            ->count();

        Log::info('[DataMaintenance] Fix approved_at for group tasks without approval', [
            'target_count' => $targetCount,
        ]);

        if ($targetCount === 0) {
            Log::info('[DataMaintenance] No target data found. Skipping update.');
            return;
        }

        // データ更新を実行
        $updated = DB::table('tasks')
            ->whereNotNull('group_task_id')
            ->where('requires_approval', false)
            ->where('is_completed', true)
            ->whereNull('approved_at')
            ->whereNotNull('completed_at')
            ->update([
                'approved_at' => DB::raw('completed_at'),
                'approved_by_user_id' => DB::raw('COALESCE(assigned_by_user_id, user_id)'),
                'updated_at' => now(),
            ]);

        Log::info('[DataMaintenance] Updated approved_at for group tasks', [
            'updated_count' => $updated,
        ]);

        // 更新結果をコンソール出力
        echo "\n";
        echo "==============================================\n";
        echo "データメンテナンス完了\n";
        echo "==============================================\n";
        echo "対象件数: {$targetCount}\n";
        echo "更新件数: {$updated}\n";
        echo "==============================================\n";
        echo "\n";
    }

    /**
     * Reverse the migrations.
     * 
     * ロールバック不可（元のデータが不明なため）
     */
    public function down(): void
    {
        Log::warning('[DataMaintenance] Rollback for approved_at fix is not supported.');
        echo "\n";
        echo "==============================================\n";
        echo "警告: このマイグレーションはロールバックできません\n";
        echo "==============================================\n";
        echo "理由: 元のデータ（null）を復元しても意味がないため\n";
        echo "\n";
    }
};
