<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * 承認不要グループタスクのapproved_at修正マイグレーション
 * 
 * @property-read \Illuminate\Console\OutputStyle|null $command コンソールコマンドインスタンス（マイグレーション実行時のみ存在）
 */
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

        if ($targetCount === 0) {
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
    }

    /**
     * Reverse the migrations.
     * 
     * ロールバック不可（元のデータが不明なため）
     */
    public function down(): void
    {
        // ロールバック不可（元データがnullのため復元不要）
    }
};
