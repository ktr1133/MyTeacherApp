/**
 * サブスクリプション管理用カスタムフック
 * 
 * サブスクリプションプラン、現在のサブスク情報、請求履歴、
 * プラン変更、キャンセル、Stripe連携の状態管理を提供
 * 
 * @module hooks/useSubscription
 */

import { useState, useCallback } from 'react';
import { Alert } from 'react-native';
import * as SubscriptionService from '../services/subscription.service';
import { getErrorMessage } from '../utils/errorMessages';
import type {
  SubscriptionPlan,
  CurrentSubscription,
  Invoice,
  CheckoutSession,
  BillingPortalSession,
} from '../types/subscription.types';

/**
 * useSubscription Hookの戻り値型
 */
export interface UseSubscriptionReturn {
  // 状態
  plans: SubscriptionPlan[];
  currentSubscription: CurrentSubscription | null;
  invoices: Invoice[];
  additionalMemberPrice: number;
  currentPlan: string | null;
  isLoading: boolean;
  error: string | null;

  // 関数
  loadPlans: () => Promise<void>;
  loadCurrentSubscription: () => Promise<void>;
  loadInvoices: () => Promise<void>;
  createCheckout: (plan: 'family' | 'enterprise', additionalMembers?: number) => Promise<CheckoutSession>;
  updatePlan: (newPlan: 'family' | 'enterprise', additionalMembers?: number) => Promise<void>;
  cancel: () => Promise<void>;
  getBillingPortal: () => Promise<BillingPortalSession>;
}

/**
 * useSubscription Hook
 * 
 * サブスクリプション関連の状態管理とAPI呼び出しを提供
 * 
 * @returns {Object} サブスクリプション管理用のステートと関数
 * 
 * @example
 * ```tsx
 * const {
 *   plans,
 *   currentSubscription,
 *   loadPlans,
 *   createCheckout,
 *   isLoading,
 * } = useSubscription();
 * 
 * // プラン一覧表示
 * {plans.map(plan => <Text>{plan.displayName}</Text>)}
 * 
 * // Checkout Session作成
 * <Button onPress={() => createCheckout('family')} />
 * ```
 */
