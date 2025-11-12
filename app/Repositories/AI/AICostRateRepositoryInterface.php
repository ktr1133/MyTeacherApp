<?php

namespace App\Repositories\AI;

use App\Models\AICostRate;
use Illuminate\Support\Collection;

/**
 * AI コスト換算レートリポジトリインターフェース
 */
interface AICostRateRepositoryInterface
{
    /**
     * 有効な換算レートを取得
     *
     * @param string $serviceType
     * @param string|null $serviceDetail
     * @return AICostRate|null
     */
    public function getActiveRate(string $serviceType, ?string $serviceDetail = null): ?AICostRate;

    /**
     * すべての有効な換算レートを取得
     *
     * @return Collection
     */
    public function getAllActiveRates(): Collection;

    /**
     * 換算レートを作成
     *
     * @param array $data
     * @return AICostRate
     */
    public function create(array $data): AICostRate;

    /**
     * 換算レートを更新
     *
     * @param AICostRate $rate
     * @param array $data
     * @return bool
     */
    public function update(AICostRate $rate, array $data): bool;

    /**
     * 換算レートを削除
     *
     * @param AICostRate $rate
     * @return bool
     */
    public function delete(AICostRate $rate): bool;
}