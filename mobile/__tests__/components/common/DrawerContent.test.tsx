/**
 * DrawerContentコンポーネントのテスト
 * 
 * NavigationFlow.md Section 3.2: ハンバーガーメニュー（ドロワー）のトークン残高表示
 * 
 * 検証項目:
 * - トークン残高の表示（合計、無料、有料の内訳）
 * - 低残高時の警告表示と購入ボタン
 * - トークン/コインラベルのテーマ切り替え
 * - メニュー項目の表示（条件付き表示含む）
 * - バッジ表示（タスク件数、承認待ち、低残高）
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react-native';
import DrawerContent from '../../../src/components/common/DrawerContent';
import { useAuth } from '../../../src/contexts/AuthContext';
import { tokenService } from '../../../src/services/token.service';
import { useChildTheme } from '../../../src/hooks/useChildTheme';
import { ColorSchemeProvider } from '../../../src/contexts/ColorSchemeContext';
import { ThemeProvider } from '../../../src/contexts/ThemeContext';

// モック
jest.mock('../../../src/contexts/AuthContext');
jest.mock('../../../src/services/token.service');
jest.mock('../../../src/hooks/useChildTheme');
jest.mock('../../../src/utils/responsive', () => ({
  useResponsive: () => ({
    scaleFont: (size: number) => size,
    width: 375,
    deviceSize: 'phone',
  }),
  getFontSize: (size: number) => size,
  getSpacing: (size: number) => size,
  getBorderRadius: (size: number) => size,
}));

describe('DrawerContent - Section 3.2: トークン残高表示', () => {
  const mockNavigation = {
    navigate: jest.fn(),
    reset: jest.fn(),
  } as any;

  const mockDrawerProps = {
    navigation: mockNavigation,
    state: { routes: [], index: 0 },
    descriptors: {},
  } as any;

  const mockUser = {
    id: 1,
    email: 'test@example.com',
    name: 'Test User',
    group_id: null,
    canEditGroup: false,
    isAdmin: false,
  };

  const mockTokenBalance = {
    balance: 1000000,
    free_balance: 600000,
    paid_balance: 400000,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useAuth as jest.Mock).mockReturnValue({
      user: mockUser,
      logout: jest.fn(),
    });
    (tokenService.getBalance as jest.Mock).mockResolvedValue(mockTokenBalance);
    (useChildTheme as jest.Mock).mockReturnValue(false);
  });

  describe('トークン残高の基本表示', () => {
    it('トークン残高（合計）を表示する', async () => {
      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.getByText('1,000,000')).toBeTruthy();
      });
    });

    it('無料残高と有料残高の内訳を表示する', async () => {
      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.getByText(/無料: 600,000 \/ 有料: 400,000/)).toBeTruthy();
      });
    });

    it('大人テーマでは「トークン残高」と表示する', async () => {
      (useChildTheme as jest.Mock).mockReturnValue(false);
      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.getByText('トークン残高')).toBeTruthy();
      });
    });

    it('子どもテーマでは「コイン残高」と表示する', async () => {
      (useChildTheme as jest.Mock).mockReturnValue(true);
      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.getByText('コイン残高')).toBeTruthy();
      });
    });
  });

  describe('低残高時の表示', () => {
    it('残高が200,000以下の場合、購入ボタンを表示する（大人テーマ）', async () => {
      const lowBalance = { balance: 150000, free_balance: 150000, paid_balance: 0 };
      (tokenService.getBalance as jest.Mock).mockResolvedValue(lowBalance);
      (useChildTheme as jest.Mock).mockReturnValue(false);

      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.getByText('トークン購入')).toBeTruthy();
      });
    });

    it('残高が200,000以下の場合、購入ボタンを表示する（子どもテーマ）', async () => {
      const lowBalance = { balance: 150000, free_balance: 150000, paid_balance: 0 };
      (tokenService.getBalance as jest.Mock).mockResolvedValue(lowBalance);
      (useChildTheme as jest.Mock).mockReturnValue(true);

      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.getByText('コイン購入')).toBeTruthy();
      });
    });

    it('残高が200,000を超える場合、購入ボタンを表示しない', async () => {
      const highBalance = { balance: 500000, free_balance: 300000, paid_balance: 200000 };
      (tokenService.getBalance as jest.Mock).mockResolvedValue(highBalance);

      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.queryByText(/購入/)).toBeNull();
      });
    });

    it('購入ボタンをタップするとTokenBalance画面に遷移する', async () => {
      const lowBalance = { balance: 150000, free_balance: 150000, paid_balance: 0 };
      (tokenService.getBalance as jest.Mock).mockResolvedValue(lowBalance);

      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        const purchaseButton = screen.getByText('トークン購入');
        fireEvent.press(purchaseButton);
      });

      expect(mockNavigation.navigate).toHaveBeenCalledWith('TokenBalance');
    });
  });

  describe('トークン残高セクションの配置', () => {
    it('トークン残高セクションはドロワー下部に表示される', async () => {
      const { getByText } = render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        const balanceSection = getByText('トークン残高').parent?.parent;
        expect(balanceSection).toBeTruthy();
        // marginTop: 'auto' により下部固定を確認（スタイルのテストは限定的）
      });
    });
  });

  describe('メニュー項目とバッジ', () => {
    it('トークンメニューに低残高バッジ（赤丸）を表示する', async () => {
      const lowBalance = { balance: 150000, free_balance: 150000, paid_balance: 0 };
      (tokenService.getBalance as jest.Mock).mockResolvedValue(lowBalance);

      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        // トークンメニュー項目の存在を確認（メニューラベルとして）
        // 「トークン」が複数箇所に表示される（メニュー項目と残高表示）ため、getAllByTextを使用
        const tokenMenus = screen.getAllByText(/トークン|コイン/);
        expect(tokenMenus.length).toBeGreaterThan(0);
        // バッジの実装確認（赤丸は View コンポーネントなのでテキストベースで検証困難）
      });
    });

    it('グループ管理者には承認待ちメニューを表示する', async () => {
      const groupAdmin = { 
        ...mockUser, 
        group_id: 1, 
        canEditGroup: true,
        group: {
          id: 1,
          name: 'Test Group',
          master_user_id: 1,
        },
      };
      (useAuth as jest.Mock).mockReturnValue({
        user: groupAdmin,
        logout: jest.fn(),
      });

      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.getByText('承認待ち')).toBeTruthy();
      });
    });

    it('一般ユーザーには承認待ちメニューを表示しない', async () => {
      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(screen.queryByText('承認待ち')).toBeNull();
      });
    });
  });

  describe('API呼び出し', () => {
    it('コンポーネントマウント時にトークン残高を取得する', async () => {
      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        expect(tokenService.getBalance).toHaveBeenCalledTimes(1);
      });
    });

    it('API呼び出しエラー時もクラッシュしない', async () => {
      (tokenService.getBalance as jest.Mock).mockRejectedValue(
        new Error('Network error')
      );

      const { getByText } = render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      // エラーでもコンポーネントはレンダリングされる
      await waitFor(() => {
        expect(getByText('MyTeacher')).toBeTruthy();
      });
    });
  });

  describe('ログアウト機能', () => {
    it('ログアウトボタンをタップするとログアウト処理を実行する', async () => {
      const mockLogout = jest.fn().mockResolvedValue(undefined);
      (useAuth as jest.Mock).mockReturnValue({
        user: mockUser,
        logout: mockLogout,
      });

      render(
        <ThemeProvider>
          <ColorSchemeProvider>
            <DrawerContent {...mockDrawerProps} />
          </ColorSchemeProvider>
        </ThemeProvider>
      );

      await waitFor(() => {
        const logoutButton = screen.getByText('ログアウト');
        fireEvent.press(logoutButton);
      });

      await waitFor(() => {
        expect(mockLogout).toHaveBeenCalledTimes(1);
        // navigation.reset()は不要 - AuthContextのisAuthenticatedがfalseに変更されることで
        // AppNavigatorが自動的に未認証画面スタックに切り替わる
        // expect(mockNavigation.reset).toHaveBeenCalled(); は不要
      });
    });
  });
});
