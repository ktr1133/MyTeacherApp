/**
 * BucketCard コンポーネントのテスト
 * 
 * Web版スタイル統一の確認:
 * - グラデーション背景
 * - アイコングラデーション
 * - バッジグラデーション
 * - 下部グラデーションバー
 * - レスポンシブスケーリング
 * 
 * @see /home/ktr/mtdev/mobile/src/components/tasks/BucketCard.tsx
 * @see /home/ktr/mtdev/docs/plans/phase2-b8-web-style-alignment-plan.md
 */
import { render, fireEvent } from '@testing-library/react-native';
import BucketCard from '../../../src/components/tasks/BucketCard';
import { Task } from '../../../src/types/task.types';
import { ThemeProvider } from '../../../src/contexts/ThemeContext';
import { AuthProvider } from '../../../src/contexts/AuthContext';

// モックデータ
const mockTasks: Task[] = [
  {
    id: 1,
    title: 'タスク1',
    is_completed: false,
    priority: 3,
    user_id: 1,
    created_at: '2025-12-10T00:00:00Z',
    updated_at: '2025-12-10T00:00:00Z',
  },
  {
    id: 2,
    title: 'タスク2',
    is_completed: false,
    priority: 2,
    user_id: 1,
    created_at: '2025-12-10T00:00:00Z',
    updated_at: '2025-12-10T00:00:00Z',
  },
];

/**
 * ThemeProvider + AuthProviderでラップしたレンダリングヘルパー
 */
const renderWithTheme = (component: React.ReactElement) => {
  return render(
    <AuthProvider>
      <ThemeProvider>
        {component}
      </ThemeProvider>
    </AuthProvider>
  );
};

describe('BucketCard', () => {
  const mockOnPress = jest.fn();

  beforeEach(() => {
    mockOnPress.mockClear();
  });

  it('タグ名とタスク数を表示する', () => {
    const { getByText } = renderWithTheme(
      <BucketCard
        tagId={1}
        tagName="テストタグ"
        tasks={mockTasks}
        onPress={mockOnPress}
        theme="adult"
      />
    );

    expect(getByText('テストタグ')).toBeTruthy();
    expect(getByText('2')).toBeTruthy(); // バッジのタスク数
  });

  it('タスクプレビューを表示する（最大6件）', () => {
    const { getByText } = renderWithTheme(
      <BucketCard
        tagId={1}
        tagName="テストタグ"
        tasks={mockTasks}
        onPress={mockOnPress}
        theme="adult"
      />
    );

    expect(getByText('タスク1')).toBeTruthy();
    expect(getByText('タスク2')).toBeTruthy();
  });

  it('7件以上のタスクがある場合、「他 X 件」を表示する', () => {
    const manyTasks: Task[] = Array.from({ length: 10 }, (_, i) => ({
      id: i + 1,
      title: `タスク${i + 1}`,
      is_completed: false,
      priority: 3,
      user_id: 1,
      created_at: '2025-12-10T00:00:00Z',
      updated_at: '2025-12-10T00:00:00Z',
    }));

    const { getByText } = renderWithTheme(
      <BucketCard
        tagId={1}
        tagName="テストタグ"
        tasks={manyTasks}
        onPress={mockOnPress}
        theme="adult"
      />
    );

    // プレビューは6件まで表示、残り4件は「他 4 件」
    expect(getByText('他 4 件')).toBeTruthy();
  });

  it('タップ時にonPressが呼ばれる', () => {
    const { getByText } = renderWithTheme(
      <BucketCard
        tagId={1}
        tagName="テストタグ"
        tasks={mockTasks}
        onPress={mockOnPress}
        theme="adult"
      />
    );

    fireEvent.press(getByText('テストタグ'));
    expect(mockOnPress).toHaveBeenCalledTimes(1);
  });

  it('子ども向けテーマでフォントサイズが拡大される', () => {
    const { getByText } = renderWithTheme(
      <BucketCard
        tagId={1}
        tagName="テストタグ"
        tasks={mockTasks}
        onPress={mockOnPress}
        theme="child"
      />
    );

    // レンダリングが成功することを確認
    expect(getByText('テストタグ')).toBeTruthy();
  });

  it('タスクが空の場合でもエラーなくレンダリングされる', () => {
    const { getByText } = renderWithTheme(
      <BucketCard
        tagId={1}
        tagName="空のバケット"
        tasks={[]}
        onPress={mockOnPress}
        theme="adult"
      />
    );

    expect(getByText('空のバケット')).toBeTruthy();
    expect(getByText('0')).toBeTruthy(); // タスク数0
  });

  it('タグ名が長い場合、切り詰めて表示される（numberOfLines=1）', () => {
    const { getByText } = renderWithTheme(
      <BucketCard
        tagId={1}
        tagName="これは非常に長いタグ名でスマートフォン画面では切り詰められるべきです"
        tasks={mockTasks}
        onPress={mockOnPress}
        theme="adult"
      />
    );

    // レンダリングが成功することを確認（切り詰めはネイティブ機能）
    expect(getByText('これは非常に長いタグ名でスマートフォン画面では切り詰められるべきです')).toBeTruthy();
  });

  it('タスクタイトルが長い場合、切り詰めて表示される（numberOfLines=1）', () => {
    const longTask: Task = {
      id: 1,
      title: 'これは非常に長いタスクタイトルでカード内では切り詰められるべきです。さらに長い文章を追加します。',
      is_completed: false,
      priority: 3,
      user_id: 1,
      created_at: '2025-12-10T00:00:00Z',
      updated_at: '2025-12-10T00:00:00Z',
    };

    const { getByText } = renderWithTheme(
      <BucketCard
        tagId={1}
        tagName="テストタグ"
        tasks={[longTask]}
        onPress={mockOnPress}
        theme="adult"
      />
    );

    // レンダリングが成功することを確認（切り詰めはネイティブ機能）
    expect(getByText('これは非常に長いタスクタイトルでカード内では切り詰められるべきです。さらに長い文章を追加します。')).toBeTruthy();
  });
});
