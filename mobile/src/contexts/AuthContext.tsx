/**
 * 認証コンテキスト
 * アプリ全体で認証状態を共有
 */
import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { authService } from '../services/auth.service';
import { userService } from '../services/user.service';
import { User } from '../types/api.types';

interface AuthContextType {
  user: User | null;
  loading: boolean;
  isAuthenticated: boolean;
  login: (username: string, password: string) => Promise<{ success: boolean; error?: string }>;
  register: (email: string, password: string, name: string, privacyConsent: boolean, termsConsent: boolean) => Promise<{ success: boolean; error?: string }>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const authenticated = await authService.isAuthenticated();
      console.log('[AuthContext] RAW authenticated:', JSON.stringify(authenticated), 'type:', typeof authenticated);
      
      // 確実にbooleanに変換
      const boolValue = authenticated === true;
      console.log('[AuthContext] CONVERTED isAuthenticated:', boolValue, 'type:', typeof boolValue);
      setIsAuthenticated(boolValue);
      
      // トークンがある場合のみユーザー情報を取得
      if (boolValue) {
        try {
          console.log('[AuthContext] Fetching user data from API...');
          // APIから最新のユーザー情報（グループ情報含む）を取得
          const currentUser = await userService.getCurrentUser();
          console.log('[AuthContext] User data loaded:', JSON.stringify({
            id: currentUser.id,
            username: currentUser.username,
            group_id: currentUser.group_id,
            group_edit_flg: currentUser.group_edit_flg,
            group: currentUser.group,
          }, null, 2));
          // User型として扱う（CurrentUserResponse['data']とUser型は互換性がある）
          setUser(currentUser as User);
        } catch (error) {
          console.error('[AuthContext] Failed to get current user:', error);
          console.error('[AuthContext] Error details:', JSON.stringify(error, null, 2));
          // ユーザー情報取得失敗時は認証状態をfalseに戻す
          setIsAuthenticated(false);
          setUser(null);
        }
      } else {
        setUser(null);
      }
    } catch (error) {
      console.error('[AuthContext] Auth check failed:', error);
      setIsAuthenticated(false);
      setUser(null);
    } finally {
      console.log('[AuthContext] Setting loading to FALSE');
      setLoading(false);
    }
  };

  const login = async (username: string, password: string) => {
    try {
      // ログイン実行（トークン取得）
      await authService.login(username, password);
      
      // ログイン成功後、APIから最新のユーザー情報（グループ情報含む）を取得
      console.log('[AuthContext] LOGIN - fetching current user data from API...');
      const currentUser = await userService.getCurrentUser();
      console.log('[AuthContext] LOGIN - user data loaded:', JSON.stringify({
        id: currentUser.id,
        username: currentUser.username,
        group_id: currentUser.group_id,
        group_edit_flg: currentUser.group_edit_flg,
        group: currentUser.group,
      }, null, 2));
      
      // User型として扱う（CurrentUserResponse['data']とUser型は互換性がある）
      setUser(currentUser as User);
      console.log('[AuthContext] LOGIN - setting isAuthenticated to TRUE');
      setIsAuthenticated(true);
      return { success: true };
    } catch (error: any) {
      console.error('[AuthContext] Login failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'ログインに失敗しました',
      };
    }
  };

  const register = async (
    email: string, 
    password: string, 
    name: string, 
    privacyConsent: boolean, 
    termsConsent: boolean,
    birthdate?: string,
    parentEmail?: string
  ) => {
    try {
      // Phase 5-2: 登録実行（トークン取得、13歳未満の場合は保護者同意待ち）
      const response = await authService.register(
        email, 
        password, 
        name, 
        privacyConsent, 
        termsConsent,
        birthdate,
        parentEmail
      );
      
      // Phase 5-2: 保護者同意待ちの場合はトークン未発行
      if (response.requires_parent_consent) {
        return { 
          success: true, 
          requiresParentConsent: true,
          parentEmail: response.parent_email 
        };
      }
      
      // 登録成功後、APIから最新のユーザー情報（グループ情報含む）を取得
      console.log('[AuthContext] REGISTER - fetching current user data from API...');
      const currentUser = await userService.getCurrentUser();
      console.log('[AuthContext] REGISTER - user data loaded:', JSON.stringify({
        id: currentUser.id,
        username: currentUser.username,
        group_id: currentUser.group_id,
        group_edit_flg: currentUser.group_edit_flg,
        group: currentUser.group,
      }, null, 2));
      
      // User型として扱う（CurrentUserResponse['data']とUser型は互換性がある）
      setUser(currentUser as User);
      console.log('[AuthContext] REGISTER - setting isAuthenticated to TRUE');
      setIsAuthenticated(true);
      return { success: true };
    } catch (error: any) {
      console.error('[AuthContext] Registration failed:', error);
      return {
        success: false,
        error: error.response?.data?.message || '登録に失敗しました',
      };
    }
  };

  const logout = async () => {
    console.log('[AuthContext] LOGOUT - starting logout process');
    
    try {
      await authService.logout();
    } catch (error) {
      console.error('[AuthContext] Logout failed:', error);
    }
    
    // 状態を即座にクリア
    console.log('[AuthContext] LOGOUT - clearing user and setting isAuthenticated to FALSE');
    setUser(null);
    setIsAuthenticated(false);
  };

  const contextValue: AuthContextType = {
    user,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
  };

  console.log('[AuthContext] CONTEXT VALUES:', {
    loading: contextValue.loading,
    loadingType: typeof contextValue.loading,
    isAuthenticated: contextValue.isAuthenticated,
    isAuthenticatedType: typeof contextValue.isAuthenticated,
  });

  return (
    <AuthContext.Provider value={contextValue}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
