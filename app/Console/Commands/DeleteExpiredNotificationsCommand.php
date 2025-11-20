<?php

namespace App\Console\Commands;

use App\Services\Notification\NotificationServiceInterface;
use Illuminate\Console\Command;

/**
 * 期限切れ通知削除コマンド
 * 
 * expire_at から 30 日経過した通知を物理削除する。
 * 
 * @package App\Console\Commands
 */
class DeleteExpiredNotificationsCommand extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'notifications:delete-expired';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '期限切れから30日経過した通知を削除します';

    /**
     * コンストラクタ
     *
     * @param NotificationServiceInterface $service 通知サービス
     */
    public function __construct(
        private NotificationServiceInterface $service
    ) {
        parent::__construct();
    }

    /**
     * コマンドの実行
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('期限切れ通知の削除を開始します...');

        try {
            $deletedCount = $this->service->cleanupExpiredNotifications();

            $this->info("削除完了: {$deletedCount} 件の通知を削除しました。");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('エラーが発生しました: ' . $e->getMessage());
            \Log::error('期限切れ通知削除エラー', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}