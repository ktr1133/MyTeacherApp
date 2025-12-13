import React, { useState, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  StyleSheet,
  Alert,
  ActivityIndicator,
  TextInput,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useNotifications } from '../../hooks/useNotifications';
import { Notification, getNotificationTypeLabel } from '../../types/notification.types';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

type RootStackParamList = {
  NotificationList: undefined;
  NotificationDetail: { notificationId: number };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;

/**
 * 通知一覧画面
 * 
 * Phase 2.B-5 Step 2でLaravel API完全準拠に更新
 * 
 * 機能:
 * - 通知一覧表示（ページネーション対応、1ページ20件）
 * - 未読件数バッジ表示
 * - すべて既読ボタン
 * - 検索機能（デバウンス処理300ms）
 * - Pull-to-Refresh
 * - 無限スクロール対応
 * - 通知タップで既読化 + 詳細画面遷移
 * 
 * Web版対応:
 * - resources/views/notifications/index.blade.php に相当
 * - resources/views/dashboard/partials/header.blade.php L111-128（通知ボタン）
 */
export default function NotificationListScreen() {
  const navigation = useNavigation<NavigationProp>();
  const { theme } = useTheme();
  const { colors, accent } = useThemedColors();
  const {
    notifications,
    unreadCount,
    loading,
    error,
    hasMore,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    searchNotifications,
    loadMore,
    refresh,
  } = useNotifications(true); // ポーリング有効化（30秒間隔）

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  const [searchQuery, setSearchQuery] = useState('');
  const [searchTimeout, setSearchTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isRefreshing, setIsRefreshing] = useState(false);

  /**
   * 検索クエリ変更時のデバウンス処理
   */
  const handleSearchChange = useCallback(
    (text: string) => {
      setSearchQuery(text);

      // 既存のタイマーをクリア
      if (searchTimeout) {
        clearTimeout(searchTimeout);
      }

      // 300ms後に検索実行
      const timer = setTimeout(() => {
        if (text.trim()) {
          searchNotifications(text.trim(), 1);
        } else {
          fetchNotifications(1);
        }
      }, 300);

      setSearchTimeout(timer);
    },
    [searchTimeout, searchNotifications, fetchNotifications]
  );

  /**
   * Pull-to-Refresh
   */
  const handleRefresh = useCallback(async () => {
    setIsRefreshing(true);
    await refresh();
    setIsRefreshing(false);
  }, [refresh]);

  /**
   * 通知タップ時の処理
   */
  const handleNotificationPress = useCallback(
    async (notification: Notification) => {
      // 未読の場合は既読にする（楽観的更新はmarkAsRead内で実行）
      if (!notification.is_read) {
        await markAsRead(notification.id);
      }

      // 詳細画面へ遷移
      navigation.navigate('NotificationDetail', {
        notificationId: notification.id,
      });
    },
    [markAsRead, navigation]
  );

  /**
   * すべて既読ボタン押下時の処理
   */
  const handleMarkAllAsRead = useCallback(() => {
    Alert.alert(
      theme === 'child' ? 'かくにん' : '確認',
      theme === 'child'
        ? 'すべてのおしらせをよんだことにするよ'
        : 'すべての通知を既読にしますか？',
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'OK' : '既読にする',
          onPress: markAllAsRead,
        },
      ]
    );
  }, [theme, markAllAsRead]);

  /**
   * リスト末尾到達時の処理（次ページ読み込み）
   */
  const handleEndReached = useCallback(() => {
    if (!loading && hasMore) {
      loadMore();
    }
  }, [loading, hasMore, loadMore]);

  /**
   * 通知アイテムのレンダリング
   */
  const renderNotificationItem = useCallback(
    ({ item }: { item: Notification }) => {
      const isUnread = !item.is_read;

      return (
        <TouchableOpacity
          style={[
            styles.notificationItem,
            isUnread && styles.notificationItemUnread,
          ]}
          onPress={() => handleNotificationPress(item)}
          accessibilityLabel={`通知: ${item.template?.title || '通知'}`}
          accessibilityHint="タップして詳細を表示"
        >
          {/* 未読インジケーター */}
          <View style={styles.notificationIndicator}>
            {isUnread && <View style={styles.unreadDot} />}
          </View>

          {/* 通知内容 */}
          <View style={styles.notificationContent}>
            {/* 優先度バッジ (Laravel: 'info' | 'normal' | 'important') */}
            {item.template?.priority === 'important' && (
              <View style={styles.priorityBadge}>
                <Text style={styles.priorityText}>重要</Text>
              </View>
            )}

            {/* タイトル */}
            <Text
              style={styles.notificationTitle}
              numberOfLines={2}
            >
              {item.template?.title || '通知'}
            </Text>

            {/* メッセージ */}
            <Text
              style={styles.notificationMessage}
              numberOfLines={2}
            >
              {item.template?.content || '内容がありません'}
            </Text>

            {/* カテゴリと日時 */}
            <View style={styles.notificationMeta}>
              {item.template?.category && (
                <Text style={styles.notificationCategory}>
                  {getNotificationTypeLabel(item.template.category)}
                </Text>
              )}
              <Text style={styles.notificationDate}>
                {formatDate(item.created_at, theme)}
              </Text>
            </View>
          </View>
        </TouchableOpacity>
      );
    },
    [theme, handleNotificationPress]
  );

  /**
   * 空リストのレンダリング
   */
  const renderEmptyList = useCallback(() => {
    if (loading && notifications.length === 0) {
      return (
        <View style={styles.emptyContainer}>
          <ActivityIndicator size="large" color={accent.primary as string} />
        </View>
      );
    }

    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyIcon}>🔔</Text>
        <Text style={styles.emptyTitle}>
          {theme === 'child' ? 'おしらせはないよ' : '通知はありません'}
        </Text>
        <Text style={styles.emptyDescription}>
          {theme === 'child'
            ? 'あたらしいおしらせがくるとここにでるよ'
            : '新しい通知があるとここに表示されます'}
        </Text>
      </View>
    );
  }, [loading, notifications.length, theme]);

  /**
   * フッターのレンダリング（ページネーション読み込み中）
   */
  const renderFooter = useCallback(() => {
    if (!loading || notifications.length === 0) {
      return null;
    }

    return (
      <View style={styles.loadingFooter}>
        <ActivityIndicator size="small" color={accent.primary as string} />
      </View>
    );
  }, [loading, notifications.length]);

  return (
    <View style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <View style={styles.headerTitle}>
          <Text style={styles.headerTitleText}>
            {theme === 'child' ? 'おしらせ' : 'お知らせ'}
          </Text>
          {unreadCount > 0 && (
            <LinearGradient
              colors={[accent.primary, accent.primary] as const}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.unreadBadge}
            >
              <Text style={styles.unreadBadgeText}>{unreadCount}</Text>
            </LinearGradient>
          )}
        </View>

        {/* すべて既読ボタン */}
        {unreadCount > 0 && (
          <TouchableOpacity
            onPress={handleMarkAllAsRead}
            accessibilityLabel="すべて既読にする"
          >
            <LinearGradient
              colors={[accent.primary, accent.primary] as const}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.markAllReadButton}
            >
              <Text style={styles.markAllReadButtonText}>
                {theme === 'child' ? 'すべてよんだ' : 'すべて既読'}
              </Text>
            </LinearGradient>
          </TouchableOpacity>
        )}
      </View>

      {/* 検索バー */}
      <View style={styles.searchContainer}>
        <TextInput
          style={styles.searchInput}
          placeholder={
            theme === 'child' ? 'おしらせをさがす...' : '通知を検索...'
          }
          placeholderTextColor={colors.text.disabled as string}
          value={searchQuery}
          onChangeText={handleSearchChange}
          autoCapitalize="none"
          accessibilityLabel="検索フィールド"
        />
      </View>

      {/* エラー表示 */}
      {error && (
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>{error}</Text>
        </View>
      )}

      {/* 通知一覧 */}
      <FlatList
        data={notifications}
        renderItem={renderNotificationItem}
        keyExtractor={(item) => item.id.toString()}
        ListEmptyComponent={renderEmptyList}
        ListFooterComponent={renderFooter}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={handleRefresh}
            tintColor={accent.primary as string}
          />
        }
        onEndReached={handleEndReached}
        onEndReachedThreshold={0.5}
        contentContainerStyle={
          notifications.length === 0 ? styles.emptyListContent : undefined
        }
      />
    </View>
  );
};

