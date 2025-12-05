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
    const token = await storage.getItem(STORAGE_KEYS.JWT_TOKEN);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// レスポンスインターセプター（401エラーでログアウト）
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // トークンを削除してログアウト
      await storage.removeItem(STORAGE_KEYS.JWT_TOKEN);
      // TODO: ログイン画面へ遷移
    }
    return Promise.reject(error);
  }
);

export default api;
