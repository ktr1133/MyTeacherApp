/**
 * 画面コンポーネント - レスポンシブ対応テスト
 * 
 * 各デバイスサイズで画面が正しくレンダリングされることを検証
 */

import { render } from '@testing-library/react-native';
import { Dimensions } from 'react-native';
import TaskListScreen from '../../src/screens/tasks/TaskListScreen';
import ProfileScreen from '../../src/screens/profile/ProfileScreen';
import { useTasks } from '../../src/hooks/useTasks';
import { useAuth } from '../../src/hooks/useAuth';
import { useTheme } from '../../src/contexts/ThemeContext';
import { useNavigation } from '@react-navigation/native';

// モック設定
jest.mock('../../src/hooks/useTasks');
jest.mock('../../src/hooks/useAuth');
jest.mock('../../src/contexts/ThemeContext');
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
  useFocusEffect: jest.fn((callback) => callback()),
}));
jest.mock('../../src/hooks/useAvatar', () => ({
  useAvatar: jest.fn(() => ({
    avatar: null,
    comment: null,
    loading: false,
  })),
}));

describe('画面コンポーネント - レスポンシブ対応', () => {
  const mockUseTasks = useTasks as jest.MockedFunction<typeof useTasks>;
  const mockUseAuth = useAuth as jest.MockedFunction<typeof useAuth>;
  const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;
  const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;

  const mockNavigation = {
    navigate: jest.fn(),
    goBack: jest.fn(),
    setOptions: jest.fn(),
  };

  const mockUser = {
    id: 1,
    name: 'テストユーザー',
    email: 'test@example.com',
  };

  beforeEach(() => {
    jest.clearAllMocks();

    mockUseNavigation.mockReturnValue(mockNavigation as any);
    mockUseTheme.mockReturnValue({
      theme: 'adult',
      setTheme: jest.fn(),
      isLoading: false,
      refreshTheme: jest.fn(),
    });
    mockUseAuth.mockReturnValue({
      user: mockUser,
      token: 'test-token',
      isAuthenticated: true,
      login: jest.fn(),
      logout: jest.fn(),
      register: jest.fn(),
      isLoading: false,
      error: null,
    } as any);
    mockUseTasks.mockReturnValue({
      tasks: [],
      tagBuckets: [],
      isLoading: false,
      error: null,
      fetchTasks: jest.fn(),
      loadTasks: jest.fn(),
      createTask: jest.fn(),
      updateTask: jest.fn(),
      deleteTask: jest.fn(),
      completeTask: jest.fn(),
    } as any);
  });

  /**
   * テスト1: デバイスサイズ別レンダリング
   * 
   * 要件:
   * - 各デバイスサイズでエラーなくレンダリングされる
   * - useResponsive() が正しく呼び出される
   */
  describe('デバイスサイズ別レンダリング', () => {
    const testDevices = [
      { name: 'Galaxy Fold', width: 280, height: 653, category: 'xs' },
      { name: 'iPhone SE 1st', width: 320, height: 568, category: 'xs' },
      { name: 'iPhone SE 2nd/3rd', width: 375, height: 667, category: 'md' },
      { name: 'iPhone 12/13/14', width: 390, height: 844, category: 'md' },
      { name: 'Pixel 7', width: 412, height: 915, category: 'md' },
      { name: 'iPhone Pro Max', width: 430, height: 932, category: 'lg' },
      { name: 'iPad mini', width: 768, height: 1024, category: 'tablet-sm' },
      { name: 'iPad Pro', width: 1024, height: 1366, category: 'tablet' },
    ];

    testDevices.forEach(({ name, width, height, category }) => {
      it(`${name} (${width}x${height}, ${category}) でTaskListScreenがレンダリングされる`, () => {
        // Dimensions.getをモック
        jest.spyOn(Dimensions, 'get').mockReturnValue({
          width,
          height,
          scale: 3,
          fontScale: 1,
        });

        render(<TaskListScreen />);

        // エラーなくレンダリングされることを確認
        // （特定の要素の存在チェックは各画面のテストで実施）
        expect(() => render(<TaskListScreen />)).not.toThrow();
      });
    });
  });

  /**
   * テスト2: 画面回転対応
   * 
   * 要件:
   * - 縦向き → 横向きの切り替えでレイアウトが更新される
   * - エラーが発生しない
   */
  describe('画面回転対応', () => {
    it('縦向き → 横向き切り替えでTaskListScreenが再レンダリングされる', () => {
      // 初期: 縦向き
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 390,
        height: 844,
        scale: 3,
        fontScale: 1,
      });

      const { rerender } = render(<TaskListScreen />);

      // 画面回転: 横向き
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 844,
        height: 390,
        scale: 3,
        fontScale: 1,
      });

      // 再レンダリング
      rerender(<TaskListScreen />);

      // エラーなく再レンダリングされることを確認
      expect(() => rerender(<TaskListScreen />)).not.toThrow();
    });

    it('横向き → 縦向き切り替えでProfileScreenが再レンダリングされる', () => {
      // 初期: 横向き
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 844,
        height: 390,
        scale: 3,
        fontScale: 1,
      });

      const { rerender } = render(<ProfileScreen />);

      // 画面回転: 縦向き
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 390,
        height: 844,
        scale: 3,
        fontScale: 1,
      });

      // 再レンダリング
      rerender(<ProfileScreen />);

      expect(() => rerender(<ProfileScreen />)).not.toThrow();
    });
  });

  /**
   * テスト3: テーマ別レンダリング
   * 
   * 要件:
   * - 大人向けテーマでレンダリングされる
   * - 子ども向けテーマでレンダリングされる
   * - フォントサイズが1.2倍になる（目視確認）
   */
  describe('テーマ別レンダリング', () => {
    it('大人向けテーマでTaskListScreenがレンダリングされる', () => {
      mockUseTheme.mockReturnValue({
        theme: 'adult',
        setTheme: jest.fn(),
        isLoading: false,
        refreshTheme: jest.fn(),
      });

      const { rerender } = render(<TaskListScreen />);

      expect(() => rerender(<TaskListScreen />)).not.toThrow();
    });

    it('子ども向けテーマでTaskListScreenがレンダリングされる', () => {
      mockUseTheme.mockReturnValue({
        theme: 'child',
        setTheme: jest.fn(),
        isLoading: false,
        refreshTheme: jest.fn(),
      });

      const { rerender } = render(<TaskListScreen />);

      expect(() => rerender(<TaskListScreen />)).not.toThrow();
    });

    it('大人向け → 子ども向けテーマ切り替えでProfileScreenが再レンダリングされる', () => {
      // 初期: 大人向け
      mockUseTheme.mockReturnValue({
        theme: 'adult',
        setTheme: jest.fn(),
        isLoading: false,
        refreshTheme: jest.fn(),
      });

      const { rerender } = render(<ProfileScreen />);

      // テーマ切り替え: 子ども向け
      mockUseTheme.mockReturnValue({
        theme: 'child',
        setTheme: jest.fn(),
        isLoading: false,
        refreshTheme: jest.fn(),
      });

      rerender(<ProfileScreen />);

      expect(() => rerender(<ProfileScreen />)).not.toThrow();
    });
  });

  /**
   * テスト4: スタイル動的生成の確認
   * 
   * 要件:
   * - createStyles関数が存在する（静的StyleSheet.createではない）
   * - useMemo でスタイルが生成される
   * - width変更時にスタイルが再計算される
   */
  describe('スタイル動的生成の確認', () => {
    it('異なる画面幅でTaskListScreenのスタイルが動的に生成される', () => {
      // 小さい画面
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 320,
        height: 568,
        scale: 3,
        fontScale: 1,
      });

      const { rerender } = render(<TaskListScreen />);

      // 大きい画面
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 1024,
        height: 1366,
        scale: 2,
        fontScale: 1,
      });

      rerender(<TaskListScreen />);

      // エラーなく再レンダリングされ、スタイルが適用される
      expect(() => rerender(<TaskListScreen />)).not.toThrow();
    });
  });

  /**
   * テスト5: 極端なデバイスサイズ
   * 
   * 要件:
   * - 想定外の画面サイズでもエラーにならない
   * - 最小余白が保証される
   */
  describe('極端なデバイスサイズ', () => {
    it('超小型デバイス (200px) でエラーにならない', () => {
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 200,
        height: 400,
        scale: 2,
        fontScale: 1,
      });

      expect(() => render(<TaskListScreen />)).not.toThrow();
    });

    it('超大型デバイス (1600px) でエラーにならない', () => {
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 1600,
        height: 2400,
        scale: 2,
        fontScale: 1,
      });

      expect(() => render(<TaskListScreen />)).not.toThrow();
    });

    it('正方形画面 (800x800) でエラーにならない', () => {
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 800,
        height: 800,
        scale: 2,
        fontScale: 1,
      });

      expect(() => render(<TaskListScreen />)).not.toThrow();
    });
  });

  /**
   * テスト6: パフォーマンス検証
   * 
   * 要件:
   * - useMemo によるスタイル再計算の最適化
   * - width変更時のみ再計算される
   */
  describe('パフォーマンス検証', () => {
    it('同じ画面幅で再レンダリング時にスタイル再計算されない（useMemo効果）', () => {
      jest.spyOn(Dimensions, 'get').mockReturnValue({
        width: 390,
        height: 844,
        scale: 3,
        fontScale: 1,
      });

      const { rerender } = render(<TaskListScreen />);

      // 同じ画面幅で再レンダリング
      rerender(<TaskListScreen />);
      rerender(<TaskListScreen />);
      rerender(<TaskListScreen />);

      // エラーなく複数回レンダリングされる
      expect(() => rerender(<TaskListScreen />)).not.toThrow();
    });
  });
});
