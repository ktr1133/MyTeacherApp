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
            // リクエスト承認処理　&& トークン購入処理
            $this->approvalService->approveRequest($purchaseRequest, $parent);

            return redirect()
                ->back()
                ->with('success', '購入を承認しました。')
                ->with('avatar_event', config('const.avatar_events.token_purchased'));
            
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