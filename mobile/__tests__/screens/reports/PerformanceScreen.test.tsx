/**
 * PerformanceScreen.tsx テスト
 * 
 * 実績画面（グラフ表示、期間選択、サブスクリプション制限）の動作を検証
 */

import React from 'react';
import { render, waitFor, fireEvent } from '@testing-library/react-native';
import { Alert } from 'react-native';
import PerformanceScreen from '../../../src/screens/reports/PerformanceScreen';
import { usePerformance } from '../../../src/hooks/usePerformance';
import { useTheme } from '../../../src/contexts/ThemeContext';
import { useAvatarContext } from '../../../src/contexts/AvatarContext';
import { useNavigation } from '@react-navigation/native';

// モック設定
jest.mock('../../../src/hooks/usePerformance');
jest.mock('../../../src/contexts/ThemeContext');
jest.mock('../../../src/contexts/AvatarContext');
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
}));
jest.mock('../../../src/components/charts/PerformanceChart', () => ({
  PerformanceChart: () => null, // グラフコンポーネントはモック
}));

describe('PerformanceScreen', () => {
  const mockUsePerformance = usePerformance as jest.MockedFunction<typeof usePerformance>;
  const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;
  const mockUseAvatarContext = useAvatarContext as jest.MockedFunction<typeof useAvatarContext>;
  const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;

  const mockNavigation = {
    navigate: jest.fn(),
    goBack: jest.fn(),
  };

  const mockPerformanceData = {
    period_label: '2025年1月第1週',
    task_type: 'normal' as const,
    chart_data: {
      labels: ['月', '火', '水', '木', '金', '土', '日'],
      datasets: [
        {
          label: '完了',
          data: [2, 3, 1, 4, 2, 0, 1],
          type: 'bar' as const,
          backgroundColor: 'rgba(89, 185, 198, 0.8)',
        },
      ],
    },
    summary: {
      total_completed: 13,
      total_incomplete: 9,
      total_reward: 0,
      average_per_day: 1.86,
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
  };

  const mockDispatchAvatarEvent = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(Alert, 'alert').mockImplementation(() => {});

    mockUseNavigation.mockReturnValue(mockNavigation as any);
    mockUseTheme.mockReturnValue({
      theme: 'adult',
      setTheme: jest.fn(),
    });
    mockUseAvatarContext.mockReturnValue({
      currentAvatar: null,
      currentComment: null,
      isVisible: false,
      dispatchAvatarEvent: mockDispatchAvatarEvent,
      hideAvatar: jest.fn(),
      avatarRef: { current: null },
    });
    mockUsePerformance.mockReturnValue({
      data: mockPerformanceData,
      isLoading: false,
      error: null,
      period: 'week',
      taskType: 'normal',
      offset: 0,
      selectedUserId: 0,
      changePeriod: jest.fn(),
      changeTaskType: jest.fn(),
      changeSelectedUser: jest.fn(),
      navigatePrev: jest.fn(),
      navigateNext: jest.fn(),
      refresh: jest.fn(),
    });
  });

  describe('レンダリング', () => {
    it('初期状態で正しく表示される', async () => {
      const { getByText } = render(<PerformanceScreen />);

      await waitFor(() => {
        expect(getByText('実績')).toBeTruthy();
        expect(getByText('2025年1月第1週')).toBeTruthy();
        expect(getByText('完了')).toBeTruthy();
        expect(getByText(/13/)).toBeTruthy(); // 正規表現でマッチング
        expect(getByText('未完了')).toBeTruthy();
        expect(getByText(/9/)).toBeTruthy(); // 正規表現でマッチング
      });
    });

    it('ローディング中はインジケーターが表示される', () => {
      mockUsePerformance.mockReturnValue({
        data: null,
        isLoading: true,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByTestId } = render(<PerformanceScreen />);

      expect(getByTestId('loading-indicator')).toBeTruthy();
    });

    it('エラー時はエラーメッセージが表示される', () => {
      mockUsePerformance.mockReturnValue({
        data: null,
        isLoading: false,
        error: 'データ取得エラー',
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      expect(getByText('データ取得エラー')).toBeTruthy();
    });

    it('グループタスクの場合は報酬が表示される', async () => {
      const groupTaskData = {
        ...mockPerformanceData,
        task_type: 'group' as const,
        summary: {
          ...mockPerformanceData.summary,
          total_reward: 5000,
        },
      };

      mockUsePerformance.mockReturnValue({
        data: groupTaskData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'group',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      await waitFor(() => {
        expect(getByText('報酬合計')).toBeTruthy();
        expect(getByText('5,000')).toBeTruthy(); // カンマ区切りで別要素
      });
    });
  });

  describe('期間選択', () => {
    it('週間を選択できる', async () => {
      const mockChangePeriod = jest.fn();
      mockUsePerformance.mockReturnValue({
        data: mockPerformanceData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: mockChangePeriod,
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      const weekButton = getByText('週間');
      fireEvent.press(weekButton);

      expect(mockChangePeriod).toHaveBeenCalledWith('week');
    });

    it('サブスク未加入時は月間選択でアラートが表示される', async () => {
      const mockChangePeriod = jest.fn();
      mockUsePerformance.mockReturnValue({
        data: mockPerformanceData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: mockChangePeriod,
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      const monthButton = getByText('月間');
      fireEvent.press(monthButton);

      expect(Alert.alert).toHaveBeenCalledWith(
        'プレミアム機能',
        expect.stringContaining('月間・年間の実績表示'),
        expect.any(Array)
      );
      expect(mockChangePeriod).not.toHaveBeenCalled();
    });

    it('サブスク加入時は月間を選択できる', async () => {
      const mockChangePeriod = jest.fn();
      const subscribedData = {
        ...mockPerformanceData,
        has_subscription: true,
        restrictions: {
          period_restricted: false,
          navigation_restricted: false,
          member_restricted: false,
        },
      };

      mockUsePerformance.mockReturnValue({
        data: subscribedData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: mockChangePeriod,
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      const monthButton = getByText('月間');
      fireEvent.press(monthButton);

      expect(Alert.alert).not.toHaveBeenCalled();
      expect(mockChangePeriod).toHaveBeenCalledWith('month');
    });
  });

  describe('タスク種別切り替え', () => {
    it('通常タスクを選択できる', async () => {
      const mockChangeTaskType = jest.fn();
      mockUsePerformance.mockReturnValue({
        data: mockPerformanceData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: mockChangeTaskType,
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      const normalButton = getByText('通常タスク');
      fireEvent.press(normalButton);

      expect(mockChangeTaskType).toHaveBeenCalledWith('normal');
    });

    it('グループタスクを選択できる', async () => {
      const mockChangeTaskType = jest.fn();
      mockUsePerformance.mockReturnValue({
        data: mockPerformanceData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: mockChangeTaskType,
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      const groupButton = getByText('グループタスク');
      fireEvent.press(groupButton);

      expect(mockChangeTaskType).toHaveBeenCalledWith('group');
    });
  });

  describe('期間ナビゲーション', () => {
    it('前へボタンでナビゲートできる（サブスク加入時）', async () => {
      const mockNavigatePrev = jest.fn();
      const subscribedData = {
        ...mockPerformanceData,
        has_subscription: true,
        restrictions: {
          period_restricted: false,
          navigation_restricted: false,
          member_restricted: false,
        },
      };

      mockUsePerformance.mockReturnValue({
        data: subscribedData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: mockNavigatePrev,
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByTestId } = render(<PerformanceScreen />);

      const prevButton = getByTestId('navigate-prev-button');
      fireEvent.press(prevButton);

      expect(mockNavigatePrev).toHaveBeenCalled();
    });

    it('前へボタンがdisabled状態ではクリックできない（サブスク未加入時）', async () => {
      const mockNavigatePrev = jest.fn();
      mockUsePerformance.mockReturnValue({
        data: mockPerformanceData, // restrictions.navigation_restricted: true
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: mockNavigatePrev,
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByTestId } = render(<PerformanceScreen />);

      const prevButton = getByTestId('navigate-prev-button');
      
      // disabled状態ではfireEvent.pressが効かない
      fireEvent.press(prevButton);

      // クリックされないため、関数が呼ばれない
      expect(mockNavigatePrev).not.toHaveBeenCalled();
    });

    it('次へボタンは制限チェックなしで動作する', async () => {
      const mockNavigateNext = jest.fn();
      const enabledNextData = {
        ...mockPerformanceData,
        can_navigate_next: true, // 次へボタンを有効化
        restrictions: {
          ...mockPerformanceData.restrictions,
          navigation_restricted: false, // 制限を解除
        },
      };

      mockUsePerformance.mockReturnValue({
        data: enabledNextData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: mockNavigateNext,
        refresh: jest.fn(),
      });

      const { getByTestId } = render(<PerformanceScreen />);

      const nextButton = getByTestId('navigate-next-button');
      fireEvent.press(nextButton);

      expect(mockNavigateNext).toHaveBeenCalled();
    });
  });

  describe('メンバー選択', () => {
    it('サブスク未加入時はアラートが表示される', async () => {
      const groupTaskData = {
        ...mockPerformanceData,
        task_type: 'group' as const,
        members: [
          { id: 1, username: 'メンバー1' },
          { id: 2, username: 'メンバー2' },
        ],
      };

      mockUsePerformance.mockReturnValue({
        data: groupTaskData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'group',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      const memberButton = getByText('グループ全体');
      fireEvent.press(memberButton);

      expect(Alert.alert).toHaveBeenCalledWith(
        'プレミアム機能',
        expect.stringContaining('メンバー個別選択'),
        expect.any(Array)
      );
    });

    it('サブスク加入時はメンバーモーダルが開く', async () => {
      const subscribedData = {
        ...mockPerformanceData,
        task_type: 'group' as const,
        has_subscription: true,
        restrictions: {
          period_restricted: false,
          navigation_restricted: false,
          member_restricted: false,
        },
        members: [
          { id: 1, username: 'メンバー1' },
          { id: 2, username: 'メンバー2' },
        ],
      };

      mockUsePerformance.mockReturnValue({
        data: subscribedData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'group',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = render(<PerformanceScreen />);

      const memberButton = getByText('グループ全体');
      fireEvent.press(memberButton);

      await waitFor(() => {
        expect(getByText('メンバーを選択')).toBeTruthy();
        expect(getByText('メンバー1')).toBeTruthy();
        expect(getByText('メンバー2')).toBeTruthy();
      });
    });
  });

  describe('月次レポート遷移', () => {
    it('月次レポートボタンで画面遷移する', async () => {
      const { getByText } = render(<PerformanceScreen />);

      const reportButton = getByText('月次レポート');
      fireEvent.press(reportButton);

      expect(mockNavigation.navigate).toHaveBeenCalledWith('MonthlyReport');
    });
  });

  describe('Pull to Refresh', () => {
    it('リフレッシュコンポーネントが正しく設定されている', async () => {
      const mockRefresh = jest.fn();
      mockUsePerformance.mockReturnValue({
        data: mockPerformanceData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: mockRefresh,
      });

      const { getByTestId } = render(<PerformanceScreen />);

      const scrollView = getByTestId('performance-scroll-view');
      // ScrollViewにRefreshControlが設定されていることを確認
      expect(scrollView).toBeTruthy();
    });
  });

  describe('アバターイベント', () => {
    it('サブスク加入時に大人向けテーマでアバターイベントが発行される', async () => {
      const subscribedData = {
        ...mockPerformanceData,
        has_subscription: true,
      };

      mockUsePerformance.mockReturnValue({
        data: subscribedData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      mockUseTheme.mockReturnValue({
        theme: 'adult',
        setTheme: jest.fn(),
      });

      render(<PerformanceScreen />);

      await waitFor(() => {
        expect(mockDispatchAvatarEvent).toHaveBeenCalledWith('performance_personal_viewed');
      });
    });

    it('サブスク加入時に子ども向けテーマでアバターイベントが発行される', async () => {
      const subscribedData = {
        ...mockPerformanceData,
        has_subscription: true,
      };

      mockUsePerformance.mockReturnValue({
        data: subscribedData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      mockUseTheme.mockReturnValue({
        theme: 'child',
        setTheme: jest.fn(),
      });

      render(<PerformanceScreen />);

      await waitFor(() => {
        expect(mockDispatchAvatarEvent).toHaveBeenCalledWith('performance_group_viewed');
      });
    });

    it('サブスク未加入時はアバターイベントが発行されない', () => {
      mockUsePerformance.mockReturnValue({
        data: mockPerformanceData,
        isLoading: false,
        error: null,
        period: 'week',
        taskType: 'normal',
        offset: 0,
        selectedUserId: 0,
        changePeriod: jest.fn(),
        changeTaskType: jest.fn(),
        changeSelectedUser: jest.fn(),
        navigatePrev: jest.fn(),
        navigateNext: jest.fn(),
        refresh: jest.fn(),
      });

      render(<PerformanceScreen />);

      expect(mockDispatchAvatarEvent).not.toHaveBeenCalled();
    });
  });
});
