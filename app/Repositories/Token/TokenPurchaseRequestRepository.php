<?php

namespace App\Repositories\Token;

use App\Models\TokenPurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入リクエストリポジトリの実装
 */
class TokenPurchaseRequestRepository implements TokenPurchaseRequestRepositoryInterface
{
    /**
     * 承認待ちのリクエストを作成
     */
    public function create(int $userId, int $packageId): TokenPurchaseRequest
    {
        return TokenPurchaseRequest::create([
            'user_id' => $userId,
            'package_id' => $packageId,
            'status' => 'pending',
        ]);
    }
    
    /**
     * ユーザーの承認待ちリクエストを取得
     */
    public function getPendingByUser(int $userId): Collection
    {
        return TokenPurchaseRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->with(['package'])
            ->latest()
            ->get();
    }
    
    /**
     * 親の子どもたちの承認待ちリクエストを取得
     */
    public function getPendingForParent(User $parent): Collection
    {
        if (!$parent->isParent()) {
            return TokenPurchaseRequest::query()->whereRaw('1 = 0')->get(); // 空のEloquent Collection
        }
        
        return TokenPurchaseRequest::whereHas('user', function ($query) use ($parent) {
            $query->where('group_id', $parent->group_id)
                  ->where('id', '!=', $parent->id);
        })
        ->where('status', 'pending')
        ->with(['user', 'package'])
        ->latest()
        ->get();
    }
    
    /**
     * リクエストを承認
     */
    public function approve(TokenPurchaseRequest $request, int $approvedByUserId): TokenPurchaseRequest
    {        
        $request->update([
            'status' => 'approved',
            'approved_by_user_id' => $approvedByUserId,
            'approved_at' => now(),
        ]);
        
        return $request->fresh();
    }
    
    /**
     * リクエストを却下
     */
    public function reject(TokenPurchaseRequest $request, int $approvedByUserId, ?string $reason = null): TokenPurchaseRequest
    {
        $request->update([
            'status' => 'rejected',
            'approved_by_user_id' => $approvedByUserId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
        
        return $request->fresh();
    }
    
    /**
     * リクエストを取り下げ（子どもが自分でキャンセル）
     */
    public function cancel(TokenPurchaseRequest $request): bool
    {        
        return $request->delete();
    }
    
    /**
     * IDでリクエストを取得
     */
    public function findById(int $id): ?TokenPurchaseRequest
    {
        return TokenPurchaseRequest::with(['user', 'package'])->find($id);
    }
}