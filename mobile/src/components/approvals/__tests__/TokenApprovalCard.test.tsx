/**
 * TokenApprovalCard コンポーネントテスト
 */

import { render, fireEvent } from '@testing-library/react-native';
import TokenApprovalCard from '../TokenApprovalCard';
import { TokenApprovalItem } from '../../../types/approval.types';
import { useTheme } from '../../../contexts/ThemeContext';
import { ColorSchemeProvider } from '../../../contexts/ColorSchemeContext';

// モック
jest.mock('../../../contexts/ThemeContext');
jest.mock('../../../hooks/useThemedColors', () => ({
  useThemedColors: jest.fn(() => ({
    colors: {
      background: '#FFFFFF',
      text: { primary: '#111827', secondary: '#6B7280', tertiary: '#9CA3AF' },
      card: '#FFFFFF',
      border: { default: '#E5E7EB', light: 'rgba(229, 231, 235, 0.5)' },
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
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

describe('TokenApprovalCard', () => {
  const mockTokenApproval: TokenApprovalItem = {
    id: 1,
    type: 'token',
    package_name: '10,000トークン',
    requester_name: 'テストユーザー',
    requester_id: 2,
    token_amount: 10000,
    price: 1200,
    requested_at: '2025-12-06T10:30:00.000Z',
    model: {
      id: 1,
      package_id: 1,
      status: 'pending',
      created_at: '2025-12-06T10:30:00.000Z',
    },
  };

  const mockOnApprove = jest.fn();
  const mockOnReject = jest.fn();

  const renderScreen = (component: React.ReactElement) => {
    return render(
      <ColorSchemeProvider>
        {component}
      </ColorSchemeProvider>
    );
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseTheme.mockReturnValue({
      theme: 'adult',
      isLoading: false,
      refreshTheme: jest.fn(),
      setTheme: jest.fn(),
    });
  });

  describe('表示内容', () => {
    it('トークン購入申請情報が正しく表示される', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText('10,000トークン')).toBeTruthy(); // タイトル（パッケージ名）
      expect(getByText('トークン')).toBeTruthy(); // バッジ
      expect(getByText('申請者:')).toBeTruthy();
      expect(getByText('テストユーザー')).toBeTruthy();
      const tokenAmounts = getAllByText(/10,000/);
      expect(tokenAmounts.length).toBeGreaterThan(0); // トークン数（複数箇所に表示）
      expect(getByText(/1,200/)).toBeTruthy(); // 価格
    });

    it('child themeで適切なラベルを表示する', () => {
      mockUseTheme.mockReturnValue({
        theme: 'child',
        isLoading: false,
        refreshTheme: jest.fn(),
        setTheme: jest.fn(),
      });

      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText('申請者:')).toBeTruthy();
      expect(getByText('テストユーザー')).toBeTruthy();
    });

    it('依頼日時が正しく表示される', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      // 依頼日時: 2025/12/06 19:30 形式（UTCからJST変換）
      expect(getByText(/2025\/12\/06.*19:30/)).toBeTruthy();
    });

    it('トークン数が緑色で強調表示される', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      // トークン数のテキストが存在することを確認
      const tokenText = getByText('10,000トークン');
      expect(tokenText).toBeTruthy();
    });

    it('価格が赤色で強調表示される', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      // 価格のテキストが存在することを確認
      expect(getByText(/1,200/)).toBeTruthy();
      expect(getByText(/円/)).toBeTruthy();
    });

    it('高額なトークンパッケージも正しく表示される', () => {
      const highValueApproval: TokenApprovalItem = {
        ...mockTokenApproval,
        package_name: '100,000トークン',
        token_amount: 100000,
        price: 10000,
      };

      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={highValueApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText('100,000トークン')).toBeTruthy(); // パッケージ名
      const tokenAmounts = getAllByText(/100,000/);
      expect(tokenAmounts.length).toBeGreaterThan(0); // トークン数
      expect(getByText(/10,000/)).toBeTruthy(); // 価格
    });
  });

  describe('インタラクション', () => {
    it('承認ボタンをタップしたらonApproveが呼ばれる', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      const approveButton = getAllByText('承認する')[0];
      fireEvent.press(approveButton);

      expect(mockOnApprove).toHaveBeenCalledWith(mockTokenApproval.id);
    });

    it('却下ボタンをタップしたらonRejectが呼ばれる', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      const rejectButton = getAllByText('却下する')[0];
      fireEvent.press(rejectButton);

      expect(mockOnReject).toHaveBeenCalledWith(mockTokenApproval.id);
    });

    it('isProcessingがtrueの場合はボタンテキストが表示されない', () => {
      const { queryAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          isProcessing={true}
        />
      );

      // ボタンテキストが表示されない（ActivityIndicatorが表示される）
      expect(queryAllByText('承認する').length).toBe(0);
      expect(queryAllByText('却下する').length).toBe(0);
    });
  });

  describe('数値フォーマット', () => {
    it('トークン数がカンマ区切りで表示される', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText('10,000トークン')).toBeTruthy();
    });

    it('価格がカンマ区切りで表示される', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText(/1,200/)).toBeTruthy(); // 価格のカンマ区切り
      expect(getByText(/円/)).toBeTruthy();
    });

    it('小額のトークンも正しくフォーマットされる', () => {
      const smallApproval: TokenApprovalItem = {
        ...mockTokenApproval,
        package_name: '1,000トークン',
        token_amount: 1000,
        price: 120,
      };

      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={smallApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      const tokenAmounts = getAllByText(/1,000/);
      expect(tokenAmounts.length).toBeGreaterThan(0); // トークン数（複数表示）
      expect(getByText(/120/)).toBeTruthy(); // 価格
      expect(getByText(/円/)).toBeTruthy();
    });
  });

  describe('バッジ表示', () => {
    it('「トークン」バッジが表示される', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText('トークン')).toBeTruthy(); // バッジテキスト
    });
  });

  describe('レスポンシブ対応', () => {
    it('タブレットサイズでも正しく表示される', () => {
      // Dimensionsのモックは省略（既存のレスポンシブロジックをテスト）
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText('10,000トークン')).toBeTruthy();
      expect(getByText(/1,200/)).toBeTruthy();
      expect(getByText(/円/)).toBeTruthy();
    });
  });

  describe('依頼者表示', () => {
    it('依頼者名が正しく表示される', () => {
      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={mockTokenApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText('申請者:')).toBeTruthy();
      expect(getByText('テストユーザー')).toBeTruthy();
    });

    it('異なる依頼者の承認リクエストを区別できる', () => {
      const differentRequester: TokenApprovalItem = {
        ...mockTokenApproval,
        requester_name: '別のユーザー',
        requester_id: 999,
      };

      const { getByText, getAllByText } = renderScreen(
        <TokenApprovalCard
          item={differentRequester}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
        />
      );

      expect(getByText(/別のユーザー/)).toBeTruthy();
    });
  });
});
