<?php

namespace App\Services\Token;

use App\Models\TokenPurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * トークン購入承認サービスのインターフェース
 */
interface TokenPurchaseApprovalServiceInterface
{
    /**
     * 子どもが購入リクエストを作成
     *
     * @param User $child
     * @param int $packageId
     * @return TokenPurchaseRequest
     * @throws \Exception
     */
    public function createPurchaseRequest(User $child, int $packageId): TokenPurchaseRequest;
    
    /**
     * 親がリクエストを承認
     *
     * @param TokenPurchaseRequest $request
     * @param User $parent
     * @return TokenPurchaseRequest
     * @throws \Exception
     */
    public function approveRequest(TokenPurchaseRequest $request, User $parent): TokenPurchaseRequest;
    
    /**
     * 親がリクエストを却下
     *
     * @param TokenPurchaseRequest $request
     * @param User $parent
     * @param string|null $reason
     * @return TokenPurchaseRequest
     * @throws \Exception
     */
    public function rejectRequest(TokenPurchaseRequest $request, User $parent, ?string $reason = null): TokenPurchaseRequest;
    
    /**
     * 子どもがリクエストを取り下げ
     *
     * @param TokenPurchaseRequest $request
     * @param User $child
     * @return bool
     * @throws \Exception
     */
    public function cancelRequest(TokenPurchaseRequest $request, User $child): bool;
    
    /**
     * ユーザーの承認待ちリクエストを取得
     *
     * @param User $user
     * @return Collection
     */
    public function getPendingRequests(User $user): Collection;
    
    /**
     * 親の子どもたちの承認待ちリクエストを取得
     *
     * @param User $parent
     * @return Collection
     */
    public function getPendingRequestsForParent(User $parent): Collection;
}