/**
 * Axios APIクライアント設定
 * JWT認証ヘッダーの自動付与、エラーハンドリングを実装
 */
import axios from 'axios';
import { API_CONFIG, STORAGE_KEYS } from '../utils/constants';
import * as storage from '../utils/storage';

const api = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  timeout: API_CONFIG.TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
  },
});

// リクエストインターセプター（JWT自動付与）
api.interceptors.request.use(
  async (config) => {
    console.log('[API] Request URL:', config.baseURL + config.url);
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

// レスポンスインターセプター（401エラーでログアウト）
api.interceptors.response.use(
  (response) => {
    console.log('[API] Response status:', response.status);
    console.log('[API] Response data preview:', JSON.stringify(response.data).substring(0, 150));
    return response;
  },
  async (error) => {
    console.error('[API] Response error:', error);
    console.error('[API] Response error status:', error.response?.status);
    console.error('[API] Response error data:', error.response?.data);
    
    if (error.response?.status === 401) {
      // トークンを削除してログアウト
      await storage.removeItem(STORAGE_KEYS.JWT_TOKEN);
      // TODO: ログイン画面へ遷移
    }
    return Promise.reject(error);
  }
);

export default api;
