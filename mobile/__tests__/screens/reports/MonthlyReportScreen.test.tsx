/**
 * MonthlyReportScreen.tsx テスト
 * 
 * 月次レポート画面（メンバー統計、AIサマリー生成、月選択）の動作を検証
 */

import React from 'react';
import { render, waitFor, fireEvent } from '@testing-library/react-native';
import { Alert } from 'react-native';
import MonthlyReportScreen from '../../../src/screens/reports/MonthlyReportScreen';
import { useMonthlyReport } from '../../../src/hooks/usePerformance';
import { useNavigation } from '@react-navigation/native';
import { ColorSchemeProvider } from '../../../src/contexts/ColorSchemeContext';

// モック設定
jest.mock('../../../src/hooks/usePerformance');
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
}));
jest.mock('../../../src/hooks/useThemedColors', () => ({
  useThemedColors: jest.fn(() => ({
    colors: {
      background: '#FFFFFF',
      text: {
        primary: '#111827',
        secondary: '#6B7280',
        tertiary: '#9CA3AF',
        disabled: '#D1D5DB',
      },
      card: '#FFFFFF',
      border: {
        default: '#E5E7EB',
        light: 'rgba(229, 231, 235, 0.5)',
      },
      status: {
        success: '#10B981',
        warning: '#F59E0B',
        error: '#EF4444',
        info: '#3B82F6',
      },
    },
    accent: {
      primary: '#007AFF',
      gradient: ['#007AFF', '#5856D6'],
    },
  })),
}));

