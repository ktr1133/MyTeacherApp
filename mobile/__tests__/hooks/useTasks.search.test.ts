/**
 * useTasks.searchTasks() テスト
 * 
 * 検索Hook（デバウンス処理、エラーハンドリング）の動作を検証
 */

import { renderHook, act, waitFor } from '@testing-library/react-native';
import { useTasks } from '../../src/hooks/useTasks';
import { taskService } from '../../src/services/task.service';
import { ThemeProvider } from '../../src/contexts/ThemeContext';
import * as React from 'react';

jest.mock('../../src/services/task.service');

const mockedTaskService = taskService as jest.Mocked<typeof taskService>;

// ThemeProviderラッパー
const wrapper = ({ children }: { children: React.ReactNode }) => 
  React.createElement(ThemeProvider, {}, children);

describe('useTasks.searchTasks()', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.runOnlyPendingTimers();
    jest.useRealTimers();
  });

  describe('正常系', () => {
    it('検索クエリでタスクを取得できる', async () => {
      const mockResponse = {
        tasks: [
          {
            id: 1,
            title: '検索テスト',
            description: 'テスト説明',
            status: 'pending' as const,
            reward_tokens: 100,
            user_id: 1,
            images: [],
            tags: [],
            created_at: '2025-01-01T00:00:00.000Z',
            updated_at: '2025-01-01T00:00:00.000Z',
          },
        ],
        pagination: {
          current_page: 1,
          per_page: 10,
          total: 1,
          last_page: 1,
        },
      };

      mockedTaskService.searchTasks.mockResolvedValueOnce(mockResponse);

      const { result } = renderHook(() => useTasks(), { wrapper });

      act(() => {
        result.current.searchTasks('検索');
      });

      // デバウンス処理（300ms）を待機
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(result.current.tasks).toHaveLength(1);
      });

      expect(mockedTaskService.searchTasks).toHaveBeenCalledWith('検索', undefined);
      expect(result.current.tasks[0].title).toBe('検索テスト');
      expect(result.current.error).toBeNull();
    });

    it('検索クエリと追加フィルターでタスクを取得できる', async () => {
      const mockResponse = {
        tasks: [],
        pagination: {
          current_page: 1,
          per_page: 10,
          total: 0,
          last_page: 1,
        },
      };

      mockedTaskService.searchTasks.mockResolvedValueOnce(mockResponse);

      const { result } = renderHook(() => useTasks(), { wrapper });

      act(() => {
        result.current.searchTasks('テスト', { status: 'completed' });
      });

      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(mockedTaskService.searchTasks).toHaveBeenCalledWith('テスト', { status: 'completed' });
    });

    it('デバウンス処理で連続入力を制御できる', async () => {
      const mockResponse = {
        tasks: [],
        pagination: {
          current_page: 1,
          per_page: 10,
          total: 0,
          last_page: 1,
        },
      };

      mockedTaskService.searchTasks.mockResolvedValue(mockResponse);

      const { result } = renderHook(() => useTasks(), { wrapper });

      // 連続で3回検索を実行
      act(() => {
        result.current.searchTasks('a');
        jest.advanceTimersByTime(100);
        result.current.searchTasks('ab');
        jest.advanceTimersByTime(100);
        result.current.searchTasks('abc');
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      // 最後の検索のみ実行される（デバウンス効果）
      expect(mockedTaskService.searchTasks).toHaveBeenCalledTimes(1);
      expect(mockedTaskService.searchTasks).toHaveBeenCalledWith('abc', undefined);
    });
  });

  describe('異常系', () => {
    it('検索エラー時にエラーメッセージをセットする', async () => {
      const error = new Error('TASK_SEARCH_FAILED');
      mockedTaskService.searchTasks.mockRejectedValueOnce(error);

      const { result } = renderHook(() => useTasks(), { wrapper });

      act(() => {
        result.current.searchTasks('エラー');
      });

      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });

      expect(result.current.tasks).toHaveLength(0);
      expect(result.current.pagination).toBeNull();
    });

    it('AUTH_REQUIREDエラー時にテーマに応じたメッセージを表示', async () => {
      const error = new Error('AUTH_REQUIRED');
      mockedTaskService.searchTasks.mockRejectedValueOnce(error);

      const { result } = renderHook(() => useTasks(), { wrapper });

      act(() => {
        result.current.searchTasks('認証');
      });

      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });

      // adult テーマのエラーメッセージ
      expect(result.current.error).toContain('ログインが必要です');
    });

    it('NETWORK_ERRORエラー時にテーマに応じたメッセージを表示', async () => {
      const error = new Error('NETWORK_ERROR');
      mockedTaskService.searchTasks.mockRejectedValueOnce(error);

      const { result } = renderHook(() => useTasks(), { wrapper });

      act(() => {
        result.current.searchTasks('ネットワーク');
      });

      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });

      expect(result.current.error).toContain('ネットワークエラー');
    });
  });

  describe('ローディング状態', () => {
    it('検索中はisLoadingがtrueになる', async () => {
      const mockResponse = {
        tasks: [],
        pagination: {
          current_page: 1,
          per_page: 10,
          total: 0,
          last_page: 1,
        },
      };

      mockedTaskService.searchTasks.mockImplementation(
        () =>
          new Promise((resolve) => {
            setTimeout(() => resolve(mockResponse), 100);
          })
      );

      const { result } = renderHook(() => useTasks(), { wrapper });

      act(() => {
        result.current.searchTasks('ローディング');
      });

      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(true);
      });

      act(() => {
        jest.advanceTimersByTime(100);
      });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });
    });
  });
});
