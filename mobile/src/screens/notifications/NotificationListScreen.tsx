import { useState, useCallback } from 'react';
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
import { useNotifications } from '../../hooks/useNotifications';
import { Notification, getNotificationTypeLabel } from '../../types/notification.types';
import { useTheme } from '../../contexts/ThemeContext';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

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
          <ActivityIndicator size="large" color="#59B9C6" />
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
        <ActivityIndicator size="small" color="#59B9C6" />
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
            <View style={styles.unreadBadge}>
              <Text style={styles.unreadBadgeText}>{unreadCount}</Text>
            </View>
          )}
        </View>

        {/* すべて既読ボタン */}
        {unreadCount > 0 && (
          <TouchableOpacity
            style={styles.markAllReadButton}
            onPress={handleMarkAllAsRead}
            accessibilityLabel="すべて既読にする"
          >
            <Text style={styles.markAllReadButtonText}>
              {theme === 'child' ? 'すべてよんだ' : 'すべて既読'}
            </Text>
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
          placeholderTextColor="#9CA3AF"
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
            tintColor="#59B9C6"
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

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  headerTitle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  headerTitleText: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1F2937',
  },
  unreadBadge: {
    backgroundColor: '#59B9C6',
    borderRadius: 12,
    paddingHorizontal: 8,
    paddingVertical: 2,
    minWidth: 24,
    alignItems: 'center',
  },
  unreadBadgeText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: 'bold',
  },
  markAllReadButton: {
    backgroundColor: '#59B9C6',
    borderRadius: 8,
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  markAllReadButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
  searchContainer: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  searchInput: {
    backgroundColor: '#F3F4F6',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 14,
    color: '#1F2937',
  },
  errorContainer: {
    backgroundColor: '#FEE2E2',
    paddingHorizontal: 16,
    paddingVertical: 12,
    marginHorizontal: 16,
    marginTop: 12,
    borderRadius: 8,
  },
  errorText: {
    color: '#DC2626',
    fontSize: 14,
  },
  notificationItem: {
    flexDirection: 'row',
    backgroundColor: '#FFFFFF',
    padding: 16,
    marginHorizontal: 16,
    marginVertical: 6,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  notificationItemUnread: {
    backgroundColor: '#EFF6FF',
    borderLeftWidth: 4,
    borderLeftColor: '#59B9C6',
  },
  notificationIndicator: {
    width: 12,
    alignItems: 'center',
    justifyContent: 'flex-start',
    paddingTop: 4,
  },
  unreadDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#59B9C6',
  },
  notificationContent: {
    flex: 1,
    marginLeft: 12,
  },
  priorityBadge: {
    backgroundColor: '#FEE2E2',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
    alignSelf: 'flex-start',
    marginBottom: 6,
  },
  priorityText: {
    color: '#DC2626',
    fontSize: 11,
    fontWeight: '700',
  },
  notificationTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 4,
  },
  notificationMessage: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 8,
  },
  notificationMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  notificationCategory: {
    fontSize: 12,
    color: '#59B9C6',
    fontWeight: '600',
  },
  notificationDate: {
    fontSize: 12,
    color: '#9CA3AF',
  },
  emptyListContent: {
    flexGrow: 1,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 80,
  },
  emptyIcon: {
    fontSize: 64,
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 8,
  },
  emptyDescription: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
  },
  loadingFooter: {
    paddingVertical: 20,
    alignItems: 'center',
  },
});
