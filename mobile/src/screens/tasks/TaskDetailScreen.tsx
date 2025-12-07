/**
 * タスク詳細画面
 * 
 * タスク詳細表示、承認/却下機能、画像アップロード
 */
import { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  ScrollView,
  Alert,
  ActivityIndicator,
  Image,
  TextInput,
} from 'react-native';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { Task, TaskStatus } from '../../types/task.types';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import * as ImagePicker from 'expo-image-picker';
import { useAvatar } from '../../hooks/useAvatar';
import AvatarWidget from '../../components/common/AvatarWidget';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  TaskList: undefined;
  TaskDetail: { taskId: number };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;
type RouteProps = RouteProp<RootStackParamList, 'TaskDetail'>;

/**
 * タスク詳細画面コンポーネント
 */
export default function TaskDetailScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<RouteProps>();
  const { theme } = useTheme();
  const {
    tasks,
    isLoading,
    error,
    fetchTasks,
    getTask,
    deleteTask,
    toggleComplete,
    approveTask,
    rejectTask,
    uploadImage,
    deleteImage: removeImage,
    clearError,
  } = useTasks();
  const {
    isVisible: avatarVisible,
    currentData: avatarData,
    dispatchAvatarEvent,
    hideAvatar,
  } = useAvatar();

  // アバター状態をログ出力
  console.log('🎭 [TaskDetailScreen] Avatar state:', { avatarVisible, hasAvatarData: !!avatarData });

  const { taskId } = route.params;
  const [task, setTask] = useState<Task | null>(null);
  const [approvalComment, setApprovalComment] = useState('');
  const [showApprovalInput, setShowApprovalInput] = useState(false);
  const [showRejectInput, setShowRejectInput] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  /**
   * タスク詳細を取得
   */
  useEffect(() => {
    const loadTask = async () => {
      console.log('[TaskDetailScreen] loadTask - taskId:', taskId);
      console.log('[TaskDetailScreen] loadTask - tasks count:', tasks.length);
      
      let foundTask = tasks.find((t) => t.id === taskId);
      
      if (!foundTask) {
        console.log('[TaskDetailScreen] Task not found in current tasks, calling getTask API...');
        foundTask = await getTask(taskId);
        console.log('[TaskDetailScreen] getTask result:', foundTask ? `id=${foundTask.id}` : 'null');
      } else {
        console.log('[TaskDetailScreen] foundTask from existing tasks:', `id=${foundTask.id}`);
      }
      
      setTask(foundTask || null);
    };

    loadTask();
  }, [taskId, tasks, getTask]);

  /**
   * タスク完了切り替え
   */
  const handleToggleComplete = useCallback(async () => {
    if (!task) return;

    setIsSubmitting(true);
    console.log('🎭 [TaskDetailScreen] handleToggleComplete called:', { taskId, taskTitle: task.title });
    const success = await toggleComplete(taskId);
    console.log('🎭 [TaskDetailScreen] toggleComplete result:', { success });
    
    if (success) {
      // アバターイベント発火
      console.log('🎭 [TaskDetailScreen] Firing avatar event: task_completed');
      dispatchAvatarEvent('task_completed');
      console.log('🎭 [TaskDetailScreen] dispatchAvatarEvent called');

      // アバター表示後にアラート表示（3秒待機）
      setTimeout(() => {
        setIsSubmitting(false);
        Alert.alert(
          theme === 'child' ? 'やったね!' : '完了',
          theme === 'child' ? 'やることをおわらせたよ!' : 'タスクを完了しました'
        );
      }, 3000);

      // タスクを再取得
      await fetchTasks();
    } else {
      setIsSubmitting(false);
    }
  }, [task, taskId, toggleComplete, theme, fetchTasks, dispatchAvatarEvent]);

  /**
   * タスク削除
   */
  const handleDelete = useCallback(async () => {
    Alert.alert(
      theme === 'child' ? 'けす?' : '削除確認',
      theme === 'child' 
        ? 'ほんとうにけしてもいい?' 
        : '本当にこのタスクを削除しますか?',
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'けす' : '削除',
          style: 'destructive',
          onPress: async () => {
            const success = await deleteTask(taskId);
            if (success) {
              navigation.goBack();
            }
          },
        },
      ]
    );
  }, [taskId, deleteTask, theme, navigation]);

  /**
   * タスク承認
   */
  const handleApprove = useCallback(async () => {
    const success = await approveTask(taskId, approvalComment || undefined);
    if (success) {
      Alert.alert(
        theme === 'child' ? 'OK!' : '承認完了',
        theme === 'child' ? 'しょうにんしたよ!' : 'タスクを承認しました'
      );
      setShowApprovalInput(false);
      setApprovalComment('');
      await fetchTasks();
    }
  }, [taskId, approvalComment, approveTask, theme, fetchTasks]);

  /**
   * タスク却下
   */
  const handleReject = useCallback(async () => {
    if (!approvalComment.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' 
          ? 'りゆうをいれてね' 
          : '却下理由を入力してください'
      );
      return;
    }

    const success = await rejectTask(taskId, approvalComment);
    if (success) {
      Alert.alert(
        theme === 'child' ? 'やりなおし' : '却下完了',
        theme === 'child' ? 'やりなおしにしたよ' : 'タスクを却下しました'
      );
      setShowRejectInput(false);
      setApprovalComment('');
      await fetchTasks();
    }
  }, [taskId, approvalComment, rejectTask, theme, fetchTasks]);

  /**
   * 画像選択・アップロード
   */
  const handleImagePick = useCallback(async () => {
    // カメラロールの権限をリクエスト
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    
    if (status !== 'granted') {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' 
          ? 'しゃしんをつかうきょかがひつようだよ' 
          : '写真へのアクセス許可が必要です'
      );
      return;
    }

    // 画像を選択
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      allowsEditing: true,
      aspect: [4, 3],
      quality: 0.8,
    });

    if (!result.canceled && result.assets[0]) {
      const success = await uploadImage(taskId, result.assets[0].uri);
      if (success) {
        Alert.alert(
          theme === 'child' ? 'できたよ!' : 'アップロード完了',
          theme === 'child' ? 'しゃしんをおくったよ!' : '画像をアップロードしました'
        );
        await fetchTasks();
      }
    }
  }, [taskId, uploadImage, theme, fetchTasks]);

  /**
   * 画像削除
   */
  const handleImageDelete = useCallback(
    async (imageId: number) => {
      Alert.alert(
        theme === 'child' ? 'けす?' : '削除確認',
        theme === 'child' ? 'しゃしんをけしてもいい?' : 'この画像を削除しますか?',
        [
          {
            text: theme === 'child' ? 'やめる' : 'キャンセル',
            style: 'cancel',
          },
          {
            text: theme === 'child' ? 'けす' : '削除',
            style: 'destructive',
            onPress: async () => {
              const success = await removeImage(taskId, imageId);
              if (success) {
                await fetchTasks();
              }
            },
          },
        ]
      );
    },
    [taskId, removeImage, theme, fetchTasks]
  );

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

  if (!task) {
    return (
      <View style={styles.container}>
        <ActivityIndicator size="large" color="#4F46E5" />
      </View>
    );
  }

  // タスクのステータス判定（is_completed + approved_at）
  const isPending = !task.is_completed;
  const isCompleted = task.is_completed && !task.requires_approval;
  const isApproved = task.is_completed && task.requires_approval && task.approved_at !== null;
  const isPendingApproval = task.is_completed && task.requires_approval && task.approved_at === null;

  // 表示用のステータス文字列
  const displayStatus: TaskStatus = isApproved ? 'approved' : isPendingApproval ? 'pending' : isCompleted ? 'completed' : 'pending';

  return (
    <View style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backButtonText}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>
          {theme === 'child' ? 'やることのくわしいこと' : 'タスク詳細'}
        </Text>
        {/* グループタスクは削除ボタン非表示 */}
        {!task?.is_group_task && (
          <TouchableOpacity onPress={handleDelete} style={styles.deleteButton}>
            <Text style={styles.deleteButtonText}>🗑️</Text>
          </TouchableOpacity>
        )}
        {task?.is_group_task && <View style={styles.deleteButton} />}
      </View>

      <ScrollView style={styles.content} contentContainerStyle={styles.contentContainer}>
        {/* タイトル */}
        <View style={styles.section}>
          <Text style={styles.title}>{task.title}</Text>
          <View style={[styles.statusBadge, getStatusStyle(displayStatus)]}>
            <Text style={styles.statusText}>{getStatusLabel(displayStatus, theme)}</Text>
          </View>
        </View>

        {/* 説明 */}
        {task.description && (
          <View style={styles.section}>
            <Text style={styles.sectionLabel}>
              {theme === 'child' ? 'せつめい' : '説明'}
            </Text>
            <Text style={styles.description}>{task.description}</Text>
          </View>
        )}

        {/* 詳細情報 */}
        <View style={styles.section}>
          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>
              {theme === 'child' ? 'ほうび' : '報酬'}:
            </Text>
            <Text style={styles.infoValue}>
              {task.reward} {theme === 'child' ? '⭐' : 'トークン'}
            </Text>
          </View>

          {task.due_date && (
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>
                {theme === 'child' ? 'きげん' : '期限'}:
              </Text>
              <Text style={styles.infoValue}>{task.due_date}</Text>
            </View>
          )}

          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>
              {theme === 'child' ? 'だいじさ' : '優先度'}:
            </Text>
            <Text style={styles.infoValue}>{task.priority}</Text>
          </View>

          {task.requires_approval && (
            <View style={styles.infoRow}>
              <Text style={styles.badge}>
                {theme === 'child' ? 'かくにんがひつよう' : '承認が必要'}
              </Text>
            </View>
          )}

          {task.requires_image && (
            <View style={styles.infoRow}>
              <Text style={styles.badge}>
                {theme === 'child' ? 'しゃしんがひつよう' : '画像が必要'}
              </Text>
            </View>
          )}

          {task.is_group_task && (
            <View style={styles.infoRow}>
              <Text style={styles.badge}>
                {theme === 'child' ? 'みんなのやること' : 'グループタスク'}
              </Text>
            </View>
          )}
        </View>

        {/* 画像一覧 */}
        {task.images.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionLabel}>
              {theme === 'child' ? 'しゃしん' : '画像'}
            </Text>
            <View style={styles.imageGrid}>
              {task.images.map((image) => (
                <View key={image.id} style={styles.imageContainer}>
                  <Image source={{ uri: image.url }} style={styles.image} />
                  <TouchableOpacity
                    style={styles.imageDeleteButton}
                    onPress={() => handleImageDelete(image.id)}
                  >
                    <Text style={styles.imageDeleteButtonText}>✕</Text>
                  </TouchableOpacity>
                </View>
              ))}
            </View>
          </View>
        )}

        {/* 画像アップロードボタン */}
        <TouchableOpacity style={styles.uploadButton} onPress={handleImagePick}>
          <Text style={styles.uploadButtonText}>
            {theme === 'child' ? 'しゃしんをつける' : '画像をアップロード'}
          </Text>
        </TouchableOpacity>

        {/* アクションボタン */}
        {isPending && (
          <TouchableOpacity
            style={styles.completeButton}
            onPress={handleToggleComplete}
            disabled={isLoading || isSubmitting}
          >
            <Text style={styles.completeButtonText}>
              {theme === 'child' ? 'できた!' : '完了にする'}
            </Text>
          </TouchableOpacity>
        )}

        {/* 承認/却下ボタン（完了済みタスクのみ） */}
        {isCompleted && task.requires_approval && (
          <View style={styles.approvalSection}>
            {!showApprovalInput && !showRejectInput && (
              <View style={styles.approvalButtons}>
                <TouchableOpacity
                  style={styles.approveButton}
                  onPress={() => setShowApprovalInput(true)}
                >
                  <Text style={styles.approveButtonText}>
                    {theme === 'child' ? 'OK!' : '承認'}
                  </Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.rejectButton}
                  onPress={() => setShowRejectInput(true)}
                >
                  <Text style={styles.rejectButtonText}>
                    {theme === 'child' ? 'やりなおし' : '却下'}
                  </Text>
                </TouchableOpacity>
              </View>
            )}

            {/* 承認コメント入力 */}
            {showApprovalInput && (
              <View style={styles.commentContainer}>
                <TextInput
                  style={styles.commentInput}
                  value={approvalComment}
                  onChangeText={setApprovalComment}
                  placeholder={
                    theme === 'child' 
                      ? 'よくできました!（かかなくてもいいよ）' 
                      : 'コメント（任意）'
                  }
                  placeholderTextColor="#9CA3AF"
                  multiline
                />
                <View style={styles.commentButtons}>
                  <TouchableOpacity
                    style={styles.cancelButton}
                    onPress={() => {
                      setShowApprovalInput(false);
                      setApprovalComment('');
                    }}
                  >
                    <Text style={styles.cancelButtonText}>
                      {theme === 'child' ? 'やめる' : 'キャンセル'}
                    </Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={styles.submitApproveButton}
                    onPress={handleApprove}
                    disabled={isLoading}
                  >
                    <Text style={styles.submitApproveButtonText}>
                      {theme === 'child' ? 'OK!' : '承認'}
                    </Text>
                  </TouchableOpacity>
                </View>
              </View>
            )}

            {/* 却下理由入力 */}
            {showRejectInput && (
              <View style={styles.commentContainer}>
                <TextInput
                  style={styles.commentInput}
                  value={approvalComment}
                  onChangeText={setApprovalComment}
                  placeholder={
                    theme === 'child' 
                      ? 'どうしてやりなおしなのかおしえてね' 
                      : '却下理由を入力してください（必須）'
                  }
                  placeholderTextColor="#9CA3AF"
                  multiline
                />
                <View style={styles.commentButtons}>
                  <TouchableOpacity
                    style={styles.cancelButton}
                    onPress={() => {
                      setShowRejectInput(false);
                      setApprovalComment('');
                    }}
                  >
                    <Text style={styles.cancelButtonText}>
                      {theme === 'child' ? 'やめる' : 'キャンセル'}
                    </Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={styles.submitRejectButton}
                    onPress={handleReject}
                    disabled={isLoading}
                  >
                    <Text style={styles.submitRejectButtonText}>
                      {theme === 'child' ? 'やりなおし' : '却下'}
                    </Text>
                  </TouchableOpacity>
                </View>
              </View>
            )}
          </View>
        )}
      </ScrollView>

      {/* アバターウィジェット */}
      <AvatarWidget
        visible={avatarVisible}
        data={avatarData}
        onClose={hideAvatar}
        position="center"
      />

      {/* ローディングオーバーレイ（アバター待機中） */}
      {isSubmitting && (
        <View style={styles.loadingOverlay}>
          <View style={styles.loadingBox}>
            <ActivityIndicator size="large" color="#4F46E5" />
            <Text style={styles.loadingText}>処理中</Text>
          </View>
        </View>
      )}
    </View>
  );
}

