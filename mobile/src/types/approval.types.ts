/**
 * 承認待ち一覧画面の型定義
 * 
 * Laravel API (/api/tasks/approvals/pending) のレスポンス型
 * 
 * 参照:
 * - OpenAPI仕様: /home/ktr/mtdev/docs/api/openapi.yaml
 * - 要件定義: /home/ktr/mtdev/definitions/mobile/PendingApprovalsScreen.md
 */

import { Task } from './task.types';

/**
 * 承認アイテムの種別
 */
export type ApprovalType = 'task' | 'token';

/**
 * タスク承認アイテム（API レスポンス）
 * 
 * GET /api/tasks/approvals/pending のレスポンス配列要素
 */
export interface TaskApprovalItem {
  /** 承認アイテムID（task.id） */
  id: number;
  /** アイテム種別（固定値: 'task'） */
  type: 'task';
  /** タスクタイトル */
  title: string;
  /** 申請者名 */
  requester_name: string;
  /** 申請者ID */
  requester_id: number;
  /** 申請日時（ISO 8601） */
  requested_at: string;
  /** タスク説明 */
  description: string | null;
  /** 報酬トークン数 */
  reward: number | null;
  /** 画像添付有無 */
  has_images: boolean;
  /** 画像枚数 */
  images_count: number;
  /** タスク期限（ISO 8601） */
  due_date: string | null;
  /** 元のタスクオブジェクト（詳細画面遷移用） */
  model: Task;
}

/**
 * トークン購入承認アイテム（API レスポンス）
 * 
 * GET /api/tasks/approvals/pending のレスポンス配列要素
 */
export interface TokenApprovalItem {
  /** 承認アイテムID（purchase_request.id） */
  id: number;
  /** アイテム種別（固定値: 'token'） */
  type: 'token';
  /** パッケージ名 */
  package_name: string;
  /** 申請者名 */
  requester_name: string;
  /** 申請者ID */
  requester_id: number;
  /** 申請日時（ISO 8601） */
  requested_at: string;
  /** トークン数 */
  token_amount: number;
  /** 購入金額 */
  price: number;
  /** 元の購入リクエストオブジェクト */
  model: {
    id: number;
    package_id: number;
    status: 'pending' | 'approved' | 'rejected';
    created_at: string;
  };
}

/**
 * 承認アイテムの共用型（Union Type）
 * 
 * タスク承認とトークン購入承認を統合
 */
export type ApprovalItem = TaskApprovalItem | TokenApprovalItem;

/**
 * 承認待ち一覧APIレスポンス
 * 
 * GET /api/tasks/approvals/pending
 */
export interface PendingApprovalsResponse {
  success: boolean;
  data: {
    /** 承認待ちアイテム（タスク+トークン混在、申請日時の古い順） */
    approvals: ApprovalItem[];
    /** ページネーション情報 */
    pagination: {
      current_page: number;
      per_page: number;
      total: number;
      last_page: number;
      from: number | null;
      to: number | null;
    };
  };
}

/**
 * 承認実行レスポンス（タスク）
 * 
 * POST /api/tasks/{id}/approve
 */
export interface TaskApprovalActionResponse {
  success: boolean;
  message: string;
  data?: {
    task: Task;
  };
}

/**
 * 却下実行レスポンス（タスク）
 * 
 * POST /api/tasks/{id}/reject
 */
export interface TaskRejectActionResponse {
  success: boolean;
  message: string;
  data?: {
    task: Task;
  };
}

/**
 * 承認実行レスポンス（トークン購入）
 * 
 * POST /api/tokens/purchase-requests/{id}/approve
 */
export interface TokenApprovalActionResponse {
  success: boolean;
  message: string;
  data?: {
    purchase_request: {
      id: number;
      package_id: number;
      status: 'approved';
      approved_at: string;
    };
  };
}

/**
 * 却下実行レスポンス（トークン購入）
 * 
 * POST /api/tokens/purchase-requests/{id}/reject
 */
export interface TokenRejectActionResponse {
  success: boolean;
  message: string;
  data?: {
    purchase_request: {
      id: number;
      package_id: number;
      status: 'rejected';
      rejected_at: string;
      rejection_reason?: string;
    };
  };
}

/**
 * 却下理由データ（リクエストパラメータ）
 */
export interface RejectReasonData {
  /** 却下理由（任意） */
  rejection_reason?: string;
}

/**
 * 承認待ち件数レスポンス
 * 
 * GET /api/tasks/approvals/pending-count
 * （ナビゲーションバッジ用）
 */
export interface PendingApprovalsCountResponse {
  success: boolean;
  data: {
    count: number;
  };
}
