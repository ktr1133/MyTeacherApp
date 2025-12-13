/**
 * ドロワーコンテンツ（ハンバーガーメニュー）
 * 
 * Web版サイドバーの内容をモバイル版ドロワーに完全移植
 * 
 * @see /home/ktr/mtdev/definitions/mobile/NavigationFlow.md - Section 3.2
 * @see /home/ktr/mtdev/resources/views/components/layouts/sidebar.blade.php
 * @see /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 */

import React, { useMemo, useState, useEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  Platform,
} from 'react-native';
import {
  DrawerContentScrollView,
  DrawerContentComponentProps,
} from '@react-navigation/drawer';
import { useAuth } from '../../contexts/AuthContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { Ionicons } from '@expo/vector-icons';
import { tokenService } from '../../services/token.service';
import type { TokenBalance as TokenBalanceAPI } from '../../types/token.types';
import { useThemedColors } from '../../hooks/useThemedColors';

/**
 * ドロワーメニュー項目の定義
 */
interface DrawerMenuItem {
  id: string;
  label: string;
  labelChild?: string; // 子ども向けテーマのラベル
  icon: keyof typeof Ionicons.glyphMap;
  route: string;
  badge?: number | 'low-balance' | 'pulse';
  condition?: (user: any) => boolean; // 表示条件
}

/**
 * ドロワーコンテンツコンポーネント
 * 
 * Web版サイドバー（sidebar.blade.php）と同等の機能を提供:
 * - 一般ユーザーメニュー（8項目）
 * - 管理者メニュー（Phase 2では非表示）
 * - トークン残高表示（ドロワー下部固定）
 * - 未完了タスク件数バッジ
 * - 承認待ち件数バッジ（グループ管理者のみ）
 * - 低残高警告（赤丸表示）
 * 
 * @param props - DrawerContentComponentProps
 */
