/**
 * タスク一覧画面
 * 
 * テーマに応じた表示切り替え、完了/未完了フィルター、ページネーション対応
 */
import React, { useEffect, useState, useCallback } from 'react';
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
import { Task, TaskStatus } from '../../types/task.types';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  TaskList: undefined;
  TaskDetail: { taskId: number };
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
    pagination,
    fetchTasks,
    searchTasks,
    toggleComplete,
    clearError,
    refreshTasks,
  } = useTasks();

  const [selectedStatus, setSelectedStatus] = useState<TaskStatus | 'all'>('all');
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  /**
   * 初回データ取得
   */
  useEffect(() => {
    loadTasks();
  }, [selectedStatus]);

  /**
   * 検索クエリ変更時にタスク検索
   */
  useEffect(() => {
    if (searchQuery.trim()) {
      const filters = selectedStatus !== 'all' ? { status: selectedStatus } : undefined;
      searchTasks(searchQuery, filters);
    } else {
      loadTasks();
    }
  }, [searchQuery]);

  /**
   * タスク一覧を取得
   */
  const loadTasks = useCallback(() => {
    const filters = selectedStatus !== 'all' ? { status: selectedStatus } : undefined;
    fetchTasks(filters);
  }, [selectedStatus, fetchTasks]);

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
      const success = await toggleComplete(taskId);
      if (success) {
        Alert.alert(
          theme === 'child' ? 'やったね!' : '完了',
          theme === 'child' ? 'やることをおわらせたよ!' : 'タスクを完了しました'
        );
      }
    },
    [toggleComplete, theme]
  );

  /**
   * タスク詳細画面へ遷移
   */
  const navigateToDetail = useCallback(
    (taskId: number) => {
      navigation.navigate('TaskDetail', { taskId });
    },
    [navigation]
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
      const isCompleted = item.status === 'completed' || item.status === 'approved';
      const isPending = item.status === 'pending';

      return (
        <TouchableOpacity
          style={styles.taskItem}
          onPress={() => navigateToDetail(item.id)}
          activeOpacity={0.7}
        >
          <View style={styles.taskHeader}>
            <Text style={[styles.taskTitle, isCompleted && styles.completedText]}>
              {item.title}
            </Text>
            <View style={[styles.statusBadge, getStatusStyle(item.status)]}>
              <Text style={styles.statusText}>{getStatusLabel(item.status, theme)}</Text>
            </View>
          </View>

          {item.description && (
            <Text style={styles.taskDescription} numberOfLines={2}>
              {item.description}
            </Text>
          )}

          <View style={styles.taskFooter}>
            <Text style={styles.taskReward}>
              {theme === 'child' ? '⭐' : '報酬:'} {item.reward}
              {theme === 'child' ? '' : 'トークン'}
            </Text>
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

      {/* フィルター */}
      <View style={styles.filterContainer}>
        <TouchableOpacity
          style={[styles.filterButton, selectedStatus === 'all' && styles.filterButtonActive]}
          onPress={() => setSelectedStatus('all')}
        >
          <Text
            style={[
              styles.filterButtonText,
              selectedStatus === 'all' && styles.filterButtonTextActive,
            ]}
          >
            {theme === 'child' ? 'ぜんぶ' : 'すべて'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.filterButton, selectedStatus === 'pending' && styles.filterButtonActive]}
          onPress={() => setSelectedStatus('pending')}
        >
          <Text
            style={[
              styles.filterButtonText,
              selectedStatus === 'pending' && styles.filterButtonTextActive,
            ]}
          >
            {theme === 'child' ? 'やること' : '未完了'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[
            styles.filterButton,
            selectedStatus === 'completed' && styles.filterButtonActive,
          ]}
          onPress={() => setSelectedStatus('completed')}
        >
          <Text
            style={[
              styles.filterButtonText,
              selectedStatus === 'completed' && styles.filterButtonTextActive,
            ]}
          >
            {theme === 'child' ? 'できた' : '完了'}
          </Text>
        </TouchableOpacity>
      </View>

      {/* タスク一覧 */}
      <FlatList
        data={tasks}
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
    </View>
  );
}

/**
 * ステータスに応じたラベルを取得
 */
const getStatusLabel = (status: TaskStatus, theme: 'adult' | 'child'): string => {
  if (theme === 'child') {
    switch (status) {
      case 'pending':
        return 'やる';
      case 'completed':
        return 'できた';
      case 'approved':
        return 'OK!';
      case 'rejected':
        return 'やりなおし';
      default:
        return '?';
    }
  } else {
    switch (status) {
      case 'pending':
        return '未完了';
      case 'completed':
        return '完了';
      case 'approved':
        return '承認済み';
      case 'rejected':
        return '却下';
      default:
        return '不明';
    }
  }
};

/**
 * ステータスに応じたスタイルを取得
 */
const getStatusStyle = (status: TaskStatus) => {
  switch (status) {
    case 'pending':
      return styles.statusPending;
    case 'completed':
      return styles.statusCompleted;
    case 'approved':
      return styles.statusApproved;
    case 'rejected':
      return styles.statusRejected;
    default:
      return styles.statusPending;
  }
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
