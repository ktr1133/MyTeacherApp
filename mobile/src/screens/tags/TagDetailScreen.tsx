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

import React, { useEffect, useState } from 'react';
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
export const TagDetailScreen: React.FC<Props> = ({ route }) => {
  const { tag } = route.params;
  const { theme } = useTheme();
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
      <TouchableOpacity
        style={[styles.actionButton, styles.detachButton]}
        onPress={() => confirmDetachTask(item.id, item.title)}
        disabled={detaching}
      >
        <Text style={styles.buttonText}>
          {theme === 'child' ? 'はずす' : '解除'}
        </Text>
      </TouchableOpacity>
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
      <TouchableOpacity
        style={[styles.actionButton, styles.attachButton]}
        onPress={() => confirmAttachTask(item.id, item.title)}
        disabled={attaching}
      >
        <Text style={styles.buttonText}>
          {theme === 'child' ? 'つける' : '追加'}
        </Text>
      </TouchableOpacity>
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
        <View style={[styles.taskCountBadge, { backgroundColor: tag.color }]}>
          <Text style={styles.taskCountText}>{linkedTasks.length}</Text>
        </View>
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

const styles = StyleSheet.create({
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
    marginTop: 12,
    fontSize: 14,
    color: '#6B7280',
  },
  header: {
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderLeftWidth: 4,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  tagName: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1F2937',
  },
  taskCountBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
  },
  taskCountText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
    fontSize: 14,
  },
  section: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    marginTop: 8,
    padding: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 12,
  },
  sectionCount: {
    fontSize: 14,
    color: '#6B7280',
    fontWeight: 'normal',
  },
  taskCard: {
    backgroundColor: '#FFFFFF',
    padding: 12,
    borderRadius: 8,
    marginBottom: 8,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  taskInfo: {
    flex: 1,
    marginRight: 12,
  },
  taskTitle: {
    fontSize: 14,
    color: '#1F2937',
  },
  actionButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 6,
  },
  attachButton: {
    backgroundColor: '#10B981',
  },
  detachButton: {
    backgroundColor: '#EF4444',
  },
  buttonText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
    fontSize: 12,
  },
  emptyList: {
    flexGrow: 1,
  },
  emptyState: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 48,
  },
  emptyText: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
  },
});
