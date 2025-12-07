/**
 * タスク一覧画面
 * 
 * テーマに応じた表示切り替え、完了/未完了フィルター、ページネーション対応
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
  TextInput,
} from 'react-native';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { Task } from '../../types/task.types';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
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
  CreateTask: undefined;
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;

/**
 * タスク一覧画面コンポーネント
 */
export default function TaskListScreen() {
  const navigation = useNavigation<NavigationProp>();
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

  // アバター状態をログ出力
  console.log('🎭 [TaskListScreen] Avatar state:', { avatarVisible, hasAvatarData: !!avatarData });

  const [selectedStatus] = useState<'pending'>('pending'); // 未完了のみ表示
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [filteredTasks, setFilteredTasks] = useState<Task[]>([]);

  /**
   * 初回データ取得
   */
  useEffect(() => {
    console.log('[TaskListScreen] Mounting, loading tasks...');
    try {
      loadTasks();
    } catch (err) {
      console.error('[TaskListScreen] Error loading tasks:', err);
    }
  }, [selectedStatus]);

  /**
   * 画面フォーカス時: タスクリストを再同期
   * （削除後に前画面に戻った際、削除されたタスクを即座に消すため）
   */
  useFocusEffect(
    useCallback(() => {
      // 画面がフォーカスされたら、未完了タスクを再取得
      fetchTasks({ status: 'pending' });
    }, [fetchTasks])
  );

  /**
   * タスクデータまたは検索クエリ変更時にフィルタリング
   */
  useEffect(() => {
    console.log('[TaskListScreen] Filtering tasks, query:', searchQuery, 'tasks count:', tasks.length);
    
    if (searchQuery.trim()) {
      // 検索クエリがある場合: タイトル、説明、タグ名で部分一致フィルタリング
      const query = searchQuery.toLowerCase();
      const filtered = tasks.filter(task => {
        // タイトルで検索
        if (task.title?.toLowerCase().includes(query)) {
          return true;
        }
        // 説明で検索
        if (task.description?.toLowerCase().includes(query)) {
          return true;
        }
        // タグ名で検索
        if (task.tags?.some(tag => tag.name?.toLowerCase().includes(query))) {
          return true;
        }
        return false;
      });
      console.log('[TaskListScreen] Filtered tasks count:', filtered.length);
      setFilteredTasks(filtered);
    } else {
      // 検索クエリがない場合: 全タスクを表示
      setFilteredTasks(tasks);
    }
  }, [searchQuery, tasks]);

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
      console.log('🎭 [TaskListScreen] handleToggleComplete called:', { taskId });
      const success = await toggleComplete(taskId);
      console.log('🎭 [TaskListScreen] toggleComplete result:', { success });
      
      if (success) {
        // アバターイベント発火
        console.log('🎭 [TaskListScreen] Firing avatar event: task_completed');
        dispatchAvatarEvent('task_completed');
        console.log('🎭 [TaskListScreen] dispatchAvatarEvent called');

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
   * 通常タスク: 編集画面へ遷移
   * グループタスク: 詳細画面へ遷移
   */
  const navigateToDetail = useCallback(
    (taskId: number) => {
      console.log('[TaskListScreen] navigateToDetail called, taskId:', taskId);
      console.log('[TaskListScreen] tasks count:', tasks.length);
      
      const task = tasks.find(t => t.id === taskId);
      console.log('[TaskListScreen] found task:', task ? `id=${task.id}, is_group_task=${task.is_group_task}` : 'null');
      
      if (task?.is_group_task) {
        // グループタスク → 詳細画面（編集不可）
        console.log('[TaskListScreen] Navigating to TaskDetail');
        navigation.navigate('TaskDetail', { taskId });
      } else {
        // 通常タスク → 編集画面
        console.log('[TaskListScreen] Navigating to TaskEdit');
        navigation.navigate('TaskEdit', { taskId });
      }
    },
    [tasks, navigation]
  );

  /**
   * タスク作成画面へ遷移
   */
  const navigateToCreate = useCallback(() => {
    navigation.navigate('CreateTask');
  }, [navigation]);

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
            console.log('[TaskListScreen] Task item pressed:', item.id, item.title);
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
            ? 'やることがないよ' 
            : 'タスクがありません'}
        </Text>
        <Text style={styles.emptySubtext}>
          {theme === 'child'
            ? 'あたらしいやることをつくってね'
            : '新しいタスクを作成してください'}
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
    <View style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>
          {theme === 'child' ? 'やること' : 'タスク一覧'}
        </Text>
        <TouchableOpacity style={styles.createButton} onPress={navigateToCreate}>
          <Text style={styles.createButtonText}>＋</Text>
        </TouchableOpacity>
      </View>

      {/* 検索バー */}
      <View style={styles.searchContainer}>
        <TextInput
          style={styles.searchInput}
          placeholder={theme === 'child' ? 'さがす' : '検索（タイトル・説明）'}
          placeholderTextColor="#9CA3AF"
          value={searchQuery}
          onChangeText={setSearchQuery}
          autoCapitalize="none"
          autoCorrect={false}
        />
        {searchQuery.length > 0 && (
          <TouchableOpacity
            style={styles.clearButton}
            onPress={() => setSearchQuery('')}
          >
            <Text style={styles.clearButtonText}>✕</Text>
          </TouchableOpacity>
        )}
      </View>

      {/* 検索結果件数 */}
      {searchQuery.trim() && (
        <View style={styles.searchResultContainer}>
          <Text style={styles.searchResultText}>
            {theme === 'child' 
              ? `${filteredTasks.length}こ みつかったよ` 
              : `${filteredTasks.length}件のタスクが見つかりました`}
          </Text>
        </View>
      )}

      {/* タスク一覧 */}
      <FlatList
        data={filteredTasks}
        renderItem={renderTaskItem}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#4F46E5']} />
        }
        ListEmptyComponent={renderEmptyList}
        ListFooterComponent={renderFooter}
        onEndReachedThreshold={0.5}
      />

      {/* アバターウィジェット */}
      <AvatarWidget
        visible={avatarVisible}
        data={avatarData}
        onClose={hideAvatar}
        position="center"
      />
    </View>
  );
}



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
    paddingVertical: 16,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#111827',
  },
  createButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#4F46E5',
    justifyContent: 'center',
    alignItems: 'center',
  },
  createButtonText: {
    fontSize: 24,
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 8,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  searchInput: {
    flex: 1,
    height: 40,
    paddingHorizontal: 12,
    borderRadius: 8,
    backgroundColor: '#F3F4F6',
    fontSize: 14,
    color: '#111827',
  },
  clearButton: {
    marginLeft: 8,
    width: 32,
    height: 32,
    justifyContent: 'center',
    alignItems: 'center',
  },
  clearButtonText: {
    fontSize: 18,
    color: '#9CA3AF',
    fontWeight: 'bold',
  },
  searchResultContainer: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    backgroundColor: '#EEF2FF',
  },
  searchResultText: {
    fontSize: 14,
    color: '#4F46E5',
    fontWeight: '600',
  },
  filterContainer: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    gap: 8,
  },
  filterButton: {
    flex: 1,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    backgroundColor: '#F3F4F6',
    alignItems: 'center',
  },
  filterButtonActive: {
    backgroundColor: '#4F46E5',
  },
  filterButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6B7280',
  },
  filterButtonTextActive: {
    color: '#FFFFFF',
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
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  statusPending: {
    backgroundColor: '#FEF3C7',
  },
  statusCompleted: {
    backgroundColor: '#D1FAE5',
  },
  statusApproved: {
    backgroundColor: '#DBEAFE',
  },
  statusRejected: {
    backgroundColor: '#FEE2E2',
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#374151',
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
    marginBottom: 8,
  },
  emptySubtext: {
    fontSize: 14,
    color: '#9CA3AF',
  },
  footerLoading: {
    paddingVertical: 20,
    alignItems: 'center',
  },
});
