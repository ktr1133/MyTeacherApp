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
     * 親がリクエストを承認してStripe Checkout Sessionを作成（API用）
     *
     * @param int $requestId リクエストID
     * @param User $parent 承認する親ユーザー
     * @return array ['request' => TokenPurchaseRequest, 'checkout_url' => string, 'session_id' => string]
     * @throws \Exception
     */
    public function approveRequestWithCheckout(int $requestId, User $parent): array;
    
    /**
     * 親がリクエストを却下
     *
     * @param int $requestId リクエストID
     * @param User $parent 却下する親ユーザー
     * @param string|null $reason 却下理由
     * @return TokenPurchaseRequest
     * @throws \Exception
     */
    public function rejectRequest(int $requestId, User $parent, ?string $reason = null): TokenPurchaseRequest;
    
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