/**
 * GroupManagementScreen - グループ管理画面
 * 
 * 機能:
 * - グループ情報表示
 * - メンバー一覧表示
 * - タスクスケジュール管理への導線
 * - テーマ対応UI（adult/child）
 * 
 * ナビゲーション階層:
 * Profile → GroupManagement → ScheduledTaskList
 */

import { useState, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ScrollView,
  RefreshControl,
  StyleSheet,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import { useAuth } from '../../contexts/AuthContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow, getHeaderTitleProps } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * GroupManagementScreen コンポーネント
 */
export const GroupManagementScreen: React.FC = () => {
  const navigation = useNavigation();
  const { theme } = useTheme();
  const { user } = useAuth();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';

  // グループ情報（userから取得）
  const groupId = user?.group_id;
  const groupName = user?.group?.name || 'マイグループ';
  const isGroupMaster = user?.group_edit_flg ?? false;
  const [refreshing, setRefreshing] = useState(false);

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  /**
   * Pull-to-Refresh処理
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    // ユーザー情報は自動的に更新されるので、少し待ってから終了
    setTimeout(() => {
      setRefreshing(false);
    }, 500);
  }, []);

  /**
   * スケジュールタスク管理画面へ遷移
   */
  const navigateToScheduledTasks = () => {
    if (!groupId) {
      return;
    }
    (navigation as any).navigate('ScheduledTaskList', { groupId });
  };

  /**
   * メンバー管理画面へ遷移（将来実装）
   */
  const navigateToMemberManagement = () => {
    // TODO: Phase 2.B-8 メンバー管理画面実装時に追加
    console.log('[GroupManagementScreen] Member management not implemented yet');
  };

  /**
   * グループ設定画面へ遷移（将来実装）
   */
  const navigateToGroupSettings = () => {
    // TODO: 将来のグループ設定画面実装時に追加
    console.log('[GroupManagementScreen] Group settings not implemented yet');
  };

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={onRefresh}
          colors={['#4F46E5']}
          tintColor="#4F46E5"
        />
      }
    >
      <View style={styles.content}>
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={styles.title} {...getHeaderTitleProps()}>
            {theme === 'child' ? 'グループかんり' : 'グループ管理'}
          </Text>
        </View>

        {/* グループ情報カード */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>
            {theme === 'child' ? 'グループじょうほう' : 'グループ情報'}
          </Text>
          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>
              {theme === 'child' ? 'グループめい' : 'グループ名'}
            </Text>
            <Text style={styles.infoValue}>{groupName}</Text>
          </View>
          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>
              {theme === 'child' ? 'やくわり' : '役割'}
            </Text>
            <Text style={styles.infoValue}>
              {isGroupMaster
                ? theme === 'child'
                  ? 'グループマスター'
                  : 'グループマスター'
                : theme === 'child'
                ? 'メンバー'
                : 'メンバー'}
            </Text>
          </View>
        </View>

        {/* 管理メニュー */}
        <View style={styles.menuSection}>
          <Text style={styles.sectionTitle}>
            {theme === 'child' ? 'かんりメニュー' : '管理メニュー'}
          </Text>

          {/* タスクスケジュール管理 */}
          <TouchableOpacity
            style={styles.menuItem}
            onPress={navigateToScheduledTasks}
            disabled={!groupId}
            accessibilityLabel={
              theme === 'child'
                ? 'タスクスケジュールかんり'
                : 'タスクスケジュール管理'
            }
          >
            <View style={styles.menuItemContent}>
              <View style={styles.menuItemLeft}>
                <Text style={styles.menuIcon}>📅</Text>
                <View>
                  <Text style={styles.menuItemTitle}>
                    {theme === 'child'
                      ? 'タスクスケジュールかんり'
                      : 'タスクスケジュール管理'}
                  </Text>
                  <Text style={styles.menuItemDescription}>
                    {theme === 'child'
                      ? 'ていきてきなタスクをせっていするよ'
                      : '定期的に実行するタスクを設定'}
                  </Text>
                </View>
              </View>
              <Text style={styles.menuArrow}>›</Text>
            </View>
          </TouchableOpacity>

          {/* メンバー管理（グループマスターのみ、将来実装） */}
          {isGroupMaster && (
            <TouchableOpacity
              style={[styles.menuItem, styles.menuItemDisabled]}
              onPress={navigateToMemberManagement}
              disabled={true}
              accessibilityLabel={theme === 'child' ? 'メンバーかんり' : 'メンバー管理'}
            >
              <View style={styles.menuItemContent}>
                <View style={styles.menuItemLeft}>
                  <Text style={styles.menuIcon}>👥</Text>
                  <View>
                    <Text style={styles.menuItemTitle}>
                      {theme === 'child' ? 'メンバーかんり' : 'メンバー管理'}
                    </Text>
                    <Text style={styles.menuItemDescription}>
                      {theme === 'child'
                        ? 'メンバーをついかしたりけしたりするよ'
                        : 'メンバーの追加・削除・権限管理'}
                    </Text>
                    <Text style={styles.comingSoonBadge}>
                      {theme === 'child' ? 'じゅんびちゅう' : '準備中'}
                    </Text>
                  </View>
                </View>
                <Text style={styles.menuArrow}>›</Text>
              </View>
            </TouchableOpacity>
          )}

          {/* グループ設定（グループマスターのみ、将来実装） */}
          {isGroupMaster && (
            <TouchableOpacity
              style={[styles.menuItem, styles.menuItemDisabled]}
              onPress={navigateToGroupSettings}
              disabled={true}
              accessibilityLabel={
                theme === 'child' ? 'グループせってい' : 'グループ設定'
              }
            >
              <View style={styles.menuItemContent}>
                <View style={styles.menuItemLeft}>
                  <Text style={styles.menuIcon}>⚙️</Text>
                  <View>
                    <Text style={styles.menuItemTitle}>
                      {theme === 'child' ? 'グループせってい' : 'グループ設定'}
                    </Text>
                    <Text style={styles.menuItemDescription}>
                      {theme === 'child'
                        ? 'グループのせっていをかえるよ'
                        : 'グループ名や基本設定の変更'}
                    </Text>
                    <Text style={styles.comingSoonBadge}>
                      {theme === 'child' ? 'じゅんびちゅう' : '準備中'}
                    </Text>
                  </View>
                </View>
                <Text style={styles.menuArrow}>›</Text>
              </View>
            </TouchableOpacity>
          )}
        </View>

        {/* 説明セクション */}
        <View style={styles.helpSection}>
          <Text style={styles.helpTitle}>
            {theme === 'child' ? 'グループかんりについて' : 'グループ管理について'}
          </Text>
          <Text style={styles.helpText}>
            {theme === 'child'
              ? 'グループマスターは、メンバーをついかしたり、タスクスケジュールをせっていしたりできるよ。'
              : 'グループマスターは、メンバーの管理やタスクスケジュールの設定ができます。'}
          </Text>
          {!isGroupMaster && (
            <Text style={styles.helpText}>
              {theme === 'child'
                ? 'いまはメンバーなので、タスクスケジュールをみることができるよ。'
                : '現在はメンバー権限のため、タスクスケジュールの閲覧のみ可能です。'}
            </Text>
          )}
        </View>
      </View>
    </ScrollView>
  );
};

