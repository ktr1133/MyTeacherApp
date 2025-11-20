<?php

namespace App\Services\Approval;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 承認待ちデータを統合するサービスのインターフェース
 */
interface ApprovalMergeServiceInterface
{
    /**
     * タスクとトークン購入の承認待ちデータを統合し、申請日順で並び替える
     *
     * @param Collection $pendingTasks タスクのコレクション
     * @param Collection $pendingTokenPurchases トークン購入のコレクション
     * @param int $perPage 1ページあたりの表示件数
     * @return LengthAwarePaginator ページネーション済みデータ
     */
    public function mergeAndSortApprovals(
        Collection $pendingTasks,
        Collection $pendingTokenPurchases,
        int $perPage = 15
    ): LengthAwarePaginator;
}