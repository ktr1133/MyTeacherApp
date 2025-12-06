import React, { useState, useCallback } from 'react';
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
import { Notification } from '../../services/notification.service';
import { useTheme } from '../../contexts/ThemeContext';
import { NativeStackScreenProps } from '@react-navigation/native-stack';

type RootStackParamList = {
  NotificationList: undefined;
  NotificationDetail: { notificationId: number };
};

type Props = NativeStackScreenProps<RootStackParamList, 'NotificationList'>;

/**
 * 通知一覧画面
 * 
 * 機能:
 * - 通知一覧表示（ページネーション対応）
 * - 未読件数バッジ表示
 * - すべて既読ボタン
 * - 検索機能（デバウンス処理付き）
 * - Pull-to-Refresh
 * - 通知タップで詳細画面へ遷移
 * 
 * Web版対応:
 * - resources/views/notifications/index.blade.php に相当
 * - resources/views/dashboard/partials/header.blade.php L111-128（通知ボタン）
 */
export const NotificationListScreen: React.FC<Props> = ({ navigation }) => {
  const { theme } = useTheme();
  const {
    notifications,
    unreadCount,
    pagination,
    isLoading,
    isRefreshing,
    error,
    refreshNotifications,
    loadMore,
    markAsRead,
    markAllAsRead,
    searchNotifications,
  } = useNotifications();

  const [searchQuery, setSearchQuery] = useState('');
  const [searchTimeout, setSearchTimeout] = useState<NodeJS.Timeout | null>(null);

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
          refreshNotifications();
        }
      }, 300);

      setSearchTimeout(timer);
    },
    [searchTimeout, searchNotifications, refreshNotifications]
  );

  /**
   * 通知タップ時の処理
   */
  const handleNotificationPress = useCallback(
    async (notification: Notification) => {
      // 未読の場合は既読にする
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
    if (
      !isLoading &&
      pagination &&
      pagination.current_page < pagination.last_page
    ) {
      loadMore();
    }
  }, [isLoading, pagination, loadMore]);

  /**
   * 通知アイテムのレンダリング
   */
  const renderNotificationItem = useCallback(
    ({ item }: { item: Notification }) => {
      const isDeleted = !item.template;

      return (
        <TouchableOpacity
          style={[
            styles.notificationItem,
            !item.is_read && styles.notificationItemUnread,
          ]}
          onPress={() => handleNotificationPress(item)}
          accessibilityLabel={`通知: ${isDeleted ? '削除された通知' : item.title}`}
          accessibilityHint="タップして詳細を表示"
        >
          {/* 未読インジケーター */}
          <View style={styles.notificationIndicator}>
            {!item.is_read && <View style={styles.unreadDot} />}
          </View>

          {/* 通知内容 */}
          <View style={styles.notificationContent}>
            {/* タイトル */}
            <Text
              style={[
                styles.notificationTitle,
                isDeleted && styles.notificationTitleDeleted,
              ]}
              numberOfLines={1}
            >
              {isDeleted
                ? theme === 'child'
                  ? '[けされたおしらせ]'
                  : '[削除された通知]'
                : item.template?.title || item.title}
            </Text>

            {/* 送信者・日時 */}
            <View style={styles.notificationMeta}>
              {item.template?.sender && (
                <Text style={styles.notificationSender}>
                  {theme === 'child' ? 'せんせい' : '管理者'}:{' '}
                  {item.template.sender.username}
                </Text>
              )}
              <Text style={styles.notificationDate}>
                {formatDate(item.created_at, theme)}
              </Text>
            </View>

            {/* 優先度バッジ */}
            {item.priority === 'important' && (
              <View style={styles.priorityBadge}>
                <Text style={styles.priorityBadgeText}>
                  {theme === 'child' ? 'じゅうよう' : '重要'}
                </Text>
              </View>
            )}
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
    if (isLoading) {
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
  }, [isLoading, theme]);

  /**
   * フッターのレンダリング（ページネーション読み込み中）
   */
  const renderFooter = useCallback(() => {
    if (!isLoading || notifications.length === 0) {
      return null;
    }

    return (
      <View style={styles.loadingFooter}>
        <ActivityIndicator size="small" color="#59B9C6" />
      </View>
    );
  }, [isLoading, notifications.length]);

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
            onRefresh={refreshNotifications}
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
  notificationTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 4,
  },
  notificationTitleDeleted: {
    color: '#9CA3AF',
  },
  notificationMeta: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 8,
  },
  notificationSender: {
    fontSize: 12,
    color: '#6B7280',
  },
  notificationDate: {
    fontSize: 12,
    color: '#9CA3AF',
  },
  priorityBadge: {
    backgroundColor: '#FEE2E2',
    borderRadius: 4,
    paddingHorizontal: 8,
    paddingVertical: 2,
    alignSelf: 'flex-start',
  },
  priorityBadgeText: {
    color: '#DC2626',
    fontSize: 11,
    fontWeight: '600',
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
