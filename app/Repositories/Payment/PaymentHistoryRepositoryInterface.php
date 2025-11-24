<?php

namespace App\Repositories\Payment;

use App\Models\PaymentHistory;

/**
 * PaymentHistoryリポジトリインターフェース
 */
interface PaymentHistoryRepositoryInterface
{
    /**
     * 課金履歴を作成
     *
     * @param array $data 課金履歴データ
     * @return PaymentHistory 作成された課金履歴
     */
    public function create(array $data): PaymentHistory;
}
