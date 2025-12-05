<?php

namespace App\Http\Responders\Api\Token;

use App\Models\TokenBalance;
use App\Models\TokenPackage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

/**
 * API: トークン管理レスポンダ
 * 
 * トークン関連APIのJSONレスポンスを生成。
 * 
 * @package App\Http\Responders\Api\Token
 */
class TokenApiResponder
{
    /**
     * トークン残高取得成功レスポンス
     *
     * @param TokenBalance $balance
     * @return JsonResponse
     */
    public function balance(TokenBalance $balance): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $this->formatBalanceData($balance),
            ],
        ], 200);
    }

    /**
     * トークン履歴統計取得成功レスポンス
     *
     * @param array $stats
     * @return JsonResponse
     */
    public function history(array $stats): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200);
    }

    /**
     * トークンパッケージ一覧取得成功レスポンス
     *
     * @param Collection $packages
     * @return JsonResponse
     */
    public function packages(Collection $packages): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'packages' => $packages->map(function ($package) {
                    return $this->formatPackageData($package);
                })->toArray(),
            ],
        ], 200);
    }

    /**
     * Stripe Checkout Session作成成功レスポンス
     *
     * @param string $sessionId
     * @param string $sessionUrl
     * @return JsonResponse
     */
    public function checkoutSession(string $sessionId, string $sessionUrl): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Checkout Sessionを作成しました。',
            'data' => [
                'session_id' => $sessionId,
                'session_url' => $sessionUrl,
            ],
        ], 200);
    }

    /**
     * トークンモード切替成功レスポンス
     *
     * @param string $tokenMode
     * @return JsonResponse
     */
    public function modeToggled(string $tokenMode): JsonResponse
    {
        $message = $tokenMode === 'individual' 
            ? 'トークンモードを個人請求に切り替えました。'
            : 'トークンモードをグループ請求に切り替えました。';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'token_mode' => $tokenMode,
            ],
        ], 200);
    }

    /**
     * エラーレスポンス
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function error(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * トークン残高データをフォーマット
     *
     * @param TokenBalance $balance
     * @return array
     */
    private function formatBalanceData(TokenBalance $balance): array
    {
        return [
            'id' => $balance->id,
            'tokenable_type' => $balance->tokenable_type,
            'tokenable_id' => $balance->tokenable_id,
            'balance' => $balance->balance,
            'free_balance' => $balance->free_balance,
            'paid_balance' => $balance->paid_balance,
            'free_balance_reset_at' => $balance->free_balance_reset_at?->toIso8601String(),
            'total_consumed' => $balance->total_consumed,
            'monthly_consumed' => $balance->monthly_consumed,
            'monthly_consumed_reset_at' => $balance->monthly_consumed_reset_at?->toIso8601String(),
            'created_at' => $balance->created_at->toIso8601String(),
            'updated_at' => $balance->updated_at->toIso8601String(),
        ];
    }

    /**
     * トークンパッケージデータをフォーマット
     *
     * @param TokenPackage $package
     * @return array
     */
    private function formatPackageData(TokenPackage $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'description' => $package->description,
            'amount' => $package->amount,
            'price' => $package->price,
            'stripe_price_id' => $package->stripe_price_id,
            'is_active' => $package->is_active,
            'display_order' => $package->display_order,
            'created_at' => $package->created_at->toIso8601String(),
        ];
    }
}
