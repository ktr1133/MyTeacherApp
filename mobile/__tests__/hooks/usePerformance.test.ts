/**
 * usePerformance.ts テスト
 * 
 * パフォーマンスHook（状態管理、API呼び出し、エラーハンドリング）の動作を検証
 */

import { renderHook, act, waitFor } from '@testing-library/react-native';
import { usePerformance, useMonthlyReport } from '../../src/hooks/usePerformance';
import * as performanceService from '../../src/services/performance.service';
import { ThemeProvider } from '../../src/contexts/ThemeContext';
import { authService } from '../../src/services/auth.service';
import * as React from 'react';

jest.mock('../../src/services/performance.service');
jest.mock('../../src/services/auth.service', () => ({
  authService: {
    isAuthenticated: jest.fn(),
    getUser: jest.fn(),
  },
}));

const mockedPerformanceService = performanceService as jest.Mocked<typeof performanceService>;
const mockedAuthService = authService as jest.Mocked<typeof authService>;

// モックユーザー
const mockUser = {
  id: 1,
  email: 'test@example.com',
  name: 'テストユーザー',
  group_id: 1,
  role: 'user' as const,
  created_at: '2025-01-01T00:00:00.000Z',
};

// AuthContextのモック
jest.mock('../../src/contexts/AuthContext', () => {
  const actualModule = jest.requireActual('../../src/contexts/AuthContext');
  return {
    ...actualModule,
    useAuth: jest.fn(),
    AuthProvider: ({ children }: { children: React.ReactNode }) => children,
  };
});

// ThemeProviderラッパー
const wrapper = ({ children }: { children: React.ReactNode }) =>
  React.createElement(ThemeProvider, {}, children);

// useAuthのモック取得
const { useAuth } = require('../../src/contexts/AuthContext');

