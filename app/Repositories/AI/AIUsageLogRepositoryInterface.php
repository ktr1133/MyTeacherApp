<?php

namespace App\Repositories\AI;

use App\Models\AIUsageLog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * AI 使用ログリポジトリインターフェース
 */
interface AIUsageLogRepositoryInterface
{
    /**
     * AI使用ログを作成
     *
     * @param array $data
     * @return AIUsageLog
     */
    public function create(array $data): AIUsageLog;

    /**
     * 指定モデルの使用ログを取得
     *
     * @param string $usableType
     * @param int $usableId
     * @return Collection
     */
    public function getByUsable(string $usableType, int $usableId): Collection;

    /**
     * ユーザーの使用ログを取得
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getByUser(User $user, int $limit = 100): Collection;

    /**
     * 月次使用量を取得
     *
     * @param User $user
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getMonthlyUsage(User $user, int $year, int $month): array;
}