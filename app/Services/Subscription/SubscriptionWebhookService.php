<?php

namespace App\Services\Subscription;

use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stripe WebhookハンドラーService
 * 
 * サブスクリプション関連のWebhookイベントを処理し、
 * グループのサブスクリプション状態を更新する
 */
class SubscriptionWebhookService implements SubscriptionWebhookServiceInterface
{
    /**
     * サブスクリプション作成イベントを処理
     * 
     * Stripe側でサブスクリプションが作成された際に呼び出され、
     * グループのサブスクリプション状態を有効化する
     * 
     * @param array $payload Stripe Webhookペイロード
     * @return void
     */
    public function handleSubscriptionCreated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $groupId = $subscription['metadata']['group_id'] ?? null;
        $plan = $subscription['metadata']['plan'] ?? null;
        
        Log::info('Webhook: Processing subscription created for Groups table', [
            'subscription_id' => $subscription['id'] ?? 'unknown',
            'customer_id' => $subscription['customer'] ?? 'unknown',
            'group_id' => $groupId,
            'plan' => $plan,
        ]);
        
        if (!$groupId || !$plan) {
            Log::error('Subscription created: metadata missing', [
                'subscription_id' => $subscription['id'] ?? 'unknown',
                'payload' => $payload,
            ]);
            return;
        }
        
        try {
            DB::transaction(function () use ($groupId, $plan, $subscription) {
                $group = Group::findOrFail($groupId);
                
                // サブスクリプション有効化
                $group->update([
                    'subscription_active' => true,
                    'subscription_plan' => $plan,
                    'max_members' => $this->getMaxMembers($plan),
                ]);
                
                Log::info('Subscription activated (Groups table updated)', [
                    'group_id' => $groupId,
                    'plan' => $plan,
                    'stripe_subscription_id' => $subscription['id'],
                    'max_members' => $group->max_members,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Subscription created: processing failed', [
                'group_id' => $groupId,
                'plan' => $plan,
                'subscription_id' => $subscription['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * サブスクリプション更新イベントを処理
     * 
     * プラン変更、ステータス変更、数量変更などを処理する
     * 期間終了（ends_at < now()）を検知してGroupsテーブルをリセット
     * 
     * @param array $payload Stripe Webhookペイロード
     * @return void
     */
    public function handleSubscriptionUpdated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $groupId = $subscription['metadata']['group_id'] ?? null;
        
        if (!$groupId) {
            Log::error('Subscription updated: metadata missing', [
                'subscription_id' => $subscription['id'] ?? 'unknown',
                'payload' => $payload,
            ]);
            return;
        }
        
        // 期間終了検知: canceled状態 かつ current_period_end が過去
        if ($subscription['status'] === 'canceled' &&
            isset($subscription['current_period_end']) &&
            $subscription['current_period_end'] < time()) {
            
            Log::info('Webhook: Subscription period ended detected', [
                'subscription_id' => $subscription['id'],
                'group_id' => $groupId,
                'status' => $subscription['status'],
                'current_period_end' => $subscription['current_period_end'],
                'current_time' => time(),
            ]);
            
            // Groupsテーブルをリセット
            $this->resetGroupToFreeByStripeId($subscription['id'], $groupId, 'webhook');
            return;
        }
        
        try {
            DB::transaction(function () use ($groupId, $subscription) {
                $group = Group::findOrFail($groupId);
                
                $status = $subscription['status'];
                $plan = $subscription['metadata']['plan'] ?? $group->subscription_plan;
                
                // ステータスに応じた処理
                $isActive = in_array($status, ['active', 'trialing']);
                
                $group->update([
                    'subscription_active' => $isActive,
                    'subscription_plan' => $plan,
                    'max_members' => $this->getMaxMembers($plan),
                ]);
                
                Log::info('Subscription updated', [
                    'group_id' => $groupId,
                    'plan' => $plan,
                    'status' => $status,
                    'is_active' => $isActive,
                    'stripe_subscription_id' => $subscription['id'],
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Subscription updated: processing failed', [
                'group_id' => $groupId,
                'subscription_id' => $subscription['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * サブスクリプション削除イベントを処理
     * 
     * サブスクリプションがキャンセルされた際に呼び出され、
     * グループのサブスクリプション状態を無効化する
     * 
     * @param array $payload Stripe Webhookペイロード
     * @return void
     */
    public function handleSubscriptionDeleted(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $groupId = $subscription['metadata']['group_id'] ?? null;
        
        if (!$groupId) {
            Log::error('Subscription deleted: metadata missing', [
                'subscription_id' => $subscription['id'] ?? 'unknown',
                'payload' => $payload,
            ]);
            return;
        }
        
        try {
            DB::transaction(function () use ($groupId, $subscription) {
                $group = Group::findOrFail($groupId);
                
                // サブスクリプション無効化
                $group->update([
                    'subscription_active' => false,
                    'subscription_plan' => null,
                    'max_members' => 6, // デフォルトの無料枠に戻す
                ]);
                
                Log::info('Subscription canceled', [
                    'group_id' => $groupId,
                    'stripe_subscription_id' => $subscription['id'],
                    'max_members_reset_to' => 6,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Subscription deleted: processing failed', [
                'group_id' => $groupId,
                'subscription_id' => $subscription['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Groupsテーブルを無料プラン状態にリセット（Stripe IDから検索）
     * 
     * @param string $stripeSubscriptionId Stripe Subscription ID
     * @param int|string $groupId Group ID
     * @param string $trigger 'webhook' or 'cron'
     * @return void
     */
    protected function resetGroupToFreeByStripeId(string $stripeSubscriptionId, int|string $groupId, string $trigger): void
    {
        try {
            $group = Group::findOrFail($groupId);
            
            // 既にリセット済みならスキップ（冪等性）
            if (!$group->subscription_active) {
                Log::info('Webhook: Group already reset', [
                    'group_id' => $group->id,
                    'trigger' => $trigger,
                ]);
                return;
            }
            
            DB::transaction(function () use ($group, $stripeSubscriptionId, $trigger) {
                $group->update([
                    'subscription_active' => false,
                    'subscription_plan' => null,
                    'max_members' => 6,
                    'max_groups' => 1,
                ]);
                
                Log::info('Subscription expired: Groups table reset', [
                    'group_id' => $group->id,
                    'stripe_subscription_id' => $stripeSubscriptionId,
                    'trigger' => $trigger,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Groups reset failed', [
                'group_id' => $groupId,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Webhook処理は失敗してもHTTP 200を返す（Stripe再送を防ぐ）
            // Cronジョブでリトライされる
        }
    }

    /**
     * プランに応じた最大メンバー数を取得
     * 
     * @param string $plan プラン名（family, enterprise）
     * @return int 最大メンバー数
     */
    protected function getMaxMembers(string $plan): int
    {
        return match ($plan) {
            'family' => 6,
            'enterprise' => 20,
            default => 6, // デフォルトは無料枠
        };
    }
}
