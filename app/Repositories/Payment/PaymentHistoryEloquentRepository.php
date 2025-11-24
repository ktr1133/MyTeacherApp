<?php

namespace App\Repositories\Payment;

use App\Models\PaymentHistory;
use Illuminate\Support\Facades\Log;

/**
 * PaymentHistoryリポジトリ実装
 */
class PaymentHistoryEloquentRepository implements PaymentHistoryRepositoryInterface
{
    /**
     * 課金履歴を作成
     *
     * @param array $data 課金履歴データ
     * @return PaymentHistory 作成された課金履歴
     */
    public function create(array $data): PaymentHistory
    {
        Log::info('[PaymentHistoryRepository] Creating payment history', [
            'payable_type' => $data['payable_type'] ?? null,
            'payable_id' => $data['payable_id'] ?? null,
            'amount' => $data['amount'] ?? null,
        ]);

        return PaymentHistory::create($data);
    }
}
