/**
 * サブスクリプションサービス
 * 
 * サブスクリプションプラン、現在のサブスク情報、請求履歴、
 * プラン変更、キャンセル、Stripe連携のAPI通信を担当
 * 
 * @module services/subscription.service
 */

import api from './api';
import type {
  SubscriptionPlan,
  CurrentSubscription,
  Invoice,
  CheckoutSession,
  BillingPortalSession,
} from '../types/subscription.types';

/**
 * サブスクリプションプラン一覧を取得
 * 
 * GET /api/v1/subscriptions/plans
 * 
 * @returns Promise<SubscriptionPlan[]> プラン一覧
 * @throws エラー時は例外をスロー（403: 子どもテーマユーザー）
 */
export async function getSubscriptionPlans(): Promise<{
  plans: SubscriptionPlan[];
  additionalMemberPrice: number;
  currentPlan: string | null;
}> {
  const response = await api.get('/subscriptions/plans');
  return {
    plans: response.data.plans,
    additionalMemberPrice: response.data.additional_member_price,
    currentPlan: response.data.current_plan,
  };
}

/**
 * 現在のサブスクリプション情報を取得
 * 
 * GET /api/v1/subscriptions/current
 * 
 * @returns Promise<CurrentSubscription | null> サブスク情報（未加入時はnull）
 * @throws エラー時は例外をスロー
 */
export async function getCurrentSubscription(): Promise<CurrentSubscription | null> {
  const response = await api.get('/subscriptions/current');
  return response.data.subscription;
}

/**
 * Stripe Checkout Sessionを作成
 * 
 * POST /api/v1/subscriptions/checkout
 * 
 * @param plan プラン種別（family | enterprise）
 * @param additionalMembers 追加メンバー数（0-100）
 * @returns Promise<CheckoutSession> Checkout Session情報
 * @throws エラー時は例外をスロー（400: バリデーションエラー、403: 子どもテーマユーザー）
 */
export async function createCheckoutSession(
  plan: 'family' | 'enterprise',
  additionalMembers: number = 0
): Promise<CheckoutSession> {
  const response = await api.post('/subscriptions/checkout', {
    plan,
    additional_members: additionalMembers,
  });
  return { url: response.data.session_url };
}

/**
 * 請求履歴を取得
 * 
 * GET /api/v1/subscriptions/invoices
 * 
 * @returns Promise<Invoice[]> 請求履歴一覧
 * @throws エラー時は例外をスロー（403: 子どもテーマユーザー、404: 未加入）
 */
export async function getInvoices(): Promise<Invoice[]> {
  const response = await api.get('/subscriptions/invoices');
  return response.data.invoices;
}

/**
 * サブスクリプションプランを変更
 * 
 * POST /api/v1/subscriptions/update
 * 
 * @param newPlan 新プラン（family | enterprise）
 * @param additionalMembers 追加メンバー数（0-100）
 * @returns Promise<void>
 * @throws エラー時は例外をスロー（400: バリデーション、403: 子どもテーマ、404: 未加入）
 */
export async function updateSubscriptionPlan(
  newPlan: 'family' | 'enterprise',
  additionalMembers: number = 0
): Promise<void> {
  await api.post('/subscriptions/update', {
    new_plan: newPlan,
    additional_members: additionalMembers,
  });
}

/**
 * サブスクリプションをキャンセル（期間終了時に解約）
 * 
 * POST /api/v1/subscriptions/cancel
 * 
 * @returns Promise<void>
 * @throws エラー時は例外をスロー（403: 子どもテーマユーザー、404: 未加入）
 */
export async function cancelSubscription(): Promise<void> {
  await api.post('/subscriptions/cancel');
}

/**
 * Stripe Billing Portal URLを取得
 * 
 * POST /api/v1/subscriptions/billing-portal
 * 
 * @returns Promise<BillingPortalSession> Billing Portal情報
 * @throws エラー時は例外をスロー（403: 子どもテーマユーザー、404: 未加入）
 */
export async function getBillingPortalUrl(): Promise<BillingPortalSession> {
  const response = await api.post('/subscriptions/billing-portal');
  return { url: response.data.portal_url };
}
