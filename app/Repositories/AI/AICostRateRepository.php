<?php

namespace App\Repositories\AI;

use App\Models\AICostRate;
use Illuminate\Support\Collection;

/**
 * AI コスト換算レートリポジトリ
 */
class AICostRateRepository implements AICostRateRepositoryInterface
{
    /**
     * 有効な換算レートを取得
     *
     * @param string $serviceType
     * @param string|null $serviceDetail
     * @return AICostRate|null
     */
    public function getActiveRate(string $serviceType, ?string $serviceDetail = null): ?AICostRate
    {
        return AICostRate::active()
            ->where('service_type', $serviceType)
            ->when($serviceDetail, function ($query, $detail) {
                return $query->where('service_detail', 'like', '%' . $detail . '%');
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * すべての有効な換算レートを取得
     *
     * @return Collection
     */
    public function getAllActiveRates(): Collection
    {
        return AICostRate::active()
            ->orderBy('service_type')
            ->orderBy('service_detail')
            ->get();
    }

    /**
     * 換算レートを作成
     *
     * @param array $data
     * @return AICostRate
     */
    public function create(array $data): AICostRate
    {
        return AICostRate::create($data);
    }

    /**
     * 換算レートを更新
     *
     * @param AICostRate $rate
     * @param array $data
     * @return bool
     */
    public function update(AICostRate $rate, array $data): bool
    {
        return $rate->update($data);
    }

    /**
     * 換算レートを削除
     *
     * @param AICostRate $rate
     * @return bool
     */
    public function delete(AICostRate $rate): bool
    {
        return $rate->delete();
    }
}