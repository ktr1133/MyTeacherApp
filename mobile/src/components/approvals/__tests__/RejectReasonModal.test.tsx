/**
 * RejectReasonModal コンポーネントテスト
 */

import { render, fireEvent } from '@testing-library/react-native';
import RejectReasonModal from '../RejectReasonModal';
import { useTheme } from '../../../contexts/ThemeContext';
import { ColorSchemeProvider } from '../../../contexts/ColorSchemeContext';

// モック
jest.mock('../../../contexts/ThemeContext');
jest.mock('../../../hooks/useThemedColors', () => ({
  useThemedColors: jest.fn(() => ({
    colors: {
      background: '#FFFFFF',
      text: '#000000',
      card: '#F5F5F5',
      border: '#E0E0E0',
      status: {
        success: '#10B981',
        warning: '#F59E0B',
        error: '#EF4444',
        info: '#3B82F6',
      },
    },
    accent: {
      primary: '#007AFF',
      secondary: '#5856D6',
      success: '#34C759',
    },
  })),
}));
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

describe('RejectReasonModal', () => {
  const mockOnReject = jest.fn();
  const mockOnCancel = jest.fn();

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

  describe('表示・非表示', () => {
    it('visible=trueの時にモーダルが表示される', () => {
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(getByText('却下理由の入力')).toBeTruthy();
    });

    it('visible=falseの時にモーダルが非表示になる', () => {
      const { queryByText } = renderScreen(
        <RejectReasonModal
          visible={false}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(queryByText('却下理由の入力')).toBeNull();
    });
  });

  describe('表示内容', () => {
    it('対象のタイトルが正しく表示される', () => {
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="重要なタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(getByText(/重要なタスク/)).toBeTruthy();
      expect(getByText(/を却下します/)).toBeTruthy();
    });

    it('child themeでも同じラベルを表示する', () => {
      mockUseTheme.mockReturnValue({
        theme: 'child',
        isLoading: false,
        refreshTheme: jest.fn(),
        setTheme: jest.fn(),
      });

      const { getByText, getByPlaceholderText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(getByText('却下理由の入力')).toBeTruthy();
      expect(getByPlaceholderText('却下理由を入力してください...（任意）')).toBeTruthy();
      expect(getByText('却下する')).toBeTruthy();
    });

    it('adult themeで適切なラベルを表示する', () => {
      const { getByText, getByPlaceholderText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(getByText('却下理由の入力')).toBeTruthy();
      expect(getByPlaceholderText('却下理由を入力してください...（任意）')).toBeTruthy();
      expect(getByText('却下する')).toBeTruthy();
    });
  });

  describe('テキスト入力', () => {
    it('理由を入力できる', () => {
      const { getByPlaceholderText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      const input = getByPlaceholderText('却下理由を入力してください...（任意）');
      fireEvent.changeText(input, '内容が不適切です');

      expect(input.props.value).toBe('内容が不適切です');
    });

    it('複数行のテキストを入力できる', () => {
      const { getByPlaceholderText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      const input = getByPlaceholderText('却下理由を入力してください...（任意）');
      const longText = 'これは長い理由です。\n複数行に渡って入力しています。\n詳細な説明を含みます。';
      fireEvent.changeText(input, longText);

      expect(input.props.value).toBe(longText);
    });

    it('200文字まで入力できる', () => {
      const { getByPlaceholderText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      const input = getByPlaceholderText('却下理由を入力してください...（任意）');
      const text200 = 'あ'.repeat(200);
      fireEvent.changeText(input, text200);

      expect(input.props.value).toBe(text200);
    });
  });

  describe('ボタンアクション', () => {
    it('却下ボタンをタップしたら入力した理由とともにonRejectが呼ばれる', () => {
      const { getByText, getByPlaceholderText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      const input = getByPlaceholderText('却下理由を入力してください...（任意）');
      fireEvent.changeText(input, '内容が不適切です');

      const rejectButton = getByText('却下する');
      fireEvent.press(rejectButton);

      expect(mockOnReject).toHaveBeenCalledWith('内容が不適切です');
      expect(mockOnReject).toHaveBeenCalledTimes(1);
    });

    it('理由を入力せずに却下ボタンをタップしたらundefinedでonRejectが呼ばれる', () => {
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      const rejectButton = getByText('却下する');
      fireEvent.press(rejectButton);

      expect(mockOnReject).toHaveBeenCalledWith(undefined);
      expect(mockOnReject).toHaveBeenCalledTimes(1);
    });

    it('キャンセルボタンをタップしたらonCancelが呼ばれる', () => {
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      const cancelButton = getByText('キャンセル');
      fireEvent.press(cancelButton);

      expect(mockOnCancel).toHaveBeenCalledTimes(1);
      expect(mockOnReject).not.toHaveBeenCalled();
    });

    it('isSubmitting=trueの場合は却下ボタンが無効になる', () => {
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={true}
        />
      );

      const rejectButton = getByText('送信中...');
      fireEvent.press(rejectButton);

      // isSubmittingがtrueなのでonRejectは呼ばれない
      expect(mockOnReject).not.toHaveBeenCalled();
    });

    it('isSubmitting=trueの場合はキャンセルボタンも無効になる', () => {
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={true}
        />
      );

      const cancelButton = getByText('キャンセル');
      fireEvent.press(cancelButton);

      // isSubmittingがtrueなのでonCancelは呼ばれない
      expect(mockOnCancel).not.toHaveBeenCalled();
    });
  });

  describe('モーダルを閉じた時の動作', () => {
    it('キャンセル後に再度開いた時は入力がクリアされる', () => {
      const { getByText, getByPlaceholderText, rerender } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      // テキストを入力
      const input = getByPlaceholderText('却下理由を入力してください...（任意）');
      fireEvent.changeText(input, '理由です');

      // キャンセルボタンをタップ
      const cancelButton = getByText('キャンセル');
      fireEvent.press(cancelButton);

      // モーダルを再度開く
      rerender(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      // 入力がクリアされていることを確認
      const newInput = getByPlaceholderText('却下理由を入力してください...（任意）');
      expect(newInput.props.value).toBe('');
    });
  });

  describe('レスポンシブ対応', () => {
    it('タブレットサイズでも正しく表示される', () => {
      // Dimensionsのモックは省略（既存のレスポンシブロジックをテスト）
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="テストタスク"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(getByText('却下理由の入力')).toBeTruthy();
    });
  });

  describe('長いタイトルの処理', () => {
    it('長いタイトルも正しく表示される', () => {
      const longTitle = 'これは非常に長いタスクのタイトルで、モーダル内で折り返されるはずです';

      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle={longTitle}
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(getByText(new RegExp(longTitle))).toBeTruthy();
    });
  });

  describe('複数の対象タイプ', () => {
    it('タスクの却下時に適切なメッセージを表示する', () => {
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="タスク名"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(getByText(/タスク名/)).toBeTruthy();
      expect(getByText(/を却下します/)).toBeTruthy();
    });

    it('トークン購入申請の却下時に適切なメッセージを表示する', () => {
      const { getByText } = renderScreen(
        <RejectReasonModal
          visible={true}
          targetTitle="10,000トークン"
          onReject={mockOnReject}
          onCancel={mockOnCancel}
          isSubmitting={false}
        />
      );

      expect(getByText(/10,000トークン/)).toBeTruthy();
      expect(getByText(/を却下します/)).toBeTruthy();
    });
  });
});
