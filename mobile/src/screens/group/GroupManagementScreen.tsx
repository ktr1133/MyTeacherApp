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

import {
  View,
  Text,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import { useAuth } from '../../contexts/AuthContext';

/**
 * GroupManagementScreen コンポーネント
 */
export const GroupManagementScreen: React.FC = () => {
  const navigation = useNavigation();
  const { theme } = useTheme();
  const { user } = useAuth();

  // グループ情報（userから取得）
  const groupId = user?.group_id;
  const groupName = 'マイグループ'; // TODO: グループ名取得APIを実装後に修正
  const isGroupMaster = (user as any)?.group_edit_flg ?? false;

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
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={styles.title}>
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

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  content: {
    padding: 16,
  },
  header: {
    marginBottom: 24,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1e293b',
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#475569',
    marginBottom: 16,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  infoLabel: {
    fontSize: 14,
    color: '#64748b',
  },
  infoValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1e293b',
  },
  menuSection: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1e293b',
    marginBottom: 12,
  },
  menuItem: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 1,
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
    fontSize: 24,
    marginRight: 12,
  },
  menuItemTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1e293b',
    marginBottom: 4,
  },
  menuItemDescription: {
    fontSize: 13,
    color: '#64748b',
  },
  comingSoonBadge: {
    fontSize: 11,
    color: '#f59e0b',
    fontWeight: '600',
    marginTop: 4,
  },
  menuArrow: {
    fontSize: 24,
    color: '#cbd5e1',
    fontWeight: '300',
  },
  helpSection: {
    backgroundColor: '#f0f9ff',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#bae6fd',
  },
  helpTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#0284c7',
    marginBottom: 8,
  },
  helpText: {
    fontSize: 14,
    color: '#0369a1',
    lineHeight: 20,
    marginBottom: 8,
  },
});

export default GroupManagementScreen;
