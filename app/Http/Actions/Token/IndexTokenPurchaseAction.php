<?php

namespace App\Http\Actions\Token;

use App\Services\Token\TokenServiceInterface;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * トークン購入ページ表示アクション
 */
class IndexTokenPurchaseAction
{
    public function __construct(
        private TokenServiceInterface $tokenService,
        private TokenPurchaseApprovalServiceInterface $approvalService
    ) {}
    
    /**
     * トークン購入ページを表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        
        // トークン残高を取得
        $tokenableType = get_class($user);
        $tokenableId = $user->id;
        $balance = $this->tokenService->getOrCreateBalance($tokenableType, $tokenableId);
        
        // パッケージ一覧を取得
        $packages = $this->tokenService->getAvailablePackages();
        
        // 承認待ちリクエストを取得（子どもの場合のみ）
        $pendingRequests = collect();
        if ($user->isChild()) {
            $pendingRequests = $this->approvalService->getPendingRequests($user);
        }
        
        // 親の場合は子どもたちの承認待ちリクエストを取得
        $childrenRequests = collect();
        if ($user->isParent()) {
            $childrenRequests = $this->approvalService->getPendingRequestsForParent($user);
        }
        
        return view('tokens.purchase', compact(
            'balance',
            'packages',
            'pendingRequests',
            'childrenRequests'
        ));
    }
}