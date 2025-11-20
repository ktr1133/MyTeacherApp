<?php

namespace App\Http\Actions\Token;

use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * トークン購入承認待ち一覧アクション（親用）
 */
class IndexPendingTokenPurchaseRequestsAction
{
    public function __construct(
        private TokenPurchaseApprovalServiceInterface $approvalService
    ) {}
    
    public function __invoke(Request $request): View
    {
        $parent = $request->user();
        
        // 親でない場合はエラー
        if (!$parent->isParent()) {
            abort(403, '親ユーザーのみアクセスできます。');
        }
        
        // 子どもたちの承認待ちリクエストを取得
        $pendingRequests = $this->approvalService->getPendingRequestsForParent($parent);
        
        return view('tokens.pending-approvals', compact('pendingRequests'));
    }
}