/**
 * トークン管理用カスタムフック
 * 
 * トークン残高、履歴、パッケージ、購入リクエストの取得・更新を管理
 * 
 * @module hooks/useTokens
 */

import { useState, useEffect, useCallback } from 'react';
import { Alert } from 'react-native';
import * as TokenService from '../services/token.service';
import { useTheme } from '../contexts/ThemeContext';
import { getErrorMessage } from '../utils/errorMessages';
import type {
  TokenBalance,
  TokenPackage,
  TokenTransaction,
  PurchaseRequest,
} from '../types/token.types';

/**
 * useTokens Hook
 * 
 * トークン関連の状態管理とAPI呼び出しを提供
 * 
 * @param theme テーマ（'adult' | 'child'）- テスト用
 * @returns {Object} トークン管理用のステートと関数
 * 
 * @example
 * ```tsx
 * const {
 *   balance,
 *   packages,
 *   refreshBalance,
 *   isLoading,
 * } = useTokens();
 * 
 * // 残高表示
 * <Text>{balance?.balance} tokens</Text>
 * 
 * // 残高更新
 * <Button onPress={refreshBalance} />
 * ```
 */
export const useTokens = (themeOverride?: 'adult' | 'child') => {
  const themeContext = useTheme();
  const theme = themeOverride || themeContext.theme;

  // トークン残高
  const [balance, setBalance] = useState<TokenBalance | null>(null);
  
  // トークンパッケージ一覧
  const [packages, setPackages] = useState<TokenPackage[]>([]);
  
  // トークン履歴
  const [history, setHistory] = useState<TokenTransaction[]>([]);
  const [historyPage, setHistoryPage] = useState(1);
  const [hasMoreHistory, setHasMoreHistory] = useState(true);
  
  // 購入リクエスト一覧（子ども承認フロー用）
  const [purchaseRequests, setPurchaseRequests] = useState<PurchaseRequest[]>([]);
  
  // ローディング状態
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  
  // エラー状態
  const [error, setError] = useState<string | null>(null);

  /**
   * トークン残高を取得
   * 
   * キャッシュがあればキャッシュを使用、なければAPI呼び出し
   */
  const loadBalance = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);

      // キャッシュがあれば先に表示
      const cached = await TokenService.getCachedTokenBalance();
      if (cached) {
        setBalance(cached);
        setIsLoading(false); // キャッシュ表示時点でloadingを解除
      }

      // 最新データを取得
      const data = await TokenService.getTokenBalance();
      setBalance(data);

      // キャッシュ更新
      await TokenService.cacheTokenBalance(data);

    } catch (err: any) {
      console.error('[useTokens] Failed to load balance', err);
      const errorCode = err.response?.data?.error || 'TOKEN_BALANCE_FETCH_FAILED';
      const message = getErrorMessage(errorCode, theme);
      setError(message);
      
      // キャッシュがない場合のみAlertを表示（オフライン対応）
      const cached = await TokenService.getCachedTokenBalance();
      if (!cached) {
        Alert.alert('エラー', message);
      }
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * トークン残高を強制更新
   * 
   * Pull-to-Refresh等で使用
   */
  const refreshBalance = useCallback(async () => {
    try {
      setError(null);
      const data = await TokenService.getTokenBalance();
      setBalance(data);
      await TokenService.cacheTokenBalance(data);
    } catch (err: any) {
      console.error('[useTokens] Failed to refresh balance', err);
      const errorCode = err.response?.data?.error || 'TOKEN_BALANCE_FETCH_FAILED';
      const message = getErrorMessage(errorCode, theme);
      setError(message);
      Alert.alert('エラー', message);
    }
  }, [theme]);

  /**
   * トークンパッケージ一覧を取得
   */
  const loadPackages = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);
      const data = await TokenService.getTokenPackages();
      setPackages(data);
    } catch (err: any) {
      console.error('[useTokens] Failed to load packages', err);
      const errorCode = err.response?.data?.error || 'TOKEN_PACKAGES_FETCH_FAILED';
      const message = getErrorMessage(errorCode, theme);
      setError(message);
      Alert.alert('エラー', message);
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * トークン履歴を取得（ページネーション対応）
   * 
   * @param page ページ番号（デフォルト: 1）
   */
  const loadHistory = useCallback(async (page: number = 1) => {
    try {
      if (page === 1) {
        setIsLoading(true);
      } else {
        setIsLoadingMore(true);
      }
      setError(null);

      const data = await TokenService.getTokenHistory(page, 20);

      if (page === 1) {
        setHistory(data.transactions);
      } else {
        setHistory(prev => [...prev, ...data.transactions]);
      }

      setHistoryPage(page);
      setHasMoreHistory(page < data.pagination.last_page);

    } catch (err: any) {
      console.error('[useTokens] Failed to load history', err);
      const errorCode = err.response?.data?.error || 'TOKEN_HISTORY_FETCH_FAILED';
      const message = getErrorMessage(errorCode, theme);
      setError(message);
      Alert.alert('エラー', message);
    } finally {
      setIsLoading(false);
      setIsLoadingMore(false);
    }
  }, [theme]);

  /**
   * 次ページの履歴を読み込み（無限スクロール用）
   */
  const loadMoreHistory = useCallback(async () => {
    if (!isLoadingMore && hasMoreHistory) {
      await loadHistory(historyPage + 1);
    }
  }, [isLoadingMore, hasMoreHistory, historyPage, loadHistory]);

  /**
   * トークン購入リクエスト一覧を取得（子ども承認フロー用）
   */
  const loadPurchaseRequests = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);
      const data = await TokenService.getPurchaseRequests();
      setPurchaseRequests(data);
    } catch (err: any) {
      console.error('[useTokens] Failed to load purchase requests', err);
      const errorCode = err.response?.data?.error || 'TOKEN_PURCHASE_REQUEST_FAILED';
      const message = getErrorMessage(errorCode, theme);
      setError(message);
      Alert.alert('エラー', message);
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * トークン購入リクエストを作成（子どもユーザー用）
   * 
   * @param packageId トークンパッケージID
   */
  const createPurchaseRequest = useCallback(async (packageId: number) => {
    try {
      setIsLoading(true);
      setError(null);
      const request = await TokenService.createPurchaseRequest(packageId);
      setPurchaseRequests(prev => [request, ...prev]);
      
      const successMessage = theme === 'child' 
        ? 'おうちのひとにおねがいしたよ！' 
        : '購入リクエストを送信しました';
      Alert.alert('成功', successMessage);
      
      return request;
    } catch (err: any) {
      console.error('[useTokens] Failed to create purchase request', err);
      const errorCode = err.response?.data?.error || 'TOKEN_PURCHASE_REQUEST_FAILED';
      const message = getErrorMessage(errorCode, theme);
      setError(message);
      Alert.alert('エラー', message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * トークン購入リクエストを承認（親ユーザー用）
   * 
   * @param requestId 購入リクエストID
   */
  const approvePurchaseRequest = useCallback(async (requestId: number) => {
    try {
      setIsLoading(true);
      setError(null);
      await TokenService.approvePurchaseRequest(requestId);
      
      // リクエスト一覧を更新
      await loadPurchaseRequests();
      
      const successMessage = theme === 'child' 
        ? 'OKをだしたよ！' 
        : '購入リクエストを承認しました';
      Alert.alert('成功', successMessage);
      
    } catch (err: any) {
      console.error('[useTokens] Failed to approve purchase request', err);
      const errorCode = err.response?.data?.error || 'TOKEN_APPROVAL_FAILED';
      const message = getErrorMessage(errorCode, theme);
      setError(message);
      Alert.alert('エラー', message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [theme, loadPurchaseRequests]);

  /**
   * トークン購入リクエストを却下（親ユーザー用）
   * 
   * @param requestId 購入リクエストID
   */
  const rejectPurchaseRequest = useCallback(async (requestId: number) => {
    try {
      setIsLoading(true);
      setError(null);
      await TokenService.rejectPurchaseRequest(requestId);
      
      // リクエスト一覧を更新
      await loadPurchaseRequests();
      
      const successMessage = theme === 'child' 
        ? 'だめっていったよ' 
        : '購入リクエストを却下しました';
      Alert.alert('成功', successMessage);
      
    } catch (err: any) {
      console.error('[useTokens] Failed to reject purchase request', err);
      const errorCode = err.response?.data?.error || 'TOKEN_REJECTION_FAILED';
      const message = getErrorMessage(errorCode, theme);
      setError(message);
      Alert.alert('エラー', message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [theme, loadPurchaseRequests]);

  // 初回マウント時に残高を取得
  useEffect(() => {
    loadBalance();
  }, []);

  return {
    // 状態
    balance,
    packages,
    history,
    purchaseRequests,
    isLoading,
    isLoadingMore,
    hasMoreHistory,
    error,

    // 関数
    refreshBalance,
    loadPackages,
    loadHistory,
    loadMoreHistory,
    loadPurchaseRequests,
    createPurchaseRequest,
    approvePurchaseRequest,
    rejectPurchaseRequest,
  };
};
