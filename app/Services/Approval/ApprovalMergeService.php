<?php

namespace App\Services\Approval;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * 承認待ちデータを統合するサービス
 */
class ApprovalMergeService implements ApprovalMergeServiceInterface
{
    /**
     * タスクとトークン購入の承認待ちデータを統合し、申請日順で並び替える
     *
     * @param Collection $pendingTasks タスクのコレクション（Task モデル）
     * @param Collection $pendingTokenPurchases トークン購入のコレクション（TokenPurchaseRequest モデル）
     * @param int $perPage 1ページあたりの表示件数
     * @return LengthAwarePaginator ページネーション済みデータ
     */
    public function mergeAndSortApprovals(
        Collection $pendingTasks,
        Collection $pendingTokenPurchases,
        int $perPage = 15
    ): LengthAwarePaginator
    {
        // 統合用コレクションを作成
        $merged = new SupportCollection();

        // タスクデータを変換して追加
        if ($pendingTasks->isNotEmpty()) {
            foreach ($pendingTasks as $task) {
                $merged->push([
                    'type'             => 'task',
                    'id'               => $task->id,
                    'model'            => $task,
                    'requester_name'   => $task->user->username ?? '不明',
                    'requester_avatar' => $task->user->avatar_url ?? null,
                    'title'            => $task->title,
                    'description'      => $task->description,
                    'reward'           => $task->reward,
                    'requested_at'     => $task->completed_at ?? $task->created_at,
                    'has_images'       => $task->images->isNotEmpty(),
                    'images_count'     => $task->images->count(),
                ]);
            }
        }

        // トークン購入データを変換して追加
        if ($pendingTokenPurchases->isNotEmpty()) {
            foreach ($pendingTokenPurchases as $purchase) {
                $merged->push([
                    'type'             => 'token_purchase',
                    'id'               => $purchase->id,
                    'model'            => $purchase,
                    'requester_name'   => $purchase->user->username ?? '不明',
                    'requester_avatar' => $purchase->user->avatar_url ?? null,
                    'package_name'     => $purchase->package->name ?? 'パッケージ',
                    'token_amount'     => $purchase->package->token_amount,
                    'price'            => $purchase->package->price,
                    'requested_at'     => $purchase->created_at,
                ]);
            }
        }

        // 申請日時の昇順で並び替え（古い順）
        $sorted = $merged->sortBy('requested_at')->values();

        // ページネーション処理
        return $this->paginate($sorted, $perPage);
    }

    /**
     * コレクションをページネーション
     *
     * @param SupportCollection $items アイテムコレクション
     * @param int $perPage 1ページあたりの表示件数
     * @return LengthAwarePaginator
     */
    protected function paginate(SupportCollection $items, int $perPage): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}