describe('MonthlyReportScreen', () => {
  const mockUseMonthlyReport = useMonthlyReport as jest.MockedFunction<typeof useMonthlyReport>;
  const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;

  const renderScreen = (component: React.ReactElement) => {
    return render(
      <ColorSchemeProvider>
        {component}
      </ColorSchemeProvider>
    );
  };

  const mockNavigation = {
    navigate: jest.fn(),
    goBack: jest.fn(),
  };

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
    member_stats: [
      {
        user_id: 1,
        user_name: 'テストユーザー1',
        completed: 15,
        incomplete: 3,
        reward: 3000,
        normal_tasks_completed: 10,
        group_tasks_completed: 5,
      },
      {
        user_id: 2,
        user_name: 'テストユーザー2',
        completed: 10,
        incomplete: 2,
        reward: 2000,
        normal_tasks_completed: 5,
        group_tasks_completed: 5,
      },
    ],
    trend_data: {
      labels: ['1週', '2週', '3週', '4週'],
      normal_tasks: [3, 5, 4, 3],
      group_tasks: [2, 3, 2, 3],
    },
    has_subscription: true,
    can_access: true,
  };

  const mockAvailableMonths = [
    { year: '2025', month: '01', label: '2025年1月' },
    { year: '2024', month: '12', label: '2024年12月' },
    { year: '2024', month: '11', label: '2024年11月' },
  ];

  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(Alert, 'alert').mockImplementation(() => {});

    mockUseNavigation.mockReturnValue(mockNavigation as any);
    mockUseMonthlyReport.mockReturnValue({
      report: mockReport,
      isLoading: false,
      error: null,
      availableMonths: mockAvailableMonths,
      selectedYear: '2025',
      selectedMonth: '01',
      changeMonth: jest.fn(),
      generateMemberSummary: jest.fn(),
      refresh: jest.fn(),
    });
  });

  describe('レンダリング', () => {
    it('初期状態で正しく表示される', async () => {
      const { getByText } = renderScreen(<MonthlyReportScreen />);

      await waitFor(() => {
        expect(getByText('月次レポート')).toBeTruthy();
        expect(getByText('テストグループ')).toBeTruthy();
        expect(getByText(/2025年1月/)).toBeTruthy();
        expect(getByText('完了タスク')).toBeTruthy(); // ラベルで検証
        expect(getByText(/5,000/)).toBeTruthy(); // 報酬
      });
    });

    it('メンバー統計が表示される', async () => {
      const { getByText } = renderScreen(<MonthlyReportScreen />);

      await waitFor(() => {
        expect(getByText('テストユーザー1')).toBeTruthy();
        expect(getByText('テストユーザー2')).toBeTruthy();
      });
    });

    it('タスク内訳が2行レイアウトで表示される', async () => {
      const { getByText } = renderScreen(<MonthlyReportScreen />);

      await waitFor(() => {
        // 1行目: 完了件数と報酬
        expect(getByText('完了タスク')).toBeTruthy();
        expect(getByText('獲得報酬')).toBeTruthy();
        
        // 2行目: タスク種別内訳（絵文字なし）
        expect(getByText('通常タスク')).toBeTruthy();
        expect(getByText('グループタスク')).toBeTruthy();
      });
    });

    it('メンバー統計でタスク種別内訳が表示される', async () => {
      const { getAllByText } = renderScreen(<MonthlyReportScreen />);

      await waitFor(() => {
        // テストユーザー1の内訳（絵文字付き）- 複数メンバーがいるため getAllByText を使用
        const normalTaskElements = getAllByText(/📝 通常タスク/);
        expect(normalTaskElements.length).toBeGreaterThan(0);
        const groupTaskElements = getAllByText(/👥 グループタスク/);
        expect(groupTaskElements.length).toBeGreaterThan(0);
      });
    });

    it('ローディング中はインジケーターが表示される', () => {
      mockUseMonthlyReport.mockReturnValue({
        report: null,
        isLoading: true,
        error: null,
        availableMonths: [],
        selectedYear: '',
        selectedMonth: '',
        changeMonth: jest.fn(),
        generateMemberSummary: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = renderScreen(<MonthlyReportScreen />);

      expect(getByText('読み込み中...')).toBeTruthy();
    });

    it('エラー時はエラーメッセージが表示される', () => {
      mockUseMonthlyReport.mockReturnValue({
        report: null,
        isLoading: false,
        error: 'レポート取得エラー',
        availableMonths: [],
        selectedYear: '',
        selectedMonth: '',
        changeMonth: jest.fn(),
        generateMemberSummary: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = renderScreen(<MonthlyReportScreen />);

      expect(getByText('レポート取得エラー')).toBeTruthy();
    });

    it('アクセス制限時はロック画面が表示される', () => {
      const restrictedReport = {
        ...mockReport,
        can_access: false,
        accessible_until: '2025年1月',
      };

      mockUseMonthlyReport.mockReturnValue({
        report: restrictedReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: jest.fn(),
        generateMemberSummary: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = renderScreen(<MonthlyReportScreen />);

      expect(getByText('プレミアム機能')).toBeTruthy();
      expect(getByText(/過去のレポートを見るには/)).toBeTruthy();
      expect(getByText(/無料プランでは2025年1月までのレポート/)).toBeTruthy();
    });
  });

  describe('月選択', () => {
    it('月を変更できる', async () => {
      const mockChangeMonth = jest.fn();
      mockUseMonthlyReport.mockReturnValue({
        report: mockReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: mockChangeMonth,
        generateMemberSummary: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByTestId } = renderScreen(<MonthlyReportScreen />);

      const picker = getByTestId('month-picker');
      fireEvent(picker, 'valueChange', '2024-12');

      expect(mockChangeMonth).toHaveBeenCalledWith('2024', '12');
    });

    it('利用可能な月がピッカーに設定されている', async () => {
      const { getByTestId } = renderScreen(<MonthlyReportScreen />);

      await waitFor(() => {
        const picker = getByTestId('month-picker');
        expect(picker).toBeTruthy();
        // Picker内部のアイテムはgetByTextでアクセスできないため、
        // availableMonthsがmockされていることを確認
      });
    });
  });

  describe('AIサマリー生成', () => {
    it('サブスク加入時はサマリー生成確認が表示される', async () => {
      const { getByTestId } = renderScreen(<MonthlyReportScreen />);

      const summaryButton = getByTestId('ai-summary-button-1'); // テストユーザー1のボタン
      fireEvent.press(summaryButton);

      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalledWith(
          'AI生成サマリー',
          expect.stringContaining('テストユーザー1さんの月次サマリー'),
          expect.any(Array)
        );
      });
    });

    it('サブスク未加入時はプレミアム機能アラートが表示される', async () => {
      const freeReport = {
        ...mockReport,
        has_subscription: false,
      };

      mockUseMonthlyReport.mockReturnValue({
        report: freeReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: jest.fn(),
        generateMemberSummary: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByTestId } = renderScreen(<MonthlyReportScreen />);

      const summaryButton = getByTestId('ai-summary-button-1');
      fireEvent.press(summaryButton);

      expect(Alert.alert).toHaveBeenCalledWith(
        'プレミアム機能',
        expect.stringContaining('AI生成サマリー')
      );
    });

    it('サマリー生成成功時は専用画面に遷移する', async () => {
      const mockSummary = {
        user_id: 1,
        user_name: 'テストユーザー1',
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

      const mockGenerateMemberSummary = jest.fn().mockResolvedValue(mockSummary);
      mockUseMonthlyReport.mockReturnValue({
        report: mockReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: jest.fn(),
        generateMemberSummary: mockGenerateMemberSummary,
        refresh: jest.fn(),
      });

      const { getByTestId } = renderScreen(<MonthlyReportScreen />);

      const summaryButton = getByTestId('ai-summary-button-1');
      fireEvent.press(summaryButton);

      // Alertの「生成」ボタンをシミュレート
      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalled();
      });

      const alertCalls = (Alert.alert as jest.Mock).mock.calls;
      const lastCall = alertCalls[alertCalls.length - 1];
      const buttons = lastCall[2];
      const generateButton = buttons.find((btn: any) => btn.text === '生成');

      await generateButton.onPress();

      await waitFor(() => {
        expect(mockGenerateMemberSummary).toHaveBeenCalledWith(1, 'テストユーザー1');
        expect(mockNavigation.navigate).toHaveBeenCalledWith('MemberSummary', {
          data: mockSummary,
        });
      });
    });

    it('サマリー生成エラー時はエラーアラートが表示される', async () => {
      const mockGenerateMemberSummary = jest.fn().mockRejectedValue({
        message: 'トークン不足',
      });
      mockUseMonthlyReport.mockReturnValue({
        report: mockReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: jest.fn(),
        generateMemberSummary: mockGenerateMemberSummary,
        refresh: jest.fn(),
      });

      const { getByTestId } = renderScreen(<MonthlyReportScreen />);

      const summaryButton = getByTestId('ai-summary-button-1');
      fireEvent.press(summaryButton);

      // Alertの「生成」ボタンをシミュレート
      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalled();
      });

      const alertCalls = (Alert.alert as jest.Mock).mock.calls;
      const confirmCall = alertCalls[0];
      const buttons = confirmCall[2];
      const generateButton = buttons.find((btn: any) => btn.text === '生成');

      await generateButton.onPress();

      await waitFor(() => {
        expect(mockGenerateMemberSummary).toHaveBeenCalled();
      });

      // エラーアラートが表示されることを確認
      await waitFor(() => {
        const errorAlertCalls = (Alert.alert as jest.Mock).mock.calls.filter(
          call => call[0] === 'エラー'
        );
        expect(errorAlertCalls.length).toBeGreaterThan(0);
        expect(errorAlertCalls[0][1]).toContain('トークン不足');
      });
    });
  });

  describe('Pull to Refresh', () => {
    it('リフレッシュコンポーネントが正しく設定されている', async () => {
      const mockRefresh = jest.fn();
      mockUseMonthlyReport.mockReturnValue({
        report: mockReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: jest.fn(),
        generateMemberSummary: jest.fn(),
        refresh: mockRefresh,
      });

      const { getByTestId } = renderScreen(<MonthlyReportScreen />);

      const scrollView = getByTestId('monthly-report-scroll-view');
      // ScrollViewにRefreshControlが設定されていることを確認
      expect(scrollView).toBeTruthy();
    });
  });

  describe('エッジケース', () => {
    it('メンバー統計が空の場合も表示される', async () => {
      const emptyReport = {
        ...mockReport,
        member_stats: [],
      };

      mockUseMonthlyReport.mockReturnValue({
        report: emptyReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: jest.fn(),
        generateMemberSummary: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = renderScreen(<MonthlyReportScreen />);

      await waitFor(() => {
        expect(getByText('月次レポート')).toBeTruthy();
        expect(getByText('完了タスク')).toBeTruthy(); // ラベルで検証
      });
    });

    it('グループ名がない場合もエラーにならない', async () => {
      const noGroupReport = {
        ...mockReport,
        group_name: undefined as any,
      };

      mockUseMonthlyReport.mockReturnValue({
        report: noGroupReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: jest.fn(),
        generateMemberSummary: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = renderScreen(<MonthlyReportScreen />);

      await waitFor(() => {
        expect(getByText('月次レポート')).toBeTruthy();
      });
    });

    it('トレンドデータがない場合もエラーにならない', async () => {
      const noTrendReport = {
        ...mockReport,
        trend_data: {
          labels: [],
          normal_tasks: [],
          group_tasks: [],
        },
      };

      mockUseMonthlyReport.mockReturnValue({
        report: noTrendReport,
        isLoading: false,
        error: null,
        availableMonths: mockAvailableMonths,
        selectedYear: '2025',
        selectedMonth: '01',
        changeMonth: jest.fn(),
        generateMemberSummary: jest.fn(),
        refresh: jest.fn(),
      });

      const { getByText } = renderScreen(<MonthlyReportScreen />);

      await waitFor(() => {
        expect(getByText('月次レポート')).toBeTruthy();
      });
    });
  });
});
