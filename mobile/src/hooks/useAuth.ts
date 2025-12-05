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
      setIsAuthenticated(authenticated);
      
      if (authenticated) {
        const currentUser = await authService.getCurrentUser();
        setUser(currentUser);
      }
    } catch (error) {
      console.error('Auth check failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const login = async (username: string, password: string) => {
    try {
      const { user: loggedInUser } = await authService.login(username, password);
      setUser(loggedInUser);
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
      // ネットワークエラーでも確実にローカル状態をクリア
    } finally {
      setUser(null);
      setIsAuthenticated(false);
    }
  };

  return {
    user,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
  };
};
