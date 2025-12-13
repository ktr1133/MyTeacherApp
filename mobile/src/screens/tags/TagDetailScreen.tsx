/**
 * タグ詳細画面（タスク紐付け・解除管理）
 * 
 * Web版との整合性（mobile-rules.md総則4項準拠）:
 * - タグに紐づくタスク一覧と未紐付けタスク一覧を表示
 * - タスクの紐付け・解除操作をサポート
 * - Web版にはタグ詳細専用画面がないが、APIは実装済み
 * - モバイルUXに最適化した2セクション構成
 * 
 * @see /home/ktr/mtdev/app/Http/Actions/Tags/TagTaskAction.php (Web版API)
 * @see /home/ktr/mtdev/docs/mobile/mobile-rules.md (モバイル開発規則)
 */

import { useEffect, useState, useMemo } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Alert,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useTagTasks } from '../../hooks/useTagTasks';
import { useTheme } from '../../contexts/ThemeContext';
import type { Tag } from '../../types/tag.types';

type RootStackParamList = {
  TagDetail: { tag: Tag };
};

type Props = NativeStackScreenProps<RootStackParamList, 'TagDetail'>;

/**
 * タグ詳細画面コンポーネント
 */
export const TagDetailScreen = ({ route }: Props) => {
  const { tag } = route.params;
  const { theme } = useTheme();
  const { width } = useResponsive();
  const {
    linkedTasks,
    availableTasks,
    loading,
    error,
    attaching,
    detaching,
    fetchTagTasks,
    attachTask,
    detachTask,
    clearError,
  } = useTagTasks();
  const [refreshing, setRefreshing] = useState(false);

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, theme), [width, theme]);

  /**
   * 初回データ取得
   */
  useEffect(() => {
    fetchTagTasks(tag.id);
  }, [tag.id, fetchTagTasks]);

  /**
   * エラー表示
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
   * Pull-to-Refresh
   */
  const handleRefresh = async () => {
    setRefreshing(true);
    await fetchTagTasks(tag.id);
    setRefreshing(false);
  };

  /**
   * タスク紐付け確認
   */
  const confirmAttachTask = (taskId: number, taskTitle: string) => {
    Alert.alert(
      theme === 'child' ? 'タスクを つける' : 'タスクを紐付ける',
      theme === 'child'
        ? `「${taskTitle}」を「${tag.name}」につける？`
        : `「${taskTitle}」を「${tag.name}」に紐付けますか？`,
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'つける' : '紐付ける',
          onPress: async () => {
            const success = await attachTask(tag.id, taskId);
            if (success) {
              Alert.alert(
                theme === 'child' ? 'やったね！' : '成功',
                theme === 'child'
                  ? 'タスクを つけたよ！'
                  : 'タスクを紐付けました。'
              );
            }
          },
        },
      ]
    );
  };

  /**
   * タスク解除確認
   */
  const confirmDetachTask = (taskId: number, taskTitle: string) => {
    Alert.alert(
      theme === 'child' ? 'タスクを はずす' : 'タスクを解除',
      theme === 'child'
        ? `「${taskTitle}」を「${tag.name}」から はずす？`
        : `「${taskTitle}」を「${tag.name}」から解除しますか？`,
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'はずす' : '解除',
          style: 'destructive',
          onPress: async () => {
            const success = await detachTask(tag.id, taskId);
            if (success) {
              Alert.alert(
                theme === 'child' ? 'やったね！' : '成功',
                theme === 'child'
                  ? 'タスクを はずしたよ！'
                  : 'タスクを解除しました。'
              );
            }
          },
        },
      ]
    );
  };

  /**
   * 紐づいているタスクのレンダリング
   */
  const renderLinkedTask = ({ item }: { item: { id: number; title: string } }) => (
    <View style={styles.taskCard}>
      <View style={styles.taskInfo}>
        <Text style={styles.taskTitle}>{item.title}</Text>
      </View>
      <View style={[styles.actionButton, styles.detachButton]}>
        <LinearGradient
          colors={['#EF4444', '#DC2626']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={styles.gradientButton}
        >
          <TouchableOpacity
            style={styles.actionButtonTouchable}
            onPress={() => confirmDetachTask(item.id, item.title)}
            disabled={detaching}
          >
            <Text style={styles.buttonText}>
              {theme === 'child' ? 'はずす' : '解除'}
            </Text>
          </TouchableOpacity>
        </LinearGradient>
      </View>
    </View>
  );

  /**
   * 追加可能なタスクのレンダリング
   */
  const renderAvailableTask = ({ item }: { item: { id: number; title: string } }) => (
    <View style={styles.taskCard}>
      <View style={styles.taskInfo}>
        <Text style={styles.taskTitle}>{item.title}</Text>
      </View>
      <View style={[styles.actionButton, styles.attachButton]}>
        <LinearGradient
          colors={['#10B981', '#059669']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={styles.gradientButton}
        >
          <TouchableOpacity
            style={styles.actionButtonTouchable}
            onPress={() => confirmAttachTask(item.id, item.title)}
            disabled={attaching}
          >
            <Text style={styles.buttonText}>
              {theme === 'child' ? 'つける' : '追加'}
            </Text>
          </TouchableOpacity>
        </LinearGradient>
      </View>
    </View>
  );

  /**
   * 空状態のレンダリング
   */
  const renderEmptyLinked = () => (
    <View style={styles.emptyState}>
      <Text style={styles.emptyText}>
        {theme === 'child'
          ? 'まだ タスクが ついていないよ'
          : 'このタグに紐づくタスクはありません'}
      </Text>
    </View>
  );

  const renderEmptyAvailable = () => (
    <View style={styles.emptyState}>
      <Text style={styles.emptyText}>
        {theme === 'child'
          ? 'つけられる タスクが ないよ'
          : '追加可能なタスクがありません'}
      </Text>
    </View>
  );

  /**
   * ローディング表示
   */
  if (loading && !refreshing) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3B82F6" />
        <Text style={styles.loadingText}>
          {theme === 'child' ? 'よみこみちゅう...' : '読み込み中...'}
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* ヘッダー情報 */}
      <View style={[styles.header, { borderLeftColor: tag.color }]}>
        <Text style={styles.tagName}>{tag.name}</Text>
        <LinearGradient
          colors={['#3B82F6', '#9333EA']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={styles.taskCountBadge}
        >
          <Text style={styles.taskCountText}>{linkedTasks.length}</Text>
        </LinearGradient>
      </View>

      {/* セクション: 紐づいているタスク */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          {theme === 'child' ? 'ついている タスク' : '紐づいているタスク'}
          <Text style={styles.sectionCount}> ({linkedTasks.length})</Text>
        </Text>
        <FlatList
          data={linkedTasks}
          renderItem={renderLinkedTask}
          keyExtractor={(item) => `linked-${item.id}`}
          ListEmptyComponent={renderEmptyLinked}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              colors={['#3B82F6']}
            />
          }
          contentContainerStyle={linkedTasks.length === 0 ? styles.emptyList : undefined}
        />
      </View>

      {/* セクション: 追加可能なタスク */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          {theme === 'child' ? 'つけられる タスク' : '追加可能なタスク'}
          <Text style={styles.sectionCount}> ({availableTasks.length})</Text>
        </Text>
        <FlatList
          data={availableTasks}
          renderItem={renderAvailableTask}
          keyExtractor={(item) => `available-${item.id}`}
          ListEmptyComponent={renderEmptyAvailable}
          contentContainerStyle={availableTasks.length === 0 ? styles.emptyList : undefined}
        />
      </View>
    </View>
  );
};

const createStyles = (width: number, theme: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F9FAFB',
  },
  loadingText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
  },
  header: {
    backgroundColor: '#FFFFFF',
    padding: getSpacing(16, width),
    borderLeftWidth: 4,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    ...getShadow(3),
  },
  tagName: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: 'bold',
    color: '#1F2937',
  },
  taskCountBadge: {
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(12, width),
  },
  taskCountText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
    fontSize: getFontSize(14, width, theme),
  },
  section: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    marginTop: getSpacing(8, width),
    padding: getSpacing(16, width),
  },
  sectionTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: getSpacing(12, width),
  },
  sectionCount: {
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
    fontWeight: 'normal',
  },
  taskCard: {
    backgroundColor: '#FFFFFF',
    padding: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(8, width),
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  taskInfo: {
    flex: 1,
    marginRight: getSpacing(12, width),
  },
  taskTitle: {
    fontSize: getFontSize(14, width, theme),
    color: '#1F2937',
  },
  actionButton: {
    width: getSpacing(70, width),
    height: getSpacing(32, width),
    borderRadius: getBorderRadius(6, width),
    overflow: 'hidden',
    ...getShadow(2),
  },
  gradientButton: {
    flex: 1,
    borderRadius: getBorderRadius(6, width),
  },
  actionButtonTouchable: {
    width: '100%',
    height: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },
  attachButton: {
    // LinearGradient適用のためbackgroundColor削除
  },
  detachButton: {
    // LinearGradient適用のためbackgroundColor削除
  },
  buttonText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
    fontSize: getFontSize(12, width, theme),
  },
  emptyList: {
    flexGrow: 1,
  },
  emptyState: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: getSpacing(48, width),
  },
  emptyText: {
    fontSize: getFontSize(14, width, theme),
    color: '#9CA3AF',
    textAlign: 'center',
  },
});
