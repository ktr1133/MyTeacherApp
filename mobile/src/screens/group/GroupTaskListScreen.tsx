/**
 * グループタスク一覧画面
 * 
 * Web版（/group-tasks）と同等の機能を提供
 * 編集・削除可能なグループタスクの一覧表示
 * 
 * @see /home/ktr/mtdev/definitions/mobile/GroupTaskManagement.md
 */
import { useState, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
  Alert,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import api from '../../services/api';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  GroupTaskList: undefined;
  GroupTaskEdit: { groupTaskId: string };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;

/**
 * グループタスク型定義
 */
interface GroupTask {
  group_task_id: string;
  title: string;
  description?: string;
  span: 1 | 3 | 6; // DB値: 1=短期, 3=中期, 6=長期
  reward: number;
  due_date?: string;
  assigned_count: number;
}

/**
 * グループタスク一覧画面コンポーネント
 */
export default function GroupTaskListScreen() {
  const navigation = useNavigation<NavigationProp>();
  const { theme } = useTheme();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();

  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  // 状態管理
  const [groupTasks, setGroupTasks] = useState<GroupTask[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  /**
   * グループタスク一覧取得
   */
  const loadGroupTasks = useCallback(async () => {
    try {
      const response = await api.get('/group-tasks');
      if (Array.isArray(response.data)) {
        setGroupTasks(response.data);
      } else {
        setGroupTasks([]);
      }
    } catch (err: any) {
      console.error('[GroupTaskListScreen] データ取得エラー:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'データがよめなかったよ' : 'データの取得に失敗しました'
      );
    } finally {
      setIsLoading(false);
      setRefreshing(false);
    }
  }, [theme]);

  /**
   * 初回マウント時にデータ取得
   */
  useFocusEffect(
    useCallback(() => {
      setIsLoading(true);
      loadGroupTasks();
    }, [loadGroupTasks])
  );

  /**
   * リフレッシュ処理
   */
  const handleRefresh = useCallback(() => {
    setRefreshing(true);
    loadGroupTasks();
  }, [loadGroupTasks]);

  /**
   * グループタスク削除
   */
  const handleDelete = useCallback(async (groupTaskId: string, title: string, assignedCount: number) => {
    Alert.alert(
      theme === 'child' ? 'ほんとうに？' : '削除確認',
      theme === 'child' 
        ? `「${title}」をけすよ？もどせないよ！`
        : `「${title}」と関連する全メンバーのタスク（${assignedCount}件）を削除します。\nこの操作は取り消せません。本当に削除しますか？`,
      [
        { text: theme === 'child' ? 'やめる' : 'キャンセル', style: 'cancel' },
        {
          text: theme === 'child' ? 'けす' : '削除',
          style: 'destructive',
          onPress: async () => {
            try {
              await api.delete(`/group-tasks/${groupTaskId}`);
              // 削除成功（200レスポンス）
              Alert.alert(
                theme === 'child' ? 'けしたよ！' : '削除完了',
                theme === 'child' ? 'タスクをけしたよ' : 'グループタスクを削除しました'
              );
              loadGroupTasks();
            } catch (err: any) {
              console.error('[GroupTaskListScreen] 削除エラー:', err);
              Alert.alert(
                theme === 'child' ? 'エラー' : 'エラー',
                theme === 'child' ? 'けせなかったよ' : '削除に失敗しました'
              );
            }
          },
        },
      ]
    );
  }, [theme, loadGroupTasks]);

  /**
   * カードレンダリング
   */
  const renderCard = useCallback(({ item }: { item: GroupTask }) => {
    // 期限の処理: 短期・中期は日付形式、長期は任意文字列
    let dueDateDisplay: string | null = null;
    let isOverdue = false;
    
    if (item.due_date) {
      if (item.span === 1 || item.span === 3) {
        // 短期・中期: 日付として処理
        const dueDate = new Date(item.due_date);
        if (!isNaN(dueDate.getTime())) {
          isOverdue = dueDate < new Date();
          dueDateDisplay = dueDate.toLocaleDateString('ja-JP', { month: 'numeric', day: 'numeric' });
        }
      } else {
        // 長期: 文字列をそのまま表示
        dueDateDisplay = item.due_date;
      }
    }
    
    // span表示用ラベル（絵文字なし）
    const spanLabel = item.span === 1 ? '短期' : item.span === 3 ? '中期' : '長期';

    return (
      <TouchableOpacity
        style={styles.card}
        onPress={() => navigation.navigate('GroupTaskEdit', { groupTaskId: item.group_task_id })}
      >
        {/* ヘッダー */}
        <View style={styles.cardHeader}>
          <LinearGradient
            colors={['#59B9C6', '#9333ea']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.iconContainer}
          >
            <Ionicons name="people" size={20} color="#FFFFFF" />
          </LinearGradient>
          <Text style={styles.title} numberOfLines={2}>
            {item.title}
          </Text>
        </View>

        {/* 説明 */}
        {item.description && (
          <Text style={styles.description} numberOfLines={2}>
            {item.description}
          </Text>
        )}

        {/* 情報行 */}
        <View style={styles.infoContainer}>
          {/* 期間 */}
          <View style={styles.infoItem}>
            <Text style={styles.spanBadge}>{spanLabel}</Text>
          </View>

          {/* 割当人数 */}
          <View style={styles.infoItem}>
            <Ionicons name="people-outline" size={16} color={colors.text.secondary as string} />
            <Text style={styles.infoText}>{item.assigned_count}人</Text>
          </View>

          {/* 期限 */}
          {dueDateDisplay && (
            <View style={styles.infoItem}>
              <Ionicons 
                name="calendar-outline" 
                size={16} 
                color={isOverdue ? '#EF4444' : (colors.text.secondary as string)} 
              />
              <Text style={[styles.infoText, isOverdue && styles.overdueText]}>
                {dueDateDisplay}
              </Text>
            </View>
          )}

          {/* 報酬 */}
          <View style={styles.infoItem}>
            <Ionicons name="gift-outline" size={16} color="#F59E0B" />
            <Text style={styles.rewardText}>{item.reward.toLocaleString()}</Text>
          </View>
        </View>

        {/* アクションボタン */}
        <View style={styles.actions}>
          <TouchableOpacity
            style={styles.editButton}
            onPress={(e) => {
              e.stopPropagation();
              navigation.navigate('GroupTaskEdit', { groupTaskId: item.group_task_id });
            }}
          >
            <Ionicons name="create-outline" size={18} color="#FFFFFF" />
            <Text style={styles.buttonText}>
              {theme === 'child' ? 'へんしゅう' : '編集'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.deleteButton}
            onPress={(e) => {
              e.stopPropagation();
              handleDelete(item.group_task_id, item.title, item.assigned_count);
            }}
          >
            <Ionicons name="trash-outline" size={18} color="#FFFFFF" />
            <Text style={styles.buttonText}>
              {theme === 'child' ? 'けす' : '削除'}
            </Text>
          </TouchableOpacity>
        </View>
      </TouchableOpacity>
    );
  }, [theme, navigation, handleDelete, styles, colors]);

  /**
   * 空状態表示
   */
  const renderEmpty = useCallback(() => (
    <View style={styles.emptyContainer}>
      <LinearGradient
        colors={['#59B9C6', '#9333ea']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.emptyIcon}
      >
        <Ionicons name="people-outline" size={32} color="#FFFFFF" />
      </LinearGradient>
      <Text style={styles.emptyTitle}>
        {theme === 'child' ? 'まだないよ' : 'グループタスクがありません'}
      </Text>
      <Text style={styles.emptySubtext}>
        {theme === 'child' ? 'グループタスクをつくってみよう！' : '編集可能なグループタスクはまだ作成されていません。'}
      </Text>
    </View>
  ), [theme, styles]);

  return (
    <View style={styles.container}>
      {/* ヘッダー */}
      <LinearGradient
        colors={['#9333ea', '#ec4899']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.header}
      >
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backButtonText}>←</Text>
        </TouchableOpacity>
        
        <View style={styles.headerCenter}>
          <Text style={styles.headerIcon}>👥</Text>
          <Text style={styles.headerTitle}>
            {theme === 'child' ? 'グループタスク' : 'グループタスク管理'}
          </Text>
        </View>
        
        <View style={styles.headerSpacer} />
      </LinearGradient>

      {/* リスト */}
      <FlatList
        data={groupTasks}
        renderItem={renderCard}
        keyExtractor={(item) => item.group_task_id}
        contentContainerStyle={styles.listContent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={accent.primary as string}
          />
        }
        ListEmptyComponent={!isLoading ? renderEmpty : null}
      />
    </View>
  );
}

/**
 * スタイル定義
 */
const createStyles = (width: number, theme: 'adult' | 'child', colors: any, _accent: any) =>
  StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: theme === 'child' ? '#FFF8E1' : colors.background,
    },
    header: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingTop: getSpacing(50, width),
      paddingBottom: getSpacing(16, width),
      paddingHorizontal: getSpacing(16, width),
    },
    backButton: {
      width: getSpacing(40, width),
      height: getSpacing(40, width),
      justifyContent: 'center',
      alignItems: 'center',
    },
    backButtonText: {
      fontSize: getFontSize(24, width, theme),
      color: '#FFFFFF',
      fontWeight: 'bold',
    },
    headerCenter: {
      flex: 1,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      gap: getSpacing(8, width),
    },
    headerIcon: {
      fontSize: getFontSize(24, width, theme),
    },
    headerTitle: {
      fontSize: getFontSize(18, width, theme),
      fontWeight: 'bold',
      color: '#FFFFFF',
    },
    headerSpacer: {
      width: getSpacing(40, width),
    },
    listContent: {
      padding: getSpacing(16, width),
    },
    card: {
      backgroundColor: colors.card,
      borderRadius: getBorderRadius(16, width),
      padding: getSpacing(16, width),
      marginBottom: getSpacing(12, width),
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.1,
      shadowRadius: 4,
      elevation: 3,
    },
    cardHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: getSpacing(12, width),
      marginBottom: getSpacing(12, width),
    },
    iconContainer: {
      width: getSpacing(40, width),
      height: getSpacing(40, width),
      borderRadius: getBorderRadius(10, width),
      justifyContent: 'center',
      alignItems: 'center',
    },
    title: {
      flex: 1,
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: colors.text.primary,
    },
    description: {
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
      marginBottom: getSpacing(12, width),
      lineHeight: getFontSize(20, width, theme),
    },
    infoContainer: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: getSpacing(16, width),
      marginBottom: getSpacing(16, width),
    },
    infoItem: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: getSpacing(4, width),
    },
    spanBadge: {
      fontSize: getFontSize(12, width, theme),
      fontWeight: '600',
      color: colors.text.primary,
      paddingHorizontal: getSpacing(8, width),
      paddingVertical: getSpacing(4, width),
      backgroundColor: colors.accent?.secondary || '#E0E7FF',
      borderRadius: getBorderRadius(6, width),
    },
    infoText: {
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
    },
    overdueText: {
      color: '#EF4444',
      fontWeight: '600',
    },
    rewardText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '700',
      color: '#F59E0B',
    },
    actions: {
      flexDirection: 'row',
      gap: getSpacing(8, width),
      paddingTop: getSpacing(12, width),
      borderTopWidth: 1,
      borderTopColor: colors.border.default,
    },
    editButton: {
      flex: 1,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      gap: getSpacing(6, width),
      paddingVertical: getSpacing(10, width),
      backgroundColor: '#59B9C6',
      borderRadius: getBorderRadius(8, width),
    },
    deleteButton: {
      flex: 1,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      gap: getSpacing(6, width),
      paddingVertical: getSpacing(10, width),
      backgroundColor: '#EF4444',
      borderRadius: getBorderRadius(8, width),
    },
    buttonText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: '#FFFFFF',
    },
    emptyContainer: {
      flex: 1,
      alignItems: 'center',
      justifyContent: 'center',
      paddingVertical: getSpacing(80, width),
    },
    emptyIcon: {
      width: getSpacing(64, width),
      height: getSpacing(64, width),
      borderRadius: getBorderRadius(16, width),
      justifyContent: 'center',
      alignItems: 'center',
      marginBottom: getSpacing(16, width),
    },
    emptyTitle: {
      fontSize: getFontSize(18, width, theme),
      fontWeight: '600',
      color: colors.text.primary,
      marginBottom: getSpacing(8, width),
    },
    emptySubtext: {
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
      textAlign: 'center',
    },
  });
