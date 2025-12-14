/**
 * タスク一覧画面
 * 
 * タグ別バケット表示（Web版整合性）
 * 検索時のみタスクカード表示に切り替え
 */
import { useEffect, useState, useCallback, useMemo, useLayoutEffect } from 'react';
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
  Pressable,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { DrawerActions } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import MaskedView from '@react-native-masked-view/masked-view';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { Task } from '../../types/task.types';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useAvatar } from '../../hooks/useAvatar';
import { useProfile } from '../../hooks/useProfile';
import { useThemedColors } from '../../hooks/useThemedColors';
import AvatarWidget from '../../components/common/AvatarWidget';
import AvatarCreationBanner from '../../components/common/AvatarCreationBanner';
import BucketCard from '../../components/tasks/BucketCard';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * バケット型定義
 */
interface Bucket {
  id: number;
  name: string;
  tasks: Task[];
}

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  TaskList: undefined;
  TaskDetail: { taskId: number };
  TaskEdit: { taskId: number };
  CreateTask: undefined;
  TagTasks: { tagId: number; tagName: string };
  GroupTaskList: undefined;
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;

/**
 * タスク一覧画面コンポーネント
 */
export default function TaskListScreen() {
  const navigation = useNavigation<NavigationProp>();
  const { theme } = useTheme();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
  const { profile, isLoading: profileLoading } = useProfile();
  
  const {
    tasks,
    isLoading,
    isLoadingMore,
    hasMore,
    error,
    fetchTasks,
    loadMoreTasks,
    toggleComplete,
    clearError,
  } = useTasks();
  const {
    isVisible: avatarVisible,
    currentData: avatarData,
    dispatchAvatarEvent,
    hideAvatar,
  } = useAvatar();

  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [filteredTasks, setFilteredTasks] = useState<Task[]>([]);
  const [buckets, setBuckets] = useState<Bucket[]>([]);

  /**
   * タブレット対応: 1カラム固定（視認性優先）
   * ResponsiveDesignGuideline.md Section 9参照
   */
  const numColumns = 1;

  /**
   * タスク作成画面へ遷移
   */
  const navigateToCreate = useCallback(() => {
    navigation.navigate('CreateTask');
  }, [navigation]);

  /**
   * React Navigationヘッダーのカスタマイズ
   * Web版デザイン（カラー、ボタン配置）を適用
   */
  useLayoutEffect(() => {
    navigation.setOptions({
      title: theme === 'child' ? 'やること' : 'タスク一覧',
      headerLeft: () => (
        <TouchableOpacity
          onPress={() => navigation.dispatch(DrawerActions.openDrawer())}
          style={{
            marginLeft: 16,
            padding: 4,
          }}
        >
          <Ionicons name="menu" size={28} color={colors.text.primary as string} />
        </TouchableOpacity>
      ),
      headerBackVisible: false, // iOS: 戻るボタンを非表示
      headerRight: () => (
        <Pressable
          onPress={navigateToCreate}
          style={({ pressed }) => ({
            marginRight: 0,
            paddingHorizontal: 8,
            paddingVertical: 4,
            opacity: pressed ? 0.7 : 1,
          })}
        >
          <MaskedView
            maskElement={
              <Text style={{
                fontSize: 36,
                fontWeight: '700',
                lineHeight: 36,
                backgroundColor: 'transparent',
              }}>
                ＋
              </Text>
            }
          >
            <LinearGradient
              colors={[accent.primary, accent.primary] as const}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={{
                width: 36,
                height: 36,
              }}
            />
          </MaskedView>
        </Pressable>
      ),
      headerStyle: {
        backgroundColor: colors.card as string,
      },
      headerTintColor: accent.primary as string,
      headerTitleStyle: {
        fontSize: getFontSize(20, width, themeType),
        fontWeight: '600',
        color: colors.text.primary as string,
      },
    });
  }, [navigation, theme, width, themeType, navigateToCreate, colors, accent]);

  /**
   * タスクをタグ別にグループ化してバケットを生成
   */
  const groupTasksIntoBuckets = useCallback((taskList: Task[]): Bucket[] => {
    const bucketMap: { [key: number]: Bucket } = {};

    taskList.forEach(task => {
      if (task.tags && task.tags.length > 0) {
        // 複数タグを持つタスクは各バケットに追加
        task.tags.forEach(tag => {
          if (!bucketMap[tag.id]) {
            bucketMap[tag.id] = {
              id: tag.id,
              name: tag.name,
              tasks: [],
            };
          }
          bucketMap[tag.id].tasks.push(task);
        });
      } else {
        // タグなしタスクは「未分類」バケット
        if (!bucketMap[0]) {
          bucketMap[0] = {
            id: 0,
            name: theme === 'child' ? 'そのほか' : '未分類',
            tasks: [],
          };
        }
        bucketMap[0].tasks.push(task);
      }
    });

    // タスク件数降順でソート
    return Object.values(bucketMap).sort((a, b) => b.tasks.length - a.tasks.length);
  }, [theme]);

  /**
   * タスク一覧を取得（未完了のみ）
   * バケット表示のため全件取得（per_page=100で明示）
   */
  const loadTasks = useCallback(async () => {
    await fetchTasks({ status: 'pending', per_page: 100 });
  }, [fetchTasks]);

  /**
   * 初回データ取得
   */
  useEffect(() => {
    loadTasks();
  }, [loadTasks]);

  /**
   * 画面フォーカス時: タスクリストを再同期
   * （編集・削除後に前画面に戻った際、変更を即座に反映するため）
   */
  useFocusEffect(
    useCallback(() => {
      loadTasks();
    }, [loadTasks])
  );

  /**
   * タスクデータまたは検索クエリ変更時にフィルタリング
   */
  useEffect(() => {
    if (searchQuery.trim()) {
      // 検索クエリがある場合: ローカルフィルタリング
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
      setFilteredTasks(filtered);
      setBuckets([]);
    } else {
      // 検索クエリがない場合: バケット表示
      setFilteredTasks([]);
      setBuckets(groupTasksIntoBuckets(tasks));
    }
  }, [searchQuery, tasks, groupTasksIntoBuckets]);

  /**
   * Pull-to-Refresh
   * バケット表示用の全件取得を実行
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadTasks();
    setRefreshing(false);
  }, [loadTasks]);

  /**
   * 無限スクロール: リスト末尾到達時の処理
   * 現状は全件取得（per_page=100）のため、基本的に無効
   * 100件超のユーザーのみ動作
   */
  const handleLoadMore = useCallback(() => {
    // バケット表示時のみ（検索時は全件ローカルフィルタ済み）
    if (!searchQuery.trim() && !isLoadingMore && hasMore) {
      console.log('[TaskListScreen] Loading more tasks... (over 100 tasks)');
      loadMoreTasks();
    }
  }, [searchQuery, isLoadingMore, hasMore, loadMoreTasks]);

  /**
   * タスク完了切り替え
   */
  const handleToggleComplete = useCallback(
    async (taskId: number) => {
      const success = await toggleComplete(taskId);
      
      if (success) {
        // アバターイベント発火（アバターが完了を通知）
        dispatchAvatarEvent('task_completed');
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
   * バケットアイテムをレンダリング
   */
  const renderBucketItem = useCallback(
    ({ item }: { item: Bucket }) => {
      return (
        <BucketCard
          tagId={item.id}
          tagName={item.name}
          tasks={item.tasks}
          onPress={() => {
            navigation.navigate('TagTasks', { tagId: item.id, tagName: item.name });
          }}
          theme={theme}
        />
      );
    },
    [theme, navigation]
  );

  /**
   * タスクアイテムをレンダリング（検索時のみ使用）
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

    // 検索結果が0件の場合
    if (searchQuery.trim()) {
      return (
        <View style={styles.emptyContainer}>
          <Text style={styles.emptyText}>
            {theme === 'child' 
              ? 'みつからなかったよ' 
              : '検索結果が見つかりません'}
          </Text>
          <Text style={styles.emptySubtext}>
            {theme === 'child'
              ? 'ちがうことばでさがしてみてね'
              : '別のキーワードで検索してください'}
          </Text>
        </View>
      );
    }

    // タスクが0件の場合
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
  }, [isLoading, theme, searchQuery]);

  /**
   * フッターローディング表示（無限スクロール用）
   */
  const renderFooter = useCallback(() => {
    if (!isLoadingMore) {
      return null;
    }

    return (
      <View style={styles.footerLoading}>
        <ActivityIndicator size="small" color="#4F46E5" />
        <Text style={styles.loadingText}>読み込み中...</Text>
      </View>
    );
  }, [isLoadingMore]);

  /**
   * レスポンシブスタイル生成
   * 画面幅・デバイスサイズ・テーマに基づいて動的に計算
   */
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  return (
    <View style={styles.container}>
      {/* アバター未作成バナー（条件付き表示） */}
      {!profileLoading && profile && (
        <AvatarCreationBanner />
      )}

      {/* 検索バー */}
      <View style={styles.searchContainer}>
        <View style={styles.searchBarWrapper}>
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
        
        {/* グループタスク管理ボタン（権限チェック） */}
        {(profile?.group_edit_flg || profile?.group?.master_user_id === profile?.id) && (
          <TouchableOpacity
            style={styles.groupEditHeaderButton}
            onPress={() => {
              navigation.navigate('GroupTaskList');
            }}
            accessibilityLabel={theme === 'child' ? 'グループタスクかんり' : 'グループタスク管理'}
          >
            <Ionicons name="create-outline" size={24} color="#7C3AED" />
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

      {/* バケット一覧 or タスク一覧 */}
      {searchQuery.trim() ? (
        /* 検索時: タスクカード表示（ローカルフィルタのため無限スクロール不要） */
        <FlatList
          data={filteredTasks}
          renderItem={renderTaskItem}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#4F46E5']} />
          }
          ListEmptyComponent={renderEmptyList}
        />
      ) : (
        /* 通常時: バケット表示（100件超のユーザーのみ無限スクロール） */
        <FlatList
          key={numColumns} // numColumns変更時に再レンダリング
          data={buckets}
          renderItem={renderBucketItem}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContent}
          numColumns={numColumns} // タブレット対応: 2カラム表示
          columnWrapperStyle={numColumns > 1 ? styles.columnWrapper : undefined}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#4F46E5']} />
          }
          ListEmptyComponent={renderEmptyList}
          ListFooterComponent={renderFooter}
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.3}
        />
      )}

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

/**
 * レスポンシブスタイル生成関数
 * 
 * @param width - 画面幅
 * @param theme - テーマタイプ
 * @param colors - テーマカラーパレット
 * @param accent - アクセントカラー
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  searchContainer: {
    flexDirection: 'row' as const,
    alignItems: 'center' as const,
    gap: getSpacing(8, width),
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(8, width),
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.default,
  },
  searchBarWrapper: {
    flex: 1,
    flexDirection: 'row' as const,
    alignItems: 'center' as const,
  },
  searchInput: {
    flex: 1,
    height: getSpacing(40, width),
    paddingHorizontal: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.border.light,
    fontSize: getFontSize(14, width, theme),
    color: colors.text.primary,
  },
  groupEditHeaderButton: {
    width: getSpacing(48, width),
    height: getSpacing(48, width),
    justifyContent: 'center' as const,
    alignItems: 'center' as const,
    borderRadius: getBorderRadius(12, width),
    backgroundColor: colors.background,
    shadowColor: '#7C3AED',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 4,
    elevation: 3,
  },
  clearButton: {
    marginLeft: getSpacing(8, width),
    width: getSpacing(32, width),
    height: getSpacing(32, width),
    justifyContent: 'center' as const,
    alignItems: 'center' as const,
  },
  clearButtonText: {
    fontSize: getFontSize(18, width, theme),
    color: colors.text.disabled,
    fontWeight: 'bold' as const,
  },
  searchResultContainer: {
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(8, width),
    backgroundColor: accent.primary + '15',
  },
  searchResultText: {
    fontSize: getFontSize(14, width, theme),
    color: accent.primary,
    fontWeight: '600' as const,
  },
  columnWrapper: {
    justifyContent: 'space-between' as const,
    gap: getSpacing(16, width),
  },
  filterContainer: {
    flexDirection: 'row' as const,
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    backgroundColor: colors.card,
    gap: getSpacing(8, width),
  },
  filterButton: {
    flex: 1,
    paddingVertical: getSpacing(8, width),
    paddingHorizontal: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.border.light,
    alignItems: 'center' as const,
  },
  filterButtonActive: {
    backgroundColor: accent.primary,
  },
  filterButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600' as const,
    color: colors.text.secondary,
  },
  filterButtonTextActive: {
    color: '#FFFFFF',
  },
  listContent: {
    padding: getSpacing(16, width),
  },
  taskItem: {
    backgroundColor: colors.card,
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
    color: colors.text.primary,
    marginRight: 8,
  },
  completedText: {
    textDecorationLine: 'line-through' as const,
    color: colors.text.disabled,
  },
  statusBadge: {
    paddingHorizontal: getSpacing(8, width),
    paddingVertical: getSpacing(4, width),
    borderRadius: getBorderRadius(4, width),
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
    fontSize: getFontSize(12, width, theme),
    fontWeight: '600' as const,
    color: colors.text.secondary,
  },
  taskDescription: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(12, width),
    lineHeight: getFontSize(20, width, theme),
  },
  tagsContainer: {
    flexDirection: 'row' as const,
    flexWrap: 'wrap' as const,
    gap: getSpacing(8, width),
    marginBottom: getSpacing(12, width),
  },
  tagBadge: {
    backgroundColor: accent.primary + '15',
    paddingHorizontal: getSpacing(10, width),
    paddingVertical: getSpacing(4, width),
    borderRadius: getBorderRadius(12, width),
  },
  tagText: {
    fontSize: getFontSize(12, width, theme),
    color: accent.primary,
    fontWeight: '600' as const,
  },
  taskFooter: {
    flexDirection: 'row' as const,
    justifyContent: 'space-between' as const,
    alignItems: 'center' as const,
  },
  taskReward: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600' as const,
    color: '#F59E0B',
  },
  taskDueDate: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.disabled,
  },
  completeButton: {
    marginTop: getSpacing(12, width),
    backgroundColor: '#10B981',
    borderRadius: getBorderRadius(8, width),
    paddingVertical: getSpacing(10, width),
    alignItems: 'center' as const,
  },
  completeButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600' as const,
    color: '#FFFFFF',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center' as const,
    alignItems: 'center' as const,
    paddingVertical: getSpacing(60, width),
  },
  emptyText: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600' as const,
    color: colors.text.secondary,
    marginBottom: getSpacing(8, width),
  },
  emptySubtext: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.disabled,
  },
  footerLoading: {
    paddingVertical: getSpacing(20, width),
    alignItems: 'center' as const,
    flexDirection: 'row' as const,
    justifyContent: 'center' as const,
    gap: getSpacing(10, width),
  },
  loadingText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginLeft: getSpacing(8, width),
  },
});
