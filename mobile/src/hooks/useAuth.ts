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
      const boolValue = authenticated === true || authenticated === 'true';
      console.log('[useAuth] CONVERTED isAuthenticated:', boolValue, 'type:', typeof boolValue);
      setIsAuthenticated(boolValue);
      
      if (authenticated) {
        const currentUser = await authService.getCurrentUser();
        setUser(currentUser);
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      setIsAuthenticated(false);
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
    try {
      await authService.logout();
    } catch (error) {
      console.error('Logout failed:', error);
    } finally {
      setUser(null);
      console.log('[useAuth] LOGOUT - setting isAuthenticated to FALSE');
      setIsAuthenticated(false);
    }
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