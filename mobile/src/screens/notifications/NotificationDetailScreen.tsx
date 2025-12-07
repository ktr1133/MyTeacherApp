import { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { useRoute } from '@react-navigation/native';
import type { RouteProp } from '@react-navigation/native';
import { notificationService } from '../../services/notification.service';
import { Notification, getNotificationTypeLabel } from '../../types/notification.types';
import { useTheme } from '../../contexts/ThemeContext';
import { useAuth } from '../../contexts/AuthContext';

type RootStackParamList = {
  NotificationDetail: { notificationId: number };
};

type NotificationDetailRouteProp = RouteProp<RootStackParamList, 'NotificationDetail'>;

/**
 * 通知詳細画面
 * 
 * Phase 2.B-5 Step 2で実装（Laravel API完全準拠）
 * 
 * 機能:
 * - 通知詳細表示
 * - 既読化（自動）
 * - テーマ対応UI
 */
export default function NotificationDetailScreen() {
  const route = useRoute<NotificationDetailRouteProp>();
  const { theme } = useTheme();
  const { isAuthenticated, loading: authLoading } = useAuth();
  const [notification, setNotification] = useState<Notification | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  const { notificationId } = route.params;

  const loadNotification = useCallback(async () => {
    // 認証チェック中は待機
    if (authLoading) {
      console.log('[NotificationDetailScreen] Waiting for auth check...');
      return;
    }

    // 認証状態確認
    if (!isAuthenticated) {
      setError('認証が必要です');
      setLoading(false);
      console.error('[NotificationDetailScreen] Not authenticated');
      return;
    }

    try {
      setLoading(true);
      setError(null);

      console.log('[NotificationDetailScreen] Loading notification:', notificationId);

      // 通知詳細取得
      const response = await notificationService.getNotificationDetail(notificationId);
      console.log('[NotificationDetailScreen] Response:', {
        notification: response.data.notification,
        has_template: response.data.notification.template != null,
      });
      setNotification(response.data.notification);

      // 未読の場合は既読化
      if (!response.data.notification.is_read) {
        await notificationService.markAsRead(notificationId);
        console.log('[NotificationDetailScreen] Marked as read');
      }
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '通知の取得に失敗しました';
      setError(errorMessage);
      console.error('[NotificationDetailScreen] Error:', err);
    } finally {
      setLoading(false);
    }
  }, [notificationId, isAuthenticated, authLoading]);

  useEffect(() => {
    // 認証チェック完了後に実行
    if (!authLoading) {
      loadNotification();
    }
  }, [loadNotification, authLoading]);

  const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'たった今';
    if (minutes < 60) return `${minutes}分前`;
    if (hours < 24) return `${hours}時間前`;
    if (days < 7) return `${days}日前`;

    return date.toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#59B9C6" />
      </View>
    );
  }

  if (error || !notification) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorIcon}>⚠️</Text>
        <Text style={styles.errorText}>{error || '通知が見つかりません'}</Text>
        <TouchableOpacity
          style={styles.retryButton}
          onPress={loadNotification}
        >
          <Text style={styles.retryButtonText}>再読み込み</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        {/* ヘッダー情報 */}
        <View style={styles.header}>
          <View style={styles.statusBadge}>
            <Text style={styles.statusText}>
              {notification.is_read ? '既読' : '未読'}
            </Text>
          </View>
          <Text style={styles.dateText}>{formatDate(notification.created_at)}</Text>
        </View>

        {/* 優先度バッジ (Laravel: 'info' | 'normal' | 'important') */}
        {notification.template?.priority === 'important' && (
          <View style={styles.priorityBadge}>
            <Text style={styles.priorityText}>重要</Text>
          </View>
        )}

        {/* タイトル */}
        <Text style={styles.title}>
          {notification.template?.title || '通知'}
        </Text>

        {/* カテゴリ */}
        {notification.template?.category && (
          <View style={styles.categoryContainer}>
            <Text style={styles.categoryLabel}>カテゴリ</Text>
            <Text style={styles.categoryValue}>
              {getNotificationTypeLabel(notification.template.category)}
            </Text>
          </View>
        )}

        {/* 本文 */}
        <View style={styles.contentBox}>
          <Text style={styles.contentLabel}>
            {theme === 'child' ? 'ないよう' : '内容'}
          </Text>
          <Text style={styles.contentText}>
            {notification.template?.content || '内容がありません'}
          </Text>
        </View>

        {/* 既読情報 */}
        {notification.read_at && (
          <View style={styles.readInfo}>
            <Text style={styles.readInfoLabel}>既読日時</Text>
            <Text style={styles.readInfoValue}>
              {new Date(notification.read_at).toLocaleString('ja-JP')}
            </Text>
          </View>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
    backgroundColor: '#F3F4F6',
  },
  errorIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 16,
    color: '#6B7280',
    textAlign: 'center',
    marginBottom: 24,
  },
  retryButton: {
    backgroundColor: '#59B9C6',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  content: {
    padding: 16,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  statusBadge: {
    backgroundColor: '#E5E7EB',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 12,
    color: '#6B7280',
    fontWeight: '600',
  },
  dateText: {
    fontSize: 14,
    color: '#9CA3AF',
  },
  priorityBadge: {
    backgroundColor: '#FEE2E2',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    alignSelf: 'flex-start',
    marginBottom: 16,
  },
  priorityText: {
    color: '#DC2626',
    fontSize: 12,
    fontWeight: '700',
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    color: '#1F2937',
    marginBottom: 16,
  },
  categoryContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
    gap: 8,
  },
  categoryLabel: {
    fontSize: 14,
    color: '#6B7280',
    fontWeight: '600',
  },
  categoryValue: {
    fontSize: 14,
    color: '#59B9C6',
    fontWeight: '600',
  },
  contentBox: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
  },
  contentLabel: {
    fontSize: 14,
    color: '#6B7280',
    fontWeight: '600',
    marginBottom: 8,
  },
  contentText: {
    fontSize: 16,
    color: '#1F2937',
    lineHeight: 24,
  },
  readInfo: {
    backgroundColor: '#F9FAFB',
    borderRadius: 8,
    padding: 12,
  },
  readInfoLabel: {
    fontSize: 12,
    color: '#6B7280',
    marginBottom: 4,
  },
  readInfoValue: {
    fontSize: 14,
    color: '#1F2937',
  },
});
