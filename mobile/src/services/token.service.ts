/**
 * トークンサービス
 * 
 * トークン残高、履歴、パッケージ、購入リクエストのAPI通信を担当
 * 
 * @module services/token.service
 */

import api from './api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { STORAGE_KEYS } from '../utils/constants';
import type {
  TokenBalance,
  TokenPackage,
  TokenHistoryStats,
  PurchaseRequest,
} from '../types/token.types';

/**
 * トークン残高を取得
 * 
 * GET /api/v1/tokens/balance
 * 
 * @returns Promise<TokenBalance> トークン残高情報
 * @throws エラー時は例外をスロー
 */
export async function getTokenBalance(): Promise<TokenBalance> {
  const response = await api.get('/tokens/balance');
  return response.data.data.balance;
}

/**
 * トークン履歴統計を取得
 * 
 * GET /api/v1/tokens/history
 * 
 * @returns Promise<TokenHistoryStats> トークン履歴統計（月次購入・使用情報）
 * @throws エラー時は例外をスロー
 */
export async function getTokenHistoryStats(): Promise<TokenHistoryStats> {
  const response = await api.get('/tokens/history');
  return response.data.data;
}

/**
 * トークンパッケージ一覧を取得
 * 
 * GET /api/v1/tokens/packages
 * 
 * @returns Promise<TokenPackage[]> トークンパッケージ一覧
 * @throws エラー時は例外をスロー
 */
export async function getTokenPackages(): Promise<TokenPackage[]> {
  const response = await api.get('/tokens/packages');
  return response.data.data.packages;
}

/**
 * トークン購入リクエストを作成（子どもユーザー用）
 * 
 * POST /api/v1/tokens/purchase-requests
 * 
 * @param packageId トークンパッケージID
 * @returns Promise<PurchaseRequest> 作成された購入リクエスト
 * @throws エラー時は例外をスロー
 */
export async function createPurchaseRequest(packageId: number): Promise<PurchaseRequest> {
  const response = await api.post('/tokens/purchase-requests', {
    package_id: packageId,
  });
  return response.data.data;
}

/**
 * トークン購入リクエスト一覧を取得
 * 
 * GET /api/v1/tokens/purchase-requests
 * 
 * @returns Promise<PurchaseRequest[]> 購入リクエスト一覧
 * @throws エラー時は例外をスロー
 */
export async function getPurchaseRequests(): Promise<PurchaseRequest[]> {
  const response = await api.get('/tokens/purchase-requests');
  return response.data.data.requests;
}

/**
 * トークン購入リクエストを承認（親ユーザー用）
 * 
 * PUT /api/v1/tokens/purchase-requests/{id}/approve
 * 
 * @param requestId 購入リクエストID
 * @returns Promise<void>
 * @throws エラー時は例外をスロー
 */
export async function approvePurchaseRequest(requestId: number): Promise<void> {
  await api.put(`/tokens/purchase-requests/${requestId}/approve`);
}

/**
 * トークン購入リクエストを却下（親ユーザー用）
 * 
 * PUT /api/v1/tokens/purchase-requests/{id}/reject
 * 
 * @param requestId 購入リクエストID
 * @returns Promise<void>
 * @throws エラー時は例外をスロー
 */
export async function rejectPurchaseRequest(requestId: number): Promise<void> {
  await api.put(`/tokens/purchase-requests/${requestId}/reject`);
}

/**
 * キャッシュされたトークン残高を取得
 * 
 * @returns Promise<TokenBalance | null> キャッシュされた残高、存在しない場合はnull
 */
export async function getCachedTokenBalance(): Promise<TokenBalance | null> {
  try {
    const cached = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN_BALANCE);
    return cached ? JSON.parse(cached) : null;
  } catch (error) {
    console.error('[TokenService] Failed to get cached token balance', error);
    return null;
  }
}

/**
 * トークン残高をキャッシュに保存
 * 
 * @param balance トークン残高情報
 * @returns Promise<void>
 */
export async function cacheTokenBalance(balance: TokenBalance): Promise<void> {
  try {
    await AsyncStorage.setItem(STORAGE_KEYS.TOKEN_BALANCE, JSON.stringify(balance));
  } catch (error) {
    console.error('[TokenService] Failed to cache token balance', error);
  }
}

/**
 * トークン残高キャッシュをクリア
 * 
 * @returns Promise<void>
 */
export async function clearTokenBalanceCache(): Promise<void> {
  try {
    await AsyncStorage.removeItem(STORAGE_KEYS.TOKEN_BALANCE);
  } catch (error) {
    console.error('[TokenService] Failed to clear token balance cache', error);
  }
}
