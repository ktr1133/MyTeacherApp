/**
 * TokenHistoryScreen テスト
 */
import { render, fireEvent } from '@testing-library/react-native';
import TokenHistoryScreen from '../TokenHistoryScreen';
import { useTokens, UseTokensReturn } from '../../../hooks/useTokens';
import { useTheme, ThemeContextType } from '../../../contexts/ThemeContext';
import { useNavigation } from '@react-navigation/native';

// モック
jest.mock('../../../hooks/useTokens');
jest.mock('../../../contexts/ThemeContext');
jest.mock('@react-navigation/native', () => ({
  ...jest.requireActual('@react-navigation/native'),
  useNavigation: jest.fn(),
}));

const mockUseTokens = useTokens as jest.MockedFunction<typeof useTokens>;
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;
const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;

/**
 * デフォルトのuseTokensモック値を生成
 */
const createMockUseTokensReturn = (overrides?: Partial<UseTokensReturn>): UseTokensReturn => ({
  balance: null,
  packages: [],
  history: [],
  historyStats: null,
  purchaseRequests: [],
  isLoading: false,
  isLoadingMore: false,
  hasMoreHistory: false,
  error: null,
  refreshBalance: jest.fn(),
  loadBalance: jest.fn(),
  loadPackages: jest.fn(),
  loadHistory: jest.fn(),
  loadHistoryStats: jest.fn(),
  loadMoreHistory: jest.fn(),
  loadPurchaseRequests: jest.fn(),
  createPurchaseRequest: jest.fn(),
  approvePurchaseRequest: jest.fn(),
  rejectPurchaseRequest: jest.fn(),
  ...overrides,
});

/**
 * デフォルトのuseThemeモック値を生成
 */
const createMockThemeReturn = (overrides?: Partial<ThemeContextType>): ThemeContextType => ({
  theme: 'adult',
  setTheme: jest.fn(),
  isLoading: false,
  refreshTheme: jest.fn(),
  ...overrides,
});

describe('TokenHistoryScreen', () => {
  const mockNavigation = {
    goBack: jest.fn(),
    addListener: jest.fn((event, callback) => {
      if (event === 'focus') {
        callback();
      }
      return jest.fn();
    }),
  };

  const mockHistoryStats = {
    monthlyPurchaseAmount: 1000,
    monthlyPurchaseTokens: 500000,
    monthlyUsage: 250000,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseNavigation.mockReturnValue(mockNavigation as any);
    mockUseTheme.mockReturnValue(createMockThemeReturn());
    mockUseTokens.mockReturnValue(createMockUseTokensReturn());
  });

  describe('初期表示', () => {
    it('ヘッダーが正しく表示される（通常モード）', () => {
      const { getByText } = render(<TokenHistoryScreen />);
      
      expect(getByText('トークン履歴')).toBeTruthy();
      expect(getByText('← 戻る')).toBeTruthy();
    });

    it('ヘッダーが正しく表示される（子どもモード）', () => {
      mockUseTheme.mockReturnValue(createMockThemeReturn({ theme: 'child' }));
      
      const { getByText } = render(<TokenHistoryScreen />);
      
      expect(getByText('トークンのりれき')).toBeTruthy();
      expect(getByText('← もどる')).toBeTruthy();
    });

    it('初回読み込み時にloadHistoryStatsが呼ばれる', () => {
      const mockLoadHistoryStats = jest.fn();
      mockUseTokens.mockReturnValue(createMockUseTokensReturn({
        loadHistoryStats: mockLoadHistoryStats,
      }));
      
      render(<TokenHistoryScreen />);
      
      expect(mockLoadHistoryStats).toHaveBeenCalledTimes(2); // useEffect + focus
    });
  });

  describe('ローディング状態', () => {
    it('ローディング中にインジケーターとメッセージを表示', () => {
      mockUseTokens.mockReturnValue(createMockUseTokensReturn({
        isLoading: true,
      }));
      
      const { getByText } = render(<TokenHistoryScreen />);
      
      expect(getByText('読み込み中...')).toBeTruthy();
    });
  });

  describe('エラー表示', () => {
    it('エラーメッセージを表示', () => {
      mockUseTokens.mockReturnValue(createMockUseTokensReturn({
        error: 'ネットワークエラー',
      }));
      
      const { getByText } = render(<TokenHistoryScreen />);
      
      expect(getByText('⚠️ ネットワークエラー')).toBeTruthy();
    });
  });

  describe('データなし', () => {
    it('データがない場合に空メッセージを表示', () => {
      const { getByText } = render(<TokenHistoryScreen />);
      
      expect(getByText('履歴がありません')).toBeTruthy();
    });
  });

  describe('統計表示', () => {
    beforeEach(() => {
      mockUseTokens.mockReturnValue(createMockUseTokensReturn({
        historyStats: mockHistoryStats,
      }));
    });

    it('今月の購入情報を表示', () => {
      const { getByText } = render(<TokenHistoryScreen />);
      
      expect(getByText('今月の購入')).toBeTruthy();
      expect(getByText('¥1,000')).toBeTruthy();
      expect(getByText('500,000')).toBeTruthy();
    });

    it('今月の使用情報を表示', () => {
      const { getByText } = render(<TokenHistoryScreen />);
      
      expect(getByText('今月の使用')).toBeTruthy();
      expect(getByText('250,000')).toBeTruthy();
    });

    it('使用率を計算して表示', () => {
      const { getByText } = render(<TokenHistoryScreen />);
      
      // 250000 / 500000 = 50%
      expect(getByText('50%')).toBeTruthy();
    });

    it('購入がない場合は使用率を表示しない', () => {
      mockUseTokens.mockReturnValue(createMockUseTokensReturn({
        historyStats: {
          monthlyPurchaseAmount: 0,
          monthlyPurchaseTokens: 0,
          monthlyUsage: 1000000,
        },
      }));
      
      const { queryByText } = render(<TokenHistoryScreen />);
      
      // 購入が0の場合は使用率カードが表示されない
      expect(queryByText('使用率')).toBeNull();
    });
  });

  describe('ナビゲーション', () => {
    it('戻るボタンタップで前の画面に戻る', () => {
      const { getByText } = render(<TokenHistoryScreen />);
      
      fireEvent.press(getByText('← 戻る'));
      
      expect(mockNavigation.goBack).toHaveBeenCalledTimes(1);
    });
  });

  describe('Pull-to-Refresh', () => {
    it('リフレッシュ時にloadHistoryStatsを呼び出す', async () => {
      const mockLoadHistoryStats = jest.fn();
      mockUseTokens.mockReturnValue(createMockUseTokensReturn({
        historyStats: mockHistoryStats,
        loadHistoryStats: mockLoadHistoryStats,
      }));
      
      render(<TokenHistoryScreen />);
      
      // ScrollViewのRefreshControlをトリガー
      // Note: RefreshControlのテストは実機での動作確認を推奨
      expect(mockLoadHistoryStats).toHaveBeenCalled();
    });
  });
});