describe('usePerformance()', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    
    // authServiceモックのセットアップ
    mockedAuthService.isAuthenticated.mockResolvedValue(true);
    mockedAuthService.getUser.mockResolvedValue(mockUser);
    
    // useAuthモックのセットアップ
    useAuth.mockReturnValue({
      user: mockUser,
      isAuthenticated: true,
      isLoading: false,
    });
  });

  describe('正常系', () => {
    it('初期状態が正しく設定される', async () => {
      mockedPerformanceService.getPerformanceData.mockResolvedValue({
        period_label: '2025年1月第1週',
        task_type: 'normal',
        chart_data: {
          labels: ['月', '火', '水'],
          datasets: [],
        },
        summary: {
          total_completed: 10,
          total_incomplete: 5,
          total_reward: 0,
          average_per_day: 3.3,
        },
        can_navigate_prev: false,
        can_navigate_next: false,
        has_subscription: false,
        restrictions: {
          period_restricted: true,
          navigation_restricted: true,
          member_restricted: true,
        },
        members: [],
        selected_user_id: 0,
        is_group_whole: true,
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      // 初期状態の確認
      expect(result.current.period).toBe('week');
      expect(result.current.taskType).toBe('normal');
      expect(result.current.offset).toBe(0);
      expect(result.current.selectedUserId).toBe(0);

      // データ取得完了を待機
      await waitFor(() => {
        expect(result.current.data).not.toBeNull();
      });

      expect(result.current.data?.period_label).toBe('2025年1月第1週');
      expect(result.current.isLoading).toBe(false);
      expect(result.current.error).toBeNull();
    });

    it('期間変更でデータを再取得する', async () => {
      mockedPerformanceService.getPerformanceData.mockResolvedValue({
        period_label: '2025年1月',
        task_type: 'normal',
        chart_data: { labels: [], datasets: [] },
        summary: {
          total_completed: 50,
          total_incomplete: 10,
          total_reward: 0,
          average_per_day: 1.6,
        },
        can_navigate_prev: true,
        can_navigate_next: false,
        has_subscription: false,
        restrictions: {
          period_restricted: true,
          navigation_restricted: true,
          member_restricted: true,
        },
        members: [],
        selected_user_id: 0,
        is_group_whole: true,
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.data).not.toBeNull();
      });

      act(() => {
        result.current.changePeriod('month');
      });

      expect(result.current.period).toBe('month');
      expect(result.current.offset).toBe(0); // オフセットもリセット

      await waitFor(() => {
        expect(mockedPerformanceService.getPerformanceData).toHaveBeenLastCalledWith({
          tab: 'normal',
          period: 'month',
          offset: 0,
          user_id: 0,
        });
      });
    });

    it('タスク種別変更でデータを再取得する', async () => {
      mockedPerformanceService.getPerformanceData.mockResolvedValue({
        period_label: '2025年1月第1週',
        task_type: 'group',
        chart_data: { labels: [], datasets: [] },
        summary: {
          total_completed: 20,
          total_incomplete: 5,
          total_reward: 5000,
          average_per_day: 2.8,
        },
        can_navigate_prev: false,
        can_navigate_next: false,
        has_subscription: true,
        restrictions: {
          period_restricted: false,
          navigation_restricted: false,
          member_restricted: false,
        },
        members: [],
        selected_user_id: 0,
        is_group_whole: true,
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.data).not.toBeNull();
      });

      act(() => {
        result.current.changeTaskType('group');
      });

      expect(result.current.taskType).toBe('group');
      expect(result.current.offset).toBe(0); // オフセットもリセット

      await waitFor(() => {
        expect(mockedPerformanceService.getPerformanceData).toHaveBeenLastCalledWith({
          tab: 'group',
          period: 'week',
          offset: 0,
          user_id: 0,
        });
      });
    });

    it('前へナビゲーションでオフセットが変更される', async () => {
      mockedPerformanceService.getPerformanceData.mockResolvedValue({
        period_label: '2025年1月第1週',
        task_type: 'normal',
        chart_data: { labels: [], datasets: [] },
        summary: {
          total_completed: 10,
          total_incomplete: 5,
          total_reward: 0,
          average_per_day: 1.4,
        },
        can_navigate_prev: true,
        can_navigate_next: false,
        has_subscription: true,
        restrictions: {
          period_restricted: false,
          navigation_restricted: false,
          member_restricted: false,
        },
        members: [],
        selected_user_id: 0,
        is_group_whole: true,
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.data).not.toBeNull();
      });

      act(() => {
        result.current.navigatePrev();
      });

      expect(result.current.offset).toBe(-1);

      await waitFor(() => {
        expect(mockedPerformanceService.getPerformanceData).toHaveBeenLastCalledWith({
          tab: 'normal',
          period: 'week',
          offset: -1,
          user_id: 0,
        });
      });
    });

    it('次へナビゲーションでオフセットが変更される', async () => {
      mockedPerformanceService.getPerformanceData.mockResolvedValue({
        period_label: '2025年1月第1週',
        task_type: 'normal',
        chart_data: { labels: [], datasets: [] },
        summary: {
          total_completed: 10,
          total_incomplete: 5,
          total_reward: 0,
          average_per_day: 1.4,
        },
        can_navigate_prev: false,
        can_navigate_next: true,
        has_subscription: true,
        restrictions: {
          period_restricted: false,
          navigation_restricted: false,
          member_restricted: false,
        },
        members: [],
        selected_user_id: 0,
        is_group_whole: true,
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.data).not.toBeNull();
      });

      act(() => {
        result.current.navigateNext();
      });

      expect(result.current.offset).toBe(1);

      await waitFor(() => {
        expect(mockedPerformanceService.getPerformanceData).toHaveBeenLastCalledWith({
          tab: 'normal',
          period: 'week',
          offset: 1,
          user_id: 0,
        });
      });
    });

    it('ナビゲーション不可時は何もしない', async () => {
      mockedPerformanceService.getPerformanceData.mockResolvedValue({
        period_label: '2025年1月第1週',
        task_type: 'normal',
        chart_data: { labels: [], datasets: [] },
        summary: {
          total_completed: 10,
          total_incomplete: 5,
          total_reward: 0,
          average_per_day: 1.4,
        },
        can_navigate_prev: false,
        can_navigate_next: false,
        has_subscription: false,
        restrictions: {
          period_restricted: true,
          navigation_restricted: true,
          member_restricted: true,
        },
        members: [],
        selected_user_id: 0,
        is_group_whole: true,
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.data).not.toBeNull();
      });

      const initialOffset = result.current.offset;

      act(() => {
        result.current.navigatePrev();
        result.current.navigateNext();
      });

      expect(result.current.offset).toBe(initialOffset);
    });

    it('メンバー選択変更でデータを再取得する', async () => {
      mockedPerformanceService.getPerformanceData.mockResolvedValue({
        period_label: '2025年1月第1週',
        task_type: 'normal',
        chart_data: { labels: [], datasets: [] },
        summary: {
          total_completed: 5,
          total_incomplete: 2,
          total_reward: 0,
          average_per_day: 0.7,
        },
        can_navigate_prev: false,
        can_navigate_next: false,
        has_subscription: true,
        restrictions: {
          period_restricted: false,
          navigation_restricted: false,
          member_restricted: false,
        },
        members: [
          { id: 1, name: 'メンバー1' },
          { id: 2, name: 'メンバー2' },
        ],
        selected_user_id: 1,
        is_group_whole: false,
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.data).not.toBeNull();
      });

      act(() => {
        result.current.changeSelectedUser(1);
      });

      expect(result.current.selectedUserId).toBe(1);

      await waitFor(() => {
        expect(mockedPerformanceService.getPerformanceData).toHaveBeenLastCalledWith({
          tab: 'normal',
          period: 'week',
          offset: 0,
          user_id: 1,
        });
      });
    });

    it('refreshでデータを再取得する', async () => {
      mockedPerformanceService.getPerformanceData.mockResolvedValue({
        period_label: '2025年1月第1週',
        task_type: 'normal',
        chart_data: { labels: [], datasets: [] },
        summary: {
          total_completed: 10,
          total_incomplete: 5,
          total_reward: 0,
          average_per_day: 1.4,
        },
        can_navigate_prev: false,
        can_navigate_next: false,
        has_subscription: false,
        restrictions: {
          period_restricted: true,
          navigation_restricted: true,
          member_restricted: true,
        },
        members: [],
        selected_user_id: 0,
        is_group_whole: true,
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.data).not.toBeNull();
      });

      const callCountBefore = mockedPerformanceService.getPerformanceData.mock.calls.length;

      act(() => {
        result.current.refresh();
      });

      await waitFor(() => {
        expect(mockedPerformanceService.getPerformanceData).toHaveBeenCalledTimes(callCountBefore + 1);
      });
    });
  });

  describe('異常系', () => {
    it('APIエラー時にエラーメッセージを設定する', async () => {
      mockedPerformanceService.getPerformanceData.mockRejectedValue({
        response: {
          data: {
            message: 'データ取得エラー',
          },
        },
      });

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });

      expect(result.current.error).toBe('データ取得エラー');
      expect(result.current.isLoading).toBe(false);
      expect(result.current.data).toBeNull();
    });

    it('ネットワークエラー時にデフォルトメッセージを設定する', async () => {
      mockedPerformanceService.getPerformanceData.mockRejectedValue(new Error('Network Error'));

      const { result } = renderHook(() => usePerformance(), { wrapper });

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });

      expect(result.current.error).toBe('データの取得に失敗しました');
    });
  });
});

