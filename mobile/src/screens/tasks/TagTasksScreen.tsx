/**
 * タグ別タスク一覧画面
 * 
 * 特定のタグに紐づくタスクを一覧表示
 */
import { useEffect, useState, useCallback, useMemo } from 'react';
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
  StatusBar,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { Task } from '../../types/task.types';
import { useNavigation, useRoute, RouteProp, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useAvatar } from '../../hooks/useAvatar';
import AvatarWidget from '../../components/common/AvatarWidget';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { useThemedColors } from '../../hooks/useThemedColors';
import { getDeadlineStatus } from '../../utils/taskDeadline';
import DeadlineBadge from '../../components/tasks/DeadlineBadge';

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
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
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

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  /**
   * 初回データ取得
   */
  useEffect(() => {
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
    const filtered = tasks.filter(task => {
      if (tagId === 0) {
        // 未分類バケット: タグなしタスク
        return !task.tags || task.tags.length === 0;
      } else {
        // 特定タグバケット: そのタグを持つタスク
        return task.tags?.some(tag => tag.id === tagId);
      }
    });
    
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
      const success = await toggleComplete(taskId);
      
      if (success) {
        // アバターイベント発火（アバターが完了を通知）
        dispatchAvatarEvent('task_completed');
        
        // タスク完了後、このタグのタスクが0件になった場合はタスク一覧に戻る
        const remainingTasks = filteredTasks.filter(task => 
          task.id !== taskId && !task.is_completed
        );
        
        if (remainingTasks.length === 0) {
          // 少し遅延させてアバター表示を見せる
          setTimeout(() => {
            navigation.navigate('TaskList');
          }, 500);
        }
      }
    },
    [toggleComplete, dispatchAvatarEvent, filteredTasks, navigation]
  );

  /**
   * タスク詳細/編集画面へ遷移
   */
  const navigateToDetail = useCallback(
    (taskId: number) => {
      const task = tasks.find(t => t.id === taskId);
      
      if (task?.is_group_task) {
        // グループタスク → 詳細画面（編集不可）
        navigation.navigate('TaskDetail', { taskId });
      } else {
        // 通常タスク → 編集画面
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
      const isGroupTask = item.is_group_task;

      // カードスタイルをタスク種別で変更
      const cardStyle = isGroupTask 
        ? [styles.taskItem, styles.groupTaskItem]
        : styles.taskItem;

      return (
        <TouchableOpacity
          style={cardStyle}
          onPress={() => navigateToDetail(item.id)}
          activeOpacity={0.7}
        >
          {/* 期限バッジ */}
          <DeadlineBadge 
            deadlineInfo={getDeadlineStatus(item, isChildTheme)} 
            variant="absolute" 
          />

          {/* グループタスクバッジ */}
          {isGroupTask && (
            <View style={styles.groupTaskBadge}>
              <LinearGradient
                colors={['#9333EA', '#EC4899']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.groupTaskBadgeGradient}
              >
                <Text style={styles.groupTaskBadgeText}>
                  {theme === 'child' ? '⭐グループ' : 'グループタスク'}
                </Text>
              </LinearGradient>
            </View>
          )}

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
                <View 
                  key={tag.id} 
                  style={[
                    styles.tagBadge,
                    isGroupTask && styles.groupTagBadge
                  ]}
                >
                  <Text style={styles.tagText}>{tag.name}</Text>
                </View>
              ))}
            </View>
          )}

          <View style={styles.taskFooter}>
            {/* グループタスクのみ報酬を表示 */}
            {isGroupTask && (
              <View style={styles.rewardContainer}>
                <LinearGradient
                  colors={['#F59E0B', '#F97316']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.rewardBadge}
                >
                  <Text style={styles.rewardText}>
                    {theme === 'child' ? '⭐' : '報酬'} {item.reward}
                    {theme === 'child' ? '' : 'トークン'}
                  </Text>
                </LinearGradient>
              </View>
            )}
            {item.due_date && (
              <Text style={styles.taskDueDate}>
                {theme === 'child' ? '⏰' : '期限:'} {item.due_date}
              </Text>
            )}
          </View>

          {isPending && (
            <View style={styles.completeButton}>
              <LinearGradient
                colors={['#10B981', '#059669']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.gradientButton}
              >
                <TouchableOpacity
                  style={styles.completeButtonTouchable}
                  onPress={() => handleToggleComplete(item.id)}
                >
                  <Text style={styles.completeButtonText}>
                    {theme === 'child' ? 'できた!' : '完了にする'}
                  </Text>
                </TouchableOpacity>
              </LinearGradient>
            </View>
          )}
        </TouchableOpacity>
      );
    },
    [theme, navigateToDetail, handleToggleComplete, styles]
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
      <View style={[
        styles.header,
        Platform.OS === 'android' && { paddingTop: StatusBar.currentHeight || 0 }
      ]}>
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
          <LinearGradient
            colors={['#8B5CF6', '#7C3AED']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.badge}
          >
            <Text style={styles.badgeText}>{filteredTasks.length}</Text>
          </LinearGradient>
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

