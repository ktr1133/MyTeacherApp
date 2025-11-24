<?php

namespace App\Services\Token;

use App\Models\User;
use App\Models\TokenBalance;
use App\Models\TokenPackage;
use App\Repositories\Token\TokenPackageRepositoryInterface;
use App\Repositories\Token\TokenRepositoryInterface;
use App\Repositories\Payment\PaymentHistoryRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * トークン管理サービス実装
 * 
 * トークンの消費・調整などのビジネスロジックを提供します。
 * データアクセスは全てRepositoryを経由します。
 */
class TokenService implements TokenServiceInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private TokenPackageRepositoryInterface $tokenPackageRepository,
        private PaymentHistoryRepositoryInterface $paymentHistoryRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function consumeTokens(User $user, int $amount, string $reason, $related = null): bool
    {
        try {
            $balance = $this->getBalanceForUser($user);

            // 残高チェック
            if ($balance->balance < $amount) {
                Log::warning('Insufficient token balance', [
                    'user_id' => $user->id,
                    'required' => $amount,
                    'balance' => $balance->balance,
                ]);

                return false;
            }

            // トークン消費の計算
            $consumptionData = $this->calculateConsumption($balance, $amount);

            return DB::transaction (function () use ($balance, $consumptionData, $related, $user, $reason, $amount) : bool {
                // 残高更新
                $this->tokenRepository->updateTokenBalance($balance, $consumptionData['balanceUpdate']);
    
                // related が文字列の場合は null に変換（防御的処理）
                if (is_string($related)) {
                    Log::warning('Invalid related parameter (string given)', [
                        'user_id' => $user->id,
                        'reason' => $reason,
                        'related' => $related,
                    ]);
                    $related = null;
                }
    
                // トランザクション記録
                $this->tokenRepository->createTransaction([
                    'tokenable_type' => $balance->tokenable_type,
                    'tokenable_id'   => $balance->tokenable_id,
                    'user_id'        => $user->id,
                    'type'           => 'consume',
                    'amount'         => -$amount,
                    'balance_after'  => $consumptionData['balanceUpdate']['balance'],
                    'reason'         => $reason,
                    'related_type'   => ($related && is_object($related)) ? get_class($related) : null,
                    'related_id' => ($related && is_object($related)) ? $related->id : null,
                ]);
    
                // 残高チェックして通知
                $this->checkAndNotifyLowBalance($user, $balance->fresh());

                return true;
            });

        } catch (\Exception $e) {
            Log::error('Token consumption failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkBalance(User $user, int $amount): bool
    {
        $balance = $this->getBalanceForUser($user);
        return $balance->balance >= $amount;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrCreateBalance(string $tokenableType, int $tokenableId): TokenBalance
    {
        return $this->tokenRepository->firstOrCreateTokenBalance(
            $tokenableType,
            $tokenableId,
            [
                'balance' => $this->tokenRepository->getFreeTokenSettings()->amount,
                'free_balance' => $this->tokenRepository->getFreeTokenSettings()->amount,
                'paid_balance' => 0,
                'free_balance_reset_at' => now()->addMonth(),
                'monthly_consumed_reset_at' => now()->addMonth(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function adjustTokensByAdmin(int $tokenableId, string $tokenableType, int $amount, User $admin, ?string $note = null): bool
    {
        try {
            $balance = $this->tokenRepository->findTokenBalance($tokenableType, $tokenableId);
            
            if (!$balance) {
                return false;
            }

            // 調整計算
            $adjustmentData = $this->calculateAdjustment($balance, $amount);

            // 残高更新
            $this->tokenRepository->updateTokenBalance($balance, $adjustmentData);

            // トランザクション記録
            $this->tokenRepository->createTransaction([
                'tokenable_type' => $balance->tokenable_type,
                'tokenable_id' => $balance->tokenable_id,
                'type' => 'admin_adjust',
                'amount' => $amount,
                'balance_after' => $adjustmentData['balance'],
                'reason' => 'admin_adjustment',
                'admin_user_id' => $admin->id,
                'admin_note' => $note,
            ]);

            Log::info('Token adjusted by admin', [
                'admin_id' => $admin->id,
                'tokenable_type' => $tokenableType,
                'tokenable_id' => $tokenableId,
                'amount' => $amount,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Token adjustment failed', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetFreeBalance(TokenBalance $balance): void
    {
        $freeAmount = config('const.token.free_monthly', 1000000);
        
        $resetData = [
            'free_balance' => $freeAmount,
            'balance' => $freeAmount + $balance->paid_balance,
            'free_balance_reset_at' => now()->addMonth(),
            'monthly_consumed' => 0,
            'monthly_consumed_reset_at' => now()->addMonth(),
        ];

        $this->tokenRepository->updateTokenBalance($balance, $resetData);

        $this->tokenRepository->createTransaction([
            'tokenable_type' => $balance->tokenable_type,
            'tokenable_id' => $balance->tokenable_id,
            'type' => 'free_reset',
            'amount' => $freeAmount,
            'balance_after' => $resetData['balance'],
            'reason' => 'monthly_free_reset',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistoryStats(string $tokenableType, int $tokenableId): array
    {
        return [
            'monthlyPurchaseAmount' => $this->tokenRepository->getMonthlyPurchaseAmount($tokenableType, $tokenableId),
            'monthlyPurchaseTokens' => $this->tokenRepository->getMonthlyPurchaseTokens($tokenableType, $tokenableId),
            'monthlyUsage' => $this->tokenRepository->getMonthlyUsage($tokenableType, $tokenableId),
        ];
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * ユーザーに対応するトークン残高を取得
     */
    private function getBalanceForUser(User $user): TokenBalance
    {
        if ($user->token_mode === 'group' && $user->group_id) {
            return $this->getOrCreateBalance(\App\Models\Group::class, $user->group_id);
        }
        
        return $this->getOrCreateBalance(User::class, $user->id);
    }

    /**
     * トークン消費の計算
     */
    private function calculateConsumption(TokenBalance $balance, int $amount): array
    {
        $freeBalance = $balance->free_balance;
        $paidBalance = $balance->paid_balance;
        logger()->info('計算前の無料と有料', [
            'freeBalance' => $freeBalance,
            'paidBalance' => $paidBalance,
        ]);
        // 無料枠から優先的に消費
        if ($freeBalance >= $amount) {
            $freeBalance -= $amount;
        } else {
            $remaining = $amount - $freeBalance;
            $freeBalance = 0;
            $paidBalance -= $remaining;
        }

        logger()->info('計算後の無料と有料', [
            'balance' => $freeBalance + $paidBalance,
            'freeBalance' => $freeBalance,
            'paidBalance' => $paidBalance,
            'totalConsumed' => $balance->total_consumed + $amount,
            'monthlyConsumed' => $balance->monthly_consumed + $amount,
        ]);

        return [
            'balanceUpdate' => [
                'balance'          => $freeBalance + $paidBalance,
                'free_balance'     => $freeBalance,
                'paid_balance'     => $paidBalance,
                'total_consumed'   => $balance->total_consumed + $amount,
                'monthly_consumed' => $balance->monthly_consumed + $amount,
            ],
        ];
    }

    /**
     * 管理者調整の計算
     */
    private function calculateAdjustment(TokenBalance $balance, int $amount): array
    {
        $newBalance = $balance->balance + $amount;
        $newPaidBalance = $balance->paid_balance + $amount;

        if ($amount < 0) {
            // マイナス調整の場合は有料分から減らす
            $newPaidBalance = max(0, $newPaidBalance);
        }

        return [
            'balance' => $newBalance,
            'paid_balance' => $newPaidBalance,
        ];
    }

    /**
     * 残高低下時の通知
     */
    private function checkAndNotifyLowBalance(User $user, TokenBalance $balance): void
    {
        $threshold = config('const.token.low_threshold', 200000);

        if ($balance->balance <= 0) {
            // 枯渇通知
            $this->tokenRepository->createNotification([
                'user_id' => $user->id,
                'type' => 'token_depleted',
                'title' => 'トークンが不足しています',
                'message' => 'AI機能を使用するにはトークンを購入してください。',
                'data' => ['balance' => 0],
                'action_url' => route('tokens.purchase'),
                'action_text' => '購入する',
            ]);
        } elseif ($balance->balance <= $threshold) {
            // 低残高警告（24時間以内に同じ通知がなければ作成）
            $hasRecent = $this->tokenRepository->hasRecentNotification(
                $user->id,
                'token_low',
                now()->subHours(24)
            );

            if (!$hasRecent) {
                $this->tokenRepository->createNotification([
                    'user_id' => $user->id,
                    'type' => 'token_low',
                    'title' => 'トークン残高が少なくなっています',
                    'message' => "現在のトークン残高: " . number_format($balance->balance),
                    'data' => ['balance' => $balance->balance],
                    'action_url' => route('tokens.purchase'),
                    'action_text' => '購入する',
                ]);
            }
        }
    }

    /**
     * AI使用コストを記録（トークン消費なし、ログのみ）
     *
     * @param User $user
     * @param int $amount
     * @param string $reason
     * @param mixed $related
     * @param array $aiUsageDetails
     * @return void
     */
    public function recordAICost(User $user, int $amount, string $reason, $related = null, array $aiUsageDetails = []): void
    {
        $balance = $this->getBalanceForUser($user);

        // related が文字列の場合は null に変換
        if (is_string($related)) {
            Log::warning('Invalid related parameter (string given)', [
                'user_id' => $user->id,
                'reason' => $reason,
                'related' => $related,
            ]);
            $related = null;
        }

        // トランザクション記録（AI使用料として記録）
        $this->tokenRepository->createTransaction([
            'tokenable_type' => $balance->tokenable_type,
            'tokenable_id' => $balance->tokenable_id,
            'user_id' => $user->id,
            'type' => 'ai_usage',
            'amount' => -$amount,
            'balance_after' => $balance->balance, // 残高は変更なし（既に事前消費済み）
            'reason' => $reason,
            'related_type' => ($related && is_object($related)) ? get_class($related) : null,
            'related_id' => ($related && is_object($related)) ? $related->id : null,
            'metadata' => !empty($aiUsageDetails) ? json_encode($aiUsageDetails) : null,
        ]);

        Log::info('AI usage cost recorded', [
            'user_id' => $user->id,
            'amount' => $amount,
            'reason' => $reason,
            'details' => $aiUsageDetails,
        ]);
    }

    /**
     * 事前消費したトークンを精算（差額を返金 or 追加請求）
     *
     * @param User $user
     * @param int $estimatedAmount 事前見積もり額
     * @param int $actualAmount 実際の使用額
     * @param string $reason
     * @param mixed $related
     * @return void
     */
    public function settleTokenConsumption(
        User $user,
        int $estimatedAmount,
        int $actualAmount,
        string $reason,
        $related = null
    ): void {
        logger()->info('トークン消費の精算開始', [
            'user_id' => $user->id,
            'estimatedAmount' => $estimatedAmount,
            'actualAmount' => $actualAmount,
        ]);
        $difference = $estimatedAmount - $actualAmount;

        if ($difference == 0) {
            Log::info('No settlement needed (exact match)', [
                'user_id' => $user->id,
                'estimated' => $estimatedAmount,
                'actual' => $actualAmount,
            ]);
            return;
        }

        $balance = $this->getBalanceForUser($user);

        if ($difference > 0) {
            // 返金
            $this->refundTokens($user, $difference, $reason . '（差額返金）', $related);
        } else {
            // 追加請求
            $additionalAmount = abs($difference);
            
            if ($balance->balance < $additionalAmount) {
                Log::error('Insufficient balance for additional charge', [
                    'user_id' => $user->id,
                    'required' => $additionalAmount,
                    'balance' => $balance->balance,
                ]);
                // 事前消費分を返金
                $this->refundTokens($user, $estimatedAmount, $reason . '（事前消費分返金）', $related);
                throw new \RuntimeException('トークン残高が不足しています。');
            }

            $this->consumeTokens($user, $additionalAmount, $reason . '（追加消費）', $related);
        }
    }

    /**
     * トークンを返金
     *
     * @param User $user
     * @param int $amount
     * @param string $reason
     * @param mixed $related
     * @return bool
     */
    private function refundTokens(User $user, int $amount, string $reason, $related = null): bool
    {
        try {
            $balance = $this->getBalanceForUser($user);

            // 有料残高に返金
            $newBalance = $balance->balance + $amount;
            $newPaidBalance = $balance->paid_balance + $amount;

            $this->tokenRepository->updateTokenBalance($balance, [
                'balance' => $newBalance,
                'paid_balance' => $newPaidBalance,
            ]);

            // related が文字列の場合は null に変換
            if (is_string($related)) {
                $related = null;
            }

            // トランザクション記録
            $this->tokenRepository->createTransaction([
                'tokenable_type' => $balance->tokenable_type,
                'tokenable_id' => $balance->tokenable_id,
                'user_id' => $user->id,
                'type' => 'refund',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'related_type' => ($related && is_object($related)) ? get_class($related) : null,
                'related_id' => ($related && is_object($related)) ? $related->id : null,
            ]);

            Log::info('Token refunded', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Token refund failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailablePackages(): Collection
    {
        return $this->tokenRepository->getAvailablePackages();
    }

    /**
     * IDでトークンパッケージを取得
     *
     * @param int $packageId
     * @return ?TokenPackage
     */
    public function findPackageById(int $packageId): ?TokenPackage
    {
        return $this->tokenPackageRepository->find($packageId);
    }

    /**
     * ユーザーにトークンを付与
     *
     * @param User $user ユーザー
     * @param int $amount 付与量
     * @param string $reason 理由
     * @param mixed $related 関連モデル
     * @return bool
     */
    public function grantTokens(User $user, int $amount, string $reason, $related = null): bool
    {
        try {
            $balance = $this->getBalanceForUser($user);

            // 有料残高に付与
            $newBalance = $balance->balance + $amount;
            $newPaidBalance = $balance->paid_balance + $amount;

            $this->tokenRepository->updateTokenBalance($balance, [
                'balance' => $newBalance,
                'paid_balance' => $newPaidBalance,
            ]);

            // related が文字列の場合は null に変換
            if (is_string($related)) {
                $related = null;
            }

            // トランザクション記録
            $this->tokenRepository->createTransaction([
                'tokenable_type' => $balance->tokenable_type,
                'tokenable_id'   => $balance->tokenable_id,
                'user_id'        => $user->id,
                'type'           => config('const.token_transaction_types.purchase'),
                'amount'         => $amount,
                'balance_after'  => $newBalance,
                'reason'         => $reason,
                'related_type'   => ($related && is_object($related)) ? get_class($related) : null,
                'related_id'     => ($related && is_object($related)) ? $related->id : null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('トークン購入処理でエラーが発生しました。', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function purchaseTokens(
        User $user,
        TokenPackage $package,
        string $paymentMethodType = 'manual_approval',
        ?string $stripePaymentIntentId = null,
        $related = null
    ): bool {
        DB::beginTransaction();
        
        try {
            $balance = $this->getBalanceForUser($user);
            
            // 1. TokenBalance更新（有料残高に付与）
            $newBalance = $balance->balance + $package->token_amount;
            $newPaidBalance = $balance->paid_balance + $package->token_amount;

            $this->tokenRepository->updateTokenBalance($balance, [
                'balance' => $newBalance,
                'paid_balance' => $newPaidBalance,
            ]);

            // 2. TokenTransaction作成
            $this->tokenRepository->createTransaction([
                'tokenable_type' => $balance->tokenable_type,
                'tokenable_id'   => $balance->tokenable_id,
                'user_id'        => $user->id,
                'type'           => config('const.token_transaction_types.purchase'),
                'amount'         => $package->token_amount,
                'balance_after'  => $newBalance,
                'reason'         => 'トークン購入',
                'related_type'   => $package ? get_class($package) : null,
                'related_id'     => $package ? $package->id : null,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
            ]);

            // 3. PaymentHistory作成
            // stripe_payment_intent_idが指定されていない場合は自動生成
            if (!$stripePaymentIntentId) {
                $stripePaymentIntentId = $paymentMethodType === 'manual_approval' 
                    ? 'manual_approval_' . time() . '_' . $user->id
                    : 'purchase_' . time() . '_' . $user->id;
            }

            $this->paymentHistoryRepository->create([
                'payable_type' => get_class($user),
                'payable_id' => $user->id,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
                'token_package_id' => $package->id,
                'amount' => $package->price,
                'token_amount' => $package->token_amount,
                'status' => 'succeeded',
                'payment_method_type' => $paymentMethodType,
            ]);

            DB::commit();
            
            Log::info('トークン購入処理が完了しました。', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'token_amount' => $package->token_amount,
                'price' => $package->price,
                'payment_method_type' => $paymentMethodType,
            ]);

            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('トークン購入処理でエラーが発生しました。', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}