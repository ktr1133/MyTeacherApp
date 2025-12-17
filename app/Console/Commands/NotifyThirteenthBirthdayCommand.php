<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SelfConsentRequiredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 13歳到達通知コマンド
 * 
 * 13歳に到達したユーザー（代理同意のまま）を検出し、
 * 本人同意が必要な旨を本人と保護者に通知します。
 * 
 * Phase 6D: 13歳到達時の本人再同意実装
 * 
 * @package App\Console\Commands
 */
class NotifyThirteenthBirthdayCommand extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'legal:notify-13th-birthday 
                            {--dry-run : 実際には送信せずにシミュレーションのみ}
                            {--days=7 : 何日前から検出するか（デフォルト: 7日）}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '13歳に到達したユーザーに本人同意通知を送信します';

    /**
     * コマンドの実行
     *
     * @return int
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');

        if ($dryRun) {
            $this->warn('⚠️  Dry-runモード: 実際には通知を送信しません');
        }

        $this->info("13歳に到達したユーザーを検索しています（過去{$days}日以内）...");

        try {
            // 13歳の誕生日の範囲を計算
            $today = Carbon::now();
            $targetDateStart = $today->copy()->subYears(13)->subDays($days); // 13年{$days}日前
            $targetDateEnd = $today->copy()->subYears(13); // 13年前の今日

            $this->info("検索範囲:");
            $this->line("  - 開始日: {$targetDateStart->format('Y-m-d')}");
            $this->line("  - 終了日: {$targetDateEnd->format('Y-m-d')}");

            // 条件:
            // 1. birthdateが13歳到達範囲
            // 2. created_by_user_id が NULL でない（親が作成）
            // 3. consent_given_by_user_id が本人のIDでない（代理同意のまま）
            // 4. self_consented_at が NULL（本人同意未済）
            $users = User::whereBetween('birthdate', [$targetDateStart, $targetDateEnd])
                ->whereNotNull('created_by_user_id')
                ->whereColumn('consent_given_by_user_id', '!=', 'id')
                ->whereNull('self_consented_at')
                ->whereNull('deleted_at')
                ->with(['creator', 'consentGiver']) // 親情報をEager Loading
                ->get();

            $count = $users->count();

            if ($count === 0) {
                $this->info('✅ 13歳到達で本人同意が必要なユーザーはいません。');
                return Command::SUCCESS;
            }

            $this->info("🎂 対象ユーザー: {$count} 人");

            if ($dryRun) {
                $this->table(
                    ['ID', 'ユーザー名', '誕生日', '年齢', '作成者', '同意者', '本人同意'],
                    $users->map(function ($user) {
                        return [
                            $user->id,
                            $user->username,
                            $user->birthdate?->format('Y-m-d'),
                            $user->birthdate?->age . '歳',
                            $user->creator?->username ?? '不明',
                            $user->consentGiver?->username ?? '不明',
                            $user->self_consented_at ? '済' : '未',
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
                    // 本人に通知
                    $user->notify(new SelfConsentRequiredNotification());

                    // 保護者にも通知（created_by_user_id）
                    if ($user->creator) {
                        $user->creator->notify(new SelfConsentRequiredNotification($user));
                    }

                    $successCount++;
                    Log::info('Self consent notification sent', [
                        'user_id' => $user->id,
                        'age' => $user->birthdate?->age,
                        'parent_id' => $user->created_by_user_id,
                    ]);
                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Self consent notification failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // 結果サマリー
            $this->info("✅ 送信成功: {$successCount} 件");
            if ($failureCount > 0) {
                $this->error("❌ 送信失敗: {$failureCount} 件");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("エラーが発生しました: {$e->getMessage()}");
            Log::error('NotifyThirteenthBirthdayCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
