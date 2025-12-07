/**
 * トークン関連の型定義
 * 
 * Laravel API (/api/v1/tokens/*) のレスポンス型
 */

/**
 * トークン残高情報
 * 
 * GET /api/v1/tokens/balance のレスポンス
 */
export interface TokenBalance {
  id: number;
  tokenable_type: string;
  tokenable_id: number;
  balance: number;
  free_balance: number;
  paid_balance: number;
  free_balance_reset_at: string | null;
  total_consumed: number;
  monthly_consumed: number;
  monthly_consumed_reset_at: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * トークンパッケージ情報
 * 
 * GET /api/v1/tokens/packages のレスポンス配列要素
 */
export interface TokenPackage {
  id: number;
  name: string;
  token_amount: number;
  price: number;
  stripe_price_id: string | null;
  description?: string | null;
  discount_rate?: number | null;
  sort_order?: number;
  is_active?: boolean;
  created_at?: string;
}

/**
 * トークン取引履歴
 * 
 * GET /api/v1/tokens/history のレスポンス配列要素
 */
export interface TokenTransaction {
  id: number;
  type: 'purchase' | 'consume' | 'grant' | 'free_reset' | 'admin_adjust' | 'ai_usage' | 'refund';
  amount: number;
  balance_after: number;
  description: string;
  created_at: string;
}

/**
 * トークン履歴統計情報
 * 
 * GET /api/v1/tokens/history のレスポンス
 */
export interface TokenHistoryStats {
  transactions: TokenTransaction[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

/**
 * トークン購入リクエスト（子ども承認フロー用）
 * 
 * GET /api/v1/tokens/purchase-requests のレスポンス配列要素
 */
export interface PurchaseRequest {
  id: number;
  package_id: number;
  package_name: string;
  token_amount: number;
  price: number;
  status: 'pending' | 'approved' | 'rejected' | 'completed';
  created_at: string;
  approved_at?: string;
  rejected_at?: string;
}
