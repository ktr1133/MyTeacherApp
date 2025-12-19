/**
 * Legal Service - 法的同意管理API
 * 
 * 機能:
 * - 同意状態の確認
 * - 再同意の送信
 * - 本人同意状態の確認（Phase 6D）
 * - 本人同意の送信（Phase 6D）
 * 
 * Phase 6C: 再同意プロセス実装
 * Phase 6D: 13歳到達時の本人再同意実装
 */

import api from './api';
import type { 
  ConsentStatusResponse, 
  ReconsentRequest, 
  ReconsentResponse,
  SelfConsentStatusResponse,
  SelfConsentRequest,
  SelfConsentResponse,
} from '../types/legal.types';

/**
 * 法的文書レスポンス型
 */
export interface LegalDocumentResponse {
  success: boolean;
  data: {
    type: 'privacy-policy' | 'terms-of-service';
    html: string;  // content → html に変更
    version: string;
  };
}

/**
 * LegalService クラス
 */
class LegalService {
  /**
   * 同意状態を取得
   * 
   * @returns {Promise<ConsentStatusResponse>} 同意状態
   * @throws {Error} API呼び出しエラー
   */
  async getConsentStatus(): Promise<ConsentStatusResponse> {
    const response = await api.get<ConsentStatusResponse>('/consent-status');
    return response.data;
  }

  /**
   * 再同意を送信
   * 
   * @param {ReconsentRequest} data 同意データ
   * @returns {Promise<ReconsentResponse>} 再同意レスポンス
   * @throws {Error} API呼び出しエラー
   */
  async submitReconsent(data: ReconsentRequest): Promise<ReconsentResponse> {
    const response = await api.post<ReconsentResponse>('/reconsent', data);
    return response.data;
  }

  /**
   * 本人同意状態を取得（13歳到達時）
   * 
   * @returns {Promise<SelfConsentStatusResponse>} 本人同意状態
   * @throws {Error} API呼び出しエラー
   */
  async getSelfConsentStatus(): Promise<SelfConsentStatusResponse> {
    const response = await api.get<SelfConsentStatusResponse>('/self-consent-status');
    return response.data;
  }

  /**
   * 本人同意を送信（13歳到達時）
   * 
   * @param {SelfConsentRequest} data 本人同意データ
   * @returns {Promise<SelfConsentResponse>} 本人同意レスポンス
   * @throws {Error} API呼び出しエラー
   */
  async submitSelfConsent(data: SelfConsentRequest): Promise<SelfConsentResponse> {
    const response = await api.post<SelfConsentResponse>('/self-consent', data);
    return response.data;
  }

  /**
   * プライバシーポリシーを取得
   * 
   * @returns プライバシーポリシーのテキスト
   * @throws {Error} API呼び出しエラー
   */
  async getPrivacyPolicy(): Promise<LegalDocumentResponse['data']> {
    const response = await api.get<LegalDocumentResponse>('/legal/privacy-policy');
    return response.data.data;
  }

  /**
   * 利用規約を取得
   * 
   * @returns 利用規約のテキスト
   * @throws {Error} API呼び出しエラー
   */
  async getTermsOfService(): Promise<LegalDocumentResponse['data']> {
    const response = await api.get<LegalDocumentResponse>('/legal/terms-of-service');
    return response.data.data;
  }
}

export default new LegalService();

// Named exports for convenience
export const { 
  getConsentStatus, 
  submitReconsent, 
  getSelfConsentStatus, 
  submitSelfConsent,
  getPrivacyPolicy,
  getTermsOfService,
} = new LegalService();
