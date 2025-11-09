<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\Token\TokenServiceInterface;

/**
 * テスト用トークン付与コマンド
 * 
 * 開発・テスト環境でユーザーにトークンを付与します。
 */
class GrantTestTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:grant
                            {user_id : ユーザーID}
                            {amount : 付与するトークン数}
                            {--note= : メモ（省略可）}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'テスト用にユーザーへトークンを付与します（開発環境専用）';

    public function __construct(
        private TokenServiceInterface $tokenService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 本番環境では実行不可
        if (app()->environment('production')) {
            $this->error('本番環境ではこのコマンドを実行できません。');
            return self::FAILURE;
        }

        $userId = (int) $this->argument('user_id');
        $amount = (int) $this->argument('amount');
        $note = $this->option('note') ?? 'Test token grant';

        $user = User::find($userId);

        if (!$user) {
            $this->error("ユーザーID {$userId} が見つかりません。");
            return self::FAILURE;
        }

        $this->info("ユーザー: {$user->name} (ID: {$user->id})");
        $this->info("付与トークン: " . number_format($amount));
        $this->info("メモ: {$note}");

        if (!$this->confirm('トークンを付与しますか？')) {
            $this->info('キャンセルされました。');
            return self::SUCCESS;
        }

        try {
            // 管理者として自分自身を使用（テスト用）
            $admin = User::where('is_admin', true)->first() ?? $user;
            
            $success = $this->tokenService->adjustTokensByAdmin(
                $user->id,
                User::class,
                $amount,
                $admin,
                $note
            );

            if ($success) {
                $this->info('トークンを付与しました。');
                
                $balance = $user->getOrCreateTokenBalance();
                $this->info("現在の残高: " . number_format($balance->balance));
                
                return self::SUCCESS;
            }

            $this->error('トークン付与に失敗しました。');
            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}