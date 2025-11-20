<?php

namespace App\Repositories\Token;

use App\Models\TokenPurchaseRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * トークン購入リクエストリポジトリのインターフェース
 */
interface TokenPurchaseRequestRepositoryInterface
{
    /**
     * 承認待ちのリクエストを作成
     *
     * @param int $userId 子どものユーザーID
     * @param int $packageId パッケージID
     * @return TokenPurchaseRequest
     */
    public function create(int $userId, int $packageId): TokenPurchaseRequest;
    
    /**
     * ユーザーの承認待ちリクエストを取得
     *
     * @param int $userId
     * @return Collection
     */
    public function getPendingByUser(int $userId): Collection;
    
    /**
     * 親の子どもたちの承認待ちリクエストを取得
     *
     * @param User $parent
     * @return Collection
     */
    public function getPendingForParent(User $parent): Collection;
    
    /**
     * リクエストを承認
     *
     * @param TokenPurchaseRequest $request
     * @param int $approvedByUserId 承認した親のID
     * @return TokenPurchaseRequest
     */
    public function approve(TokenPurchaseRequest $request, int $approvedByUserId): TokenPurchaseRequest;
    
    /**
     * リクエストを却下
     *
     * @param TokenPurchaseRequest $request
     * @param int $approvedByUserId 却下した親のID
     * @param string|null $reason 却下理由
     * @return TokenPurchaseRequest
     */
    public function reject(TokenPurchaseRequest $request, int $approvedByUserId, ?string $reason = null): TokenPurchaseRequest;
    
    /**
     * リクエストを取り下げ（子どもが自分でキャンセル）
     *
     * @param TokenPurchaseRequest $request
     * @return bool
     */
    public function cancel(TokenPurchaseRequest $request): bool;
    
    /**
     * IDでリクエストを取得
     *
     * @param int $id
     * @return TokenPurchaseRequest|null
     */
    public function findById(int $id): ?TokenPurchaseRequest;
}