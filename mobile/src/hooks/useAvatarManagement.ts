/**
 * アバター管理カスタムフック
 * 
 * 教師アバターのCRUD操作とローディング・エラー状態管理を提供
 * Phase 2.B-7: アバター管理機能実装
 * 
 * 注意: useAvatar.ts はAvatarContext用のため、このHookは別名
 */
import { useState, useCallback } from 'react';
import { avatarService } from '../services/avatar.service';
import { 
  Avatar, 
  CreateAvatarRequest, 
  UpdateAvatarRequest 
} from '../types/avatar.types';

/**
 * アバター管理Hook
 * 
 * @returns アバター情報とCRUD操作メソッド
 */
export const useAvatarManagement = () => {
  const [avatar, setAvatar] = useState<Avatar | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * アバター取得
   * 
   * @returns アバター情報（取得成功時）、null（未作成時）
   */
  const fetchAvatar = useCallback(async (): Promise<Avatar | null> => {
    console.log('🎭 [useAvatarManagement] fetchAvatar called');
    setIsLoading(true);
    setError(null);

    try {
      const data = await avatarService.getAvatar();
      console.log('🎭 [useAvatarManagement] Avatar fetched:', data);
      setAvatar(data);
      return data;
    } catch (err: any) {
      console.error('🎭 [useAvatarManagement] fetchAvatar error:', err);
      
      // 404エラー（未作成）の場合はエラーとしない
      if (err.response?.status === 404) {
        console.log('🎭 [useAvatarManagement] Avatar not found (not created yet)');
        setAvatar(null);
        return null;
      }
      
      const errorMessage = err.response?.data?.message || 'アバターの取得に失敗しました。';
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * アバター作成
   * 
   * @param data - アバター作成データ
   * @returns 作成されたアバター情報
   * @throws {Error} 作成失敗時
   */
  const createAvatar = useCallback(async (data: CreateAvatarRequest): Promise<Avatar> => {
    console.log('🎭 [useAvatarManagement] createAvatar called:', data);
    setIsLoading(true);
    setError(null);

    try {
      const createdAvatar = await avatarService.createAvatar(data);
      console.log('🎭 [useAvatarManagement] Avatar created:', createdAvatar);
      setAvatar(createdAvatar);
      return createdAvatar;
    } catch (err: any) {
      console.error('🎭 [useAvatarManagement] createAvatar error:', err);
      const errorMessage = err.response?.data?.message || 'アバターの作成に失敗しました。';
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * アバター更新
   * 
   * @param data - アバター更新データ
   * @returns 更新されたアバター情報
   * @throws {Error} 更新失敗時
   */
  const updateAvatar = useCallback(async (data: UpdateAvatarRequest): Promise<Avatar> => {
    console.log('🎭 [useAvatarManagement] updateAvatar called:', data);
    setIsLoading(true);
    setError(null);

    try {
      const updatedAvatar = await avatarService.updateAvatar(data);
      console.log('🎭 [useAvatarManagement] Avatar updated:', updatedAvatar);
      setAvatar(updatedAvatar);
      return updatedAvatar;
    } catch (err: any) {
      console.error('🎭 [useAvatarManagement] updateAvatar error:', err);
      const errorMessage = err.response?.data?.message || 'アバターの更新に失敗しました。';
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * アバター削除
   * 
   * @throws {Error} 削除失敗時
   */
  const deleteAvatar = useCallback(async (): Promise<void> => {
    console.log('🎭 [useAvatarManagement] deleteAvatar called');
    setIsLoading(true);
    setError(null);

    try {
      await avatarService.deleteAvatar();
      console.log('🎭 [useAvatarManagement] Avatar deleted');
      setAvatar(null);
    } catch (err: any) {
      console.error('🎭 [useAvatarManagement] deleteAvatar error:', err);
      const errorMessage = err.response?.data?.message || 'アバターの削除に失敗しました。';
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * アバター画像再生成
   * 
   * @returns 再生成開始後のアバター情報
   * @throws {Error} 再生成失敗時
   */
  const regenerateImages = useCallback(async (): Promise<Avatar> => {
    console.log('🎭 [useAvatarManagement] regenerateImages called');
    setIsLoading(true);
    setError(null);

    try {
      const regeneratedAvatar = await avatarService.regenerateImages();
      console.log('🎭 [useAvatarManagement] Avatar images regenerated:', regeneratedAvatar);
      setAvatar(regeneratedAvatar);
      return regeneratedAvatar;
    } catch (err: any) {
      console.error('🎭 [useAvatarManagement] regenerateImages error:', err);
      const errorMessage = err.response?.data?.message || 'アバター画像の再生成に失敗しました。';
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * アバター表示設定切替
   * 
   * @param isVisible - 表示/非表示フラグ
   * @returns 切替後のアバター情報
   * @throws {Error} 切替失敗時
   */
  const toggleVisibility = useCallback(async (isVisible: boolean): Promise<Avatar> => {
    console.log('🎭 [useAvatarManagement] toggleVisibility called:', isVisible);
    setIsLoading(true);
    setError(null);

    try {
      const updatedAvatar = await avatarService.toggleVisibility(isVisible);
      console.log('🎭 [useAvatarManagement] Avatar visibility toggled:', updatedAvatar);
      setAvatar(updatedAvatar);
      return updatedAvatar;
    } catch (err: any) {
      console.error('🎭 [useAvatarManagement] toggleVisibility error:', err);
      const errorMessage = err.response?.data?.message || '表示設定の切替に失敗しました。';
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * エラーメッセージクリア
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  return {
    avatar,
    isLoading,
    error,
    fetchAvatar,
    createAvatar,
    updateAvatar,
    deleteAvatar,
    regenerateImages,
    toggleVisibility,
    clearError,
  };
};

export default useAvatarManagement;
