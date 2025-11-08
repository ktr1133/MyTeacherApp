<?php

namespace App\Services\Token;

use App\Models\User;
use App\Models\TokenBalance;
use App\Repositories\Token\TokenRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * トークン管理サービス インターフェース
 */
interface TokenServiceInterface
{
    /**
     * トークンを消費する
     *
     * @param User $user 実行ユーザー
     * @param int $amount 消費量
     * @param string $reason 理由
     * @param mixed $related 関連モデル
     * @return bool 成功の可否
     */
    public function consumeTokens(User $user, int $amount, string $reason, $related = null): bool;

    /**
     * トークン残高をチェック
     *
     * @param User $user
     * @param int $amount 必要量
     * @return bool
     */
    public function checkBalance(User $user, int $amount): bool;

    /**
     * トークン残高を取得または作成
     *
     * @param string $tokenableType
     * @param int $tokenableId
     * @return TokenBalance
     */
    public function getOrCreateBalance(string $tokenableType, int $tokenableId): TokenBalance;

    /**
     * 管理者によるトークン調整
     *
     * @param int $tokenableId
     * @param string $tokenableType
     * @param int $amount
     * @param User $admin
     * @param string|null $note
     * @return bool
     */
    public function adjustTokensByAdmin(int $tokenableId, string $tokenableType, int $amount, User $admin, ?string $note = null): bool;

    /**
     * 無料枠をリセット
     *
     * @param TokenBalance $balance
     * @return void
     */
    public function resetFreeBalance(TokenBalance $balance): void;

    /**
     * 履歴画面用の統計データを取得
     *
     * @param string $tokenableType
     * @param int $tokenableId
     * @return array
     */
    public function getHistoryStats(string $tokenableType, int $tokenableId): array;
}