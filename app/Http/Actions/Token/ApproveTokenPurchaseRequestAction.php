<?php

namespace App\Http\Actions\Token;

use App\Models\TokenPurchaseRequest;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入リクエスト承認アクション
 */
class ApproveTokenPurchaseRequestAction
{
    public function __construct(
        private TokenPurchaseApprovalServiceInterface $approvalService,
        private TokenServiceInterface $tokenService
    ) {}
    
    public function __invoke(Request $request, TokenPurchaseRequest $purchaseRequest): RedirectResponse
    {
        $parent = $request->user();
        
        try {
            // リクエストを承認
            $approvedRequest = $this->approvalService->approveRequest($purchaseRequest, $parent);
            
            // 承認後、実際にトークンを購入処理
            // TODO: 実際の決済処理（Stripe等）
            
            // トークンを子どもに付与
            $this->tokenService->consumeTokens(
                $approvedRequest->user,
                $approvedRequest->package->token_amount,
                'purchase',
                $approvedRequest
            );
            
            return redirect()
                ->back()
                ->with('success', '購入を承認しました。');
            
        } catch (\Exception $e) {
            Log::error('[ApproveTokenPurchaseRequestAction] Approval failed', [
                'request_id' => $purchaseRequest->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()
                ->back()
                ->with('error', '承認処理に失敗しました。');
        }
    }
}