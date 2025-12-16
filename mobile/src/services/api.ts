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
    const token = await storage.getItem(STORAGE_KEYS.JWT_TOKEN);
    
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
      // 以下のリクエストの401エラーはトークン削除しない
      const isPollingRequest = error.config?.url?.includes('/unread-count');
      const isFCMTokenRequest = error.config?.url?.includes('/fcm-token');
      
      if (!isPollingRequest && !isFCMTokenRequest) {
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
      }
    }
    
    // 404エラー: リソースが見つからない
    else if (error.response?.status === 404) {
      const url = error.config?.url || '';
      
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
