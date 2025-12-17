<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReconsentRequiredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 再同意必要通知コマンド
 * 
 * プライバシーポリシー・利用規約が更新された際に、
 * 再同意が必要なユーザーに通知を送信します。
 * 
 * Phase 6C: 再同意プロセス実装
 * 
 * @package App\Console\Commands
 */
class NotifyReconsentRequiredCommand extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'legal:notify-reconsent 
                            {--dry-run : 実際には送信せずにシミュレーションのみ}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '再同意が必要なユーザーに通知を送信します';

    /**
     * コマンドの実行
     *
     * @return int
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  Dry-runモード: 実際には通知を送信しません');
        }

        $this->info('再同意が必要なユーザーを検索しています...');

        try {
            // 現在のバージョンを取得
            $currentPrivacyVersion = config('legal.current_versions.privacy_policy');
            $currentTermsVersion = config('legal.current_versions.terms_of_service');

            $this->info("現在のバージョン:");
            $this->line("  - プライバシーポリシー: {$currentPrivacyVersion}");
            $this->line("  - 利用規約: {$currentTermsVersion}");

            // 再同意が必要なユーザーを取得
            $users = User::where(function ($query) use ($currentPrivacyVersion, $currentTermsVersion) {
                $query->whereNull('privacy_policy_version')
                      ->orWhere('privacy_policy_version', '!=', $currentPrivacyVersion)
                      ->orWhereNull('terms_version')
                      ->orWhere('terms_version', '!=', $currentTermsVersion);
            })
            ->whereNull('deleted_at')
            ->get();

            $count = $users->count();

            if ($count === 0) {
                $this->info('✅ 再同意が必要なユーザーはいません。');
                return Command::SUCCESS;
            }

            $this->info("📧 対象ユーザー: {$count} 人");

            if ($dryRun) {
                $this->table(
                    ['ID', 'ユーザー名', 'メール', 'Privacy Ver', 'Terms Ver'],
                    $users->map(function ($user) {
                        return [
                            $user->id,
                            $user->username,
                            $user->email,
                            $user->privacy_policy_version ?? '未同意',
                            $user->terms_version ?? '未同意',
                        ];
                    })->toArray()
                );

                $this->warn('⚠️  Dry-runモードのため、通知は送信されませんでした。');
                return Command::SUCCESS;
            }

            // プログレスバーを表示
            $bar = $this->output->createProgressBar($count);
            $bar->start();

            $successCount = 0;
            $failureCount = 0;

            foreach ($users as $user) {
                try {
                    // 通知を送信
                    $user->notify(new ReconsentRequiredNotification(
                        $currentPrivacyVersion,
                        $currentTermsVersion
                    ));

                    $successCount++;
                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Reconsent notification failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ 通知送信完了:");
            $this->line("  - 成功: {$successCount} 件");

            if ($failureCount > 0) {
                $this->warn("  - 失敗: {$failureCount} 件");
            }

            Log::info('Reconsent notification batch completed', [
                'total' => $count,
                'success' => $successCount,
                'failure' => $failureCount,
            ]);

            return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: ' . $e->getMessage());
            Log::error('Reconsent notification batch error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
