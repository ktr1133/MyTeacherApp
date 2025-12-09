/**
 * スケジュールタスク実行履歴画面
 * 
 * 特定のスケジュールタスクの実行履歴を一覧表示
 * 成功・失敗・スキップの状態、作成されたタスクID、エラーメッセージを表示
 */
import { useEffect, useState, useMemo } from 'react';
import {
  View,
  Text,
  FlatList,
  StyleSheet,
  RefreshControl,
  ActivityIndicator,
  TouchableOpacity,
  ScrollView,
} from 'react-native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useScheduledTasks } from '../../hooks/useScheduledTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { ScheduledTaskExecution } from '../../types/scheduled-task.types';
import { useRoute, useNavigation } from '@react-navigation/native';
import type { RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  ScheduledTaskHistory: { scheduledTaskId: number; title: string };
  TaskDetail: { taskId: number };
};

type ScreenRouteProp = RouteProp<RootStackParamList, 'ScheduledTaskHistory'>;
type NavigationProp = NativeStackNavigationProp<RootStackParamList>;

/**
 * スケジュールタスク実行履歴画面コンポーネント
 */
export default function ScheduledTaskHistoryScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<ScreenRouteProp>();
  const { width } = useResponsive();
  const { theme } = useTheme();
  const styles = useMemo(() => createStyles(width, theme), [width, theme]);
  const { executionHistory, isLoading, error, getExecutionHistory } = useScheduledTasks();

  const [refreshing, setRefreshing] = useState(false);
  const scheduledTaskId = route.params?.scheduledTaskId;
  const title = route.params?.title || '';

  /**
   * 初回データ取得
   */
  useEffect(() => {
    console.log(`[ScheduledTaskHistoryScreen] Loading history for task ${scheduledTaskId}`);
    if (scheduledTaskId) {
      loadExecutionHistory();
    }
  }, [scheduledTaskId]);

  /**
   * 実行履歴を取得
   */
  const loadExecutionHistory = async () => {
    if (!scheduledTaskId) return;
    try {
      await getExecutionHistory(scheduledTaskId);
    } catch (err) {
      console.error('[ScheduledTaskHistoryScreen] Error loading execution history:', err);
    }
  };

  /**
   * プルダウンリフレッシュ
   */
  const onRefresh = async () => {
    setRefreshing(true);
    await loadExecutionHistory();
    setRefreshing(false);
  };

  /**
   * タスク詳細へ遷移
   */
  const handleTaskPress = (taskId: number) => {
    navigation.navigate('TaskDetail', { taskId });
  };

  /**
   * 実行結果アイコン・スタイル取得
   */
  const getStatusDisplay = (execution: ScheduledTaskExecution) => {
    switch (execution.status) {
      case 'success':
        return {
          icon: '✅',
          label: theme === 'child' ? 'せいこう' : '成功',
          color: '#10B981',
          bgColor: '#D1FAE5',
        };
      case 'failed':
        return {
          icon: '❌',
          label: theme === 'child' ? 'しっぱい' : '失敗',
          color: '#EF4444',
          bgColor: '#FEE2E2',
        };
      case 'skipped':
        return {
          icon: '⏭️',
          label: theme === 'child' ? 'スキップ' : 'スキップ',
          color: '#6B7280',
          bgColor: '#F3F4F6',
        };
      default:
        return {
          icon: '❓',
          label: theme === 'child' ? 'ふめい' : '不明',
          color: '#9CA3AF',
          bgColor: '#F9FAFB',
        };
    }
  };

  /**
   * 日時フォーマット
   */
  const formatDateTime = (dateString: string): string => {
    const date = new Date(dateString);
    const month = date.getMonth() + 1;
    const day = date.getDate();
    const hour = date.getHours().toString().padStart(2, '0');
    const minute = date.getMinutes().toString().padStart(2, '0');
    
    if (theme === 'child') {
      return `${month}がつ${day}にち ${hour}:${minute}`;
    }
    return `${month}月${day}日 ${hour}:${minute}`;
  };

  /**
   * 実行履歴カードのレンダリング
   */
  const renderExecutionCard = ({ item }: { item: ScheduledTaskExecution }) => {
    const statusDisplay = getStatusDisplay(item);

    return (
      <View style={styles.card}>
        {/* ステータスバッジ */}
        <View style={[styles.statusBadge, { backgroundColor: statusDisplay.bgColor }]}>
          <Text style={styles.statusIcon}>{statusDisplay.icon}</Text>
          <Text style={[styles.statusLabel, { color: statusDisplay.color }]}>
            {statusDisplay.label}
          </Text>
        </View>

        {/* 実行日時 */}
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>🕒 {theme === 'child' ? 'じっこうびじ' : '実行日時'}:</Text>
          <Text style={styles.infoValue}>{formatDateTime(item.executed_at)}</Text>
        </View>

        {/* 成功時: 作成されたタスクID */}
        {item.status === 'success' && item.created_task_id && (
          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>📝 {theme === 'child' ? 'つくったタスク' : '作成タスク'}:</Text>
            <TouchableOpacity onPress={() => handleTaskPress(item.created_task_id!)}>
              <Text style={styles.taskLink}>
                {theme === 'child' ? `タスク #${item.created_task_id}` : `タスクID: ${item.created_task_id}`}
              </Text>
            </TouchableOpacity>
          </View>
        )}

        {/* 削除されたタスクID（前回未完了削除時） */}
        {item.deleted_task_id && (
          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>🗑️ {theme === 'child' ? 'けしたタスク' : '削除タスク'}:</Text>
            <Text style={styles.infoValue}>
              {theme === 'child' ? `タスク #${item.deleted_task_id}` : `タスクID: ${item.deleted_task_id}`}
            </Text>
          </View>
        )}

        {/* 備考（スキップ理由等） */}
        {item.note && (
          <View style={styles.notesContainer}>
            <Text style={styles.notesLabel}>💬 {theme === 'child' ? 'メモ' : '備考'}:</Text>
            <Text style={styles.notesText}>{item.note}</Text>
          </View>
        )}

        {/* エラーメッセージ（失敗時） */}
        {item.status === 'failed' && item.error_message && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorLabel}>⚠️ {theme === 'child' ? 'エラー' : 'エラー詳細'}:</Text>
            <Text style={styles.errorText}>{item.error_message}</Text>
          </View>
        )}
      </View>
    );
  };

  /**
   * エラー表示
   */
  if (error) {
    return (
      <View style={styles.centerContainer}>
        <Text style={styles.errorTitle}>
          {theme === 'child' ? 'エラーがおきたよ' : 'エラーが発生しました'}
        </Text>
        <Text style={styles.errorMessage}>{error}</Text>
        <TouchableOpacity style={styles.retryButton} onPress={loadExecutionHistory}>
          <Text style={styles.retryButtonText}>
            {theme === 'child' ? 'もういちど' : '再試行'}
          </Text>
        </TouchableOpacity>
      </View>
    );
  }

  /**
   * ローディング表示
   */
  if (isLoading && executionHistory.length === 0) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3B82F6" />
        <Text style={styles.loadingText}>
          {theme === 'child' ? 'よみこみちゅう...' : '読み込み中...'}
        </Text>
      </View>
    );
  }

  /**
   * 空状態表示
   */
  if (executionHistory.length === 0) {
    return (
      <View style={styles.container}>
        <ScrollView
          contentContainerStyle={styles.emptyContainer}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
        >
          <Text style={styles.emptyIcon}>📊</Text>
          <Text style={styles.emptyTitle}>
            {theme === 'child' ? 'きろくがないよ' : '実行履歴なし'}
          </Text>
          <Text style={styles.emptyDescription}>
            {theme === 'child'
              ? 'まだ1かいもじっこうされてないよ'
              : 'このスケジュールはまだ実行されていません。'}
          </Text>
        </ScrollView>
      </View>
    );
  }

  /**
   * 一覧表示
   */
  return (
    <View style={styles.container}>
      <FlatList
        data={executionHistory}
        renderItem={renderExecutionCard}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContainer}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        ListHeaderComponent={
          <View style={styles.header}>
            <Text style={styles.headerTitle}>
              {theme === 'child' ? 'じっこうきろく' : '実行履歴'}
            </Text>
            <Text style={styles.headerSubtitle} numberOfLines={2}>
              {title}
            </Text>
            <Text style={styles.headerCount}>
              {theme === 'child' ? `ぜんぶで ${executionHistory.length} かい` : `全 ${executionHistory.length} 件`}
            </Text>
          </View>
        }
      />
    </View>
  );
}

