<?php

namespace App\Http\Actions\Api\Subscription;

use App\Http\Responders\Api\Subscription\SubscriptionApiResponder;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * サブスクリプションプラン一覧取得Action
 * 
 * エンドポイント: GET /api/subscriptions/plans
 */
class GetSubscriptionPlansAction
{
    /**
     * @param SubscriptionServiceInterface $subscriptionService サブスクリプションサービス
     * @param SubscriptionApiResponder $responder レスポンダー
     */
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService,
        protected SubscriptionApiResponder $responder
    ) {}

    /**
     * サブスクリプションプラン一覧を取得
     * 
     * @param Request $request リクエスト
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $group = $user->group;

            // プラン一覧の表示は子どもテーマでも許可
            // （プラン変更やキャンセルは親ユーザーのみ）

            // プラン一覧取得（連想配列を配列に変換）
            $plansData = $this->subscriptionService->getAvailablePlans();
            $plans = [];
            
            // 機能名マッピング
            $featureLabels = [
                'unlimited_group_tasks' => 'グループタスク無制限',
                'monthly_reports' => '月次レポート',
                'group_token_sharing' => 'グループ内トークン共有',
                'statistics_reports' => '統計レポート',
                'priority_support' => '優先サポート',
            ];
            
            foreach ($plansData as $key => $plan) {
                // featuresを連想配列から文字列配列に変換
                $features = [];
                if (isset($plan['features']) && is_array($plan['features'])) {
                    foreach ($plan['features'] as $featureKey => $enabled) {
                        if ($enabled && isset($featureLabels[$featureKey])) {
                            $features[] = $featureLabels[$featureKey];
                        }
                    }
                }
                
                $plans[] = [
                    'name' => $key,
                    'displayName' => $plan['name'] ?? '',
                    'description' => $plan['description'] ?? '',
                    'price' => $plan['amount'] ?? 0,
                    'maxMembers' => $plan['max_members'] ?? 0,
                    'features' => $features,
                    'stripePriceId' => $plan['price_id'] ?? '',
                    'stripePlanName' => $key,
                ];
            }
            
            // 追加メンバー価格取得
            $additionalMemberPrice = config('const.stripe.additional_member_price', 150);
            
            // 現在のプラン取得
            $currentSubscription = $this->subscriptionService->getCurrentSubscription($group);
            $currentPlan = $currentSubscription['plan'] ?? null;

            return $this->responder->plansResponse($plans, $additionalMemberPrice, $currentPlan);
        } catch (\Exception $e) {
            return $this->responder->errorResponse($e->getMessage(), 500);
        }
    }
}
