<?php

namespace App\Http\Responders\Api\Subscription;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * サブスクリプション管理モバイルAPIのレスポンス整形クラス
 */
class SubscriptionApiResponder
{
    /**
     * プラン一覧取得成功レスポンス
     * 
     * @param array $plans プラン情報配列
     * @param int $additionalMemberPrice 追加メンバー価格
     * @param string|null $currentPlan 現在のプラン種別
     * @return JsonResponse
     */
    public function plansResponse(array $plans, int $additionalMemberPrice, ?string $currentPlan): JsonResponse
    {
        return response()->json([
            'plans' => $plans,
            'additional_member_price' => $additionalMemberPrice,
            'current_plan' => $currentPlan,
        ], Response::HTTP_OK);
    }

    /**
     * 現在のサブスクリプション情報取得成功レスポンス
     * 
     * @param array|null $subscription サブスクリプション情報
     * @return JsonResponse
     */
    public function currentSubscriptionResponse(?array $subscription): JsonResponse
    {
        return response()->json([
            'subscription' => $subscription,
        ], Response::HTTP_OK);
    }

    /**
     * Checkout Session作成成功レスポンス
     * 
     * @param string $sessionUrl Stripe Checkout Session URL
     * @return JsonResponse
     */
    public function checkoutSessionResponse(string $sessionUrl): JsonResponse
    {
        return response()->json([
            'session_url' => $sessionUrl,
        ], Response::HTTP_CREATED);
    }

    /**
     * 請求履歴取得成功レスポンス
     * 
     * @param array $invoices 請求履歴配列
     * @return JsonResponse
     */
    public function invoicesResponse(array $invoices): JsonResponse
    {
        return response()->json([
            'invoices' => $invoices,
        ], Response::HTTP_OK);
    }

    /**
     * プラン変更成功レスポンス
     * 
     * @return JsonResponse
     */
    public function updateSuccessResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'プランを変更しました。',
        ], Response::HTTP_OK);
    }

    /**
     * キャンセル成功レスポンス
     * 
     * @return JsonResponse
     */
    public function cancelSuccessResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'サブスクリプションをキャンセルしました。',
        ], Response::HTTP_OK);
    }

    /**
     * Billing Portal URL取得成功レスポンス
     * 
     * @param string $portalUrl Billing Portal URL
     * @return JsonResponse
     */
    public function billingPortalResponse(string $portalUrl): JsonResponse
    {
        return response()->json([
            'portal_url' => $portalUrl,
        ], Response::HTTP_OK);
    }

    /**
     * エラーレスポンス
     * 
     * @param string $message エラーメッセージ
     * @param int $statusCode HTTPステータスコード
     * @return JsonResponse
     */
    public function errorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'error' => $message,
        ], $statusCode);
    }

    /**
     * 成功レスポンス（汎用）
     * 
     * @param array $data レスポンスデータ
     * @param int $statusCode HTTPステータスコード
     * @return JsonResponse
     */
    public function successResponse(array $data, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json($data, $statusCode);
    }

    /**
     * 権限不足エラーレスポンス
     * 
     * @return JsonResponse
     */
    public function forbiddenResponse(): JsonResponse
    {
        return $this->errorResponse(
            'サブスクリプション管理権限がありません。',
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * サブスクリプション未加入エラーレスポンス
     * 
     * @return JsonResponse
     */
    public function noSubscriptionResponse(): JsonResponse
    {
        return $this->errorResponse(
            '有効なサブスクリプションが見つかりません。',
            Response::HTTP_NOT_FOUND
        );
    }
}
