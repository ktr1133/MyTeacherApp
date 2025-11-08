<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\Token\TokenRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * 古い課金履歴削除コマンド
 * 
 * 論理削除から1年経過した課金履歴を物理削除します。
 */
class CleanOldPaymentHistories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:clean-old
                            {--days=365 : 保持日数（デフォルト: 365日）}
                            {--dry-run : 実行せずに対象のみ表示}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '古い課金履歴を削除します（論理削除から指定日数経過）';

    public function __construct(
        private TokenRepositoryInterface $tokenRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $before = now()->subDays($days);

        $this->info("課金履歴クリーンアップ開始...");
        $this->info("削除対象: {$before->format('Y-m-d')} より前に論理削除されたレコード");

        if ($this->option('dry-run')) {
            $this->warn('ドライランモード: 実際の削除は行いません');
            
            $count = \App\Models\PaymentHistory::onlyTrashed()
                ->where('deleted_at', '<', $before)
                ->count();
            
            $this->info("削除対象件数: {$count}件");
            
            return self::SUCCESS;
        }

        if (!$this->confirm('本当に削除しますか？')) {
            $this->info('キャンセルされました。');
            return self::SUCCESS;
        }

        try {
            $deletedCount = $this->tokenRepository->deleteOldPaymentHistories($before);
            
            $this->info("削除完了: {$deletedCount}件");
            
            Log::info('Old payment histories cleaned', [
                'count' => $deletedCount,
                'before' => $before->toDateString(),
            ]);
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('削除に失敗しました: ' . $e->getMessage());
            
            Log::error('Payment histories cleanup failed', [
                'error' => $e->getMessage(),
            ]);
            
            return self::FAILURE;
        }
    }
}