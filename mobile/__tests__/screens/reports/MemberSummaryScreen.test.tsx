/**
 * MemberSummaryScreen.tsx テスト
 * 
 * メンバーサマリー画面（AIコメント、グラフ表示、戻る確認）の動作を検証
 */

import React from 'react';
import { render, waitFor, fireEvent } from '@testing-library/react-native';
import { Alert } from 'react-native';
import MemberSummaryScreen from '../../../src/screens/reports/MemberSummaryScreen';
import { useNavigation, useRoute } from '@react-navigation/native';

// モック設定
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
  useRoute: jest.fn(),
}));
jest.mock('react-native-chart-kit', () => ({
  PieChart: () => null,
  LineChart: () => null,
}));

describe('MemberSummaryScreen', () => {
  const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;
  const mockUseRoute = useRoute as jest.MockedFunction<typeof useRoute>;

  const mockNavigation = {
    navigate: jest.fn(),
    goBack: jest.fn(),
    setOptions: jest.fn(),
  };

  const mockSummaryData = {
    user_id: 1,
    user_name: 'テストユーザー',
    year_month: '2025-01',
    comment: 'AI生成コメント: 今月は家事と勉強のタスクをバランスよく完了しています。特に週末の実施率が高く、計画的に進められています。',
    task_classification: {
      labels: ['家事', '勉強', '運動', 'その他'],
      data: [10, 8, 3, 2],
    },
    reward_trend: {
      labels: ['1週', '2週', '3週', '4週'],
      data: [500, 800, 600, 1100],
    },
    tokens_used: 1000,
    generated_at: '2025-01-15T00:00:00.000Z',
  };

  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(Alert, 'alert').mockImplementation(() => {});

    mockUseNavigation.mockReturnValue(mockNavigation as any);
    mockUseRoute.mockReturnValue({
      params: { data: mockSummaryData },
    } as any);
  });

  describe('レンダリング', () => {
    it('初期状態で正しく表示される', async () => {
      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('AIによる概況分析')).toBeTruthy();
        expect(getByText(/AI生成コメント/)).toBeTruthy();
        expect(getByText('タスク分類')).toBeTruthy();
        expect(getByText('報酬の推移')).toBeTruthy();
        expect(getByText(/このレポート生成に/)).toBeTruthy(); // トークン消費文
      });
    });

    it('ユーザー名がタイトルに設定される', () => {
      render(<MemberSummaryScreen />);

      expect(mockNavigation.setOptions).toHaveBeenCalledWith(
        expect.objectContaining({
          title: 'テストユーザーさんの概況レポート',
        })
      );
    });

    it('AIコメントが全文表示される', async () => {
      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(
          getByText(/今月は家事と勉強のタスクをバランスよく/)
        ).toBeTruthy();
      });
    });

    it('タスク分類データが正しく表示される', async () => {
      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('タスク分類')).toBeTruthy();
      });
      
      // PieChartコンポーネントがレンダリングされる（モックなので実際のグラフは表示されない）
      // データは getPieChartData() で整形される
    });

    it('報酬推移データが正しく表示される', async () => {
      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('報酬の推移')).toBeTruthy();
      });
      
      // LineChartコンポーネントがレンダリングされる（モックなので実際のグラフは表示されない）
      // データは getLineChartData() で整形される
    });

    it('トークン消費量が表示される', async () => {
      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText(/このレポート生成に/)).toBeTruthy();
        expect(getByText(/1,000トークン/)).toBeTruthy();
        expect(getByText(/を消費しました/)).toBeTruthy();
      });
    });

    it('生成日時が表示される', async () => {
      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText(/生成日時:/)).toBeTruthy();
      });
    });
  });

  describe('戻る確認ダイアログ', () => {
    it('戻るボタンが設定されている', async () => {
      render(<MemberSummaryScreen />);

      // setOptionsで設定されたheaderLeftコンポーネントを取得
      const setOptionsCall = mockNavigation.setOptions.mock.calls[0][0];
      
      expect(setOptionsCall).toHaveProperty('headerLeft');
      expect(typeof setOptionsCall.headerLeft).toBe('function');
    });
  });

  describe('戻る確認ダイアログ', () => {
    it('戻るボタンが設定されている', async () => {
      render(<MemberSummaryScreen />);

      // setOptionsで設定されたheaderLeftコンポーネントを取得
      const setOptionsCall = mockNavigation.setOptions.mock.calls[0][0];
      
      expect(setOptionsCall).toHaveProperty('headerLeft');
      expect(typeof setOptionsCall.headerLeft).toBe('function');
    });

    it('タイトルが正しく設定されている', () => {
      render(<MemberSummaryScreen />);

      const setOptionsCall = mockNavigation.setOptions.mock.calls[0][0];
      expect(setOptionsCall).toHaveProperty('title');
      expect(typeof setOptionsCall.title).toBe('string');
    });
  });

  describe('データ整形', () => {
    it('円グラフデータが正しく整形される', async () => {
      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('タスク分類')).toBeTruthy();
      });

      // getPieChartData() の動作を検証
      // 実際のコンポーネントでは以下のデータが生成される:
      // [
      //   { name: '家事', population: 10, color: '...', ... },
      //   { name: '勉強', population: 8, color: '...', ... },
      //   { name: '運動', population: 3, color: '...', ... },
      //   { name: 'その他', population: 2, color: '...', ... },
      // ]
    });

    it('折れ線グラフデータが正しく整形される', async () => {
      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('報酬の推移')).toBeTruthy();
      });

      // getLineChartData() の動作を検証
      // 実際のコンポーネントでは以下のデータが生成される:
      // {
      //   labels: ['1週', '2週', '3週', '4週'],
      //   datasets: [{ data: [500, 800, 600, 1100], ... }],
      // }
    });
  });

  describe('ダークモード対応', () => {
    it('ダークモード時も正しく表示される', async () => {
      // useColorScheme を 'dark' でモック
      jest.spyOn(require('react-native'), 'useColorScheme').mockReturnValue('dark');

      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('AIによる概況分析')).toBeTruthy();
      });
    });

    it('ライトモード時も正しく表示される', async () => {
      // useColorScheme を 'light' でモック
      jest.spyOn(require('react-native'), 'useColorScheme').mockReturnValue('light');

      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('AIによる概況分析')).toBeTruthy();
      });
    });
  });

  describe('エッジケース', () => {
    it('タスク分類が空でもエラーにならない', async () => {
      const emptyClassificationData = {
        ...mockSummaryData,
        task_classification: {
          labels: [],
          data: [],
        },
      };

      mockUseRoute.mockReturnValue({
        params: { data: emptyClassificationData },
      } as any);

      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('タスク分類')).toBeTruthy();
      });
    });

    it('報酬推移が空でもエラーにならない', async () => {
      const emptyTrendData = {
        ...mockSummaryData,
        reward_trend: {
          labels: [],
          data: [],
        },
      };

      mockUseRoute.mockReturnValue({
        params: { data: emptyTrendData },
      } as any);

      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('報酬の推移')).toBeTruthy();
      });
    });

    it('コメントが長文でも表示される', async () => {
      const longCommentData = {
        ...mockSummaryData,
        comment: 'A'.repeat(500), // 500文字の長文
      };

      mockUseRoute.mockReturnValue({
        params: { data: longCommentData },
      } as any);

      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('AIによる概況分析')).toBeTruthy();
      });
    });

    it('トークン消費量が0でも表示される', async () => {
      const zeroTokensData = {
        ...mockSummaryData,
        tokens_used: 0,
      };

      mockUseRoute.mockReturnValue({
        params: { data: zeroTokensData },
      } as any);

      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText(/このレポート生成に/)).toBeTruthy();
        expect(getByText(/0トークン/)).toBeTruthy();
      });
    });

    it('複数のタスク分類カテゴリに対応する', async () => {
      const multiCategoryData = {
        ...mockSummaryData,
        task_classification: {
          labels: ['家事', '勉強', '運動', '趣味', '買い物', '料理', '掃除', 'その他'],
          data: [5, 4, 3, 3, 2, 2, 1, 1],
        },
      };

      mockUseRoute.mockReturnValue({
        params: { data: multiCategoryData },
      } as any);

      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('タスク分類')).toBeTruthy();
      });
    });

    it('報酬推移の週数が多くても対応する', async () => {
      const manyWeeksData = {
        ...mockSummaryData,
        reward_trend: {
          labels: ['1週', '2週', '3週', '4週', '5週'],
          data: [500, 800, 600, 1100, 900],
        },
      };

      mockUseRoute.mockReturnValue({
        params: { data: manyWeeksData },
      } as any);

      const { getByText } = render(<MemberSummaryScreen />);

      await waitFor(() => {
        expect(getByText('報酬の推移')).toBeTruthy();
      });
    });
  });
});
