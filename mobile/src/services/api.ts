/**
 * Axios APIクライアント設定
 * JWT認証ヘッダーの自動付与、エラーハンドリングを実装
 * 
 * @see /home/ktr/mtdev/definitions/mobile/NavigationFlow.md - Section 8
 */
import axios from 'axios';
import { Alert } from 'react-native';
import { API_CONFIG, STORAGE_KEYS } from '../utils/constants';
import * as storage from '../utils/storage';
import { resetTo } from '../utils/navigationRef';

const api = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  timeout: API_CONFIG.TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    'ngrok-skip-browser-warning': 'true', // ngrok警告画面をスキップ
  },
});

// リクエストインターセプター（JWT自動付与）
api.interceptors.request.use(
  async (config) => {
    console.log('[API] Request URL:', (config.baseURL || '') + (config.url || ''));
    console.log('[API] Request method:', config.method);
    console.log('[API] Request params:', config.params);
    
    const token = await storage.getItem(STORAGE_KEYS.JWT_TOKEN);
    console.log('[API] JWT token:', token ? `${token.substring(0, 20)}...` : 'NOT FOUND');
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    console.error('[API] Request error:', error);
    return Promise.reject(error);
  }
);

// レスポンスインターセプター（エラーハンドリング）
api.interceptors.response.use(
  (response) => {
    return response;
  },
  async (error) => {
    console.error('[API] Response error:', error);
    console.error('[API] Response error status:', error.response?.status);
    console.error('[API] Response error data:', error.response?.data);
    
    // 401エラー: 認証エラー（セッション切れ）
    if (error.response?.status === 401) {
      // ポーリングの401エラーはトークン削除しない（一時的なエラーの可能性）
      const isPollingRequest = error.config?.url?.includes('/unread-count');
      
      if (!isPollingRequest) {
        console.log('[API] Authentication failed, removing token and redirecting to login');
        
        // トークン削除
        await storage.removeItem(STORAGE_KEYS.JWT_TOKEN);
        
        // エラーメッセージ表示
        Alert.alert(
          'Session Expired',
          'Your session has expired. Please log in again.',
          [
            {
              text: 'OK',
              onPress: () => {
                // ログイン画面へ遷移
                resetTo('Login');
              },
            },
          ],
          { cancelable: false }
        );
      } else {
        console.log('[API] Polling 401 error, keeping token (temporary error)');
      }
    }
    
    // 404エラー: リソースが見つからない
    else if (error.response?.status === 404) {
      const url = error.config?.url || '';
      console.log('[API] 404 error for URL:', url);
      
      // タスク詳細など特定のリソースの404エラーの場合
      if (url.includes('/tasks/') || url.includes('/notifications/')) {
        Alert.alert(
          'Not Found',
          'The requested resource was not found.',
          [
            {
              text: 'OK',
              onPress: () => {
                // 3秒後にタスク一覧画面へ自動遷移
                setTimeout(() => {
                  resetTo('TaskList');
                }, 3000);
              },
            },
          ]
        );
      }
    }
    
    // ネットワークエラー
    else if (error.message === 'Network Error' || !error.response) {
      console.log('[API] Network error detected');
      
      Alert.alert(
        'Network Error',
        'Please check your internet connection and try again.',
        [
          {
            text: 'Retry',
            onPress: () => {
              // リトライ処理: 同じリクエストを再実行
              return api.request(error.config);
            },
          },
          {
            text: 'Cancel',
            style: 'cancel',
          },
        ]
      );
    }
    
    return Promise.reject(error);
  }
);

export default api;