export default function DrawerContent(props: DrawerContentComponentProps) {
  const { navigation } = props;
  const { user, logout } = useAuth();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  // 状態管理
  const [taskTotal, setTaskTotal] = useState<number>(0);
  const [pendingTotal, setPendingTotal] = useState<number>(0);
  const [tokenBalance, setTokenBalance] = useState<TokenBalanceAPI | null>(null);
  const [isLowBalance, setIsLowBalance] = useState<boolean>(false);
  const [showGeneralMenu, setShowGeneralMenu] = useState<boolean>(true);

  // データ取得
  useEffect(() => {
    if (user) {
      loadDrawerData();
    }
  }, [user]);

  /**
   * ドロワー表示に必要なデータを取得
   * 
   * Web版sidebar.blade.php Lines 1-24 の@php部分と同等
   */
  const loadDrawerData = async () => {
    try {
      // トークン残高取得（GET /api/tokens/balance）
      const balance = await tokenService.getBalance();
      setTokenBalance(balance);
      
      // 低残高判定（閾値: 200,000トークン）
      const LOW_THRESHOLD = 200000;
      setIsLowBalance(balance.balance <= LOW_THRESHOLD);

      // TODO: 未完了タスク件数取得（GET /api/tasks?is_completed=false）
      // setTaskTotal(count);

      // TODO: 承認待ち件数取得（グループ管理者のみ）
      // setPendingTotal(count);
    } catch (error) {
      console.error('[DrawerContent] Failed to load drawer data:', error);
    }
  };

  /**
   * 一般ユーザーメニュー項目
   * 
   * @see NavigationFlow.md - Section 3.2 Table 1
   * @see sidebar.blade.php Lines 99-244
   */
  const generalMenuItems: DrawerMenuItem[] = [
    {
      id: 'task-list',
      label: 'タスクリスト',
      labelChild: 'ToDo',
      icon: 'clipboard-outline',
      route: 'TaskList',
      badge: taskTotal,
    },
    {
      id: 'pending-approvals',
      label: '承認待ち',
      icon: 'time-outline',
      route: 'PendingApprovals',
      badge: pendingTotal > 0 ? 'pulse' : undefined,
      condition: (user) => {
        // マスター権限またはグループ編集権限を持つユーザーのみ表示
        const isGroupMaster = user?.group?.master_user_id === user?.id;
        return isGroupMaster || user?.group_edit_flg === true;
      },
    },
    {
      id: 'tag-management',
      label: 'タグ管理',
      labelChild: 'タグ',
      icon: 'pricetag-outline',
      route: 'TagManagement',
    },
    {
      id: 'avatar-manage',
      label: '教師アバター',
      labelChild: 'サポートアバター',
      icon: 'person-outline',
      route: 'AvatarManage',
    },
    {
      id: 'performance',
      label: '実績',
      icon: 'bar-chart-outline',
      route: 'Performance',
    },
    {
      id: 'token-balance',
      label: 'トークン',
      labelChild: 'コイン',
      icon: 'cash-outline',
      route: 'TokenBalance',
      badge: isLowBalance ? 'low-balance' : undefined,
    },
    {
      id: 'subscription',
      label: 'サブスクリプション',
      icon: 'card-outline',
      route: 'SubscriptionManage',
      condition: (user) => {
        console.log('[DrawerContent] Subscription condition check:', {
          user_id: user?.id,
          group_id: user?.group_id,
          group: user?.group,
          master_user_id: user?.group?.master_user_id,
          group_edit_flg: user?.group_edit_flg,
        });
        
        if (!user?.group_id) {
          console.log('[DrawerContent] No group_id, hiding subscription menu');
          return false;
        }
        
        // マスター権限またはグループ編集権限を持つユーザーのみ表示
        const isGroupMaster = user?.group?.master_user_id === user?.id;
        const hasEditPermission = user?.group_edit_flg === true;
        
        console.log('[DrawerContent] Permission check:', {
          isGroupMaster,
          hasEditPermission,
          result: isGroupMaster || hasEditPermission,
        });
        
        return isGroupMaster || hasEditPermission;
      },
    },
    {
      id: 'settings',
      label: '設定',
      icon: 'settings-outline',
      route: 'Settings',
    },
  ];

  /**
   * メニュー項目をレンダリング
   * 
   * @param item - メニュー項目
   * @param index - インデックス
   */
  const renderMenuItem = (item: DrawerMenuItem, index: number) => {
    // 表示条件チェック
    if (item.condition && !item.condition(user)) {
      return null;
    }

    const label = isChildTheme && item.labelChild ? item.labelChild : item.label;
    const isActive = false; // TODO: 現在のルートと比較

    return (
      <TouchableOpacity
        key={item.id}
        style={[styles.menuItem, isActive && styles.menuItemActive]}
        onPress={() => navigation.navigate(item.route as never)}
        activeOpacity={0.7}
      >
        <Ionicons
          name={item.icon}
          size={getFontSize(20, width, themeType)}
          color={isActive ? accent.primary : colors.text.secondary}
          style={styles.menuIcon}
        />
        <Text style={[styles.menuLabel, isActive && styles.menuLabelActive]}>
          {label}
        </Text>
        {renderBadge(item.badge)}
      </TouchableOpacity>
    );
  };

  /**
   * バッジをレンダリング
   * 
   * @param badge - バッジタイプ（数値、'low-balance'、'pulse'）
   */
  const renderBadge = (badge?: number | 'low-balance' | 'pulse') => {
    if (!badge) return null;

    if (badge === 'low-balance') {
      // 低残高警告（赤丸）
      return <View style={styles.badgeDot} />;
    }

    if (badge === 'pulse') {
      // 承認待ちバッジ（アニメーション付き）
      return (
        <View style={[styles.badge, styles.badgePulse]}>
          <Text style={styles.badgeText}>{pendingTotal}</Text>
        </View>
      );
    }

    // 数値バッジ
    return (
      <View style={styles.badge}>
        <Text style={styles.badgeText}>{badge}</Text>
      </View>
    );
  };

  /**
   * トークン残高セクションをレンダリング
   * 
   * @see sidebar.blade.php Lines 434-476
   * @see NavigationFlow.md - Section 3.2 (トークン残高表示)
   */
  const renderTokenBalance = () => {
    const tokenLabel = isChildTheme ? 'コイン' : 'トークン';

    // データ読み込み中はローディング表示
    if (!tokenBalance) {
      return (
        <View style={styles.tokenBalanceContainer}>
          <Text style={styles.tokenBalanceDetailText}>読み込み中...</Text>
        </View>
      );
    }

    return (
      <View style={styles.tokenBalanceContainer}>
        <View style={styles.tokenBalanceHeader}>
          <Ionicons
            name="cash-outline"
            size={getFontSize(20, width, themeType)}
            color={accent.primary}
          />
          <Text style={styles.tokenBalanceTitle}>{tokenLabel}残高</Text>
        </View>
        
        <Text style={styles.tokenBalanceTotal}>
          {tokenBalance?.balance?.toLocaleString() ?? '0'}
        </Text>
        
        <View style={styles.tokenBalanceDetail}>
          <Text style={styles.tokenBalanceDetailText}>
            無料: {tokenBalance?.free_balance?.toLocaleString() ?? '0'} / 有料: {tokenBalance?.paid_balance?.toLocaleString() ?? '0'}
          </Text>
        </View>

        {isLowBalance && (
          <TouchableOpacity
            style={styles.tokenPurchaseButton}
            onPress={() => navigation.navigate('TokenBalance' as never)}
            activeOpacity={0.7}
          >
            <Text style={styles.tokenPurchaseButtonText}>
              {tokenLabel}購入
            </Text>
          </TouchableOpacity>
        )}
      </View>
    );
  };

  /**
   * ログアウトボタンをレンダリング
   * 
   * 注意: navigation.reset()は不要。
   * AuthContextのisAuthenticatedがfalseに変更されることで
   * AppNavigatorが自動的に未認証画面スタックに切り替わる。
   */
  const handleLogout = async () => {
    try {
      console.log('[DrawerContent] Starting logout...');
      await logout();
      console.log('[DrawerContent] Logout completed, AppNavigator will handle navigation');
      // navigation.reset()は不要 - AuthContext状態変更でAppNavigatorが自動切替
    } catch (error) {
      console.error('[DrawerContent] Logout failed:', error);
    }
  };

  return (
    <DrawerContentScrollView
      {...props}
      contentContainerStyle={styles.scrollViewContent}
    >
      {/* ヘッダー: ロゴ + ユーザー情報 */}
      <View style={styles.header}>
        <Text style={styles.logo}>MyTeacher</Text>
        {user && (
          <Text style={styles.userName}>{user.name || user.email}</Text>
        )}
      </View>

      {/* 管理者: 一般メニュー表示切替ボタン */}
      {user?.isAdmin && (
        <View style={styles.adminToggleContainer}>
          <Text style={styles.adminToggleLabel}>一般メニュー</Text>
          <TouchableOpacity
            style={styles.adminToggleButton}
            onPress={() => setShowGeneralMenu(!showGeneralMenu)}
            activeOpacity={0.7}
          >
            <Ionicons
              name={showGeneralMenu ? 'eye-outline' : 'eye-off-outline'}
              size={getFontSize(18, width, themeType)}
              color="#4B5563"
            />
          </TouchableOpacity>
        </View>
      )}

      {/* 一般ユーザーメニュー */}
      {showGeneralMenu && (
        <View style={styles.menuSection}>
          {generalMenuItems.map((item, index) => renderMenuItem(item, index))}
        </View>
      )}

      {/* 管理者メニュー（Phase 2では非表示） */}
      {user?.isAdmin && (
        <View style={styles.menuSection}>
          <Text style={styles.menuSectionTitle}>管理者メニュー</Text>
          <Text style={styles.comingSoonText}>Phase 3で実装予定</Text>
        </View>
      )}

      {/* トークン残高表示（下部固定） */}
      {renderTokenBalance()}

      {/* ログアウトボタン */}
      <TouchableOpacity
        style={styles.logoutButton}
        onPress={handleLogout}
        activeOpacity={0.7}
      >
        <Ionicons
          name="log-out-outline"
          size={getFontSize(20, width, themeType)}
          color="#EF4444"
        />
        <Text style={styles.logoutButtonText}>ログアウト</Text>
      </TouchableOpacity>
    </DrawerContentScrollView>
  );
}