/**
 * レスポンシブスタイル生成関数
 * 
 * @param width - 画面幅
 * @param theme - テーマ (adult | child)
 * @param colors - テーマカラー
 * @param accent - アクセントカラー
 * @returns StyleSheet
 */
const createStyles = (
  width: number,
  theme: 'adult' | 'child',
  colors: ReturnType<typeof useThemedColors>['colors'],
  accent: ReturnType<typeof useThemedColors>['accent']
) => StyleSheet.create({
  container: {
    flex: 1,
    // Web版child-theme.cssの.dashboard-gradient-bgに統一（#FFF8E1 クリーム色）
    backgroundColor: theme === 'child' ? '#FFF8E1' : colors.background,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: getSpacing(16, width),
    paddingTop: getSpacing(12, width),
    paddingBottom: getSpacing(16, width),
    // Web版のdashboard-header-blurに準拠（透過+ブラー効果）、画面背景色ベース
    backgroundColor: theme === 'child' ? 'rgba(255, 248, 225, 0.95)' : colors.card,
    borderBottomWidth: 1,
    borderBottomColor: theme === 'child' ? 'rgba(255, 107, 107, 0.2)' : colors.border.default,
  },
  backButton: {
    width: 40,
    height: 40,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: getSpacing(8, width),
  },
  backButtonText: {
    fontSize: getFontSize(24, width, theme),
    color: accent.primary,
    fontWeight: 'bold',
  },
  headerTitleContainer: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    flex: 1,
    marginRight: getSpacing(8, width),
  },
  badge: {
    borderRadius: getBorderRadius(12, width),
    paddingHorizontal: getSpacing(10, width),
    paddingVertical: getSpacing(4, width),
    minWidth: 32,
    alignItems: 'center',
  },
  badgeText: {
    color: '#FFFFFF',
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
  },
  listContent: {
    padding: getSpacing(16, width),
  },
  taskItem: {
    // Web版child-theme.cssの.task-card-modernに統一（太いボーダー+大きめサイズ）
    backgroundColor: theme === 'child' ? '#FFFFFF' : colors.card,
    borderRadius: getBorderRadius(theme === 'child' ? 20 : 12, width), // child: 1.25rem (20px)
    padding: getSpacing(theme === 'child' ? 20 : 16, width),
    marginBottom: getSpacing(12, width),
    // Web版child-theme.css: 太いボーダー（3px）
    borderWidth: theme === 'child' ? 3 : 0,
    borderColor: theme === 'child' ? '#FF6B6B' : 'transparent',
    ...getShadow(theme === 'child' ? 6 : 3),
  },
  groupTaskItem: {
    // Web版child-theme.cssに統一（クエスト専用=紫ボーダー）
    backgroundColor: theme === 'child' ? '#FFFFFF' : colors.card,
    borderWidth: theme === 'child' ? 4 : 2, // child: 4px（クエスト強調）
    // グループタスク=クエスト（var(--child-quest): #9b59b6）
    borderColor: theme === 'child' ? '#9b59b6' : accent.primary,
    ...getShadow(theme === 'child' ? 6 : 4),
  },
  groupTaskBadge: {
    position: 'absolute',
    top: -1,
    right: -1,
    zIndex: 1,
    borderTopRightRadius: getBorderRadius(11, width),
    borderBottomLeftRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  groupTaskBadgeGradient: {
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(4, width),
  },
  groupTaskBadgeText: {
    color: '#FFFFFF',
    fontSize: getFontSize(11, width, theme),
    fontWeight: 'bold',
  },
  taskHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: getSpacing(8, width),
  },
  taskTitle: {
    flex: 1,
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    marginRight: getSpacing(8, width),
  },
  completedText: {
    textDecorationLine: 'line-through',
    color: colors.text.disabled,
  },
  taskDescription: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(12, width),
    lineHeight: getFontSize(20, width, theme),
  },
  tagsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: getSpacing(8, width),
    marginBottom: getSpacing(12, width),
  },
  tagBadge: {
    backgroundColor: accent.primary,
    paddingHorizontal: getSpacing(10, width),
    paddingVertical: getSpacing(4, width),
    borderRadius: getBorderRadius(12, width),
  },
  groupTagBadge: {
    backgroundColor: `${accent.primary}15`,
    borderWidth: 1,
    borderColor: `${accent.primary}4D`,
  },
  tagText: {
    fontSize: getFontSize(12, width, theme),
    color: '#FFFFFF',
    fontWeight: '600',
  },
  taskFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: getSpacing(8, width),
  },
  rewardContainer: {
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  rewardBadge: {
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(8, width),
  },
  rewardText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '700',
    color: '#FFFFFF',
  },
  taskDueDate: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
  },
  completeButton: {
    marginTop: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
    ...getShadow(2),
  },
  gradientButton: {
    flex: 1,
    borderRadius: getBorderRadius(8, width),
  },
  completeButtonTouchable: {
    paddingVertical: getSpacing(10, width),
    alignItems: 'center',
    justifyContent: 'center',
  },
  completeButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: '#FFFFFF',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: getSpacing(60, width),
  },
  emptyText: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
  },
  footerLoading: {
    paddingVertical: getSpacing(20, width),
    alignItems: 'center',
  },
});
