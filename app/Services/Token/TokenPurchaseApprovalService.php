<?php

namespace App\Services\Token;

use App\Models\TokenPurchaseRequest;
use App\Models\User;
use App\Repositories\Token\TokenPurchaseRequestRepositoryInterface;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入承認サービスの実装
 */
class TokenPurchaseApprovalService implements TokenPurchaseApprovalServiceInterface
{
    public function __construct(
        private TokenPurchaseRequestRepositoryInterface $repository,
        private NotificationService $notificationService
    ) {}
    
    /**
     * 子どもが購入リクエストを作成
     */
    public function createPurchaseRequest(User $child, int $packageId): TokenPurchaseRequest
    {
        // 子どもでない場合はエラー
        if (!$child->isChild()) {
            throw new \Exception('購入承認リクエストは子どもユーザーのみ作成できます。');
        }
        
        // 承認が不要な場合はエラー
        if (!$child->requiresPurchaseApproval()) {
            throw new \Exception('このユーザーは承認不要設定です。');
        }
        
        DB::beginTransaction();
        
        try {
            // リクエスト作成
            $request = $this->repository->create($child->id, $packageId);
            
            // 親に通知を送信
            $this->sendRequestNotificationToParent($child, $request);
            
            DB::commit();
            
            Log::info('[TokenPurchaseApprovalService] Purchase request created successfully', [
                'request_id' => $request->id,
            ]);
            
            return $request;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[TokenPurchaseApprovalService] Failed to create purchase request', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * 親がリクエストを承認
     */
    public function approveRequest(TokenPurchaseRequest $request, User $parent): TokenPurchaseRequest
    {        
        // 親でない場合はエラー
        if (!$parent->isParent()) {
            throw new \Exception('承認は親ユーザーのみ実行できます。');
        }
        
        // 同じグループか確認
        if ($request->user->group_id !== $parent->group_id) {
            throw new \Exception('異なるグループのリクエストは承認できません。');
        }
        
        // 承認待ちでない場合はエラー
        if (!$request->isPending()) {
            throw new \Exception('承認待ちのリクエストではありません。');
        }
        
        DB::beginTransaction();
        
        try {
            // リクエストを承認
            $approvedRequest = $this->repository->approve($request, $parent->id);
            
            // 子どもに承認通知を送信
            $this->sendApprovalNotificationToChild($approvedRequest);
            
            DB::commit();
            
            Log::info('[TokenPurchaseApprovalService] Request approved successfully', [
                'request_id' => $approvedRequest->id,
            ]);
            
            return $approvedRequest;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[TokenPurchaseApprovalService] Failed to approve request', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * 親がリクエストを却下
     */
    public function rejectRequest(TokenPurchaseRequest $request, User $parent, ?string $reason = null): TokenPurchaseRequest
    {
        // 親でない場合はエラー
        if (!$parent->isParent()) {
            throw new \Exception('却下は親ユーザーのみ実行できます。');
        }
        
        // 同じグループか確認
        if ($request->user->group_id !== $parent->group_id) {
            throw new \Exception('異なるグループのリクエストは却下できません。');
        }
        
        // 承認待ちでない場合はエラー
        if (!$request->isPending()) {
            throw new \Exception('承認待ちのリクエストではありません。');
        }
        
        DB::beginTransaction();
        
        try {
            // リクエストを却下
            $rejectedRequest = $this->repository->reject($request, $parent->id, $reason);
            
            // 子どもに却下通知を送信
            $this->sendRejectionNotificationToChild($rejectedRequest);
            
            DB::commit();
            
            Log::info('[TokenPurchaseApprovalService] Request rejected successfully', [
                'request_id' => $rejectedRequest->id,
            ]);
            
            return $rejectedRequest;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[TokenPurchaseApprovalService] Failed to reject request', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * 子どもがリクエストを取り下げ
     */
    public function cancelRequest(TokenPurchaseRequest $request, User $child): bool
    {
        // 自分のリクエストか確認
        if ($request->user_id !== $child->id) {
            throw new \Exception('他のユーザーのリクエストは取り下げできません。');
        }
        
        // 承認待ちでない場合はエラー
        if (!$request->isPending()) {
            throw new \Exception('承認待ちのリクエストではありません。');
        }
        
        DB::beginTransaction();
        
        try {
            // リクエストを削除
            $result = $this->repository->cancel($request);
            
            // 親に取り下げ通知を送信
            $this->sendCancellationNotificationToParent($child, $request);
            
            DB::commit();
            
            Log::info('[TokenPurchaseApprovalService] Request canceled successfully', [
                'request_id' => $request->id,
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[TokenPurchaseApprovalService] Failed to cancel request', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * ユーザーの承認待ちリクエストを取得
     */
    public function getPendingRequests(User $user): Collection
    {
        return $this->repository->getPendingByUser($user->id);
    }
    
    /**
     * 親の子どもたちの承認待ちリクエストを取得
     */
    public function getPendingRequestsForParent(User $parent): Collection
    {
        return $this->repository->getPendingForParent($parent);
    }
    
    /**
     * 親にリクエスト通知を送信
     */
    private function sendRequestNotificationToParent(User $child, TokenPurchaseRequest $request): void
    {
        $parent = User::where('group_id', $child->group_id)
            ->where('group_edit_flg', true)
            ->first();
        
        if (!$parent) {
            Log::warning('[TokenPurchaseApprovalService] Parent not found for notification', [
                'child_id' => $child->id,
                'group_id' => $child->group_id,
            ]);
            return;
        }
        
        $this->notificationService->createPurchaseRequestNotification(
            $parent,
            $child,
            $request->package
        );
    }
    
    /**
     * 子どもに承認通知を送信
     */
    private function sendApprovalNotificationToChild(TokenPurchaseRequest $request): void
    {
        $this->notificationService->createPurchaseApprovedNotification(
            $request->user,
            $request->approvedBy,
            $request->package
        );
    }
    
    /**
     * 子どもに却下通知を送信
     */
    private function sendRejectionNotificationToChild(TokenPurchaseRequest $request): void
    {
        $this->notificationService->createPurchaseRejectedNotification(
            $request->user,
            $request->approvedBy,
            $request->package,
            $request->rejection_reason
        );
    }
    
    /**
     * 親に取り下げ通知を送信
     */
    private function sendCancellationNotificationToParent(User $child, TokenPurchaseRequest $request): void
    {
        $parent = User::where('group_id', $child->group_id)
            ->where('group_edit_flg', true)
            ->first();
        
        if (!$parent) {
            return;
        }
        
        $this->notificationService->createPurchaseCanceledNotification(
            $parent,
            $child,
            $request->package
        );
    }
}