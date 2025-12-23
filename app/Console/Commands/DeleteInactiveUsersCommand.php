<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Task;
use App\Models\TokenTransaction;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * 90日経過ユーザー削除バッチコマンド
 * 
 * GDPR（EU一般データ保護規則）対応のため、論理削除から90日経過したユーザーを
 * 物理削除し、関連データおよびS3オブジェクトを完全に削除する。
 * 
 * 削除対象:
 * - usersテーブルのレコード
 * - 関連データ（tasks, token_transactions, notifications等）
 * - S3オブジェクト（avatars/, task_approvals/配下のユーザーディレクトリ）
 * 
 * 実行スケジュール:
 * - 毎日午前1時（JST）に自動実行
 * - Kernel.phpのschedule()メソッドで定義
 * 
 * @see /home/ktr/mtdev/docs/plans/privacy-policy-and-terms-implementation-plan.md - Phase 4
 */
class DeleteInactiveUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:delete-inactive-users
                            {--dry-run : Dry run mode - no actual deletion}
                            {--days=90 : Number of days since soft delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '論理削除から90日経過したユーザーを物理削除（GDPR対応）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        
        $this->info("=== 90日経過ユーザー削除バッチ開始 ===");
        $this->info("実行モード: " . ($dryRun ? 'DRY RUN（削除なし）' : '本番実行'));
        $this->info("削除対象: {$days}日以上前に論理削除されたユーザー");
        
        // 削除対象ユーザーを取得
        $cutoffDate = Carbon::now()->subDays($days);
        $users = User::onlyTrashed()
            ->where('deleted_at', '<=', $cutoffDate)
            ->get();
        
        $this->info("削除対象ユーザー数: {$users->count()}");
        
        if ($users->isEmpty()) {
            $this->info("削除対象ユーザーはありません。");
            return Command::SUCCESS;
        }
        
        // プログレスバー表示
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($users as $user) {
            try {
                if ($dryRun) {
                    // Dry runモード: 削除せず情報表示のみ
                    $this->newLine();
                    $this->line("DRY RUN: ユーザー削除 - ID: {$user->id}, Username: {$user->username}, Deleted: {$user->deleted_at}");
                } else {
                    // 本番実行: トランザクション内で削除
                    DB::transaction(function () use ($user) {
                        // 関連データ削除
                        $this->deleteRelatedData($user);
                        
                        // S3オブジェクト削除
                        $this->deleteS3Objects($user);
                        
                        // ユーザーレコード物理削除
                        $user->forceDelete();
                        
                        Log::info("ユーザー完全削除成功", [
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'deleted_at' => $user->deleted_at,
                        ]);
                    });
                }
                
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("ユーザー削除エラー", [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $this->newLine();
                $this->error("ユーザー削除エラー - ID: {$user->id}, Error: {$e->getMessage()}");
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // 結果サマリー
        $this->info("=== 削除バッチ完了 ===");
        $this->table(
            ['項目', '件数'],
            [
                ['成功', $successCount],
                ['エラー', $errorCount],
                ['合計', $users->count()],
            ]
        );
        
        return Command::SUCCESS;
    }
    
    /**
     * 関連データを削除
     * 
     * @param User $user
     * @return void
     */
    protected function deleteRelatedData(User $user): void
    {
        // タスク削除（カスケード削除でtask_images, task_completions等も削除）
        Task::where('user_id', $user->id)->forceDelete();
        
        // トークン取引履歴削除
        TokenTransaction::where('user_id', $user->id)->forceDelete();
        
        // ユーザー通知削除
        UserNotification::where('user_id', $user->id)->forceDelete();
        
        // トークン残高削除（ポリモーフィックリレーション）
        DB::table('token_balances')
            ->where('tokenable_type', 'App\\Models\\User')
            ->where('tokenable_id', $user->id)
            ->delete();
        
        // アバター削除（外部キー制約でカスケード削除されるが明示的に削除）
        DB::table('teacher_avatars')->where('user_id', $user->id)->delete();
    }
    
    /**
     * S3オブジェクトを削除
     * 
     * @param User $user
     * @return void
     */
    protected function deleteS3Objects(User $user): void
    {
        try {
            // avatars/{user_id}/配下のファイルを削除
            $avatarPath = "avatars/{$user->id}/";
            if (Storage::disk('s3')->exists($avatarPath)) {
                Storage::disk('s3')->deleteDirectory($avatarPath);
                Log::info("S3削除成功: {$avatarPath}");
            }
            
            // task_approvals/{user_id}/配下のファイルを削除
            $approvalPath = "task_approvals/{$user->id}/";
            if (Storage::disk('s3')->exists($approvalPath)) {
                Storage::disk('s3')->deleteDirectory($approvalPath);
                Log::info("S3削除成功: {$approvalPath}");
            }
        } catch (\Exception $e) {
            Log::warning("S3削除エラー（処理継続）", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