export const useSubscription = (): UseSubscriptionReturn => {
  // サブスクリプションプラン一覧
  const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
  
  // 追加メンバー単価
  const [additionalMemberPrice, setAdditionalMemberPrice] = useState<number>(150);
  
  // 現在のプラン名
  const [currentPlan, setCurrentPlan] = useState<string | null>(null);
  
  // 現在のサブスクリプション情報
  const [currentSubscription, setCurrentSubscription] = useState<CurrentSubscription | null>(null);
  
  // 請求履歴
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  
  // ローディング状態
  const [isLoading, setIsLoading] = useState(false);
  
  // エラー状態
  const [error, setError] = useState<string | null>(null);

  /**
   * サブスクリプションプラン一覧を取得
   * 
   * GET /api/v1/subscriptions/plans
   * @throws 403エラー（子どもテーマユーザー）
   */
  const loadPlans = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);

      const data = await SubscriptionService.getSubscriptionPlans();
      setPlans(data.plans);
      setAdditionalMemberPrice(data.additionalMemberPrice);
      setCurrentPlan(data.currentPlan);
    } catch (err: any) {
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      console.error('[useSubscription] loadPlans error:', err);
      Alert.alert('エラー', errorMessage);
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * 現在のサブスクリプション情報を取得
   * 
   * GET /api/v1/subscriptions/current
   * @returns 未加入の場合はnull
   */
  const loadCurrentSubscription = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);

      const data = await SubscriptionService.getCurrentSubscription();
      setCurrentSubscription(data);
    } catch (err: any) {
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      console.error('[useSubscription] loadCurrentSubscription error:', err);
      // 未加入の場合はエラーを表示しない
      if (err.response?.status !== 404) {
        Alert.alert('エラー', errorMessage);
      }
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * 請求履歴を取得
   * 
   * GET /api/v1/subscriptions/invoices
   * @throws 403エラー（子どもテーマユーザー）、404エラー（未加入）
   */
  const loadInvoices = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);

      const data = await SubscriptionService.getInvoices();
      setInvoices(data);
    } catch (err: any) {
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      console.error('[useSubscription] loadInvoices error:', err);
      // 未加入の場合はエラーを表示しない
      if (err.response?.status !== 404) {
        Alert.alert('エラー', errorMessage);
      }
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * Stripe Checkout Sessionを作成
   * 
   * POST /api/v1/subscriptions/checkout
   * 
   * @param plan プラン種別（family | enterprise）
   * @param additionalMembers 追加メンバー数（0-100）
   * @returns CheckoutSession オブジェクト
   * @throws 400エラー（バリデーション）、403エラー（子どもテーマユーザー）
   */
  const createCheckout = useCallback(async (
    plan: 'family' | 'enterprise',
    additionalMembers: number = 0
  ): Promise<CheckoutSession> => {
    try {
      setIsLoading(true);
      setError(null);

      const data = await SubscriptionService.createCheckoutSession(plan, additionalMembers);
      return data;
    } catch (err: any) {
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      console.error('[useSubscription] createCheckout error:', err);
      Alert.alert('エラー', errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * サブスクリプションプランを変更
   * 
   * POST /api/v1/subscriptions/update
   * 
   * @param newPlan 新プラン（family | enterprise）
   * @param additionalMembers 追加メンバー数（0-100）
   * @throws 400エラー（バリデーション）、403エラー（子どもテーマ）、404エラー（未加入）
   */
  const updatePlan = useCallback(async (
    newPlan: 'family' | 'enterprise',
    additionalMembers: number = 0
  ): Promise<void> => {
    try {
      setIsLoading(true);
      setError(null);

      await SubscriptionService.updateSubscriptionPlan(newPlan, additionalMembers);
      
      // 更新成功後、最新情報を再取得
      await loadCurrentSubscription();
      
      Alert.alert('成功', 'プランを変更しました。');
    } catch (err: any) {
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      console.error('[useSubscription] updatePlan error:', err);
      Alert.alert('エラー', errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [loadCurrentSubscription]);

  /**
   * サブスクリプションをキャンセル（期間終了時に解約）
   * 
   * POST /api/v1/subscriptions/cancel
   * 
   * @throws 403エラー（子どもテーマユーザー）、404エラー（未加入）
   */
  const cancel = useCallback(async (): Promise<void> => {
    try {
      setIsLoading(true);
      setError(null);

      await SubscriptionService.cancelSubscription();
      
      // キャンセル成功後、最新情報を再取得
      await loadCurrentSubscription();
      
      Alert.alert('成功', 'サブスクリプションをキャンセルしました。期間終了時に解約されます。');
    } catch (err: any) {
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      console.error('[useSubscription] cancel error:', err);
      Alert.alert('エラー', errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [loadCurrentSubscription]);

  /**
   * Stripe Billing Portal URLを取得
   * 
   * POST /api/v1/subscriptions/billing-portal
   * 
   * @returns BillingPortalSession オブジェクト
   * @throws 403エラー（子どもテーマユーザー）、404エラー（未加入）
   */
  const getBillingPortal = useCallback(async (): Promise<BillingPortalSession> => {
    try {
      setIsLoading(true);
      setError(null);

      const data = await SubscriptionService.getBillingPortalUrl();
      return data;
    } catch (err: any) {
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      console.error('[useSubscription] getBillingPortal error:', err);
      Alert.alert('エラー', errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  return {
    // 状態
    plans,
    currentSubscription,
    invoices,
    additionalMemberPrice,
    currentPlan,
    isLoading,
    error,

    // 関数
    loadPlans,
    loadCurrentSubscription,
    loadInvoices,
    createCheckout,
    updatePlan,
    cancel,
    getBillingPortal,
  };
};
