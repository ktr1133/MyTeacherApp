<?php

namespace App\Http\Actions\Token;

use App\Models\TokenPurchaseRequest;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入リクエスト取り下げアクション（子ども用）
 */
class CancelTokenPurchaseRequestAction
{
    public function __construct(
        private TokenPurchaseApprovalServiceInterface $approvalService
    ) {}
    
    public function __invoke(Request $request, TokenPurchaseRequest $purchaseRequest): RedirectResponse
    {
        $child = $request->user();
        
        try {
            // リクエストを取り下げ
            $this->approvalService->cancelRequest($purchaseRequest, $child);
            
            $message = $child->theme === 'child'
                ? '「買いたい」をやめたよ！'
                : '購入リクエストを取り下げました。';
            
            return redirect()
                ->route('tokens.purchase')
                ->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('[CancelTokenPurchaseRequestAction] Cancellation failed', [
                'request_id' => $purchaseRequest->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()
                ->back()
                ->with('error', '取り下げ処理に失敗しました。');
        }
    }
}