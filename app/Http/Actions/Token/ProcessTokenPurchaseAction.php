<?php

namespace App\Http\Actions\Token;

use App\Http\Requests\Token\PurchaseTokenRequest;
use App\Exceptions\RedirectException;
use App\Services\Token\TokenServiceInterface;
use App\Services\Token\TokenPurchaseApprovalServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * トークン購入処理アクション
 */
class ProcessTokenPurchaseAction
{
    public function __construct(
        private TokenServiceInterface $tokenService,
        private TokenPurchaseApprovalServiceInterface $approvalService
    ) {}
    
    /**
     * トークン購入を処理
     *
     * @param PurchaseTokenRequest $request
     * @return RedirectResponse
     */
    public function __invoke(PurchaseTokenRequest $request): RedirectResponse
    {
        $user = $request->user();
        $packageId = $request->validated()['package_id'];
        
        try {
            // 子どもで承認が必要な場合
            if ($user->requiresPurchaseApproval()) {
                // 承認リクエストを作成
                $purchaseRequest = $this->approvalService->createPurchaseRequest($user, $packageId);
                
                Log::info('[ProcessTokenPurchaseAction] Purchase request created', [
                    'user_id' => $user->id,
                    'request_id' => $purchaseRequest->id,
                ]);
                
                $message = $user->theme === 'child'
                    ? 'おうちの人に「買ってもいい？」ってお願いしたよ！'
                    : '購入リクエストを送信しました。親の承認をお待ちください。';
                
                return redirect()
                    ->route('tokens.purchase')
                    ->with('info', $message);
            }
            
            // 通常の購入処理（承認不要の場合）
            $package = $this->tokenService->findPackageById($packageId);

            if (!$package) {
                throw new RedirectException('パッケージが見つかりません。');
            }
            
            // 実際の決済処理（Stripe等）
            // TODO: 決済処理の実装
            
            // トークンを追加
            $this->tokenService->grantTokens(
                $user,
                $package->token_amount,
                'トークン購入',
                $package
            );
            
            Log::info('[ProcessTokenPurchaseAction] Token purchased successfully', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'tokens' => $package->token_amount,
            ]);
            
            $message = $user->theme === 'child'
                ? "やった！{$package->token_amount}コインをゲットしたよ！"
                : "{$package->token_amount}トークンを購入しました。";
            
            return redirect()
                ->route('tokens.purchase')
                ->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('[ProcessTokenPurchaseAction] Purchase failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            $message = $user->theme === 'child'
                ? 'あれれ...うまくいかなかったよ。もう一回ためしてね！'
                : '購入処理に失敗しました。もう一度お試しください。';
            
            return redirect()
                ->route('tokens.purchase')
                ->with('error', $message);
        }
    }
}