/**
 * レスポンシブスタイル生成関数
 * 
 * @param width - 画面幅
 * @param theme - テーマ (adult | child)
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  content: {
    padding: getSpacing(16, width),
  },
  header: {
    marginBottom: getSpacing(24, width),
  },
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: '#1e293b',
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(24, width),
    ...getShadow(2),
  },
  cardTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#475569',
    marginBottom: getSpacing(16, width),
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: getSpacing(12, width),
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  infoLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#64748b',
  },
  infoValue: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: '#1e293b',
  },
  menuSection: {
    marginBottom: getSpacing(24, width),
  },
  sectionTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: '#1e293b',
    marginBottom: getSpacing(12, width),
  },
  menuItem: {
    backgroundColor: '#fff',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(12, width),
    ...getShadow(1),
  },
  menuItemDisabled: {
    opacity: 0.6,
  },
  menuItemContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  menuItemLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  menuIcon: {
    fontSize: getFontSize(24, width, theme),
    marginRight: getSpacing(12, width),
  },
  menuItemTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#1e293b',
    marginBottom: getSpacing(4, width),
  },
  menuItemDescription: {
    fontSize: getFontSize(13, width, theme),
    color: '#64748b',
  },
  comingSoonBadge: {
    fontSize: getFontSize(11, width, theme),
    color: '#f59e0b',
    fontWeight: '600',
    marginTop: getSpacing(4, width),
  },
  menuArrow: {
    fontSize: getFontSize(24, width, theme),
    color: '#cbd5e1',
    fontWeight: '300',
  },
  helpSection: {
    backgroundColor: '#f0f9ff',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    borderWidth: 1,
    borderColor: '#bae6fd',
  },
  helpTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#0284c7',
    marginBottom: getSpacing(8, width),
  },
  helpText: {
    fontSize: getFontSize(14, width, theme),
    color: '#0369a1',
    lineHeight: getFontSize(20, width, theme),
    marginBottom: getSpacing(8, width),
  },
});

export default GroupManagementScreen;
