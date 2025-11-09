<?php

namespace App\Repositories\Token;

use App\Models\TokenBalance;
use App\Models\TokenTransaction;
use App\Models\TokenPackage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * トークン関連リポジトリインターフェース
 */
interface TokenRepositoryInterface
{
    /**
     * トークン残高を取得
     *
     * @param string $tokenableType クラス名
     * @param int $tokenableId ID
     * @return TokenBalance|null
     */
    public function findTokenBalance(string $tokenableType, int $tokenableId): ?TokenBalance;

    /**
     * トークン履歴を取得（ページネーション）
     *
     * @param string $tokenableType クラス名
     * @param int $tokenableId ID
     * @param int $perPage ページあたりの件数
     * @return LengthAwarePaginator
     */
    public function getTransactions(string $tokenableType, int $tokenableId, int $perPage = 20): LengthAwarePaginator;

    /**
     * 有効なトークンパッケージ一覧を取得
     *
     * @return Collection
     */
    public function getActivePackages(): Collection;

    /**
     * トークンパッケージをIDで取得
     *
     * @param int $id
     * @return TokenPackage|null
     */
    public function findPackage(int $id): ?TokenPackage;

    /**
     * 全ユーザーのトークン統計を取得
     *
     * @return array
     */
    public function getTokenStats(): array;

    /**
     * トークン残高一覧を取得（管理者用）
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTokenBalances(int $perPage = 20): LengthAwarePaginator;

    /**
     * 今月の購入金額を取得
     *
     * @param string $tokenableType
     * @param int $tokenableId
     * @return int
     */
    public function getMonthlyPurchaseAmount(string $tokenableType, int $tokenableId): int;

    /**
     * 今月の購入トークン数を取得
     *
     * @param string $tokenableType
     * @param int $tokenableId
     * @return int
     */
    public function getMonthlyPurchaseTokens(string $tokenableType, int $tokenableId): int;

    /**
     * 今月の使用トークン数を取得
     *
     * @param string $tokenableType
     * @param int $tokenableId
     * @return int
     */
    public function getMonthlyUsage(string $tokenableType, int $tokenableId): int;

    /**
     * トークン残高を更新
     *
     * @param TokenBalance $balance
     * @param array $data
     * @return bool
     */
    public function updateTokenBalance(TokenBalance $balance, array $data): bool;

    /**
     * トークン取引を作成
     *
     * @param array $data
     * @return TokenTransaction
     */
    public function createTransaction(array $data): TokenTransaction;

    /**
     * トークン残高を取得または作成
     *
     * @param string $tokenableType
     * @param int $tokenableId
     * @param array $defaults
     * @return TokenBalance
     */
    public function firstOrCreateTokenBalance(string $tokenableType, int $tokenableId, array $defaults = []): TokenBalance;

    /**
     * 通知を作成
     *
     * @param array $data
     * @return mixed
     */
    public function createNotification(array $data);

    /**
     * 最近の通知があるかチェック
     *
     * @param int $userId
     * @param string $type
     * @param \Carbon\Carbon $since
     * @return bool
     */
    public function hasRecentNotification(int $userId, string $type, \Carbon\Carbon $since): bool;
}