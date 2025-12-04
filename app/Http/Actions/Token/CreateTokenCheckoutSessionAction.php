<?php

namespace App\Http\Actions\Token;

use App\Http\Requests\Token\PurchaseTokenRequest;
use App\Services\Token\TokenServiceInterface;
use App\Services\Token\TokenPurchaseServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Checkout Session作成アクション
 * 
 * トークン購入のためのStripe Checkout画面にリダイレクトします。
 */
class CreateTokenCheckoutSessionAction
{
    public function __construct(
        protected TokenServiceInterface $tokenService,
        protected TokenPurchaseServiceInterface $purchaseService
    ) {}

    /**
     * Checkout Session作成とリダイレクト
     *
     * @param PurchaseTokenRequest $request
     * @return RedirectResponse
     */
    public function __invoke(PurchaseTokenRequest $request): RedirectResponse
    {
        $user = $request->user();
        $packageId = $request->validated()['package_id'];
        
        try {
            // パッケージ取得
            $package = $this->tokenService->findPackageById($packageId);
            
            if (!$package) {
                return redirect()
                    ->route('tokens.purchase')
                    ->with('error', 'パッケージが見つかりません。');
            }
            
            // Stripe Price IDチェック
            if (!$package->stripe_price_id) {
                Log::error('Stripe Price ID not set', [
                    'package_id' => $packageId,
                    'package_name' => $package->name,
                ]);
                
                return redirect()
                    ->route('tokens.purchase')
                    ->with('error', 'このパッケージは現在購入できません。');
            }
            
            // Checkout Session作成
            $checkoutSession = $this->purchaseService->createCheckoutSession($user, $package);
            
            Log::info('Checkout Session created', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'session_id' => $checkoutSession->id,
            ]);
            
            // Stripeの決済画面にリダイレクト
            return redirect($checkoutSession->url);
            
        } catch (\Exception $e) {
            Log::error('Failed to create Checkout Session', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $message = $user->theme === 'child'
                ? 'あれれ...うまくいかなかったよ。もう一回ためしてね！'
                : '決済画面の作成に失敗しました。もう一度お試しください。';
            
            return redirect()
                ->route('tokens.purchase')
                ->withErrors(['error' => $message]);
        }
    }
}
