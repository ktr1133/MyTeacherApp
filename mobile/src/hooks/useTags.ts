/**
 * タグ管理フック
 * 
 * TagServiceを呼び出し、状態管理とテーマに応じたエラーメッセージ表示を担当
 * アバターイベントをAvatarContextに通知
 */

import { useState, useCallback } from 'react';
import * as tagService from '../services/tag.service';
import { useTheme } from '../contexts/ThemeContext';
import { useAvatarContext } from '../contexts/AvatarContext';
import { getErrorMessage } from '../utils/errorMessages';
import type { Tag, CreateTagRequest, UpdateTagRequest } from '../types/tag.types';

/**
 * タグフックの戻り値型
 */
interface UseTagsReturn {
  // 状態
  tags: Tag[];
  isLoading: boolean;
  error: string | null;

  // タグ操作
  fetchTags: () => Promise<void>;
  createTag: (data: CreateTagRequest) => Promise<Tag | null>;
  updateTag: (id: number, data: UpdateTagRequest) => Promise<Tag | null>;
  deleteTag: (id: number) => Promise<boolean>;

  // ユーティリティ
  clearError: () => void;
  refreshTags: () => Promise<void>;
}

/**
 * タグ管理カスタムフック
 * 
 * TagServiceを呼び出し、UIに必要な状態管理とエラーハンドリングを提供
 * テーマに応じたエラーメッセージを自動変換
 * アバターイベントをAvatarContextに通知
 * 
 * @example
 * ```tsx
 * const { tags, isLoading, error, fetchTags, createTag } = useTags();
 * 
 * useEffect(() => {
 *   fetchTags();
 * }, []);
 * 
 * const handleCreateTag = async () => {
 *   const newTag = await createTag({ name: '数学', color: '#3B82F6' });
 *   if (newTag) {
 *     console.log('タグ作成成功:', newTag);
 *   }
 * };
 * ```
 */
export const useTags = (): UseTagsReturn => {
  const { theme } = useTheme();
  const { dispatchAvatarEvent } = useAvatarContext();
  const [tags, setTags] = useState<Tag[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * エラーをテーマに応じたメッセージに変換してセット
   */
  const handleError = useCallback(
    (error: any) => {
      const errorMessage = error.message || 'UNKNOWN_ERROR';
      const localizedMessage = getErrorMessage(errorMessage, theme);
      setError(localizedMessage);
    },
    [theme]
  );

  /**
   * エラーをクリア
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  /**
   * タグ一覧を取得
   * 
   * @returns Promise（成功時はvoid、失敗時はエラーハンドリング済み）
   */
  const fetchTags = useCallback(async (): Promise<void> => {
    setIsLoading(true);
    setError(null);

    try {
      const response = await tagService.getTagsWithTasks();
      setTags(response.tags);
    } catch (err: any) {
      handleError(err);
    } finally {
      setIsLoading(false);
    }
  }, [handleError]);

  /**
   * タグを作成
   * 
   * @param data タグ名と色（オプション）
   * @returns 作成されたタグ（失敗時はnull）
   */
  const createTag = useCallback(
    async (data: CreateTagRequest): Promise<Tag | null> => {
      setIsLoading(true);
      setError(null);

      try {
        const response = await tagService.createTag(data);
        
        // タグ一覧を更新（楽観的更新）
        setTags((prevTags) => [
          ...prevTags,
          { ...response.tag, tasks_count: 0 }, // 新規タグはタスク件数0
        ]);

        // アバターイベント表示
        if (response.avatar_event) {
          await dispatchAvatarEvent(response.avatar_event as any);
        }

        return response.tag;
      } catch (err: any) {
        handleError(err);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError, dispatchAvatarEvent]
  );

  /**
   * タグを更新
   * 
   * @param id タグID
   * @param data タグ名と色
   * @returns 更新されたタグ（失敗時はnull）
   */
  const updateTag = useCallback(
    async (id: number, data: UpdateTagRequest): Promise<Tag | null> => {
      setIsLoading(true);
      setError(null);

      try {
        const response = await tagService.updateTag(id, data);
        
        // タグ一覧を更新（楽観的更新）
        setTags((prevTags) =>
          prevTags.map((tag) =>
            tag.id === id ? { ...tag, ...response.tag } : tag
          )
        );

        // アバターイベント表示
        if (response.avatar_event) {
          await dispatchAvatarEvent(response.avatar_event as any);
        }

        return response.tag;
      } catch (err: any) {
        handleError(err);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError, dispatchAvatarEvent]
  );

  /**
   * タグを削除
   * 
   * @param id タグID
   * @returns 成功時はtrue、失敗時はfalse
   */
  const deleteTag = useCallback(
    async (id: number): Promise<boolean> => {
      setIsLoading(true);
      setError(null);

      try {
        const response = await tagService.deleteTag(id);
        
        // タグ一覧から削除（楽観的更新）
        setTags((prevTags) => prevTags.filter((tag) => tag.id !== id));

        // アバターイベント表示
        if (response.avatar_event) {
          await dispatchAvatarEvent(response.avatar_event as any);
        }

        return true;
      } catch (err: any) {
        handleError(err);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError, dispatchAvatarEvent]
  );

  /**
   * タグ一覧を再取得
   * 
   * @returns Promise（成功時はvoid、失敗時はエラーハンドリング済み）
   */
  const refreshTags = useCallback(async (): Promise<void> => {
    await fetchTags();
  }, [fetchTags]);

  return {
    tags,
    isLoading,
    error,
    fetchTags,
    createTag,
    updateTag,
    deleteTag,
    clearError,
    refreshTags,
  };
};
