/**
 * スケジュールタスク一覧画面
 * 
 * グループのスケジュールタスク一覧を表示
 * カード形式でステータス（有効・一時停止）、スケジュール、報酬を表示
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
  ScrollView,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useScheduledTasks } from '../../hooks/useScheduledTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { ScheduledTask } from '../../types/scheduled-task.types';
import { useNavigation, useRoute, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RouteProp } from '@react-navigation/native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

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
  const { width } = useResponsive();
  const { colors, accent } = useThemedColors();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
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

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

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
    const statusColor = isActive ? colors.status.success : colors.text.secondary;
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
              onPress={() => handlePause(item)}
            >
              <LinearGradient
                colors={['#fef3c7', '#fde68a'] as const} // yellow-100 → yellow-200
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={[styles.actionButton, styles.pauseButton]}
              >
                <Text style={styles.actionButtonText}>
                  ⏸️ {theme === 'child' ? 'とめる' : '一時停止'}
                </Text>
              </LinearGradient>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity
              onPress={() => handleResume(item)}
            >
              <LinearGradient
                colors={[colors.status.success, colors.status.success] as const}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={[styles.actionButton, styles.resumeButton]}
              >
                <Text style={styles.actionButtonText}>
                  ▶️ {theme === 'child' ? 'うごかす' : '再開'}
                </Text>
              </LinearGradient>
            </TouchableOpacity>
          )}

          <TouchableOpacity
            onPress={() => handleDelete(item)}
          >
            <LinearGradient
              colors={[colors.status.error, colors.status.error] as const}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={[styles.actionButton, styles.deleteButton]}
            >
              <Text style={styles.actionButtonText}>
                🗑️ {theme === 'child' ? 'けす' : '削除'}
              </Text>
            </LinearGradient>
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
        <TouchableOpacity onPress={loadScheduledTasks}>
          <LinearGradient
            colors={[accent.primary, accent.primary] as const}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.retryButton}
          >
            <Text style={styles.retryButtonText}>
              {theme === 'child' ? 'もういちど' : '再試行'}
            </Text>
          </LinearGradient>
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
        <ActivityIndicator size="large" color={accent.primary} />
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
          <TouchableOpacity onPress={handleCreate}>
            <LinearGradient
              colors={[accent.primary, accent.primary] as const}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.createButton}
            >
              <Text style={styles.createButtonText}>
                ➥ {theme === 'child' ? 'つくる' : 'スケジュールを作成'}
              </Text>
            </LinearGradient>
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
          <TouchableOpacity onPress={handleCreate}>
            <LinearGradient
              colors={[accent.primary, accent.primary] as const}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.createButtonBottom}
            >
              <Text style={styles.createButtonText}>
                ➥ {theme === 'child' ? 'あたらしくつくる' : '新規作成'}
              </Text>
            </LinearGradient>
          </TouchableOpacity>
        }
      />
    </View>
  );
}

/**
 * レスポンシブスタイル生成関数
 */
const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
    padding: getSpacing(20, width),
  },
  listContainer: {
    padding: getSpacing(16, width),
  },
  header: {
    marginBottom: getSpacing(16, width),
  },
  headerTitle: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    marginBottom: getSpacing(4, width),
  },
  headerSubtitle: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
  },
  card: {
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(12, width),
    ...getShadow(3),
  },
  statusBadge: {
    position: 'absolute',
    top: getSpacing(12, width),
    right: getSpacing(12, width),
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(4, width),
    borderRadius: getBorderRadius(12, width),
  },
  statusText: {
    color: colors.background,
    fontSize: getFontSize(12, width, theme),
    fontWeight: 'bold',
  },
  cardTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    marginBottom: getSpacing(8, width),
    marginRight: 80, // ステータスバッジ分のスペース
  },
  cardDescription: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(12, width),
    lineHeight: getFontSize(20, width, theme),
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: getSpacing(8, width),
  },
  infoLabel: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    width: 100,
    flexShrink: 0,
  },
  infoValue: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.primary,
    flex: 1,
  },
  rewardValue: {
    fontSize: getFontSize(14, width, theme),
    color: colors.status.warning,
    fontWeight: 'bold',
    flex: 1,
  },
  tagsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: getSpacing(8, width),
    marginBottom: getSpacing(12, width),
  },
  tag: {
    backgroundColor: accent.primary + '20',
    paddingHorizontal: getSpacing(8, width),
    paddingVertical: getSpacing(4, width),
    borderRadius: getBorderRadius(12, width),
    marginRight: getSpacing(6, width),
    marginBottom: getSpacing(6, width),
  },
  tagText: {
    fontSize: getFontSize(12, width, theme),
    color: accent.primary,
  },
  moreTagsText: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    alignSelf: 'center',
  },
  actionButtons: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: getSpacing(12, width),
    paddingTop: getSpacing(12, width),
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  actionButton: {
    flex: 1,
    paddingVertical: getSpacing(8, width),
    paddingHorizontal: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    marginHorizontal: getSpacing(4, width),
    overflow: 'hidden', // LinearGradient用
  },
  pauseButton: {
    // LinearGradientで背景色設定のためコメントアウト
    // backgroundColor: '#FEF3C7',
  },
  resumeButton: {
    // LinearGradientで背景色設定のためコメントアウト
    // backgroundColor: '#D1FAE5',
  },
  deleteButton: {
    // LinearGradientで背景色設定のためコメントアウト
    // backgroundColor: '#FEE2E2',
  },
  actionButtonText: {
    fontSize: getFontSize(12, width, theme),
    textAlign: 'center',
    color: colors.text.primary,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: getSpacing(32, width),
  },
  emptyIcon: {
    fontSize: getFontSize(64, width, theme),
    marginBottom: getSpacing(16, width),
  },
  emptyTitle: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    marginBottom: getSpacing(8, width),
    textAlign: 'center',
  },
  emptyDescription: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    textAlign: 'center',
    marginBottom: getSpacing(24, width),
    lineHeight: getFontSize(20, width, theme),
  },
  createButton: {
    paddingHorizontal: getSpacing(24, width),
    paddingVertical: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden', // LinearGradient用
  },
  createButtonBottom: {
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    marginTop: getSpacing(16, width),
    marginBottom: getSpacing(32, width),
    overflow: 'hidden', // LinearGradient用
  },
  createButtonText: {
    color: colors.background,
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
    textAlign: 'center',
  },
  errorText: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: colors.status.error,
    marginBottom: getSpacing(8, width),
    textAlign: 'center',
  },
  errorMessage: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    textAlign: 'center',
    marginBottom: getSpacing(16, width),
  },
  retryButton: {
    paddingHorizontal: getSpacing(24, width),
    paddingVertical: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden', // LinearGradient用
  },
  retryButtonText: {
    color: colors.background,
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
  },
  loadingText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
  },
});
