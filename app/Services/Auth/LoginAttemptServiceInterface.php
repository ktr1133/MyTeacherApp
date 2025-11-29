<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * ログイン試行・アカウントロック管理サービス
 * 
 * Stripe要件対応:
 * - 5回のログイン失敗でアカウントロック
 * - 不正ログイン対策
 * - ログイン試行履歴の記録
 */
interface LoginAttemptServiceInterface
{
    /**
     * ログイン試行を記録
     */
    public function recordAttempt(string $email, string $ipAddress, bool $successful, ?string $errorMessage = null): void;

    /**
     * 指定ユーザーの連続失敗回数を取得
     */
    public function getFailedAttempts(string $email): int;

    /**
     * アカウントロックを確認
     */
    public function isLocked(string $email): bool;

    /**
     * アカウントをロック
     */
    public function lockAccount(User $user, string $reason = '連続ログイン失敗'): void;

    /**
     * アカウントロックを解除
     */
    public function unlockAccount(User $user): void;

    /**
     * ログイン成功時の処理（失敗カウントリセット）
     */
    public function handleSuccessfulLogin(User $user): void;

    /**
     * 疑わしいIPアドレスをブロック（Rate Limiting）
     */
    public function isSuspiciousIp(string $ipAddress): bool;
}