/**
 * スタイル定義
 * 
 * レスポンシブデザインガイドラインに準拠:
 * - getFontSize: デバイスサイズに応じた動的フォントサイズ
 * - getSpacing: デバイスサイズに応じた動的余白
 * - getBorderRadius: デバイスサイズに応じた動的角丸
 * 
 * @param width - 画面幅
 * @param theme - テーマタイプ（'adult' | 'child'）
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) =>
  StyleSheet.create({
    scrollViewContent: {
      flexGrow: 1,
      backgroundColor: colors.background,
    },
    header: {
      paddingHorizontal: getSpacing(16, width),
      paddingVertical: getSpacing(20, width),
      borderBottomWidth: 1,
      borderBottomColor: colors.border.default,
    },
    logo: {
      fontSize: getFontSize(24, width, theme),
      fontWeight: 'bold',
      color: accent.primary,
      marginBottom: getSpacing(8, width),
    },
    userName: {
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
    },
    adminToggleContainer: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      paddingHorizontal: getSpacing(16, width),
      paddingVertical: getSpacing(12, width),
      backgroundColor: '#F9FAFB',
    },
    adminToggleLabel: {
      fontSize: getFontSize(12, width, theme),
      fontWeight: '600',
      color: '#6B7280',
      textTransform: 'uppercase',
    },
    adminToggleButton: {
      padding: getSpacing(4, width),
      borderRadius: getBorderRadius(6, width),
      backgroundColor: '#FFFFFF',
    },
    menuSection: {
      paddingVertical: getSpacing(8, width),
    },
    menuSectionTitle: {
      fontSize: getFontSize(12, width, theme),
      fontWeight: '600',
      color: '#6B7280',
      textTransform: 'uppercase',
      paddingHorizontal: getSpacing(16, width),
      paddingVertical: getSpacing(12, width),
    },
    menuItem: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: getSpacing(16, width),
      paddingVertical: getSpacing(12, width),
      marginHorizontal: getSpacing(8, width),
      marginVertical: getSpacing(2, width),
      borderRadius: getBorderRadius(12, width),
    },
    menuItemActive: {
      backgroundColor: '#EFF6FF',
    },
    menuIcon: {
      marginRight: getSpacing(12, width),
    },
    menuLabel: {
      flex: 1,
      fontSize: getFontSize(14, width, theme),
      fontWeight: '500',
      color: '#4B5563',
    },
    menuLabelActive: {
      color: '#59B9C6',
      fontWeight: '600',
    },
    badge: {
      minWidth: getSpacing(24, width),
      height: getSpacing(24, width),
      borderRadius: getBorderRadius(12, width),
      backgroundColor: accent.primary,
      justifyContent: 'center',
      alignItems: 'center',
      paddingHorizontal: getSpacing(6, width),
    },
    badgePulse: {
      backgroundColor: colors.status.warning,
      // TODO: アニメーション実装
    },
    badgeText: {
      fontSize: getFontSize(11, width, theme),
      fontWeight: 'bold',
      color: '#FFFFFF',
    },
    badgeDot: {
      width: getSpacing(8, width),
      height: getSpacing(8, width),
      borderRadius: getBorderRadius(4, width),
      backgroundColor: colors.status.error,
    },
    tokenBalanceContainer: {
      marginTop: 'auto', // 下部固定
      paddingHorizontal: getSpacing(16, width),
      paddingVertical: getSpacing(16, width),
      backgroundColor: colors.surface,
      borderTopWidth: 1,
      borderTopColor: colors.border.default,
    },
    tokenBalanceHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      marginBottom: getSpacing(8, width),
    },
    tokenBalanceTitle: {
      fontSize: getFontSize(12, width, theme),
      fontWeight: '600',
      color: colors.text.secondary,
      marginLeft: getSpacing(8, width),
    },
    tokenBalanceTotal: {
      fontSize: getFontSize(24, width, theme),
      fontWeight: 'bold',
      color: colors.text.primary,
      marginBottom: getSpacing(4, width),
    },
    tokenBalanceDetail: {
      marginBottom: getSpacing(12, width),
    },
    tokenBalanceDetailText: {
      fontSize: getFontSize(12, width, theme),
      color: colors.text.secondary,
    },
    tokenPurchaseButton: {
      backgroundColor: accent.primary,
      paddingVertical: getSpacing(8, width),
      paddingHorizontal: getSpacing(16, width),
      borderRadius: getBorderRadius(8, width),
      alignItems: 'center',
    },
    tokenPurchaseButtonText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: '#FFFFFF',
    },
    logoutButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      paddingVertical: getSpacing(12, width),
      marginHorizontal: getSpacing(16, width),
      marginVertical: getSpacing(16, width),
      borderRadius: getBorderRadius(8, width),
      borderWidth: 1,
      borderColor: colors.status.error + '40',
      backgroundColor: colors.status.error + '10',
    },
    logoutButtonText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: colors.status.error,
      marginLeft: getSpacing(8, width),
    },
    comingSoonText: {
      fontSize: getFontSize(12, width, theme),
      color: colors.text.tertiary,
      fontStyle: 'italic',
      paddingHorizontal: getSpacing(16, width),
    },
  });
