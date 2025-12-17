/**
 * Legal Types - 法的同意管理の型定義
 * 
 * Phase 6C: 再同意プロセス実装
 * Phase 6D: 13歳到達時の本人再同意実装
 */

/**
 * 同意状態レスポンス
 */
export interface ConsentStatusResponse {
  /** 再同意が必要かどうか */
  requires_reconsent: boolean;
  /** プライバシーポリシーの同意状態 */
  privacy_policy: {
    /** 現在のバージョン */
    current_version: string | null;
    /** 必要なバージョン */
    required_version: string;
    /** 再同意が必要かどうか */
    needs_reconsent: boolean;
    /** 同意日時（ISO 8601形式） */
    agreed_at: string | null;
  };
  /** 利用規約の同意状態 */
  terms: {
    /** 現在のバージョン */
    current_version: string | null;
    /** 必要なバージョン */
    required_version: string;
    /** 再同意が必要かどうか */
    needs_reconsent: boolean;
    /** 同意日時（ISO 8601形式） */
    agreed_at: string | null;
  };
}

/**
 * 再同意リクエスト
 */
export interface ReconsentRequest {
  /** プライバシーポリシーへの同意 */
  privacy_policy_consent: boolean;
  /** 利用規約への同意 */
  terms_consent: boolean;
}

/**
 * 再同意レスポンス
 */
export interface ReconsentResponse {
  /** メッセージ */
  message: string;
  /** 更新後のユーザー情報 */
  user: {
    /** プライバシーポリシーバージョン */
    privacy_policy_version: string;
    /** 利用規約バージョン */
    terms_version: string;
    /** プライバシーポリシー同意日時（ISO 8601形式） */
    privacy_policy_agreed_at: string;
    /** 利用規約同意日時（ISO 8601形式） */
    terms_agreed_at: string;
  };
}

/**
 * 本人同意状態レスポンス（13歳到達時）
 */
export interface SelfConsentStatusResponse {
  /** 本人同意が必要かどうか */
  requires_self_consent: boolean;
  /** 年齢 */
  age: number | null;
  /** 未成年者かどうか */
  is_minor: boolean;
  /** 作成者のユーザーID（親） */
  created_by_user_id: number | null;
  /** 同意者のユーザーID（代理同意の場合は親） */
  consent_given_by_user_id: number | null;
  /** 本人同意日時（ISO 8601形式） */
  self_consented_at: string | null;
  /** プライバシーポリシーの同意状態 */
  privacy_policy: {
    /** 現在のバージョン */
    current_version: string | null;
    /** 同意日時（ISO 8601形式） */
    agreed_at: string | null;
  };
  /** 利用規約の同意状態 */
  terms: {
    /** 現在のバージョン */
    current_version: string | null;
    /** 同意日時（ISO 8601形式） */
    agreed_at: string | null;
  };
}

/**
 * 本人同意リクエスト（13歳到達時）
 */
export interface SelfConsentRequest {
  /** プライバシーポリシーへの同意 */
  privacy_policy_consent: boolean;
  /** 利用規約への同意 */
  terms_consent: boolean;
}

/**
 * 本人同意レスポンス（13歳到達時）
 */
export interface SelfConsentResponse {
  /** メッセージ */
  message: string;
  /** 更新後のユーザー情報 */
  user: {
    /** ユーザーID */
    id: number;
    /** 年齢 */
    age: number | null;
    /** プライバシーポリシーバージョン */
    privacy_policy_version: string;
    /** 利用規約バージョン */
    terms_version: string;
    /** プライバシーポリシー同意日時（ISO 8601形式） */
    privacy_policy_agreed_at: string;
    /** 利用規約同意日時（ISO 8601形式） */
    terms_agreed_at: string;
    /** 本人同意日時（ISO 8601形式） */
    self_consented_at: string;
    /** 同意者のユーザーID（本人に更新される） */
    consent_given_by_user_id: number;
  };
}
