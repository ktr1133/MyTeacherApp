<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 保護者未同意の13歳未満ユーザー削除コマンド
 * 
 * 同意期限（7日間）を過ぎても保護者の同意を得られなかった
 * 13歳未満のユーザーを自動削除します。
 * 
 * Phase 5-2: 13歳未満新規登録時の保護者メール同意実装
 * 
 * 実行: php artisan legal:delete-unconsented-minors
 * Cron: 毎日午前3時実行（app/Console/Kernel.php）
 */
class DeleteUnconsentedMinorsCommand extends Command
{
    /**
     * コマンドシグネチャ
     *
     * @var string
     */
    protected $signature = 'legal:delete-unconsented-minors 
                            {--dry-run : 実際には削除せず、削除対象のみ表示}
                            {--days=7 : 同意期限の日数（デフォルト: 7日）}';

    /**
     * コマンド説明
     *
     * @var string
     */
    protected $description = '保護者未同意の13歳未満ユーザーを削除（同意期限切れ）';

    /**
     * コマンド実行
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        
        $this->info('=== 保護者未同意ユーザー削除処理 ===');
        $this->info('同意期限: ' . $days . '日間');
        
        if ($dryRun) {
            $this->warn('⚠️  DRY-RUNモード: 実際には削除されません');
        }

        try {
            // 削除対象ユーザーを取得
            $targetUsers = User::query()
                ->where('is_minor', true) // 13歳未満フラグ
                ->whereNull('parent_consented_at') // 保護者未同意
                ->whereNotNull('parent_consent_expires_at') // 期限設定済み
                ->where('parent_consent_expires_at', '<', now()) // 期限切れ
                ->get();

            $count = $targetUsers->count();

            if ($count === 0) {
                $this->info('✅ 削除対象のユーザーはいません。');
                return self::SUCCESS;
            }

            $this->warn('削除対象: ' . $count . '人');
            $this->newLine();

            // 削除対象の詳細表示
            $this->table(
                ['ID', 'ユーザー名', 'メール', '保護者メール', '登録日時', '期限切れ日時'],
                $targetUsers->map(function ($user) {
                    return [
                        $user->id,
                        $user->username,
                        $user->email,
                        $user->parent_email ?? '未設定',
                        $user->created_at->format('Y-m-d H:i'),
                        $user->parent_consent_expires_at?->format('Y-m-d H:i') ?? '未設定',
                    ];
                })
            );

            if ($dryRun) {
                $this->info('DRY-RUNモードのため、削除は実行されませんでした。');
                Log::info('[DeleteUnconsentedMinors] DRY-RUN: ' . $count . ' users would be deleted');
                return self::SUCCESS;
            }

            // 確認プロンプト
            if (!$this->confirm('上記のユーザーを削除してもよろしいですか？', false)) {
                $this->info('削除をキャンセルしました。');
                return self::SUCCESS;
            }

            // トランザクション内で削除
            DB::transaction(function () use ($targetUsers, &$deletedCount) {
                foreach ($targetUsers as $user) {
                    $userId = $user->id;
                    $username = $user->username;
                    
                    // ユーザー削除（関連データも CASCADE または手動削除）
                    $user->delete();
                    
                    Log::info('[DeleteUnconsentedMinors] Deleted user', [
                        'user_id' => $userId,
                        'username' => $username,
                        'parent_email' => $user->parent_email,
                        'expired_at' => $user->parent_consent_expires_at,
                    ]);
                }
                
                $deletedCount = $targetUsers->count();
            });

            $this->info('✅ ' . $count . '人のユーザーを削除しました。');
            
            Log::info('[DeleteUnconsentedMinors] Completed', [
                'deleted_count' => $count,
                'days' => $days,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ エラーが発生しました: ' . $e->getMessage());
            Log::error('[DeleteUnconsentedMinors] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