describe('useMonthlyReport()', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    
    // authServiceモックのセットアップ
    mockedAuthService.isAuthenticated.mockResolvedValue(true);
    mockedAuthService.getUser.mockResolvedValue(mockUser);
    
    // useAuthモックのセットアップ
    useAuth.mockReturnValue({
      user: mockUser,
      isAuthenticated: true,
      isLoading: false,
    });
  });

  describe('正常系', () => {
    it('初期状態が正しく設定され、利用可能月を取得する', async () => {
      const mockAvailableMonths = [
        { year: '2024', month: '12', label: '2024年12月' },
        { year: '2024', month: '11', label: '2024年11月' },
      ];

      const mockReport = {
        month_label: '2024年12月',
        year_month: '2024-12',
        group_name: 'テストグループ',
        summary: {
          total_completed: 25,
          total_incomplete: 5,
          total_reward: 5000,
          normal_tasks_count: 15,
          group_tasks_count: 10,
        },
        member_stats: [],
        trend_data: {
          labels: [],
          normal_tasks: [],
          group_tasks: [],
        },
        has_subscription: true,
        can_access: true,
      };

      mockedPerformanceService.getAvailableMonths.mockResolvedValue(mockAvailableMonths);
      mockedPerformanceService.getMonthlyReport.mockResolvedValue(mockReport);

      const { result } = renderHook(() => useMonthlyReport(), { wrapper });

      // 初期状態の確認
      expect(result.current.isLoading).toBe(false);
      expect(result.current.error).toBeNull();

      await waitFor(() => {
        expect(result.current.availableMonths).toHaveLength(2);
      });

      // 前月（12月）が自動選択される
      await waitFor(() => {
        expect(result.current.selectedYear).toBe('2024');
        expect(result.current.selectedMonth).toBe('12');
      });

      await waitFor(() => {
        expect(result.current.report).not.toBeNull();
      });

      expect(result.current.report?.month_label).toBe('2024年12月');
    });

    it('月変更でレポートを再取得する', async () => {
      const mockAvailableMonths = [
        { year: '2025', month: '01', label: '2025年1月' },
        { year: '2024', month: '12', label: '2024年12月' },
      ];

      const mockReport1 = {
        month_label: '2024年12月',
        year_month: '2024-12',
        group_name: 'テストグループ',
        summary: {
          total_completed: 20,
          total_incomplete: 5,
          total_reward: 4000,
          normal_tasks_count: 12,
          group_tasks_count: 8,
        },
        member_stats: [],
        trend_data: { labels: [], normal_tasks: [], group_tasks: [] },
        has_subscription: true,
        can_access: true,
      };

      const mockReport2 = {
        month_label: '2025年1月',
        year_month: '2025-01',
        group_name: 'テストグループ',
        summary: {
          total_completed: 30,
          total_incomplete: 10,
          total_reward: 6000,
          normal_tasks_count: 18,
          group_tasks_count: 12,
        },
        member_stats: [],
        trend_data: { labels: [], normal_tasks: [], group_tasks: [] },
        has_subscription: true,
        can_access: true,
      };

      mockedPerformanceService.getAvailableMonths.mockResolvedValue(mockAvailableMonths);
      mockedPerformanceService.getMonthlyReport
        .mockResolvedValueOnce(mockReport1)
        .mockResolvedValueOnce(mockReport2);

      const { result } = renderHook(() => useMonthlyReport(), { wrapper });

      await waitFor(() => {
        expect(result.current.report).not.toBeNull();
      });

      act(() => {
        result.current.changeMonth('2025', '01');
      });

      expect(result.current.selectedYear).toBe('2025');
      expect(result.current.selectedMonth).toBe('01');

      await waitFor(() => {
        expect(result.current.report?.month_label).toBe('2025年1月');
      });
    });

    it('メンバーサマリーを生成できる', async () => {
      const mockAvailableMonths = [
        { year: '2025', month: '01', label: '2025年1月' },
      ];

      const mockReport = {
        month_label: '2025年1月',
        year_month: '2025-01',
        group_name: 'テストグループ',
        summary: {
          total_completed: 25,
          total_incomplete: 5,
          total_reward: 5000,
          normal_tasks_count: 15,
          group_tasks_count: 10,
        },
        member_stats: [],
        trend_data: { labels: [], normal_tasks: [], group_tasks: [] },
        has_subscription: true,
        can_access: true,
      };

      const mockSummary = {
        user_id: 1,
        user_name: 'テストユーザー',
        year_month: '2025-01',
        comment: 'テストコメント',
        task_classification: {
          labels: ['家事', '勉強'],
          data: [10, 5],
        },
        reward_trend: {
          labels: ['1週', '2週'],
          data: [500, 800],
        },
        tokens_used: 1000,
        generated_at: '2025-01-15T00:00:00.000Z',
      };

      mockedPerformanceService.getAvailableMonths.mockResolvedValue(mockAvailableMonths);
      mockedPerformanceService.getMonthlyReport.mockResolvedValue(mockReport);
      mockedPerformanceService.generateMemberSummary.mockResolvedValue(mockSummary);

      const { result } = renderHook(() => useMonthlyReport(), { wrapper });

      await waitFor(() => {
        expect(result.current.selectedYear).toBe('2025');
        expect(result.current.selectedMonth).toBe('01');
      });

      let summary: any = null;
      await act(async () => {
        summary = await result.current.generateMemberSummary(1, 'テストユーザー');
      });

      expect(summary).not.toBeNull();
      expect(summary.comment).toBe('テストコメント');
      expect(summary.task_classification.labels).toEqual(['家事', '勉強']);
      expect(summary.tokens_used).toBe(1000);
    });

    it('refreshでレポートを再取得する', async () => {
      const mockAvailableMonths = [
        { year: '2025', month: '01', label: '2025年1月' },
      ];

      const mockReport = {
        month_label: '2025年1月',
        year_month: '2025-01',
        group_name: 'テストグループ',
        summary: {
          total_completed: 25,
          total_incomplete: 5,
          total_reward: 5000,
          normal_tasks_count: 15,
          group_tasks_count: 10,
        },
        member_stats: [],
        trend_data: { labels: [], normal_tasks: [], group_tasks: [] },
        has_subscription: true,
        can_access: true,
      };

      mockedPerformanceService.getAvailableMonths.mockResolvedValue(mockAvailableMonths);
      mockedPerformanceService.getMonthlyReport.mockResolvedValue(mockReport);

      const { result } = renderHook(() => useMonthlyReport(), { wrapper });

      await waitFor(() => {
        expect(result.current.report).not.toBeNull();
      });

      const callCountBefore = mockedPerformanceService.getMonthlyReport.mock.calls.length;

      act(() => {
        result.current.refresh();
      });

      await waitFor(() => {
        expect(mockedPerformanceService.getMonthlyReport).toHaveBeenCalledTimes(callCountBefore + 1);
      });
    });
  });

  describe('異常系', () => {
    it('APIエラー時にエラーメッセージを設定する', async () => {
      mockedPerformanceService.getAvailableMonths.mockResolvedValue([
        { year: '2025', month: '01', label: '2025年1月' },
      ]);
      mockedPerformanceService.getMonthlyReport.mockRejectedValue({
        response: {
          data: {
            message: 'レポート取得エラー',
          },
        },
      });

      const { result } = renderHook(() => useMonthlyReport(), { wrapper });

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });

      expect(result.current.error).toBe('レポート取得エラー');
      expect(result.current.isLoading).toBe(false);
    });

    it('年月未選択時にメンバーサマリー生成でエラーをスローする', async () => {
      mockedPerformanceService.getAvailableMonths.mockResolvedValue([]);

      const { result } = renderHook(() => useMonthlyReport(), { wrapper });

      await waitFor(() => {
        expect(result.current.availableMonths).toHaveLength(0);
      });

      await expect(
        act(async () => {
          await result.current.generateMemberSummary(1, 'テストユーザー');
        })
      ).rejects.toThrow('年月が選択されていません');
    });

    it('グループID未取得時にメンバーサマリー生成でエラーをスローする', async () => {
      // グループIDなしのユーザーをモック
      mockedAuthService.getUser.mockResolvedValue({
        ...mockUser,
        group_id: undefined as any,
      });

      mockedPerformanceService.getAvailableMonths.mockResolvedValue([
        { year: '2025', month: '01', label: '2025年1月' },
      ]);
      mockedPerformanceService.getMonthlyReport.mockResolvedValue({
        month_label: '2025年1月',
        year_month: '2025-01',
        group_name: 'テストグループ',
        summary: {
          total_completed: 0,
          total_incomplete: 0,
          total_reward: 0,
          normal_tasks_count: 0,
          group_tasks_count: 0,
        },
        member_stats: [],
        trend_data: { labels: [], normal_tasks: [], group_tasks: [] },
        has_subscription: true,
        can_access: true,
      });

      // group_idがnullのuserをモック
      useAuth.mockReturnValue({
        user: { ...mockUser, group_id: null },
        isAuthenticated: true,
        isLoading: false,
      });

      const { result } = renderHook(() => useMonthlyReport(), { wrapper });

      await waitFor(() => {
        expect(result.current.selectedYear).toBe('2025');
      });

      await expect(
        act(async () => {
          await result.current.generateMemberSummary(1, 'テストユーザー');
        })
      ).rejects.toThrow('グループIDが取得できません');
    });

    it('不正なレスポンス構造でエラーをスローする', async () => {
      mockedPerformanceService.getAvailableMonths.mockResolvedValue([
        { year: '2025', month: '01', label: '2025年1月' },
      ]);
      mockedPerformanceService.getMonthlyReport.mockResolvedValue({
        month_label: '2025年1月',
        year_month: '2025-01',
        group_name: 'テストグループ',
        summary: {
          total_completed: 0,
          total_incomplete: 0,
          total_reward: 0,
          normal_tasks_count: 0,
          group_tasks_count: 0,
        },
        member_stats: [],
        trend_data: { labels: [], normal_tasks: [], group_tasks: [] },
        has_subscription: true,
        can_access: true,
      });

      // 不正なレスポンス（commentが欠落）
      mockedPerformanceService.generateMemberSummary.mockResolvedValue({
        user_id: 1,
        user_name: 'テストユーザー',
        year_month: '2025-01',
        comment: null as any, // 不正
        task_classification: null as any, // 不正
        reward_trend: null as any, // 不正
        tokens_used: 1000,
        generated_at: '2025-01-15T00:00:00.000Z',
      });

      const { result } = renderHook(() => useMonthlyReport(), { wrapper });

      await waitFor(() => {
        expect(result.current.selectedYear).toBe('2025');
      });

      await expect(
        act(async () => {
          await result.current.generateMemberSummary(1, 'テストユーザー');
        })
      ).rejects.toThrow('サマリーの生成に失敗しました');
    });
  });
});
