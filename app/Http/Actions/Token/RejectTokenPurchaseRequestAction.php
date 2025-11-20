<?php

namespace App\Http\Actions\Token;

use App\Models\TokenPurchaseRequest;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入リクエスト却下アクション
 */
class RejectTokenPurchaseRequestAction
{
    public function __construct(
        private TokenPurchaseApprovalServiceInterface $approvalService
    ) {}
    
    public function __invoke(Request $request, TokenPurchaseRequest $purchaseRequest): RedirectResponse
    {
        $parent = $request->user();
        $reason = $request->input('reason');
        
        try {
            // リクエストを却下
            $this->approvalService->rejectRequest($purchaseRequest, $parent, $reason);
            
            return redirect()
                ->back()
                ->with('success', '購入を却下しました。');
            
        } catch (\Exception $e) {
            Log::error('[RejectTokenPurchaseRequestAction] Rejection failed', [
                'request_id' => $purchaseRequest->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()
                ->back()
                ->with('error', '却下処理に失敗しました。');
        }
    }
}