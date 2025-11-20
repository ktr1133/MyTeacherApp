<?php

namespace App\Repositories\Token;

use App\Models\FreeTokenSetting;
use App\Models\TokenBalance;
use App\Models\TokenTransaction;
use App\Models\TokenPackage;
use App\Models\PaymentHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * トークン関連リポジトリ実装
 */
class TokenEloquentRepository implements TokenRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findTokenBalance(string $tokenableType, int $tokenableId): ?TokenBalance
    {
        return TokenBalance::where('tokenable_type', $tokenableType)
            ->where('tokenable_id', $tokenableId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactions(string $tokenableType, int $tokenableId, int $perPage = 20): LengthAwarePaginator
    {
        return TokenTransaction::where('tokenable_type', $tokenableType)
            ->where('tokenable_id', $tokenableId)
            ->with(['user', 'related', 'admin'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getActivePackages(): Collection
    {
        return TokenPackage::active()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findPackage(int $id): ?TokenPackage
    {
        return TokenPackage::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenStats(): array
    {
        $totalUsers = DB::table('token_balances')
            ->where('tokenable_type', 'App\\Models\\User')
            ->count();

        $totalBalance = DB::table('token_balances')->sum('balance');
        $totalConsumed = DB::table('token_balances')->sum('total_consumed');
        $monthlyConsumed = DB::table('token_balances')->sum('monthly_consumed');

        $totalRevenue = DB::table('payment_histories')
            ->where('status', 'succeeded')
            ->sum('amount');

        return [
            'total_users' => $totalUsers,
            'total_balance' => $totalBalance,
            'total_consumed' => $totalConsumed,
            'monthly_consumed' => $monthlyConsumed,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenBalances(int $perPage = 20): LengthAwarePaginator
    {
        return TokenBalance::with('tokenable')
            ->orderBy('balance', 'asc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyPurchaseAmount(string $tokenableType, int $tokenableId): int
    {
        $startOfMonth = now()->startOfMonth();

        return (int) PaymentHistory::where('payable_type', $tokenableType)
            ->where('payable_id', $tokenableId)
            ->where('status', 'succeeded')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyPurchaseTokens(string $tokenableType, int $tokenableId): int
    {
        $startOfMonth = now()->startOfMonth();

        return TokenTransaction::where('tokenable_type', $tokenableType)
            ->where('tokenable_id', $tokenableId)
            ->where('type', 'purchase')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');
    }

    /**
     * {@inheritdoc}
     */
    public function getMonthlyUsage(string $tokenableType, int $tokenableId): int
    {
        $balance = $this->findTokenBalance($tokenableType, $tokenableId);
        
        return $balance ? $balance->monthly_consumed : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTokenBalance(TokenBalance $balance, array $data): bool
    {
        return $balance->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function createTransaction(array $data): TokenTransaction
    {
        return TokenTransaction::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function firstOrCreateTokenBalance(string $tokenableType, int $tokenableId, array $defaults = []): TokenBalance
    {
        return TokenBalance::firstOrCreate(
            [
                'tokenable_type' => $tokenableType,
                'tokenable_id' => $tokenableId,
            ],
            $defaults
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createNotification(array $data)
    {
        // 通知システムが実装されている場合はここで作成
        // 現時点では実装なし
        Log::info('Token notification', $data);
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRecentNotification(int $userId, string $type, \Carbon\Carbon $since): bool
    {
        // 通知システムが実装されている場合はここでチェック
        // 現時点では常にfalse
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentHistories(int $perPage = 20): LengthAwarePaginator
    {
        return PaymentHistory::with(['user'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getFreeTokenSettings(): FreeTokenSetting
    {
        return FreeTokenSetting::first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailablePackages(): Collection
    {
        return TokenPackage::where('is_active', true)
            ->orderBy('price')
            ->get();
    }
}