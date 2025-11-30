<?php

namespace App\Http\Responders\Subscription;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * サブスクリプション関連のレスポンダー
 */
class SubscriptionResponder
{
    /**
     * サブスクリプションプラン選択画面を返す
     * 
     * @param array $plans 利用可能なプラン情報
     * @param array|null $currentSubscription 現在のサブスクリプション情報
     * @param bool $isChildTheme 子どもテーマかどうか
     * @param array $invoices 請求履歴（サブスクリプション加入者のみ）
     * @return View
     */
    public function showPlans(array $plans, ?array $currentSubscription, bool $isChildTheme, array $invoices = []): View
    {
        return view('subscriptions.select-plan', [
            'plans' => $plans,
            'currentSubscription' => $currentSubscription,
            'isChildTheme' => $isChildTheme,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Checkout Session作成成功後のリダイレクト
     * 
     * @param string $checkoutUrl Stripe Checkout URL
     * @return RedirectResponse
     */
    public function redirectToCheckout(string $checkoutUrl): RedirectResponse
    {
        return redirect()->away($checkoutUrl);
    }

    /**
     * サブスクリプション処理成功画面を返す
     * 
     * @param bool $isChildTheme 子どもテーマかどうか
     * @return View
     */
    public function showSuccess(bool $isChildTheme): View
    {
        return view('subscriptions.success', [
            'isChildTheme' => $isChildTheme,
        ]);
    }

    /**
     * サブスクリプション処理キャンセル画面を返す
     * 
     * @param bool $isChildTheme 子どもテーマかどうか
     * @return View
     */
    public function showCancel(bool $isChildTheme): View
    {
        return view('subscriptions.cancel', [
            'isChildTheme' => $isChildTheme,
        ]);
    }

    /**
     * エラーレスポンスを返す
     * 
     * @param string $message エラーメッセージ
     * @return RedirectResponse
     */
    public function error(string $message): RedirectResponse
    {
        return redirect()->back()
            ->withErrors(['error' => $message])
            ->withInput();
    }

    /**
     * サブスクリプション管理画面を返す
     * 
     * @param array|null $subscription 現在のサブスクリプション情報
     * @param array $plans 利用可能なプラン情報
     * @param array $invoices 請求履歴
     * @param bool $isChildTheme 子どもテーマかどうか
     * @return View
     */
    public function showManagePage(?array $subscription, array $plans, array $invoices, bool $isChildTheme): View
    {
        return view('subscriptions.manage', [
            'subscription' => $subscription,
            'plans' => $plans,
            'invoices' => $invoices,
            'isChildTheme' => $isChildTheme,
        ]);
    }

    /**
     * サブスクリプション操作成功時のリダイレクト
     * 
     * @param string $message 成功メッセージ
     * @return RedirectResponse
     */
    public function success(string $message): RedirectResponse
    {
        return redirect()->route('subscriptions.manage')
            ->with('success', $message);
    }

    /**
     * Billing Portal へのリダイレクト
     * 
     * @param string $portalUrl Billing Portal URL
     * @return RedirectResponse
     */
    public function redirectToBillingPortal(string $portalUrl): RedirectResponse
    {
        return redirect()->away($portalUrl);
    }
}
