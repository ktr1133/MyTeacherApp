<?php

namespace App\Services\Auth;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ログイン試行・アカウントロック管理サービス実装
 * 
 * Stripe要件対応:
 * - 5回のログイン失敗でアカウントロック
 * - 不正ログイン対策
 * - ログイン試行履歴の記録
 */
class LoginAttemptService implements LoginAttemptServiceInterface
{
    /**
     * ログイン失敗の上限回数
     */
    private const MAX_FAILED_ATTEMPTS = 5;

    /**
     * ログイン試行履歴の保持期間（分）
     */
    private const ATTEMPT_WINDOW_MINUTES = 30;

    /**
     * 疑わしいIPの判定閾値（短時間の試行回数）
     */
    private const SUSPICIOUS_IP_THRESHOLD = 10;

    /**
     * 疑わしいIPの監視時間（分）
     */
    private const SUSPICIOUS_IP_WINDOW_MINUTES = 10;

    /**
     * ログイン試行を記録
     * 
     * @param string $email メールアドレス
     * @param string $ipAddress IPアドレス
     * @param bool $successful 成功/失敗
     * @param string|null $errorMessage エラーメッセージ
     */
    public function recordAttempt(string $email, string $ipAddress, bool $successful, ?string $errorMessage = null): void
    {
        try {
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $ipAddress,
                'successful' => $successful,
                'user_agent' => request()->userAgent(),
                'error_message' => $errorMessage,
                'attempted_at' => now(),
            ]);

            // 失敗時の処理
            if (!$successful) {
                // ユーザーの失敗カウントを更新
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->increment('failed_login_attempts');
                    $user->update(['last_failed_login_at' => now()]);

                    // 上限到達でアカウントロック
                    if ($user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
                        $this->lockAccount($user, sprintf(
                            '%d回の連続ログイン失敗により自動ロック',
                            self::MAX_FAILED_ATTEMPTS
                        ));
                    }
                }

                // IPアドレスの試行回数をカウント（Rate Limiting用）
                $cacheKey = "login_attempts_ip:{$ipAddress}";
                $attempts = Cache::get($cacheKey, 0);
                Cache::put(
                    $cacheKey,
                    $attempts + 1,
                    now()->addMinutes(self::SUSPICIOUS_IP_WINDOW_MINUTES)
                );
            }

            Log::channel('daily')->info('Login attempt recorded', [
                'email' => $email,
                'ip' => $ipAddress,
                'successful' => $successful,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record login attempt', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 指定ユーザーの連続失敗回数を取得
     * 
     * @param string $email メールアドレス
     * @return int 失敗回数
     */
    public function getFailedAttempts(string $email): int
    {
        $user = User::where('email', $email)->first();
        return $user ? $user->failed_login_attempts : 0;
    }

    /**
     * アカウントロックを確認
     * 
     * @param string $email メールアドレス
     * @return bool ロック状態
     */
    public function isLocked(string $email): bool
    {
        $user = User::where('email', $email)->first();
        return $user && $user->is_locked;
    }

    /**
     * アカウントをロック
     * 
     * @param User $user ユーザー
     * @param string $reason ロック理由
     */
    public function lockAccount(User $user, string $reason = '連続ログイン失敗'): void
    {
        $user->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_reason' => $reason,
        ]);

        Log::warning('Account locked', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reason' => $reason,
        ]);

        // キャッシュにもロック情報を保存（高速判定用）
        Cache::put("user_locked:{$user->id}", true, now()->addHours(24));
    }

    /**
     * アカウントロックを解除
     * 
     * @param User $user ユーザー
     */
    public function unlockAccount(User $user): void
    {
        $user->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_reason' => null,
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
        ]);

        Log::info('Account unlocked', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // キャッシュからロック情報を削除
        Cache::forget("user_locked:{$user->id}");
    }

    /**
     * ログイン成功時の処理（失敗カウントリセット）
     * 
     * @param User $user ユーザー
     */
    public function handleSuccessfulLogin(User $user): void
    {
        // 失敗カウントをリセット
        $user->update([
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
            'last_login_at' => now(),
        ]);

        // 成功ログ記録
        $this->recordAttempt(
            $user->email,
            request()->ip(),
            true
        );
    }

    /**
     * 疑わしいIPアドレスをブロック（Rate Limiting）
     * 
     * @param string $ipAddress IPアドレス
     * @return bool 疑わしいIP判定
     */
    public function isSuspiciousIp(string $ipAddress): bool
    {
        $cacheKey = "login_attempts_ip:{$ipAddress}";

        // 短時間内の試行回数を取得
        $attempts = Cache::get($cacheKey, 0);

        // 閾値超過でブロック
        if ($attempts >= self::SUSPICIOUS_IP_THRESHOLD) {
            Log::warning('Suspicious IP detected', [
                'ip' => $ipAddress,
                'attempts' => $attempts,
            ]);
            return true;
        }

        // 試行回数をインクリメント
        Cache::put(
            $cacheKey,
            $attempts + 1,
            now()->addMinutes(self::SUSPICIOUS_IP_WINDOW_MINUTES)
        );

        return false;
    }

    /**
     * 過去の試行履歴を取得（管理者用）
     * 
     * @param string|null $email メールアドレス（null=全履歴）
     * @param int $limit 取得件数
     * @return \Illuminate\Support\Collection
     */
    public function getAttemptHistory(?string $email = null, int $limit = 100)
    {
        $query = LoginAttempt::query()
            ->orderBy('attempted_at', 'desc')
            ->limit($limit);

        if ($email) {
            $query->where('email', $email);
        }

        return $query->get();
    }

    /**
     * 古いログイン試行履歴を削除（クリーンアップ）
     * 
     * @param int $daysToKeep 保持日数
     * @return int 削除件数
     */
    public function cleanupOldAttempts(int $daysToKeep = 90): int
    {
        $deletedCount = LoginAttempt::where('attempted_at', '<', now()->subDays($daysToKeep))
            ->delete();

        Log::info('Cleaned up old login attempts', [
            'deleted_count' => $deletedCount,
            'days_kept' => $daysToKeep,
        ]);

        return $deletedCount;
    }
}