/**
 * 日時フォーマット
 */
const formatDate = (dateString: string, theme: 'adult' | 'child'): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diff = now.getTime() - date.getTime();
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const days = Math.floor(hours / 24);

  if (days === 0) {
    if (hours === 0) {
      return theme === 'child' ? 'いまさっき' : 'たった今';
    }
    return theme === 'child' ? `${hours}じかんまえ` : `${hours}時間前`;
  }

  if (days < 7) {
    return theme === 'child' ? `${days}にちまえ` : `${days}日前`;
  }

  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const day = date.getDate();

  return `${year}/${month}/${day}`;
};

const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.default,
  },
  headerTitle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: getSpacing(8, width),
  },
  headerTitleText: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
  },
  unreadBadge: {
    borderRadius: getBorderRadius(12, width),
    paddingHorizontal: getSpacing(8, width),
    paddingVertical: getSpacing(2, width),
    minWidth: getSpacing(24, width),
    alignItems: 'center',
    overflow: 'hidden', // LinearGradient用
  },
  unreadBadgeText: {
    color: '#FFFFFF', // LinearGradient上のテキストは常に白
    fontSize: getFontSize(12, width, theme),
    fontWeight: 'bold',
  },
  markAllReadButton: {
    borderRadius: getBorderRadius(8, width),
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(8, width),
    overflow: 'hidden', // LinearGradient用
  },
  markAllReadButtonText: {
    color: '#FFFFFF', // LinearGradient上のテキストは常に白
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
  },
  searchContainer: {
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.default,
  },
  searchInput: {
    backgroundColor: colors.surface,
    borderRadius: getBorderRadius(8, width),
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(10, width),
    fontSize: getFontSize(14, width, theme),
    color: colors.text.primary,
  },
  errorContainer: {
    backgroundColor: colors.status.error + '20', // 透明度20%
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    marginHorizontal: getSpacing(16, width),
    marginTop: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
  },
  errorText: {
    color: colors.status.error,
    fontSize: getFontSize(14, width, theme),
  },
  notificationItem: {
    flexDirection: 'row',
    backgroundColor: colors.card,
    padding: getSpacing(16, width),
    marginHorizontal: getSpacing(16, width),
    marginVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(12, width),
    ...getShadow(2),
  },
  notificationItemUnread: {
    backgroundColor: colors.card,
    borderLeftWidth: 4,
    borderLeftColor: accent.primary,
  },
  notificationIndicator: {
    width: getSpacing(12, width),
    alignItems: 'center',
    justifyContent: 'flex-start',
    paddingTop: getSpacing(4, width),
  },
  unreadDot: {
    width: getSpacing(8, width),
    height: getSpacing(8, width),
    borderRadius: getBorderRadius(4, width),
    backgroundColor: accent.primary,
  },
  notificationContent: {
    flex: 1,
    marginLeft: getSpacing(12, width),
  },
  priorityBadge: {
    backgroundColor: colors.status.error + '20', // 透明度20%
    paddingHorizontal: getSpacing(8, width),
    paddingVertical: getSpacing(2, width),
    borderRadius: getBorderRadius(4, width),
    alignSelf: 'flex-start',
    marginBottom: getSpacing(6, width),
  },
  priorityText: {
    color: colors.status.error,
    fontSize: getFontSize(11, width, theme),
    fontWeight: '700',
  },
  notificationTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    marginBottom: getSpacing(4, width),
  },
  notificationMessage: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(8, width),
  },
  notificationMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: getSpacing(8, width),
  },
  notificationCategory: {
    fontSize: getFontSize(12, width, theme),
    color: accent.primary,
    fontWeight: '600',
  },
  notificationDate: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.tertiary,
  },
  emptyListContent: {
    flexGrow: 1,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: getSpacing(80, width),
  },
  emptyIcon: {
    fontSize: getFontSize(64, width, theme),
    marginBottom: getSpacing(16, width),
  },
  emptyTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    marginBottom: getSpacing(8, width),
  },
  emptyDescription: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    textAlign: 'center',
  },
  loadingFooter: {
    paddingVertical: getSpacing(20, width),
    alignItems: 'center',
  },
});
