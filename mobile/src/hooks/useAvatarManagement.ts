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
import { API_CONFIG } from '../utils/constants';

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
    setIsLoading(true);
    setError(null);

    try {
      const data = await avatarService.getAvatar();
      
      // dataがnullの場合（アバター未作成）
      if (!data) {
        setAvatar(null);
        return null;
      }
      
      // localhost URLをngrok URLに置換（モバイルからはlocalhostにアクセス不可）
      if (data.images && data.images.length > 0) {
        data.images = data.images.map(image => {
          if (image.image_url && image.image_url.includes('localhost')) {
            const replacedUrl = image.image_url.replace('http://localhost:9100/mtdev-app-bucket', `${API_CONFIG.BASE_URL}/mtdev-app-bucket`);
            return { ...image, image_url: replacedUrl };
          }
          return image;
        });
      }
      
      setAvatar(data);
      return data;
    } catch (err: any) {
      
      // 404エラー（未作成）の場合はエラーとしない
      if (err.response?.status === 404) {
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
    setIsLoading(true);
    setError(null);

    try {
      const createdAvatar = await avatarService.createAvatar(data);
      setAvatar(createdAvatar);
      return createdAvatar;
    } catch (err: any) {
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
    setIsLoading(true);
    setError(null);

    try {
      const updatedAvatar = await avatarService.updateAvatar(data);
      setAvatar(updatedAvatar);
      return updatedAvatar;
    } catch (err: any) {
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
    setIsLoading(true);
    setError(null);

    try {
      await avatarService.deleteAvatar();
      setAvatar(null);
    } catch (err: any) {
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
    setIsLoading(true);
    setError(null);

    try {
      const regeneratedAvatar = await avatarService.regenerateImages();
      setAvatar(regeneratedAvatar);
      return regeneratedAvatar;
    } catch (err: any) {
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
    setIsLoading(true);
    setError(null);

    try {
      const updatedAvatar = await avatarService.toggleVisibility(isVisible);
      setAvatar(updatedAvatar);
      return updatedAvatar;
    } catch (err: any) {
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
