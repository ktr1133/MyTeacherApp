<?php

namespace App\Repositories\AI;

use App\Models\AIUsageLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AI 使用ログリポジトリ
 */
class AIUsageLogRepository implements AIUsageLogRepositoryInterface
{
    /**
     * AI使用ログを作成
     *
     * @param array $data
     * @return AIUsageLog
     */
    public function create(array $data): AIUsageLog
    {
        return AIUsageLog::create($data);
    }

    /**
     * 指定モデルの使用ログを取得
     *
     * @param string $usableType
     * @param int $usableId
     * @return Collection
     */
    public function getByUsable(string $usableType, int $usableId): Collection
    {
        return AIUsageLog::where('usable_type', $usableType)
            ->where('usable_id', $usableId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * ユーザーの使用ログを取得
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getByUser(User $user, int $limit = 100): Collection
    {
        return AIUsageLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 月次使用量を取得
     *
     * @param User $user
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getMonthlyUsage(User $user, int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $result = AIUsageLog::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->select(
                'service_type',
                DB::raw('SUM(units_used) as total_units'),
                DB::raw('SUM(cost_usd) as total_cost_usd'),
                DB::raw('SUM(token_cost) as total_token_cost')
            )
            ->groupBy('service_type')
            ->get();

        return [
            'details' => $result,
            'total_cost_usd' => $result->sum('total_cost_usd'),
            'total_token_cost' => $result->sum('total_token_cost'),
        ];
    }
}