/**
 * ステータスに応じたラベルを取得
 */
const getStatusLabel = (status: string, theme: 'adult' | 'child'): string => {
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
const getStatusStyle = (status: string) => {
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
  backButton: {
    width: 40,
    height: 40,
    justifyContent: 'center',
    alignItems: 'center',
  },
  backButtonText: {
    fontSize: 24,
    color: '#4F46E5',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#111827',
  },
  deleteButton: {
    width: 40,
    height: 40,
    justifyContent: 'center',
    alignItems: 'center',
  },
  deleteButtonText: {
    fontSize: 20,
  },
  content: {
    flex: 1,
  },
  contentContainer: {
    padding: 16,
  },
  section: {
    marginBottom: 24,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#111827',
    marginBottom: 12,
  },
  statusBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    alignSelf: 'flex-start',
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
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  sectionLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6B7280',
    marginBottom: 8,
  },
  description: {
    fontSize: 16,
    color: '#374151',
    lineHeight: 24,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  infoLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6B7280',
    marginRight: 8,
  },
  infoValue: {
    fontSize: 14,
    color: '#111827',
  },
  badge: {
    fontSize: 12,
    fontWeight: '600',
    color: '#4F46E5',
    backgroundColor: '#EEF2FF',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  imageGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  imageContainer: {
    position: 'relative',
    width: 100,
    height: 100,
  },
  image: {
    width: '100%',
    height: '100%',
    borderRadius: 8,
  },
  imageDeleteButton: {
    position: 'absolute',
    top: 4,
    right: 4,
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  imageDeleteButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: 'bold',
  },
  uploadButton: {
    backgroundColor: '#FFFFFF',
    borderWidth: 2,
    borderColor: '#4F46E5',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
    marginBottom: 12,
  },
  uploadButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#4F46E5',
  },
  completeButton: {
    backgroundColor: '#10B981',
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
    marginBottom: 24,
  },
  completeButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  approvalSection: {
    marginTop: 12,
  },
  approvalButtons: {
    flexDirection: 'row',
    gap: 12,
  },
  approveButton: {
    flex: 1,
    backgroundColor: '#10B981',
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
  },
  approveButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  rejectButton: {
    flex: 1,
    backgroundColor: '#EF4444',
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
  },
  rejectButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  commentContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
  },
  commentInput: {
    backgroundColor: '#F3F4F6',
    borderRadius: 8,
    padding: 12,
    fontSize: 14,
    color: '#111827',
    height: 80,
    textAlignVertical: 'top',
    marginBottom: 12,
  },
  commentButtons: {
    flexDirection: 'row',
    gap: 12,
  },
  cancelButton: {
    flex: 1,
    backgroundColor: '#F3F4F6',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6B7280',
  },
  submitApproveButton: {
    flex: 1,
    backgroundColor: '#10B981',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
  },
  submitApproveButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  submitRejectButton: {
    flex: 1,
    backgroundColor: '#EF4444',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
  },
  submitRejectButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  loadingOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingBox: {
    backgroundColor: '#fff',
    padding: 24,
    borderRadius: 12,
    alignItems: 'center',
    minWidth: 200,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: '#374151',
    textAlign: 'center',
  },
});
