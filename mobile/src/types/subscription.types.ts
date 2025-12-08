/**
 * サブスクリプション関連の型定義
 * 
 * Laravel API (/api/v1/subscriptions/*) のレスポンス型
 */

/**
 * サブスクリプションプラン情報
 * 
 * GET /api/v1/subscriptions/plans のレスポンス配列要素
 */
export interface SubscriptionPlan {
  name: string;
  displayName: string;
  description: string;
  price: number;
  maxMembers: number;
  features: string[];
  stripePriceId: string;
  stripePlanName: string;
}

/**
 * 現在のサブスクリプション情報
 * 
 * GET /api/v1/subscriptions/current のレスポンス
 */
export interface CurrentSubscription {
  plan: string; // 'family' | 'enterprise'
  stripe_status: string;
  current_period_end: string;
  cancel_at_period_end: boolean;
  additional_members: number;
  max_members: number;
  stripe_id: string;
  ends_at: string | null; // サブスクリプション終了予定日（解約後）
  trial_ends_at: string | null; // トライアル終了日
}

/**
 * 請求書情報
 * 
 * GET /api/v1/subscriptions/invoices のレスポンス配列要素
 */
export interface Invoice {
  id: string;
  date: string;
  total: number; // 最小通貨単位（円）
  amount_paid: number; // 支払済み金額
  status: string; // 'draft' | 'open' | 'paid' | 'uncollectible' | 'void'
  currency: string; // 'jpy'
  invoice_pdf: string | null;
}

/**
 * Checkout Session情報
 * 
 * POST /api/v1/subscriptions/checkout のレスポンス
 */
export interface CheckoutSession {
  url: string;
}

/**
 * Billing Portal Session情報
 * 
 * POST /api/v1/subscriptions/billing-portal のレスポンス
 */
export interface BillingPortalSession {
  url: string;
}
