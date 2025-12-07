/**
 * タグ別タスク一覧画面
 * 
 * 特定のタグに紐づくタスクを一覧表示
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
  SafeAreaView,
} from 'react-native';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { Task } from '../../types/task.types';
import { useNavigation, useRoute, RouteProp, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useAvatar } from '../../hooks/useAvatar';
import AvatarWidget from '../../components/common/AvatarWidget';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  TaskList: undefined;
  TaskDetail: { taskId: number };
  TaskEdit: { taskId: number };
  TagTasks: { tagId: number; tagName: string };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList, 'TagTasks'>;
type TagTasksRouteProp = RouteProp<RootStackParamList, 'TagTasks'>;

/**
 * タグ別タスク一覧画面コンポーネント
 */
export default function TagTasksScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<TagTasksRouteProp>();
  const { tagId, tagName } = route.params;
  const { theme } = useTheme();
  const {
    tasks,
    isLoading,
    error,
    fetchTasks,
    toggleComplete,
    clearError,
    refreshTasks,
  } = useTasks();
  const {
    isVisible: avatarVisible,
    currentData: avatarData,
    dispatchAvatarEvent,
    hideAvatar,
  } = useAvatar();

  const [refreshing, setRefreshing] = useState(false);
  const [filteredTasks, setFilteredTasks] = useState<Task[]>([]);

  /**
   * 初回データ取得
   */
  useEffect(() => {
    console.log('[TagTasksScreen] Mounting, loading tasks...');
    loadTasks();
  }, []);

  /**
   * 画面フォーカス時: タスクリストを再同期
   */
  useFocusEffect(
    useCallback(() => {
      fetchTasks({ status: 'pending' });
    }, [fetchTasks])
  );

  /**
   * タスクデータ変更時にフィルタリング
   */
  useEffect(() => {
    console.log('[TagTasksScreen] Filtering tasks, tagId:', tagId, 'tasks count:', tasks.length);
    
    const filtered = tasks.filter(task => {
      if (tagId === 0) {
        // 未分類バケット: タグなしタスク
        return !task.tags || task.tags.length === 0;
      } else {
        // 特定タグバケット: そのタグを持つタスク
        return task.tags?.some(tag => tag.id === tagId);
      }
    });
    
    console.log('[TagTasksScreen] Filtered tasks count:', filtered.length);
    setFilteredTasks(filtered);
  }, [tagId, tasks]);

  /**
   * タスク一覧を取得（未完了のみ）
   */
  const loadTasks = useCallback(() => {
    fetchTasks({ status: 'pending' });
  }, [fetchTasks]);

  /**
   * Pull-to-Refresh
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await refreshTasks();
    setRefreshing(false);
  }, [refreshTasks]);

  /**
   * タスク完了切り替え
   */
  const handleToggleComplete = useCallback(
    async (taskId: number) => {
      console.log('🎭 [TagTasksScreen] handleToggleComplete called:', { taskId });
      const success = await toggleComplete(taskId);
      console.log('🎭 [TagTasksScreen] toggleComplete result:', { success });
      
      if (success) {
        // アバターイベント発火
        console.log('🎭 [TagTasksScreen] Firing avatar event: task_completed');
        dispatchAvatarEvent('task_completed');

        // アバター表示後にアラート表示（3秒待機）
        setTimeout(() => {
          Alert.alert(
            theme === 'child' ? 'やったね!' : '完了',
            theme === 'child' ? 'やることをおわらせたよ!' : 'タスクを完了しました'
          );
        }, 3000);
      }
    },
    [toggleComplete, theme, dispatchAvatarEvent]
  );

  /**
   * タスク詳細/編集画面へ遷移
   */
  const navigateToDetail = useCallback(
    (taskId: number) => {
      console.log('[TagTasksScreen] navigateToDetail called, taskId:', taskId);
      
      const task = tasks.find(t => t.id === taskId);
      console.log('[TagTasksScreen] found task:', task ? `id=${task.id}, is_group_task=${task.is_group_task}` : 'null');
      
      if (task?.is_group_task) {
        // グループタスク → 詳細画面（編集不可）
        console.log('[TagTasksScreen] Navigating to TaskDetail');
        navigation.navigate('TaskDetail', { taskId });
      } else {
        // 通常タスク → 編集画面
        console.log('[TagTasksScreen] Navigating to TaskEdit');
        navigation.navigate('TaskEdit', { taskId });
      }
    },
    [tasks, navigation]
  );

  /**
   * エラー表示時のアラート
   */
  useEffect(() => {
    if (error) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        error,
        [{ text: 'OK', onPress: clearError }]
      );
    }
  }, [error, theme, clearError]);

  /**
   * タスクアイテムをレンダリング
   */
  const renderTaskItem = useCallback(
    ({ item }: { item: Task }) => {
      const isCompleted = item.is_completed;
      const isPending = !item.is_completed;

      return (
        <TouchableOpacity
          style={styles.taskItem}
          onPress={() => {
            console.log('[TagTasksScreen] Task item pressed:', item.id, item.title);
            navigateToDetail(item.id);
          }}
          activeOpacity={0.7}
        >
          <View style={styles.taskHeader}>
            <Text style={[styles.taskTitle, isCompleted && styles.completedText]}>
              {item.title}
            </Text>
          </View>

          {item.description && (
            <Text style={styles.taskDescription} numberOfLines={2}>
              {item.description}
            </Text>
          )}

          {/* タグ表示 */}
          {item.tags && item.tags.length > 0 && (
            <View style={styles.tagsContainer}>
              {item.tags.map((tag) => (
                <View key={tag.id} style={styles.tagBadge}>
                  <Text style={styles.tagText}>{tag.name}</Text>
                </View>
              ))}
            </View>
          )}

          <View style={styles.taskFooter}>
            {/* グループタスクのみ報酬を表示 */}
            {item.is_group_task && (
              <Text style={styles.taskReward}>
                {theme === 'child' ? '⭐' : '報酬:'} {item.reward}
                {theme === 'child' ? '' : 'トークン'}
              </Text>
            )}
            {item.due_date && (
              <Text style={styles.taskDueDate}>
                {theme === 'child' ? '⏰' : '期限:'} {item.due_date}
              </Text>
            )}
          </View>

          {isPending && (
            <TouchableOpacity
              style={styles.completeButton}
              onPress={() => handleToggleComplete(item.id)}
            >
              <Text style={styles.completeButtonText}>
                {theme === 'child' ? 'できた!' : '完了にする'}
              </Text>
            </TouchableOpacity>
          )}
        </TouchableOpacity>
      );
    },
    [theme, navigateToDetail, handleToggleComplete]
  );

  /**
   * 空リスト表示
   */
  const renderEmptyList = useCallback(() => {
    if (isLoading) {
      return null;
    }

    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyText}>
          {theme === 'child' 
            ? 'このタグのやることがないよ' 
            : 'このタグのタスクがありません'}
        </Text>
      </View>
    );
  }, [isLoading, theme]);

  /**
   * フッターローディング表示
   */
  const renderFooter = useCallback(() => {
    if (!isLoading) {
      return null;
    }

    return (
      <View style={styles.footerLoading}>
        <ActivityIndicator size="small" color="#4F46E5" />
      </View>
    );
  }, [isLoading]);

  return (
    <SafeAreaView style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}
        >
          <Text style={styles.backButtonText}>←</Text>
        </TouchableOpacity>
        <View style={styles.headerTitleContainer}>
          <Text style={styles.headerTitle} numberOfLines={1}>
            {tagName}
          </Text>
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{filteredTasks.length}</Text>
          </View>
        </View>
      </View>

      {/* タスク一覧 */}
      <FlatList
        testID="task-list"
        data={filteredTasks}
        renderItem={renderTaskItem}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#4F46E5']} />
        }
        ListEmptyComponent={renderEmptyList}
        ListFooterComponent={renderFooter}
      />

      {/* アバターウィジェット */}
      <AvatarWidget
        visible={avatarVisible}
        data={avatarData}
        onClose={hideAvatar}
        position="center"
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 16,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  backButton: {
    width: 40,
    height: 40,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 8,
  },
  backButtonText: {
    fontSize: 24,
    color: '#4F46E5',
    fontWeight: 'bold',
  },
  headerTitleContainer: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#111827',
    flex: 1,
    marginRight: 8,
  },
  badge: {
    backgroundColor: '#8B5CF6',
    borderRadius: 12,
    paddingHorizontal: 10,
    paddingVertical: 4,
    minWidth: 32,
    alignItems: 'center',
  },
  badgeText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
  listContent: {
    padding: 16,
  },
  taskItem: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  taskHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  taskTitle: {
    flex: 1,
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
    marginRight: 8,
  },
  completedText: {
    textDecorationLine: 'line-through',
    color: '#9CA3AF',
  },
  taskDescription: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 12,
    lineHeight: 20,
  },
  tagsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 12,
  },
  tagBadge: {
    backgroundColor: '#E0E7FF',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  tagText: {
    fontSize: 12,
    color: '#4F46E5',
    fontWeight: '600',
  },
  taskFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  taskReward: {
    fontSize: 14,
    fontWeight: '600',
    color: '#F59E0B',
  },
  taskDueDate: {
    fontSize: 12,
    color: '#9CA3AF',
  },
  completeButton: {
    marginTop: 12,
    backgroundColor: '#10B981',
    borderRadius: 8,
    paddingVertical: 10,
    alignItems: 'center',
  },
  completeButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 60,
  },
  emptyText: {
    fontSize: 18,
    fontWeight: '600',
    color: '#6B7280',
  },
  footerLoading: {
    paddingVertical: 20,
    alignItems: 'center',
  },
});
