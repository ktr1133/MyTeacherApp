/**
 * ホーム画面（認証後）
 */
import React, { useMemo } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '../contexts/AuthContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../utils/responsive';
import { useChildTheme } from '../hooks/useChildTheme';
import { useThemedColors } from '../hooks/useThemedColors';

export default function HomeScreen() {
  const navigation = useNavigation();
  const { user, logout } = useAuth();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>ようこそ</Text>
        <Text style={styles.userName}>{user?.name}さん</Text>
        
        <View style={styles.infoBox}>
          <Text style={styles.infoLabel}>メールアドレス</Text>
          <Text style={styles.infoValue}>{user?.email}</Text>
        </View>

        <View style={styles.statusBox}>
          <Text style={styles.statusText}>✅ 認証機能実装完了</Text>
          <Text style={styles.statusSubtext}>Phase 2.B-2</Text>
        </View>

        <TouchableOpacity
          style={styles.taskButton}
          onPress={() => navigation.navigate('TaskList' as never)}
        >
          <Text style={styles.taskButtonText}>📋 タスク一覧</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.avatarButton}
          onPress={() => navigation.navigate('AvatarManage' as never)}
        >
          <Text style={styles.avatarButtonText}>👤 アバター管理</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.notificationButton}
          onPress={() => navigation.navigate('NotificationList' as never)}
        >
          <Text style={styles.notificationButtonText}>🔔 通知一覧</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.tagButton}
          onPress={() => navigation.navigate('TagManagement' as never)}
        >
          <Text style={styles.tagButtonText}>🏷️ タグ管理</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.performanceButton}
          onPress={() => navigation.navigate('Performance' as never)}
        >
          <Text style={styles.performanceButtonText}>📊 実績</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.tokenButton}
          onPress={() => navigation.navigate('TokenBalance' as never)}
        >
          <Text style={styles.tokenButtonText}>💰 トークン購入</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.subscriptionButton}
          onPress={() => navigation.navigate('SubscriptionManage' as never)}
        >
          <Text style={styles.subscriptionButtonText}>💳 サブスクリプション管理</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.approvalButton}
          onPress={() => navigation.navigate('PendingApprovals' as never)}
        >
          <Text style={styles.approvalButtonText}>✓ 承認待ち一覧</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.profileButton}
          onPress={() => navigation.navigate('Profile' as never)}
        >
          <Text style={styles.profileButtonText}>プロフィール</Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.logoutButton} onPress={logout}>
          <Text style={styles.logoutButtonText}>ログアウト</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme === 'child' ? '#FFF8E1' : colors.background,
  },
  content: {
    flex: 1,
    padding: getSpacing(24, width),
    justifyContent: 'center',
  },
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    marginBottom: getSpacing(8, width),
  },
  userName: {
    fontSize: getFontSize(32, width, theme),
    fontWeight: 'bold',
    color: accent.primary,
    marginBottom: getSpacing(32, width),
  },
  infoBox: {
    backgroundColor: colors.card,
    padding: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(24, width),
  },
  infoLabel: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(4, width),
  },
  infoValue: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
  },
  statusBox: {
    backgroundColor: colors.status.success + '20',
    padding: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(32, width),
    alignItems: 'center',
  },
  statusText: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
    color: colors.status.success,
    marginBottom: getSpacing(4, width),
  },
  statusSubtext: {
    fontSize: getFontSize(14, width, theme),
    color: colors.status.success,
  },
  taskButton: {
    backgroundColor: '#10b981',
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  taskButtonText: {
    color: '#fff',
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
  },
  avatarButton: {
    backgroundColor: '#EC4899',
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  avatarButtonText: {
    color: '#fff',
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
  },
  notificationButton: {
    backgroundColor: '#59B9C6',
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  notificationButtonText: {
    color: '#fff',
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
  },
  tagButton: {
    backgroundColor: '#8B5CF6',
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  tagButtonText: {
    color: '#fff',
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
  },
  performanceButton: {
    backgroundColor: '#06b6d4',
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  performanceButtonText: {
    color: '#fff',
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
  },
  tokenButton: {
    backgroundColor: '#f97316',
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  tokenButtonText: {
    color: '#fff',
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
  },
  subscriptionButton: {
    backgroundColor: '#6366f1',
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  subscriptionButtonText: {
    color: '#fff',
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
  },
  approvalButton: {
    backgroundColor: '#28a745',
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  approvalButtonText: {
    color: '#fff',
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
  },
  profileButton: {
    backgroundColor: '#3b82f6',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  profileButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#fff',
  },
  logoutButton: {
    backgroundColor: '#ef4444',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    alignItems: 'center',
  },
  logoutButtonText: {
    color: '#fff',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
  },
});
