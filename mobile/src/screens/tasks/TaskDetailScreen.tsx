/**
 * タスク詳細画面
 * 
 * タスク詳細表示、承認/却下機能、画像アップロード
 * Web版スタイル統一: グラデーション背景、ボーダー、ボタンスタイル
 */
import { useEffect, useState, useCallback, useMemo, useLayoutEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Alert,
  ActivityIndicator,
  Image,
  TextInput,
  Pressable,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import MaskedView from '@react-native-masked-view/masked-view';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { Task, TaskStatus } from '../../types/task.types';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import * as ImagePicker from 'expo-image-picker';
import { useAvatar } from '../../hooks/useAvatar';
import AvatarWidget from '../../components/common/AvatarWidget';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

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
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
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

  const { taskId } = route.params;
  const [task, setTask] = useState<Task | undefined>(undefined);
  const [approvalComment, setApprovalComment] = useState('');
  const [showApprovalInput, setShowApprovalInput] = useState(false);
  const [showRejectInput, setShowRejectInput] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  /**
   * ヘッダーカスタマイズ: 削除ボタン（グラデーションテキスト）
   */
  useLayoutEffect(() => {
    // タスクがまだロードされていない、またはグループタスクの場合は削除ボタンを表示しない
    if (!task || task.is_group_task) {
      navigation.setOptions({
        headerRight: undefined,
      });
      return;
    }

    navigation.setOptions({
      headerRight: () => (
        <Pressable
          onPress={handleDelete}
          style={{
            padding: getSpacing(8, width),
            marginRight: getSpacing(6, width),
          }}
        >
          <MaskedView
            maskElement={
              <Text
                style={{
                  fontSize: getFontSize(36, width, themeType),
                  fontWeight: '700',
                  backgroundColor: 'transparent',
                }}
              >
                🗑️
              </Text>
            }
            style={{ flexDirection: 'row', height: getFontSize(36, width, themeType) }}
          >
            <LinearGradient
              colors={['#59B9C6', '#9333EA']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={{ flex: 1 }}
            />
          </MaskedView>
        </Pressable>
      ),
    });
  }, [navigation, task, theme, width, themeType]);

  /**
   * Pull-to-Refresh処理
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      const fetchedTask = await getTask(taskId);
      setTask(fetchedTask ?? undefined);
    } catch (error) {
      console.error('[TaskDetailScreen] Refresh error:', error);
    } finally {
      setRefreshing(false);
    }
  }, [taskId, getTask]);

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
        const fetchedTask = await getTask(taskId);
        foundTask = fetchedTask ?? undefined;
        console.log('[TaskDetailScreen] getTask result:', foundTask ? `id=${foundTask.id}` : 'undefined');
      } else {
        console.log('[TaskDetailScreen] foundTask from existing tasks:', `id=${foundTask.id}`);
      }
      
      setTask(foundTask || undefined);
    };

    loadTask();
  }, [taskId, tasks, getTask]);

  /**
   * タスク完了切り替え
   */
  const handleToggleComplete = useCallback(async () => {
    if (!task) return;

    setIsSubmitting(true);
    const success = await toggleComplete(taskId);
    
    if (success) {
      // アバターイベント発火
      dispatchAvatarEvent('task_completed');

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
              if (navigation.canGoBack()) {
                navigation.goBack();
              } else {
                navigation.navigate('TaskList' as never);
              }
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
      <ScrollView
        style={styles.content}
        contentContainerStyle={styles.contentContainer}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={isChildTheme ? ['#F59E0B'] : ['#59B9C6']}
            tintColor={isChildTheme ? '#F59E0B' : '#59B9C6'}
          />
        }
      >
        {/* タイトル */}
        <View style={styles.section}>
          {/* グラデーションテキスト */}
          <MaskedView
            maskElement={<Text style={styles.titleMask}>{task.title}</Text>}
            style={{ marginBottom: getSpacing(12, width) }}
          >
            <LinearGradient
              colors={['#59B9C6', '#9333EA']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={{ flex: 1 }}
            >
              <Text style={[styles.title, { opacity: 0 }]}>{task.title}</Text>
            </LinearGradient>
          </MaskedView>

          {/* ステータスバッジ（グラデーション） */}
          <LinearGradient
            colors={
              displayStatus === 'approved' || displayStatus === 'completed'
                ? ['#10b981', '#059669']
                : displayStatus === 'rejected'
                ? ['#ef4444', '#dc2626']
                : ['#59B9C6', '#3b82f6']
            }
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={[styles.statusBadge, getShadow(4)]}
          >
            <Text style={styles.statusText}>{getStatusLabel(displayStatus, theme)}</Text>
          </LinearGradient>
        </View>

        {/* 説明 */}
        {task.description && (
          <View style={styles.sectionCard}>
            <View style={styles.sectionHeader}>
              <Text style={styles.sectionIcon}>📝</Text>
              <Text style={styles.sectionLabel}>
                {theme === 'child' ? 'せつめい' : '説明'}
              </Text>
            </View>
            <Text style={styles.description}>{task.description}</Text>
          </View>
        )}

        {/* 詳細情報 */}
        <View style={styles.sectionCard}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionIcon}>ℹ️</Text>
            <Text style={styles.sectionLabel}>
              {theme === 'child' ? 'くわしいこと' : '詳細情報'}
            </Text>
          </View>
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
        {task.images && task.images.length > 0 && (
          <View style={styles.sectionCard}>
            <View style={styles.sectionHeader}>
              <Text style={styles.sectionIcon}>🖼️</Text>
              <Text style={styles.sectionLabel}>
                {theme === 'child' ? 'しゃしん' : '画像'}
              </Text>
            </View>
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

        {/* 画像アップロードボタン（グラデーション） */}
        <LinearGradient
          colors={['#59B9C6', '#3b82f6']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={[styles.uploadButton, getShadow(6)]}
        >
          <TouchableOpacity
            style={styles.uploadButtonInner}
            onPress={handleImagePick}
          >
            <Text style={styles.uploadButtonIcon}>📷</Text>
            <Text style={styles.uploadButtonText}>
              {theme === 'child' ? 'しゃしんをつける' : '画像をアップロード'}
            </Text>
          </TouchableOpacity>
        </LinearGradient>

        {/* アクションボタン */}
        {isPending && (
          <LinearGradient
            colors={['#59B9C6', '#9333EA']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={[styles.completeButton, styles.buttonShadowEnhanced]}
          >
            <TouchableOpacity
              onPress={handleToggleComplete}
              disabled={isLoading || isSubmitting}
              style={styles.completeButtonInner}
            >
              <Text style={styles.buttonIcon}>✓</Text>
              <Text style={styles.completeButtonText}>
                {theme === 'child' ? 'できた!' : '完了にする'}
              </Text>
            </TouchableOpacity>
          </LinearGradient>
        )}

        {/* 承認/却下ボタン（承認待ちタスクのみ） */}
        {isPendingApproval && (
          <View style={styles.approvalSection}>
            {!showApprovalInput && !showRejectInput && (
              <View style={styles.approvalButtons}>
                <LinearGradient
                  colors={['#59B9C6', '#9333EA']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={[styles.approveButton, styles.buttonShadowEnhanced]}
                >
                  <TouchableOpacity
                    onPress={() => setShowApprovalInput(true)}
                    style={styles.approveButtonInner}
                  >
                    <Text style={styles.buttonIcon}>✓</Text>
                    <Text style={styles.approveButtonText}>
                      {theme === 'child' ? 'OK!' : '承認'}
                    </Text>
                  </TouchableOpacity>
                </LinearGradient>
                
                <TouchableOpacity
                  style={[styles.rejectButton, styles.buttonShadowEnhanced]}
                  onPress={() => setShowRejectInput(true)}
                >
                  <Text style={styles.buttonIcon}>✕</Text>
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
                  style={[
                    styles.commentInput,
                    {
                      borderColor: isChildTheme ? '#FCD34D' : 'rgba(89, 185, 198, 0.3)', // border-amber-300 : border-[#59B9C6]/30
                    }
                  ]}
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
                    style={[
                      styles.cancelButton,
                      {
                        borderColor: isChildTheme ? 'rgba(251, 191, 36, 0.3)' : 'rgba(89, 185, 198, 0.3)', // border-amber-300/30 : border-[#59B9C6]/30
                      }
                    ]}
                    onPress={() => {
                      setShowApprovalInput(false);
                      setApprovalComment('');
                    }}
                  >
                    <Text style={[
                      styles.cancelButtonText,
                      { color: isChildTheme ? '#92400E' : '#374151' } // text-amber-800 : text-gray-700
                    ]}>
                      {theme === 'child' ? 'やめる' : 'キャンセル'}
                    </Text>
                  </TouchableOpacity>
                  
                  <LinearGradient
                    colors={['#59B9C6', '#9333EA']}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 0 }}
                    style={[styles.submitApproveButton, styles.buttonShadowEnhanced]}
                  >
                    <TouchableOpacity
                      onPress={handleApprove}
                      disabled={isLoading}
                      style={styles.submitApproveButtonInner}
                    >
                      <Text style={styles.buttonIcon}>✓</Text>
                      <Text style={styles.submitApproveButtonText}>
                        {theme === 'child' ? 'OK!' : '承認'}
                      </Text>
                    </TouchableOpacity>
                  </LinearGradient>
                </View>
              </View>
            )}

            {/* 却下理由入力 */}
            {showRejectInput && (
              <View style={styles.commentContainer}>
                <TextInput
                  style={[
                    styles.commentInput,
                    {
                      borderColor: isChildTheme ? '#FCD34D' : 'rgba(89, 185, 198, 0.3)', // border-amber-300 : border-[#59B9C6]/30
                    }
                  ]}
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
                    style={[
                      styles.cancelButton,
                      {
                        borderColor: isChildTheme ? 'rgba(251, 191, 36, 0.3)' : 'rgba(89, 185, 198, 0.3)', // border-amber-300/30 : border-[#59B9C6]/30
                      }
                    ]}
                    onPress={() => {
                      setShowRejectInput(false);
                      setApprovalComment('');
                    }}
                  >
                    <Text style={[
                      styles.cancelButtonText,
                      { color: isChildTheme ? '#92400E' : '#374151' } // text-amber-800 : text-gray-700
                    ]}>
                      {theme === 'child' ? 'やめる' : 'キャンセル'}
                    </Text>
                  </TouchableOpacity>
                  
                  <TouchableOpacity
                    style={[styles.submitRejectButton, styles.buttonShadowEnhanced]}
                    onPress={handleReject}
                    disabled={isLoading}
                  >
                    <Text style={styles.buttonIcon}>✕</Text>
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

      {/* ローディングオーバーレイ（アバター待機中・blur効果） */}
      {isSubmitting && (
        <View style={styles.loadingOverlay}>
          <View style={[styles.loadingBox, getShadow(12)]}>
            <ActivityIndicator size="large" color="#59B9C6" />
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
 * レスポンシブスタイル生成関数
 * Web版スタイル統一: グラデーション、ボーダー、シャドウ
 */
const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme === 'child' ? '#FFF8E1' : '#F9FAFB', // Web版child-theme.cssに統一
  },
  content: {
    flex: 1,
    backgroundColor: 'transparent',
  },
  contentContainer: {
    padding: getSpacing(16, width),
  },
  section: {
    marginBottom: getSpacing(24, width),
  },
  sectionCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: getBorderRadius(16, width), // rounded-2xl
    padding: getSpacing(16, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(6),
    borderWidth: 1,
    borderColor: 'rgba(89, 185, 198, 0.1)',
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  sectionIcon: {
    fontSize: getFontSize(20, width, theme),
    marginRight: getSpacing(8, width),
  },
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: '#111827',
  },
  titleMask: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    backgroundColor: 'transparent',
  },
  statusBadge: {
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(8, width),
    borderRadius: getBorderRadius(12, width), // rounded-xl
    alignSelf: 'flex-start',
  },
  statusText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '700', // font-bold
    color: '#FFFFFF', // text-white
  },
  sectionLabel: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: '#6B7280',
    marginBottom: getSpacing(8, width),
  },
  description: {
    fontSize: getFontSize(16, width, theme),
    color: '#374151',
    lineHeight: getFontSize(24, width, theme),
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(8, width),
  },
  infoLabel: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: '#6B7280',
    marginRight: getSpacing(8, width),
  },
  infoValue: {
    fontSize: getFontSize(14, width, theme),
    color: '#111827',
  },
  badge: {
    fontSize: getFontSize(12, width, theme),
    fontWeight: '600',
    color: theme === 'child' ? '#92400E' : '#59B9C6', // text-amber-800 : text-[#59B9C6]
    backgroundColor: theme === 'child' ? '#FEF3C7' : 'rgba(89, 185, 198, 0.1)', // bg-amber-100 : bg-[#59B9C6]/10
    paddingHorizontal: getSpacing(8, width),
    paddingVertical: getSpacing(4, width),
    borderRadius: getBorderRadius(4, width),
  },
  imageGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: getSpacing(12, width),
  },
  imageContainer: {
    position: 'relative',
    width: 100,
    height: 100,
  },
  image: {
    width: '100%',
    height: '100%',
    borderRadius: getBorderRadius(8, width),
  },
  imageDeleteButton: {
    position: 'absolute',
    top: getSpacing(4, width),
    right: getSpacing(4, width),
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  imageDeleteButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(14, width, theme),
    fontWeight: 'bold',
  },
  uploadButton: {
    borderRadius: getBorderRadius(12, width), // rounded-xl
    overflow: 'hidden',
    marginBottom: getSpacing(12, width),
  },
  uploadButtonInner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(14, width),
  },
  uploadButtonIcon: {
    fontSize: getFontSize(20, width, theme),
    marginRight: getSpacing(8, width),
  },
  uploadButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: '#FFFFFF',
  },
  completeButton: {
    borderRadius: getBorderRadius(12, width), // rounded-xl
    overflow: 'hidden',
    marginBottom: getSpacing(24, width),
  },
  completeButtonInner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(14, width),
  },
  completeButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: '#FFFFFF',
  },
  buttonIcon: {
    fontSize: getFontSize(18, width, theme),
    color: '#FFFFFF',
    marginRight: getSpacing(6, width),
  },
  buttonShadowEnhanced: {
    shadowColor: '#59B9C6',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.3,
    shadowRadius: 16,
    elevation: 12,
  },
  approvalSection: {
    marginTop: getSpacing(12, width),
  },
  approvalButtons: {
    flexDirection: 'row',
    gap: getSpacing(12, width),
  },
  approveButton: {
    flex: 1,
    borderRadius: getBorderRadius(12, width), // rounded-xl
    overflow: 'hidden',
  },
  approveButtonInner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(14, width),
  },
  approveButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: '#FFFFFF',
  },
  rejectButton: {
    flex: 1,
    flexDirection: 'row',
    backgroundColor: '#EF4444', // bg-red-500
    borderRadius: getBorderRadius(12, width), // rounded-xl
    paddingVertical: getSpacing(14, width),
    alignItems: 'center',
    justifyContent: 'center',
  },
  rejectButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: '#FFFFFF',
  },
  commentContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
  },
  commentInput: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderRadius: getBorderRadius(12, width), // rounded-xl
    padding: getSpacing(12, width),
    fontSize: getFontSize(14, width, theme),
    color: '#111827',
    height: 80,
    textAlignVertical: 'top',
    marginBottom: getSpacing(12, width),
  },
  commentButtons: {
    flexDirection: 'row',
    gap: getSpacing(12, width),
  },
  cancelButton: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderWidth: 2,
    borderRadius: getBorderRadius(12, width), // rounded-xl
    paddingVertical: getSpacing(12, width),
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
  },
  submitApproveButton: {
    flex: 1,
    borderRadius: getBorderRadius(12, width), // rounded-xl
    overflow: 'hidden',
  },
  submitApproveButtonInner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(12, width),
  },
  submitApproveButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '700',
    color: '#FFFFFF',
  },
  submitRejectButton: {
    flex: 1,
    flexDirection: 'row',
    backgroundColor: '#EF4444', // bg-red-500
    borderRadius: getBorderRadius(12, width), // rounded-xl
    paddingVertical: getSpacing(12, width),
    alignItems: 'center',
    justifyContent: 'center',
  },
  submitRejectButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '700',
    color: '#FFFFFF',
  },
  loadingOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(17, 24, 39, 0.75)', // bg-gray-900/75 (Web版統一)
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingBox: {
    backgroundColor: '#FFFFFF',
    padding: getSpacing(24, width),
    borderRadius: getBorderRadius(16, width), // rounded-2xl
    alignItems: 'center',
    minWidth: 200,
  },
  loadingText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    color: '#374151', // text-gray-700
    fontWeight: '600',
    textAlign: 'center',
  },
});
