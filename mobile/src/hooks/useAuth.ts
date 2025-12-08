/**
 * 認証フック
 * ログイン状態の管理とユーザー情報の取得
 */
import { useState, useEffect } from 'react';
import { authService } from '../services/auth.service';
import { User } from '../types/api.types';

export const useAuth = () => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const authenticated = await authService.isAuthenticated();
      console.log('[useAuth] RAW authenticated:', JSON.stringify(authenticated), 'type:', typeof authenticated);
      
      // 確実にbooleanに変換
      const boolValue = authenticated === true;
      console.log('[useAuth] CONVERTED isAuthenticated:', boolValue, 'type:', typeof boolValue);
      setIsAuthenticated(boolValue);
      
      // トークンがある場合のみユーザー情報を取得
      if (boolValue) {
        try {
          const currentUser = await authService.getCurrentUser();
          setUser(currentUser);
        } catch (error) {
          console.error('[useAuth] Failed to get current user:', error);
          // ユーザー情報取得失敗時は認証状態をfalseに戻す
          setIsAuthenticated(false);
          setUser(null);
        }
      } else {
        setUser(null);
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      setIsAuthenticated(false);
      setUser(null);
    } finally {
      console.log('[useAuth] Setting loading to FALSE');
      setLoading(false);
    }
  };

  const login = async (username: string, password: string) => {
    try {
      const { user: loggedInUser } = await authService.login(username, password);
      setUser(loggedInUser);
      console.log('[useAuth] LOGIN - setting isAuthenticated to TRUE');
      setIsAuthenticated(true);
      return { success: true };
    } catch (error: any) {
      console.error('Login failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'ログインに失敗しました',
      };
    }
  };

  const register = async (email: string, password: string, name: string) => {
    try {
      const { user: registeredUser } = await authService.register(
        email,
        password,
        name
      );
      setUser(registeredUser);
      console.log('[useAuth] REGISTER - setting isAuthenticated to TRUE');
      setIsAuthenticated(true);
      return { success: true };
    } catch (error: any) {
      console.error('Registration failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || '登録に失敗しました',
      };
    }
  };

  const logout = async () => {
    console.log('[useAuth] LOGOUT - starting logout process');
    
    try {
      await authService.logout();
    } catch (error) {
      console.error('Logout failed:', error);
    }
    
    // 状態を即座にクリア（finallyブロックの外で実行）
    console.log('[useAuth] LOGOUT - clearing user and setting isAuthenticated to FALSE');
    setUser(null);
    setIsAuthenticated(false);
    
    // 強制的に再レンダリングをトリガー
    setTimeout(() => {
      setLoading(true);
      setTimeout(() => {
        setLoading(false);
      }, 0);
    }, 0);
  };

  const returnValue = {
    user,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
  };

  console.log('[useAuth] RETURN VALUES:', {
    loading: returnValue.loading,
    loadingType: typeof returnValue.loading,
    isAuthenticated: returnValue.isAuthenticated,
    isAuthenticatedType: typeof returnValue.isAuthenticated,
  });

  return returnValue;
};