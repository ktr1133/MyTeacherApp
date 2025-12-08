/**
 * スケジュールタスク一覧画面
 * 
 * グループのスケジュールタスク一覧を表示
 * カード形式でステータス（有効・一時停止）、スケジュール、報酬を表示
 */
import { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
  ActivityIndicator,
  Alert,
  ScrollView,
} from 'react-native';
import { useScheduledTasks } from '../../hooks/useScheduledTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { ScheduledTask } from '../../types/scheduled-task.types';
import { useNavigation, useRoute, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RouteProp } from '@react-navigation/native';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  ScheduledTaskList: { groupId: number };
  ScheduledTaskHistory: { scheduledTaskId: number; title: string };
  ScheduledTaskCreate: { groupId: number };
  ScheduledTaskEdit: { scheduledTaskId: number };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;
type ScreenRouteProp = RouteProp<RootStackParamList, 'ScheduledTaskList'>;

/**
 * スケジュールタスク一覧画面コンポーネント
 */
export default function ScheduledTaskListScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<ScreenRouteProp>();
  const { theme } = useTheme();
  const {
    scheduledTasks,
    isLoading,
    error,
    getScheduledTasks,
    deleteScheduledTask,
    pauseScheduledTask,
    resumeScheduledTask,
  } = useScheduledTasks();

  const [refreshing, setRefreshing] = useState(false);
  const groupId = route.params?.groupId || 1; // デフォルトはグループID=1

  /**
   * 初回データ取得
   */
  useEffect(() => {
    console.log('[ScheduledTaskListScreen] Mounting, loading scheduled tasks...');
    loadScheduledTasks();
  }, [groupId]);

  /**
   * 画面フォーカス時: スケジュールタスクリストを再同期
   */
  useFocusEffect(
    useCallback(() => {
      console.log('[ScheduledTaskListScreen] Screen focused, reloading...');
      getScheduledTasks(groupId);
    }, [groupId, getScheduledTasks])
  );

  /**
   * スケジュールタスク一覧を取得
   */
  const loadScheduledTasks = async () => {
    try {
      await getScheduledTasks(groupId);
    } catch (err) {
      console.error('[ScheduledTaskListScreen] Error loading scheduled tasks:', err);
    }
  };

  /**
   * プルダウンリフレッシュ
   */
  const onRefresh = async () => {
    setRefreshing(true);
    await loadScheduledTasks();
    setRefreshing(false);
  };

  /**
   * スケジュールタスク削除
   */
  const handleDelete = (scheduledTask: ScheduledTask) => {
    Alert.alert(
      theme === 'child' ? 'けす' : '削除確認',
      theme === 'child'
        ? `「${scheduledTask.title}」をけしますか？`
        : `「${scheduledTask.title}」を削除しますか？\nこのスケジュールは実行されなくなります。`,
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'けす' : '削除',
          style: 'destructive',
          onPress: async () => {
            const success = await deleteScheduledTask(scheduledTask.id);
            if (success) {
              Alert.alert(
                theme === 'child' ? 'できた！' : '削除完了',
                theme === 'child' ? 'けせたよ！' : 'スケジュールタスクを削除しました。'
              );
            }
          },
        },
      ]
    );
  };

  /**
   * スケジュールタスク一時停止
   */
  const handlePause = async (scheduledTask: ScheduledTask) => {
    Alert.alert(
      theme === 'child' ? 'とめる' : '一時停止',
      theme === 'child'
        ? `「${scheduledTask.title}」をとめますか？`
        : `「${scheduledTask.title}」を一時停止しますか？`,
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'とめる' : '一時停止',
          onPress: async () => {
            const result = await pauseScheduledTask(scheduledTask.id);
            if (result) {
              Alert.alert(
                theme === 'child' ? 'できた！' : '一時停止完了',
                theme === 'child' ? 'とめたよ！' : 'スケジュールを一時停止しました。'
              );
            }
          },
        },
      ]
    );
  };

  /**
   * スケジュールタスク再開
   */
  const handleResume = async (scheduledTask: ScheduledTask) => {
    Alert.alert(
      theme === 'child' ? 'うごかす' : '再開',
      theme === 'child'
        ? `「${scheduledTask.title}」をうごかしますか？`
        : `「${scheduledTask.title}」を再開しますか？`,
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'うごかす' : '再開',
          onPress: async () => {
            const result = await resumeScheduledTask(scheduledTask.id);
            if (result) {
              Alert.alert(
                theme === 'child' ? 'できた！' : '再開完了',
                theme === 'child' ? 'うごきだしたよ！' : 'スケジュールを再開しました。'
              );
            }
          },
        },
      ]
    );
  };

  /**
   * 実行履歴表示
   */
  const handleShowHistory = (scheduledTask: ScheduledTask) => {
    navigation.navigate('ScheduledTaskHistory', {
      scheduledTaskId: scheduledTask.id,
      title: scheduledTask.title,
    });
  };

  /**
   * スケジュールタスク編集
   */
  const handleEdit = (scheduledTask: ScheduledTask) => {
    navigation.navigate('ScheduledTaskEdit', {
      scheduledTaskId: scheduledTask.id,
    });
  };

  /**
   * スケジュールタスク作成画面へ遷移
   */
  const handleCreate = () => {
    navigation.navigate('ScheduledTaskCreate', { groupId });
  };

  /**
   * スケジュールタイプの日本語表示
   */
  const getScheduleText = (schedules: ScheduledTask['schedules']): string => {
    if (!schedules || schedules.length === 0) return '-';
    
    const texts = schedules.map(schedule => {
      switch (schedule.type) {
        case 'daily':
          return theme === 'child' ? `まいにち ${schedule.time}` : `毎日 ${schedule.time}`;
        case 'weekly':
          if (schedule.days && schedule.days.length > 0) {
            const dayNames = theme === 'child'
              ? ['にち', 'げつ', 'か', 'すい', 'もく', 'きん', 'ど']
              : ['日', '月', '火', '水', '木', '金', '土'];
            const daysText = schedule.days.map(d => dayNames[d]).join('・');
            return theme === 'child' ? `まいしゅう ${daysText} ${schedule.time}` : `毎週 ${daysText} ${schedule.time}`;
          }
          return theme === 'child' ? `まいしゅう ${schedule.time}` : `毎週 ${schedule.time}`;
        case 'monthly':
          if (schedule.dates && schedule.dates.length > 0) {
            const datesText = schedule.dates.join(theme === 'child' ? 'にち・' : '日・') + (theme === 'child' ? 'にち' : '日');
            return theme === 'child' ? `まいつき ${datesText} ${schedule.time}` : `毎月 ${datesText} ${schedule.time}`;
          }
          return theme === 'child' ? `まいつき ${schedule.time}` : `毎月 ${schedule.time}`;
        default:
          return '-';
      }
    });
    
    return texts.join(theme === 'child' ? '、' : ' / ');
  };

  /**
   * 担当者表示
   */
  const getAssigneeText = (scheduledTask: ScheduledTask): string => {
    if (scheduledTask.assigned_user_id) {
      return theme === 'child' ? 'だれか' : '特定メンバー';
    }
    return theme === 'child' ? 'みんな' : 'グループ全員';
  };

  /**
   * スケジュールタスクカードのレンダリング
   */
  const renderScheduledTaskCard = ({ item }: { item: ScheduledTask }) => {
    const isActive = item.is_active;
    const statusColor = isActive ? '#10B981' : '#6B7280';
    const statusText = isActive
      ? (theme === 'child' ? 'うごいてる' : '有効')
      : (theme === 'child' ? 'とまってる' : '一時停止');

    return (
      <TouchableOpacity
        style={styles.card}
        onPress={() => handleEdit(item)}
        activeOpacity={0.7}
      >
        {/* ステータスバッジ */}
        <View style={[styles.statusBadge, { backgroundColor: statusColor }]}>
          <Text style={styles.statusText}>{statusText}</Text>
        </View>

        {/* タイトル */}
        <Text style={styles.cardTitle} numberOfLines={2}>
          {item.title}
        </Text>

        {/* 説明 */}
        {item.description && (
          <Text style={styles.cardDescription} numberOfLines={2}>
            {item.description}
          </Text>
        )}

        {/* スケジュール情報 */}
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>📅 {theme === 'child' ? 'いつ' : 'スケジュール'}:</Text>
          <Text style={styles.infoValue} numberOfLines={2}>
            {getScheduleText(item.schedules)}
          </Text>
        </View>

        {/* 担当者 */}
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>👤 {theme === 'child' ? 'だれ' : '担当者'}:</Text>
          <Text style={styles.infoValue}>{getAssigneeText(item)}</Text>
        </View>

        {/* 報酬 */}
        {item.reward > 0 && (
          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>💰 {theme === 'child' ? 'ごほうび' : '報酬'}:</Text>
            <Text style={styles.rewardValue}>{item.reward.toLocaleString()} トークン</Text>
          </View>
        )}

        {/* タグ */}
        {(() => {
          const tags = item.tag_names || item.tags || [];
          return tags.length > 0 && (
            <View style={styles.tagsContainer}>
              {tags.slice(0, 3).map((tag, index) => (
                <View key={index} style={styles.tag}>
                  <Text style={styles.tagText}>{tag}</Text>
                </View>
              ))}
              {tags.length > 3 && (
                <Text style={styles.moreTagsText}>+{tags.length - 3}</Text>
              )}
            </View>
          );
        })()}

        {/* アクションボタン */}
        <View style={styles.actionButtons}>
          <TouchableOpacity
            style={styles.actionButton}
            onPress={() => handleShowHistory(item)}
          >
            <Text style={styles.actionButtonText}>
              📊 {theme === 'child' ? 'きろく' : '履歴'}
            </Text>
          </TouchableOpacity>

          {isActive ? (
            <TouchableOpacity
              style={[styles.actionButton, styles.pauseButton]}
              onPress={() => handlePause(item)}
            >
              <Text style={styles.actionButtonText}>
                ⏸️ {theme === 'child' ? 'とめる' : '一時停止'}
              </Text>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity
              style={[styles.actionButton, styles.resumeButton]}
              onPress={() => handleResume(item)}
            >
              <Text style={styles.actionButtonText}>
                ▶️ {theme === 'child' ? 'うごかす' : '再開'}
              </Text>
            </TouchableOpacity>
          )}

          <TouchableOpacity
            style={[styles.actionButton, styles.deleteButton]}
            onPress={() => handleDelete(item)}
          >
            <Text style={styles.actionButtonText}>
              🗑️ {theme === 'child' ? 'けす' : '削除'}
            </Text>
          </TouchableOpacity>
        </View>
      </TouchableOpacity>
    );
  };

  /**
   * エラー表示
   */
  if (error) {
    return (
      <View style={styles.centerContainer}>
        <Text style={styles.errorText}>
          {theme === 'child' ? 'エラーがおきたよ' : 'エラーが発生しました'}
        </Text>
        <Text style={styles.errorMessage}>{error}</Text>
        <TouchableOpacity style={styles.retryButton} onPress={loadScheduledTasks}>
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
  if (isLoading && scheduledTasks.length === 0) {
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
  if (scheduledTasks.length === 0) {
    return (
      <View style={styles.container}>
        <ScrollView
          contentContainerStyle={styles.emptyContainer}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
        >
          <Text style={styles.emptyIcon}>📅</Text>
          <Text style={styles.emptyTitle}>
            {theme === 'child' ? 'スケジュールがないよ' : 'スケジュールタスクなし'}
          </Text>
          <Text style={styles.emptyDescription}>
            {theme === 'child'
              ? 'ていきてきにじどうでタスクをつくれるよ'
              : '定期的に自動実行するタスクを設定できます。'}
          </Text>
          <TouchableOpacity style={styles.createButton} onPress={handleCreate}>
            <Text style={styles.createButtonText}>
              ➕ {theme === 'child' ? 'つくる' : 'スケジュールを作成'}
            </Text>
          </TouchableOpacity>
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
        data={scheduledTasks}
        renderItem={renderScheduledTaskCard}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContainer}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        ListHeaderComponent={
          <View style={styles.header}>
            <Text style={styles.headerTitle}>
              {theme === 'child' ? 'スケジュール' : 'スケジュールタスク一覧'}
            </Text>
            <Text style={styles.headerSubtitle}>
              {scheduledTasks.length} {theme === 'child' ? 'こ' : '件'}
            </Text>
          </View>
        }
        ListFooterComponent={
          <TouchableOpacity style={styles.createButtonBottom} onPress={handleCreate}>
            <Text style={styles.createButtonText}>
              ➕ {theme === 'child' ? 'あたらしくつくる' : '新規作成'}
            </Text>
          </TouchableOpacity>
        }
      />
    </View>
  );
}

/**
 * スタイル定義
 */
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
    padding: 20,
  },
  listContainer: {
    padding: 16,
  },
  header: {
    marginBottom: 16,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 4,
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#6B7280',
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  statusBadge: {
    position: 'absolute',
    top: 12,
    right: 12,
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: 'bold',
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 8,
    marginRight: 80, // ステータスバッジ分のスペース
  },
  cardDescription: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 12,
    lineHeight: 20,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  infoLabel: {
    fontSize: 14,
    color: '#6B7280',
    width: 100,
    flexShrink: 0,
  },
  infoValue: {
    fontSize: 14,
    color: '#1F2937',
    flex: 1,
  },
  rewardValue: {
    fontSize: 14,
    color: '#F59E0B',
    fontWeight: 'bold',
    flex: 1,
  },
  tagsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: 8,
    marginBottom: 12,
  },
  tag: {
    backgroundColor: '#DBEAFE',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
    marginRight: 6,
    marginBottom: 6,
  },
  tagText: {
    fontSize: 12,
    color: '#1E40AF',
  },
  moreTagsText: {
    fontSize: 12,
    color: '#6B7280',
    alignSelf: 'center',
  },
  actionButtons: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
  },
  actionButton: {
    flex: 1,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    backgroundColor: '#F3F4F6',
    marginHorizontal: 4,
  },
  pauseButton: {
    backgroundColor: '#FEF3C7',
  },
  resumeButton: {
    backgroundColor: '#D1FAE5',
  },
  deleteButton: {
    backgroundColor: '#FEE2E2',
  },
  actionButtonText: {
    fontSize: 12,
    textAlign: 'center',
    color: '#1F2937',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  emptyIcon: {
    fontSize: 64,
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 8,
    textAlign: 'center',
  },
  emptyDescription: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
    marginBottom: 24,
    lineHeight: 20,
  },
  createButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
  },
  createButtonBottom: {
    backgroundColor: '#3B82F6',
    paddingVertical: 16,
    borderRadius: 8,
    marginTop: 16,
    marginBottom: 32,
  },
  createButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
  },
  errorText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#EF4444',
    marginBottom: 8,
    textAlign: 'center',
  },
  errorMessage: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
    marginBottom: 16,
  },
  retryButton: {
    backgroundColor: '#3B82F6',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  loadingText: {
    marginTop: 12,
    fontSize: 14,
    color: '#6B7280',
  },
});
