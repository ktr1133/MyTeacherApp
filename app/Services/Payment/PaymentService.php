<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\TokenPackage;
use App\Repositories\Token\TokenRepositoryInterface;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 決済サービス実装
 * 
 * Stripeを使用した決済処理とWebhook処理を提供します。
 * データアクセスは全てRepositoryを経由します。
 */
class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private TokenServiceInterface $tokenService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function processPurchase(User $user, TokenPackage $package, string $paymentMethodId): array
    {
        try {
            // 課金対象を決定
            $billable = $this->getBillableEntity($user);

            DB::beginTransaction();

            // Stripe決済実行
            $paymentIntent = $billable->charge(
                $package->price * 100, // 円→銭
                $paymentMethodId,
                [
                    'metadata' => [
                        'package_id' => $package->id,
                        'token_amount' => $package->token_amount,
                        'user_id' => $user->id,
                    ],
                ]
            );

            // 支払い履歴を記録
            $history = $this->tokenRepository->createPaymentHistory([
                'payable_type' => get_class($billable),
                'payable_id' => $billable->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                'token_package_id' => $package->id,
                'amount' => $package->price,
                'token_amount' => $package->token_amount,
                'status' => $paymentIntent->status === 'succeeded' ? 'succeeded' : 'pending',
                'payment_method_type' => $paymentIntent->payment_method_types[0] ?? null,
                'stripe_metadata' => $paymentIntent->metadata->toArray(),
            ]);

            // 決済成功時はトークン追加
            if ($paymentIntent->status === 'succeeded') {
                $this->addTokensFromPayment($billable, $package, $paymentIntent->id);
                $this->notifyPaymentSuccess($user, $package);
            }

            DB::commit();

            Log::info('Token purchase processed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'error' => null,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Token purchase failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'payment_intent' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handlePaymentSucceeded(array $payload): void
    {
        $paymentIntent = $payload['data']['object'];

        $history = $this->tokenRepository->findPaymentHistoryByIntent($paymentIntent['id']);
        
        if (!$history) {
            Log::warning('Payment history not found for succeeded payment', [
                'payment_intent_id' => $paymentIntent['id'],
            ]);
            return;
        }

        DB::transaction(function () use ($history, $paymentIntent) {
            // 履歴更新
            $this->tokenRepository->updatePaymentHistory($history, [
                'status' => 'succeeded',
                'stripe_charge_id' => $paymentIntent['charges']['data'][0]['id'] ?? null,
            ]);

            // トークン追加（重複チェック）
            $alreadyAdded = $this->tokenRepository->transactionExists(
                $history->payable_type,
                $history->payable_id,
                'purchase',
                $paymentIntent['id']
            );

            if (!$alreadyAdded) {
                $package = $this->tokenRepository->findPackage($history->token_package_id);
                $this->addTokensFromPayment($history->payable, $package, $paymentIntent['id']);
            }
        });

        Log::info('Payment succeeded webhook processed', [
            'payment_intent_id' => $paymentIntent['id'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function handlePaymentFailed(array $payload): void
    {
        $paymentIntent = $payload['data']['object'];

        $history = $this->tokenRepository->findPaymentHistoryByIntent($paymentIntent['id']);
        
        if (!$history) {
            Log::warning('Payment history not found for failed payment', [
                'payment_intent_id' => $paymentIntent['id'],
            ]);
            return;
        }

        $this->tokenRepository->updatePaymentHistory($history, [
            'status' => 'failed',
            'failure_message' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown error',
        ]);

        // 失敗通知
        $this->notifyPaymentFailure($history);

        Log::info('Payment failed webhook processed', [
            'payment_intent_id' => $paymentIntent['id'],
        ]);
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * 課金対象エンティティを取得
     */
    private function getBillableEntity(User $user)
    {
        if ($user->token_mode === 'group' && $user->group_id) {
            return $user->group;
        }
        return $user;
    }

    /**
     * 決済からトークンを追加
     */
    private function addTokensFromPayment($billable, TokenPackage $package, string $paymentIntentId): void
    {
        $balance = $this->tokenService->getOrCreateBalance(
            get_class($billable),
            $billable->id
        );

        // 残高更新
        $newPaidBalance = $balance->paid_balance + $package->token_amount;
        $newBalance = $balance->free_balance + $newPaidBalance;

        $this->tokenRepository->updateTokenBalance($balance, [
            'paid_balance' => $newPaidBalance,
            'balance' => $newBalance,
        ]);

        // トランザクション記録
        $this->tokenRepository->createTransaction([
            'tokenable_type' => get_class($billable),
            'tokenable_id' => $billable->id,
            'type' => 'purchase',
            'amount' => $package->token_amount,
            'balance_after' => $newBalance,
            'reason' => 'token_purchase',
            'stripe_payment_intent_id' => $paymentIntentId,
            'stripe_metadata' => ['package_id' => $package->id],
        ]);
    }

    /**
     * 決済成功の通知
     */
    private function notifyPaymentSuccess(User $user, TokenPackage $package): void
    {
        $this->tokenRepository->createNotification([
            'user_id' => $user->id,
            'type' => 'payment_success',
            'title' => 'トークン購入が完了しました',
            'message' => "{$package->name}を購入しました。",
            'data' => [
                'package' => $package->name,
                'amount' => $package->token_amount,
            ],
            'action_url' => route('tokens.history'),
            'action_text' => '履歴を見る',
        ]);
    }

    /**
     * 決済失敗の通知
     */
    private function notifyPaymentFailure($history): void
    {
        if ($history->payable_type === User::class) {
            $user = $history->payable;
        } else {
            // グループの場合はマスターに通知
            $user = $history->payable->master;
        }

        if ($user) {
            $this->tokenRepository->createNotification([
                'user_id' => $user->id,
                'type' => 'payment_failed',
                'title' => '決済に失敗しました',
                'message' => '申し訳ございませんが、決済処理に失敗しました。',
                'data' => ['error' => $history->failure_message],
                'action_url' => route('tokens.purchase'),
                'action_text' => '再試行',
            ]);
        }
    }
}