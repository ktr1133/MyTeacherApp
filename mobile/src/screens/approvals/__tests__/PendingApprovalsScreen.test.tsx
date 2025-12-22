/**
 * PendingApprovalsScreen テスト
 */

import { render, fireEvent, waitFor, act } from '@testing-library/react-native';
import { Alert } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import PendingApprovalsScreen from '../PendingApprovalsScreen';
import { usePendingApprovals } from '../../../hooks/usePendingApprovals';
import { useTheme } from '../../../contexts/ThemeContext';
import { TaskApprovalItem, TokenApprovalItem } from '../../../types/approval.types';
import { ColorSchemeProvider } from '../../../contexts/ColorSchemeContext';

// モック
jest.mock('../../../hooks/usePendingApprovals');
jest.mock('../../../contexts/ThemeContext');
jest.mock('../../../hooks/useThemedColors', () => ({
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

const mockUsePendingApprovals = usePendingApprovals as jest.MockedFunction<typeof usePendingApprovals>;
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

// navigationのモック（コンポーネント内部のuseNavigation()で使用される）
const mockNavigate = jest.fn();
// mockNavigationオブジェクトは@react-navigation/nativeのuseNavigationモック用
jest.mock('@react-navigation/native', () => {
  const actual = jest.requireActual('@react-navigation/native');
  return {
    ...actual,
    useNavigation: () => ({
      navigate: mockNavigate,
      goBack: jest.fn(),
      addListener: jest.fn(() => jest.fn()),
      removeListener: jest.fn(),
      dispatch: jest.fn(),
      setOptions: jest.fn(),
      isFocused: jest.fn(() => true),
      canGoBack: jest.fn(() => true),
      getId: jest.fn(),
      getParent: jest.fn(),
      getState: jest.fn(),
      setParams: jest.fn(),
    }),
  };
});

// Alert.alertのモック
jest.spyOn(Alert, 'alert');

describe('PendingApprovalsScreen', () => {
  // テスト用データ
  const mockTaskApproval: TaskApprovalItem = {
    id: 1,
    type: 'task',
    title: 'テストタスク',
    requester_name: 'テストユーザー',
    requester_id: 2,
    requested_at: '2025-12-06T00:00:00.000Z',
    description: 'テスト説明',
    reward: 100,
    has_images: false,
    images_count: 0,
    due_date: '2025-12-31',
    model: {
      id: 1,
      title: 'テストタスク',
      description: 'テスト説明',
      span: 1,
      due_date: '2025-12-31',
      priority: 3,
      is_completed: false,
      completed_at: null,
      reward: 100,
      requires_approval: true,
      requires_image: false,
      is_group_task: false,
      group_task_id: null,
      assigned_by_user_id: null,
      tags: [],
      images: [],
      created_at: '2025-12-06T00:00:00.000Z',
      updated_at: '2025-12-06T00:00:00.000Z',
    },
  };

  const mockTokenApproval: TokenApprovalItem = {
    id: 1,
    type: 'token',
    package_name: '10,000トークン',
    requester_name: 'テストユーザー',
    requester_id: 2,
    requested_at: '2025-12-06T00:00:00.000Z',
    token_amount: 10000,
    price: 1200,
    model: {
      id: 1,
      package_id: 1,
      status: 'pending',
      created_at: '2025-12-06T00:00:00.000Z',
    },
  };

  const mockPendingApprovalsHook = {
    approvals: [mockTaskApproval, mockTokenApproval],
    isLoading: false,
    isLoadingMore: false,
    hasMore: false,
    error: null,
    pagination: {
      current_page: 1,
      per_page: 15,
      total: 2,
      last_page: 1,
      from: 1,
      to: 2,
    },
    fetchApprovals: jest.fn(),
    loadMoreApprovals: jest.fn(),
    refreshApprovals: jest.fn(),
    approveTaskItem: jest.fn(),
    rejectTaskItem: jest.fn(),
    approveTokenItem: jest.fn(),
    rejectTokenItem: jest.fn(),
    clearError: jest.fn(),
  };

  const renderWithNavigation = (component: React.ReactElement) => {
    return render(
      <ColorSchemeProvider>
        <NavigationContainer>
          {component}
        </NavigationContainer>
      </ColorSchemeProvider>
    );
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUsePendingApprovals.mockReturnValue(mockPendingApprovalsHook);
    mockUseTheme.mockReturnValue({
      theme: 'adult',
      isLoading: false,
      refreshTheme: jest.fn(),
      setTheme: jest.fn(),
    });
  });

  describe('画面表示', () => {
    it('承認待ち一覧を表示する', async () => {
      const { getByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      await waitFor(() => {
        expect(getByText('テストタスク')).toBeTruthy();
        expect(getByText('10,000トークン')).toBeTruthy();
      });
    });

    it('承認待ちが0件の場合に空状態を表示する', async () => {
      mockUsePendingApprovals.mockReturnValue({
        ...mockPendingApprovalsHook,
        approvals: [],
      });

      const { getByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      await waitFor(() => {
        expect(getByText('承認待ちの項目がありません')).toBeTruthy();
      });
    });

    it('ローディング中にインジケーターを表示する', () => {
      mockUsePendingApprovals.mockReturnValue({
        ...mockPendingApprovalsHook,
        isLoading: true,
        approvals: [],
      });

      const { getByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      expect(getByText('読み込み中...')).toBeTruthy();
    });
  });

  describe('初回データ取得', () => {
    it('マウント時にデータを取得する', async () => {
      renderWithNavigation(
        <PendingApprovalsScreen />
      );

      await waitFor(() => {
        expect(mockPendingApprovalsHook.fetchApprovals).toHaveBeenCalledTimes(1);
      });
    });
  });

  describe('Pull to Refresh', () => {
    it('リフレッシュ操作でデータを再取得する', async () => {
      const { getByTestId } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      const flatList = getByTestId('approvals-list');
      const { refreshControl } = flatList.props;

      await act(async () => {
        refreshControl.props.onRefresh();
      });

      await waitFor(() => {
        expect(mockPendingApprovalsHook.refreshApprovals).toHaveBeenCalledTimes(1);
      });
    });
  });

  describe('Infinite Scroll', () => {
    it('リストの最後に到達したら追加データを読み込む', async () => {
      mockUsePendingApprovals.mockReturnValue({
        ...mockPendingApprovalsHook,
        hasMore: true,
      });

      const { getByTestId } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      const flatList = getByTestId('approvals-list');

      await act(async () => {
        flatList.props.onEndReached();
      });

      await waitFor(() => {
        expect(mockPendingApprovalsHook.loadMoreApprovals).toHaveBeenCalledTimes(1);
      });
    });

    it('hasMoreがfalseの場合は追加読み込みしない', async () => {
      mockUsePendingApprovals.mockReturnValue({
        ...mockPendingApprovalsHook,
        hasMore: false,
      });

      const { getByTestId } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      const flatList = getByTestId('approvals-list');

      await act(async () => {
        flatList.props.onEndReached();
      });

      expect(mockPendingApprovalsHook.loadMoreApprovals).not.toHaveBeenCalled();
    });
  });

  describe('タスク承認アクション', () => {
    it('タスクをタップしたら詳細画面に遷移する', async () => {
      const { getByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      const taskCard = getByText('テストタスク');
      fireEvent.press(taskCard.parent!.parent!);

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('TaskDetail', { taskId: 1 });
      });
    });

    it('タスクの承認ボタンをタップしたら確認ダイアログを表示する', async () => {
      const { getAllByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      // 「承認」ボタンは複数あるため、最初の要素を取得
      const approveButtons = getAllByText('承認する');
      fireEvent.press(approveButtons[0]);

      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalledWith(
          '承認確認',
          'このタスクを承認しますか?',
          expect.arrayContaining([
            expect.objectContaining({ text: 'キャンセル' }),
            expect.objectContaining({ text: '承認する' }),
          ])
        );
      });
    });

    it('承認確認後にトークン購入申請を承認する', async () => {
      (Alert.alert as jest.Mock).mockImplementation((_title, _message, buttons) => {
        // 「承認する」ボタンを自動的に押す
        const approveButton = buttons?.find((b: any) => b.text === '承認する');
        approveButton?.onPress();
      });

      const { getAllByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      const approveButtons = getAllByText('承認する');
      await act(async () => {
        fireEvent.press(approveButtons[0]);
      });

      await waitFor(() => {
        expect(mockPendingApprovalsHook.approveTaskItem).toHaveBeenCalledWith(
          mockTaskApproval.id
        );
      });
    });

    it('タスクの却下ボタンをタップしたら理由入力モーダルを表示する', async () => {
      const { getAllByText, getByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      const rejectButtons = getAllByText('却下する');
      fireEvent.press(rejectButtons[0]);

      await waitFor(() => {
        expect(getByText('却下理由の入力')).toBeTruthy();
      });
    });

    it('理由入力後にタスクを却下する', async () => {
      const { getAllByText, getByText, getByPlaceholderText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      // 却下ボタンをタップしてモーダルを開く
      const rejectButtons = getAllByText('却下する');
      fireEvent.press(rejectButtons[0]);

      await waitFor(() => {
        expect(getByText('却下理由の入力')).toBeTruthy();
      });

      // 理由を入力
      const reasonInput = getByPlaceholderText('却下理由を入力してください...（任意）');
      fireEvent.changeText(reasonInput, '内容が不適切です');

      // モーダル内の却下ボタンを再取得して最後の要素をタップ
      await act(async () => {
        const allRejectButtonsAfterModal = getAllByText('却下する');
        fireEvent.press(allRejectButtonsAfterModal[allRejectButtonsAfterModal.length - 1]);
      });

      await waitFor(() => {
        expect(mockPendingApprovalsHook.rejectTaskItem).toHaveBeenCalledWith(
          mockTaskApproval.id,
          '内容が不適切です'
        );
      }, { timeout: 3000 });
    });

    it('理由なしでもタスクを却下できる', async () => {
      const { getAllByText, getByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      // 却下ボタンをタップしてモーダルを開く
      const rejectButtons = getAllByText('却下する');
      fireEvent.press(rejectButtons[0]);

      await waitFor(() => {
        expect(getByText('却下理由の入力')).toBeTruthy();
      });

      // 理由を入力せずにモーダル内の却下ボタンをタップ
      await act(async () => {
        const allRejectButtonsAfterModal = getAllByText('却下する');
        fireEvent.press(allRejectButtonsAfterModal[allRejectButtonsAfterModal.length - 1]);
      });

      await waitFor(() => {
        expect(mockPendingApprovalsHook.rejectTaskItem).toHaveBeenCalledWith(
          mockTaskApproval.id,
          undefined
        );
      }, { timeout: 3000 });
    });
  });

  describe('トークン購入承認アクション', () => {
    it('トークン購入申請の承認ボタンをタップしたら確認ダイアログを表示する', async () => {
      const { getAllByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      // 2番目の「承認」ボタン（トークン購入申請）
      const approveButtons = getAllByText('承認する');
      fireEvent.press(approveButtons[1]);

      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalledWith(
          '承認確認',
          'このトークン購入申請を承認しますか?',
          expect.arrayContaining([
            expect.objectContaining({ text: 'キャンセル' }),
            expect.objectContaining({ text: '承認する' }),
          ])
        );
      });
    });

    it('承認確認後にトークン購入申請を承認する', async () => {
      (Alert.alert as jest.Mock).mockImplementation((_title, _message, buttons) => {
        const approveButton = buttons?.find((b: any) => b.text === '承認する');
        approveButton?.onPress();
      });

      const { getAllByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      const approveButtons = getAllByText('承認する');
      await act(async () => {
        fireEvent.press(approveButtons[1]);
      });

      await waitFor(() => {
        expect(mockPendingApprovalsHook.approveTokenItem).toHaveBeenCalledWith(
          mockTokenApproval.id
        );
      }, { timeout: 3000 });
    });

    it('トークン購入申請の却下ボタンをタップしたら理由入力モーダルを表示する', async () => {
      const { getAllByText, getByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      // 2番目の「却下」ボタン（トークン購入申請）
      const rejectButtons = getAllByText('却下する');
      fireEvent.press(rejectButtons[1]);

      await waitFor(() => {
        expect(getByText('却下理由の入力')).toBeTruthy();
      });
    });

    it('理由入力後にトークン購入申請を却下する', async () => {
      const { getAllByText, getByText, getByPlaceholderText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      // 却下ボタンをタップしてモーダルを開く
      const rejectButtons = getAllByText('却下する');
      fireEvent.press(rejectButtons[1]);

      await waitFor(() => {
        expect(getByText('却下理由の入力')).toBeTruthy();
      });

      // 理由を入力
      const reasonInput = getByPlaceholderText('却下理由を入力してください...（任意）');
      fireEvent.changeText(reasonInput, '予算が足りません');

      // モーダル内の却下ボタン（最後の要素）をタップ
      await act(async () => {
        const allRejectButtonsAfterModal = getAllByText('却下する');
        fireEvent.press(allRejectButtonsAfterModal[allRejectButtonsAfterModal.length - 1]);
      });

      await waitFor(() => {
        expect(mockPendingApprovalsHook.rejectTokenItem).toHaveBeenCalledWith(
          mockTokenApproval.id,
          '予算が足りません'
        );
      }, { timeout: 3000 });
    });
  });

  describe('エラーハンドリング', () => {
    it('エラーメッセージを表示する', async () => {
      mockUsePendingApprovals.mockReturnValue({
        ...mockPendingApprovalsHook,
        error: '承認待ち一覧の取得に失敗しました',
      });

      renderWithNavigation(
        <PendingApprovalsScreen />
      );

      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalledWith(
          'エラー',
          '承認待ち一覧の取得に失敗しました',
          expect.arrayContaining([
            expect.objectContaining({ text: 'OK' }),
          ])
        );
      });
    });

    it('エラーメッセージの閉じるボタンをタップしたらエラーをクリアする', async () => {
      const mockClearError = jest.fn();
      mockUsePendingApprovals.mockReturnValue({
        ...mockPendingApprovalsHook,
        error: 'エラーメッセージ',
        clearError: mockClearError,
      });

      (Alert.alert as jest.Mock).mockImplementation((_title, _message, buttons) => {
        // OKボタンを自動的に押す
        const okButton = buttons?.find((b: any) => b.text === 'OK');
        okButton?.onPress();
      });

      renderWithNavigation(
        <PendingApprovalsScreen />
      );

      await waitFor(() => {
        expect(mockClearError).toHaveBeenCalled();
      });
    });
  });

  describe('モーダル操作', () => {
    it('却下理由入力モーダルのキャンセルボタンでモーダルを閉じる', async () => {
      const { getAllByText, getByText, queryByText } = renderWithNavigation(
        <PendingApprovalsScreen />
      );

      // 却下ボタンをタップしてモーダルを開く
      const rejectButtons = getAllByText('却下する');
      fireEvent.press(rejectButtons[0]);

      await waitFor(() => {
        expect(getByText('却下理由の入力')).toBeTruthy();
      });

      // キャンセルボタンをタップ
      const cancelButton = getByText('キャンセル');
      fireEvent.press(cancelButton);

      // モーダルが閉じたことを確認（却下理由入力のテキストが消える）
      await waitFor(() => {
        expect(queryByText('却下理由の入力')).toBeNull();
      });
    });
  });
});
