<?php

namespace App\Http\Actions\Token;

use App\Http\Requests\Token\PurchaseTokenRequest;
use App\Repositories\Token\TokenRepositoryInterface;
use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * トークン購入処理アクション
 */
class ProcessTokenPurchaseAction
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private PaymentServiceInterface $paymentService
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
        $package = $this->tokenRepository->findPackage($request->package_id);

        if (!$package) {
            return redirect()
                ->back()
                ->with('error', '選択されたパッケージが見つかりません。');
        }

        $result = $this->paymentService->processPurchase(
            $user,
            $package,
            $request->payment_method
        );

        if ($result['success']) {
            if ($result['payment_intent']->status === 'succeeded') {
                return redirect()
                    ->route('tokens.history')
                    ->with('success', 'トークンを購入しました。');
            }

            return redirect()
                ->back()
                ->with('info', '決済処理中です。しばらくお待ちください。');
        }

        return redirect()
            ->back()
            ->with('error', '決済に失敗しました: ' . $result['error']);
    }
}