/**
 * スタイル定義
 */
const createStyles = (width: number, theme: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
    padding: getSpacing(20, width),
  },
  listContainer: {
    padding: getSpacing(16, width),
  },
  header: {
    marginBottom: getSpacing(16, width),
  },
  headerTitle: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: getSpacing(4, width),
  },
  headerSubtitle: {
    fontSize: getFontSize(16, width, theme),
    color: '#6B7280',
    marginBottom: getSpacing(4, width),
  },
  headerCount: {
    fontSize: getFontSize(14, width, theme),
    color: '#9CA3AF',
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(12, width),
    ...getShadow(3, width),
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(16, width),
    marginBottom: getSpacing(12, width),
  },
  statusIcon: {
    fontSize: getFontSize(16, width, theme),
    marginRight: getSpacing(6, width),
  },
  statusLabel: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: 'bold',
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(8, width),
  },
  infoLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
    width: getSpacing(120, width),
    flexShrink: 0,
  },
  infoValue: {
    fontSize: getFontSize(14, width, theme),
    color: '#1F2937',
    flex: 1,
  },
  taskLink: {
    fontSize: getFontSize(14, width, theme),
    color: '#3B82F6',
    textDecorationLine: 'underline',
    flex: 1,
  },
  notesContainer: {
    marginTop: getSpacing(8, width),
    padding: getSpacing(12, width),
    backgroundColor: '#F9FAFB',
    borderRadius: getBorderRadius(8, width),
  },
  notesLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
    marginBottom: getSpacing(4, width),
  },
  notesText: {
    fontSize: getFontSize(14, width, theme),
    color: '#1F2937',
    lineHeight: getFontSize(20, width, theme),
  },
  errorContainer: {
    marginTop: getSpacing(8, width),
    padding: getSpacing(12, width),
    backgroundColor: '#FEE2E2',
    borderRadius: getBorderRadius(8, width),
    borderLeftWidth: 4,
    borderLeftColor: '#EF4444',
  },
  errorLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#991B1B',
    fontWeight: 'bold',
    marginBottom: getSpacing(4, width),
  },
  errorText: {
    fontSize: getFontSize(14, width, theme),
    color: '#991B1B',
    lineHeight: getFontSize(20, width, theme),
  },
  executionTimeContainer: {
    marginTop: getSpacing(8, width),
    alignItems: 'flex-end',
  },
  executionTimeText: {
    fontSize: getFontSize(12, width, theme),
    color: '#9CA3AF',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: getSpacing(32, width),
  },
  emptyIcon: {
    fontSize: getFontSize(64, width, theme),
    marginBottom: getSpacing(16, width),
  },
  emptyTitle: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: getSpacing(8, width),
    textAlign: 'center',
  },
  emptyDescription: {
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
    textAlign: 'center',
    lineHeight: getFontSize(20, width, theme),
  },
  errorTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: '#EF4444',
    marginBottom: getSpacing(8, width),
    textAlign: 'center',
  },
  errorMessage: {
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
    textAlign: 'center',
    marginBottom: getSpacing(16, width),
  },
  retryButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: getSpacing(24, width),
    paddingVertical: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
  },
  loadingText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
  },
});
