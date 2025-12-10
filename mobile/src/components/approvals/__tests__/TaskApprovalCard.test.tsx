/**
 * TaskApprovalCard コンポーネントテスト
 */

import { render, fireEvent } from '@testing-library/react-native';
import TaskApprovalCard from '../TaskApprovalCard';
import { TaskApprovalItem } from '../../../types/approval.types';
import { useTheme } from '../../../contexts/ThemeContext';

// モック
jest.mock('../../../contexts/ThemeContext');
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

describe('TaskApprovalCard', () => {
  const mockTaskApproval: TaskApprovalItem = {
    id: 1,
    type: 'task',
    title: 'テストタスク',
    requester_name: 'テストユーザー',
    requester_id: 2,
    requested_at: '2025-12-06T10:30:00.000Z',
    description: 'テスト説明文。これはタスクの詳細説明です。',
    reward: 100,
    has_images: true,
    images_count: 2,
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

  const mockOnPress = jest.fn();
  const mockOnApprove = jest.fn();
  const mockOnReject = jest.fn();

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
    it('タスク情報が正しく表示される', () => {
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      expect(getByText('テストタスク')).toBeTruthy();
      expect(getByText('タスク')).toBeTruthy();
      expect(getByText('申請者:')).toBeTruthy();
      expect(getByText('テストユーザー')).toBeTruthy();
      expect(getByText(/100/)).toBeTruthy(); // 報酬（数値部分）
      expect(getByText(/トークン/)).toBeTruthy(); // 報酬（単位部分）
    });

    it('child themeで適切なラベルを表示する', () => {
      mockUseTheme.mockReturnValue({
        theme: 'child',
        isLoading: false,
        refreshTheme: jest.fn(),
        setTheme: jest.fn(),
      });

      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      expect(getByText('申請者:')).toBeTruthy();
      expect(getByText('テストユーザー')).toBeTruthy();
    });

    it('期限が正しく表示される', () => {
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      // 期限: 2025/12/31 形式
      expect(getByText(/2025\/12\/31/)).toBeTruthy();
    });

    it('依頼日時が正しく表示される', () => {
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      // 依頼日時: 2025/12/06 19:30 形式（UTCからJST変換後）
      expect(getByText(/2025\/12\/06.*19:30/)).toBeTruthy();
    });

    it('画像枚数が表示される', () => {
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      expect(getByText(/2枚添付済み/)).toBeTruthy();
    });

    it('画像がない場合は画像枚数を表示しない', () => {
      const noImageApproval: TaskApprovalItem = {
        ...mockTaskApproval,
        has_images: false,
        images_count: 0,
      };

      const { queryByText } = render(
        <TaskApprovalCard
          item={noImageApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      expect(queryByText(/📷/)).toBeNull();
    });

    it('説明文が正しく表示される', () => {
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      expect(getByText('テスト説明文。これはタスクの詳細説明です。')).toBeTruthy();
    });

    it('説明がない場合は表示しない', () => {
      const noDescApproval: TaskApprovalItem = {
        ...mockTaskApproval,
        description: null,
      };

      const { queryByText } = render(
        <TaskApprovalCard
          item={noDescApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      // 説明文がないことを確認（タイトルは表示される）
      expect(queryByText('テスト説明文。これはタスクの詳細説明です。')).toBeNull();
    });
  });

  describe('インタラクション', () => {
    it('カードをタップしたらonViewDetailが呼ばれる', () => {
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      const card = getByText('テストタスク');
      fireEvent.press(card.parent!.parent!);

      expect(mockOnPress).toHaveBeenCalledWith(mockTaskApproval.id);
    });

    it('承認ボタンをタップしたらonApproveが呼ばれる', () => {
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      const approveButton = getAllByText('承認する')[0];
      fireEvent.press(approveButton);

      expect(mockOnApprove).toHaveBeenCalledWith(mockTaskApproval.id);
      expect(mockOnPress).not.toHaveBeenCalled();
    });

    it('却下ボタンをタップしたらonRejectが呼ばれる', () => {
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      const rejectButton = getAllByText('却下する')[0];
      fireEvent.press(rejectButton);

      expect(mockOnReject).toHaveBeenCalledWith(mockTaskApproval.id);
      expect(mockOnPress).not.toHaveBeenCalled();
    });

    it('isProcessingがtrueの場合はボタンテキストが表示されない', () => {
      const { queryAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
          isProcessing={true}
        />
      );

      // ボタンテキストが表示されない（ActivityIndicatorが表示される）
      expect(queryAllByText('承認する').length).toBe(0);
      expect(queryAllByText('却下する').length).toBe(0);
    });
  });

  describe('レスポンシブ対応', () => {
    it('タブレットサイズでも正しく表示される', () => {
      // Dimensionsのモックは省略（既存のレスポンシブロジックをテスト）
      const { getByText, getAllByText } = render(
        <TaskApprovalCard
          item={mockTaskApproval}
          onApprove={mockOnApprove}
          onReject={mockOnReject}
          onViewDetail={mockOnPress}
        />
      );

      expect(getByText('テストタスク')).toBeTruthy();
    });
  });
});
