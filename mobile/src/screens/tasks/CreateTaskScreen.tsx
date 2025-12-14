/**
 * タスク作成画面
 * 
 * グループタスク作成対応、テーマに応じたラベル表示
 * 通常タスク: 報酬・承認の有無・画像必須の設定なし
 * グループタスク: 報酬・承認の有無・画像必須の設定あり、グループメンバー必須
 */
import React, { useState, useCallback, useEffect, useMemo } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ScrollView,
  Alert,
  ActivityIndicator,
  Switch,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Picker } from '@react-native-picker/picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { CreateTaskData, TaskSpan } from '../../types/task.types';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import api from '../../services/api';
import { useAvatar } from '../../hooks/useAvatar';
import AvatarWidget from '../../components/common/AvatarWidget';
import GroupTaskLimitModal from '../../components/common/GroupTaskLimitModal';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { useThemedColors } from '../../hooks/useThemedColors';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  TaskList: undefined;
  CreateTask: undefined;
  TaskDecomposition: {
    initialTitle?: string;
    initialSpan?: TaskSpan;
    initialDueDate?: string;
  };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;

/**
 * グループメンバー情報
 */
interface GroupMember {
  id: number;
  username: string;
  name: string;
}

/**
 * テンプレートタスク情報
 */
interface TemplateTask {
  id: number;
  title: string;
  description: string | null;
  reward: number | null;
  due_date: string | null;
  requires_approval: boolean;
  requires_image: boolean;
}

/**
 * タスク作成画面コンポーネント
 */
export default function CreateTaskScreen() {
  const navigation = useNavigation<NavigationProp>();
  const { theme } = useTheme();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
  const { createTask, isLoading, error, clearError } = useTasks();
  const {
    isVisible: avatarVisible,
    currentData: avatarData,
    dispatchAvatarEvent,
    hideAvatar,
  } = useAvatar();

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  // フォーム状態
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [span, setSpan] = useState<TaskSpan>(1);
  const [dueDate, setDueDate] = useState(''); // 短期: YYYY-MM-DD、中期: YYYY年、長期: 任意文字列
  const [selectedDate, setSelectedDate] = useState(new Date()); // DateTimePicker用（短期のみ）
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear().toString()); // 年選択用（中期のみ）
  const [showDatePicker, setShowDatePicker] = useState(false); // DateTimePicker表示フラグ
  const [reward, setReward] = useState('10');
  const [requiresApproval, setRequiresApproval] = useState(false);
  const [requiresImage, setRequiresImage] = useState(false);
  const [isGroupTask, setIsGroupTask] = useState(false);
  
  // タスク作成方式（新規 or テンプレート）
  const [taskMode, setTaskMode] = useState<'new' | 'template'>('new');
  const [templateTasks, setTemplateTasks] = useState<TemplateTask[]>([]);
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | null>(null);
  const [isLoadingTemplates, setIsLoadingTemplates] = useState(false);
  
  // タグ状態
  const [availableTags, setAvailableTags] = useState<Array<{ id: number; name: string; color?: string }>>([]);
  const [selectedTagIds, setSelectedTagIds] = useState<number[]>([]);
  const [isLoadingTags, setIsLoadingTags] = useState(false);
  const [tagSearchQuery, setTagSearchQuery] = useState(''); // タグ検索クエリ
  const [isTagListExpanded, setIsTagListExpanded] = useState(false); // タグリスト展開状態
  
  // グループメンバー状態
  const [groupMembers, setGroupMembers] = useState<GroupMember[]>([]);
  const [isLoadingMembers, setIsLoadingMembers] = useState(false);
  
  // グループタスク上限エラーモーダル状態
  const [showLimitModal, setShowLimitModal] = useState(false);
  const [limitErrorMessage, setLimitErrorMessage] = useState('');

  /**
   * 初回マウント時にタグ一覧を取得
   */
  useEffect(() => {
    fetchTags();
  }, []);

  /**
   * タグ一覧取得処理
   */
  const fetchTags = async () => {
    setIsLoadingTags(true);
    try {
      const response = await api.get('/tags');
      if (response.data.success && response.data.data.tags) {
        setAvailableTags(response.data.data.tags);
      }
    } catch (error: any) {
      console.error('[CreateTaskScreen] タグ取得エラー:', error);
      // エラー時は空配列のままで続行（タグ選択は任意機能）
    } finally {
      setIsLoadingTags(false);
    }
  };

  /**
   * タグ選択/解除ハンドラー
   */
  const toggleTagSelection = useCallback((tagId: number) => {
    setSelectedTagIds((prev) =>
      prev.includes(tagId) ? prev.filter((id) => id !== tagId) : [...prev, tagId]
    );
  }, []);

  /**
   * グループタスク切り替え時のメンバーチェック + テンプレート取得
   */
  useEffect(() => {
    if (isGroupTask) {
      checkGroupMembers();
      fetchTemplateTasks(); // テンプレート一覧を取得
    }
  }, [isGroupTask]);

  /**
   * グループメンバー存在チェック
   */
  const checkGroupMembers = async () => {
    setIsLoadingMembers(true);
    try {
      const response = await api.get('/groups/edit');
      if (response.data.success && response.data.data.members) {
        const members = response.data.data.members as GroupMember[];
        setGroupMembers(members);
        
        if (members.length === 0) {
          Alert.alert(
            theme === 'child' ? 'エラー' : 'エラー',
            theme === 'child' 
              ? 'みんなのやることをつくるには、グループメンバーがひつようだよ。さきにメンバーをついかしてね。'
              : 'グループタスクを作成するには、グループメンバーが必要です。先にメンバーを追加してください。',
            [{ text: 'OK', onPress: () => setIsGroupTask(false) }]
          );
        }
      } else {
        throw new Error('グループ情報の取得に失敗しました');
      }
    } catch (error: any) {
      console.error('[CreateTaskScreen] グループメンバー取得エラー:', error);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' 
          ? 'グループのじょうほうがとれなかったよ。もういちどためしてね。'
          : 'グループ情報の取得に失敗しました。もう一度お試しください。',
        [{ text: 'OK', onPress: () => setIsGroupTask(false) }]
      );
    } finally {
      setIsLoadingMembers(false);
    }
  };

  /**
   * テンプレートタスク一覧取得
   */
  const fetchTemplateTasks = async () => {
    setIsLoadingTemplates(true);
    try {
      const response = await api.get('/tasks', {
        params: {
          filter: 'group_templates',
          per_page: 50,
        },
      });
      
      if (response.data.success && response.data.data.tasks) {
        setTemplateTasks(response.data.data.tasks);
        console.log('[CreateTaskScreen] テンプレートタスク取得成功:', response.data.data.tasks.length, '件');
      }
    } catch (error: any) {
      console.error('[CreateTaskScreen] テンプレートタスク取得エラー:', error);
      // エラー時は空配列のままで続行（テンプレート選択は任意機能）
    } finally {
      setIsLoadingTemplates(false);
    }
  };

  /**
   * テンプレート選択時の処理
   */
  const handleTemplateSelect = useCallback((templateId: number) => {
    const template = templateTasks.find(t => t.id === templateId);
    if (template) {
      setSelectedTemplateId(templateId);
      setTitle(template.title);
      setDescription(template.description || '');
      setReward(template.reward?.toString() || '10');
      setRequiresApproval(template.requires_approval);
      setRequiresImage(template.requires_image);
      console.log('[CreateTaskScreen] テンプレート適用:', template.title);
    }
  }, [templateTasks]);

  /**
   * DateTimePicker変更ハンドラー（短期用）
   */
  const handleDateChange = useCallback((_event: any, date?: Date) => {
    setShowDatePicker(Platform.OS === 'ios'); // iOSは常に表示、Androidは自動で閉じる
    if (date) {
      setSelectedDate(date);
      // YYYY-MM-DD形式に変換
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      setDueDate(`${year}-${month}-${day}`);
    }
  }, []);

  /**
   * span変更時の処理（期限入力をリセット）
   */
  useEffect(() => {
    console.log('[CreateTaskScreen] span changed:', span);
    if (span === 1) {
      // 短期: 今日の日付を初期値として設定
      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const day = String(today.getDate()).padStart(2, '0');
      const dateStr = `${year}-${month}-${day}`;
      setDueDate(dateStr);
      setSelectedDate(today);
      console.log('[CreateTaskScreen] 短期初期化: dueDate =', dateStr);
    } else if (span === 2) {
      // 中期: 今年の年を初期値として設定
      const currentYear = new Date().getFullYear().toString();
      const dueDateStr = `${currentYear}年`;
      setDueDate(dueDateStr);
      setSelectedYear(currentYear);
      console.log('[CreateTaskScreen] 中期初期化: dueDate =', dueDateStr, ', selectedYear =', currentYear);
    } else {
      // 長期: 空文字
      setDueDate('');
      console.log('[CreateTaskScreen] 長期初期化: dueDate = ""');
    }
  }, [span]);

  /**
   * タスク作成処理
   */
  const handleCreate = useCallback(async () => {
    // バリデーション
    if (!title.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'やることのなまえをいれてね' : 'タイトルを入力してください'
      );
      return;
    }

    // グループタスクの場合、メンバー必須チェック
    if (isGroupTask && groupMembers.length === 0) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' 
          ? 'みんなのやることをつくるには、グループメンバーがひつようだよ。'
          : 'グループタスクを作成するには、グループメンバーが必要です。',
        [{ text: 'OK' }]
      );
      return;
    }

    // タスクデータ作成（通常タスクとグループタスクで分岐）
    // 中期の場合、due_dateから「年」を削除（例: 2027年 → 2027）
    let formattedDueDate = dueDate.trim() || undefined;
    if (span === 2 && formattedDueDate) {
      formattedDueDate = formattedDueDate.replace('年', '');
    }

    const taskData: CreateTaskData = {
      title: title.trim(),
      description: description.trim() || undefined,
      span,
      due_date: formattedDueDate,
      is_group_task: isGroupTask,
      tag_ids: selectedTagIds.length > 0 ? selectedTagIds : undefined, // タグIDを追加
      ...(isGroupTask && {
        reward: parseInt(reward, 10) || 10,
        requires_approval: requiresApproval,
        requires_image: requiresImage,
      }),
    };

    try {
      const newTask = await createTask(taskData);

      if (newTask) {
        // アバターイベント発火（グループタスク or 通常タスク）
        const eventType = isGroupTask ? 'group_task_created' : 'task_created';
        dispatchAvatarEvent(eventType);

        // アバター表示後に画面遷移（3秒待機）
        setTimeout(() => {
          Alert.alert(
            theme === 'child' ? 'できたよ!' : '作成完了',
            theme === 'child' ? 'あたらしいやることをつくったよ!' : 'タスクを作成しました',
            [
              {
                text: 'OK',
                onPress: () => navigation.goBack(),
              },
            ]
          );
        }, 3000);
      }
    } catch (err: any) {
      // グループタスク作成上限エラーの場合はモーダル表示
      if (err.upgrade_required) {
        setLimitErrorMessage(err.message || 'グループタスクの作成上限に達しました。');
        setShowLimitModal(true);
      } else {
        // その他のエラーは通常のアラート表示（useTasks内でerror stateにセット済み）
        console.error('[CreateTaskScreen] Task creation error:', err);
      }
    }
  }, [
    title,
    description,
    span,
    dueDate,
    reward,
    requiresApproval,
    requiresImage,
    isGroupTask,
    groupMembers,
    selectedTagIds,
    createTask,
    theme,
    navigation,
    dispatchAvatarEvent,
  ]);

  /**
   * AIタスク分解画面に遷移
   */
  const handleDecompose = useCallback(() => {
    // タイトルが入力されていない場合は警告
    if (!title.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'やることのなまえをいれてね' : 'タイトルを入力してください'
      );
      return;
    }

    // AIタスク分解画面に遷移（初期値を渡す）
    navigation.navigate('TaskDecomposition', {
      initialTitle: title.trim(),
      initialSpan: span,
      initialDueDate: dueDate.trim(),
    });
  }, [title, span, dueDate, theme, navigation]);

  /**
   * エラー表示
   */
  React.useEffect(() => {
    if (error) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        error,
        [{ text: 'OK', onPress: clearError }]
      );
    }
  }, [error, theme, clearError]);

  // ヘッダーグラデーションカラー（グループタスク判定）
  const headerGradientColors = isGroupTask
    ? (['#9333ea', '#ec4899'] as const) // purple-600 → pink-600
    : (['#59B9C6', '#3b82f6'] as const); // プライマリ → blue-600

  return (
    <View style={styles.container}>
      {/* ヘッダー */}
      <LinearGradient
        colors={headerGradientColors}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.header}
      >
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backButtonText}>←</Text>
        </TouchableOpacity>
        
        <View style={styles.headerCenter}>
          <Text style={styles.headerIcon}>✚</Text>
          <Text style={styles.headerTitle}>
            {theme === 'child' ? 'やることをつくる' : 'タスク作成'}
          </Text>
        </View>
        
        <View style={styles.headerSpacer} />
      </LinearGradient>

      <ScrollView style={styles.content} contentContainerStyle={styles.contentContainer}>
        {/* タイトル */}
        <View style={styles.fieldContainer}>
          <Text style={styles.label}>
            {theme === 'child' ? 'やることのなまえ' : 'タイトル'}
            <Text style={styles.required}> *</Text>
          </Text>
          <TextInput
            style={styles.input}
            value={title}
            onChangeText={setTitle}
            placeholder={
              theme === 'child' ? 'れい: しゅくだいをする' : '例: 宿題をする'
            }
            placeholderTextColor="#9CA3AF"
          />
        </View>

        {/* 説明 */}
        <View style={styles.fieldContainer}>
          <Text style={styles.label}>
            {theme === 'child' ? 'せつめい' : '説明'}
          </Text>
          <TextInput
            style={[styles.input, styles.textArea]}
            value={description}
            onChangeText={setDescription}
            placeholder={
              theme === 'child'
                ? 'どんなやることかせつめいしてね'
                : 'タスクの詳細を入力してください'
            }
            placeholderTextColor="#9CA3AF"
            multiline
            numberOfLines={4}
            textAlignVertical="top"
          />
        </View>

        {/* 期間（Span） */}
        <View style={styles.fieldContainer}>
          <Text style={styles.label}>
            {theme === 'child' ? 'いつまでにやる?' : '期間'}
          </Text>
          <View style={styles.segmentContainer}>
            <TouchableOpacity
              style={[styles.segmentButton, span === 1 && styles.segmentButtonActive]}
              onPress={() => setSpan(1)}
            >
              <Text
                style={[
                  styles.segmentButtonText,
                  span === 1 && styles.segmentButtonTextActive,
                ]}
              >
                {theme === 'child' ? 'すぐ' : '短期'}
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.segmentButton, span === 2 && styles.segmentButtonActive]}
              onPress={() => setSpan(2)}
            >
              <Text
                style={[
                  styles.segmentButtonText,
                  span === 2 && styles.segmentButtonTextActive,
                ]}
              >
                {theme === 'child' ? 'ちょっと' : '中期'}
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.segmentButton, span === 3 && styles.segmentButtonActive]}
              onPress={() => setSpan(3)}
            >
              <Text
                style={[
                  styles.segmentButtonText,
                  span === 3 && styles.segmentButtonTextActive,
                ]}
              >
                {theme === 'child' ? 'ながい' : '長期'}
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* 期限 */}
        <View style={styles.fieldContainer}>
          <Text style={styles.label}>
            {theme === 'child' ? 'きげん' : '期限日'}
          </Text>

          {/* 短期: DateTimePicker（日付選択） */}
          {span === 1 && (
            <>
              <TouchableOpacity
                style={styles.dateButton}
                onPress={() => setShowDatePicker(true)}
              >
                <Text style={styles.dateButtonText}>
                  {dueDate || (theme === 'child' ? 'ひづけをえらぶ' : '日付を選択')}
                </Text>
              </TouchableOpacity>
              {showDatePicker && (
                <DateTimePicker
                  value={selectedDate}
                  mode="date"
                  display={Platform.OS === 'ios' ? 'spinner' : 'default'}
                  onChange={handleDateChange}
                />
              )}
            </>
          )}

          {/* 中期: Picker（年選択） */}
          {span === 2 && (
            <View style={styles.pickerContainer}>
              <Picker
                selectedValue={selectedYear}
                onValueChange={(value) => {
                  setSelectedYear(value);
                  setDueDate(`${value}年`);
                }}
                style={styles.picker}
                itemStyle={styles.pickerItem}
              >
                {Array.from({ length: 6 }, (_, i) => {
                  const year = new Date().getFullYear() + i;
                  return (
                    <Picker.Item
                      key={year}
                      label={`${year}年`}
                      value={year.toString()}
                      color={Platform.OS === 'ios' ? '#111827' : undefined}
                    />
                  );
                })}
              </Picker>
            </View>
          )}

          {/* 長期: TextInput（任意文字列） */}
          {span === 3 && (
            <TextInput
              style={styles.input}
              value={dueDate}
              onChangeText={setDueDate}
              placeholder={
                theme === 'child' ? 'れい: 5ねんご' : '例: 5年後'
              }
              placeholderTextColor="#9CA3AF"
            />
          )}
        </View>

        {/* タグ選択 */}
        <View style={styles.fieldContainer}>
          <Text style={styles.label}>
            {theme === 'child' ? 'タグ' : 'タグ'}
          </Text>
          
          {/* 選択済みタグ表示 */}
          {selectedTagIds.length > 0 && (
            <View style={styles.selectedTagsContainer}>
              <Text style={styles.selectedTagsLabel}>
                {theme === 'child' ? 'えらんだタグ:' : '選択中:'}
              </Text>
              <View style={styles.tagContainer}>
                {availableTags
                  .filter((tag) => selectedTagIds.includes(tag.id))
                  .map((tag) => (
                    <TouchableOpacity
                      key={tag.id}
                      style={[styles.tagChip, styles.tagChipSelected]}
                      onPress={() => toggleTagSelection(tag.id)}
                    >
                      <Text style={styles.tagChipTextSelected}>{tag.name}</Text>
                      <Text style={styles.tagRemoveIcon}> ✕</Text>
                    </TouchableOpacity>
                  ))}
              </View>
            </View>
          )}

          {isLoadingTags ? (
            <ActivityIndicator size="small" color="#4F46E5" />
          ) : availableTags.length > 0 ? (
            <>
              {/* タグ検索バー */}
              <View style={styles.tagSearchContainer}>
                <TextInput
                  style={styles.tagSearchInput}
                  placeholder={theme === 'child' ? 'タグをさがす' : 'タグを検索'}
                  placeholderTextColor="#9CA3AF"
                  value={tagSearchQuery}
                  onChangeText={setTagSearchQuery}
                  autoCapitalize="none"
                  autoCorrect={false}
                />
                {tagSearchQuery.length > 0 && (
                  <TouchableOpacity
                    style={styles.tagSearchClear}
                    onPress={() => setTagSearchQuery('')}
                  >
                    <Text style={styles.tagSearchClearText}>✕</Text>
                  </TouchableOpacity>
                )}
              </View>

              {/* タグリスト展開ボタン */}
              <TouchableOpacity
                style={styles.tagExpandButton}
                onPress={() => setIsTagListExpanded(!isTagListExpanded)}
              >
                <Text style={styles.tagExpandButtonText}>
                  {isTagListExpanded
                    ? theme === 'child'
                      ? 'とじる ▲'
                      : '閉じる ▲'
                    : theme === 'child'
                    ? 'タグをみる ▼'
                    : 'タグ一覧を表示 ▼'}
                </Text>
              </TouchableOpacity>

              {/* 展開可能なタグリスト */}
              {isTagListExpanded && (
                <View style={styles.tagListContainer}>
                  {availableTags
                    .filter(
                      (tag) =>
                        tagSearchQuery === '' ||
                        tag.name.toLowerCase().includes(tagSearchQuery.toLowerCase())
                    )
                    .filter((tag) => !selectedTagIds.includes(tag.id)) // 未選択のみ表示
                    .map((tag) => (
                      <TouchableOpacity
                        key={tag.id}
                        style={styles.tagListItem}
                        onPress={() => {
                          toggleTagSelection(tag.id);
                          setTagSearchQuery(''); // 選択後に検索クエリをクリア
                        }}
                      >
                        <Text style={styles.tagListItemText}>{tag.name}</Text>
                      </TouchableOpacity>
                    ))}
                  {availableTags.filter(
                    (tag) =>
                      (tagSearchQuery === '' ||
                        tag.name.toLowerCase().includes(tagSearchQuery.toLowerCase())) &&
                      !selectedTagIds.includes(tag.id)
                  ).length === 0 && (
                    <Text style={styles.tagListEmptyText}>
                      {theme === 'child'
                        ? 'タグがみつからないよ'
                        : '該当するタグがありません'}
                    </Text>
                  )}
                </View>
              )}
            </>
          ) : (
            <Text style={styles.helpText}>
              {theme === 'child' ? 'タグがないよ' : 'タグがありません'}
            </Text>
          )}
        </View>

        {/* 報酬（グループタスクのみ） */}
        {isGroupTask && (
          <View style={styles.fieldContainer}>
            <Text style={styles.label}>
              {theme === 'child' ? 'ほうび' : '報酬トークン'}
            </Text>
            <TextInput
              style={styles.input}
              value={reward}
              onChangeText={setReward}
              placeholder="10"
              placeholderTextColor="#9CA3AF"
              keyboardType="numeric"
            />
          </View>
        )}

        {/* スイッチ類（グループタスクのみ） */}
        {isGroupTask && (
          <>
            {/* 承認必須カード（アンバーグラデーション） */}
            <View style={styles.fieldContainer}>
              <LinearGradient
                colors={['#fef3c7', '#fed7aa']} // from-amber-50 to-orange-50
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.switchCard}
              >
                <View style={styles.switchRow}>
                  <Text style={styles.switchLabel}>
                    {theme === 'child' ? 'かくにんがひつよう' : '承認が必要'}
                  </Text>
                  <Switch
                    value={requiresApproval}
                    onValueChange={setRequiresApproval}
                    trackColor={{ false: '#D1D5DB', true: '#FCD34D' }}
                    thumbColor={requiresApproval ? '#F59E0B' : '#F3F4F6'}
                  />
                </View>
                <Text style={styles.helpText}>
                  {theme === 'child'
                    ? 'できたらおとなにみせてね'
                    : '完了時に親が承認する必要があります'}
                </Text>
              </LinearGradient>
            </View>

            {/* 画像必須カード（パープルグラデーション） */}
            <View style={styles.fieldContainer}>
              <LinearGradient
                colors={['#fae8ff', '#fce7f3']} // from-purple-50 to-pink-50
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.switchCard}
              >
                <View style={styles.switchRow}>
                  <Text style={styles.switchLabel}>
                    {theme === 'child' ? 'しゃしんがひつよう' : '画像が必要'}
                  </Text>
                  <Switch
                    value={requiresImage}
                    onValueChange={setRequiresImage}
                    trackColor={{ false: '#D1D5DB', true: '#C084FC' }}
                    thumbColor={requiresImage ? '#9333EA' : '#F3F4F6'}
                  />
                </View>
                <Text style={styles.helpText}>
                  {theme === 'child'
                    ? 'できたらしゃしんをとってね'
                    : '完了時に写真の添付が必要です'}
                </Text>
              </LinearGradient>
            </View>
          </>
        )}

        <View style={styles.fieldContainer}>
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>
              {theme === 'child' ? 'みんなのやること' : 'グループタスク'}
            </Text>
            {isLoadingMembers ? (
              <ActivityIndicator size="small" color="#4F46E5" />
            ) : (
              <Switch
                value={isGroupTask}
                onValueChange={setIsGroupTask}
                trackColor={{ false: '#D1D5DB', true: '#A5B4FC' }}
                thumbColor={isGroupTask ? '#4F46E5' : '#F3F4F6'}
              />
            )}
          </View>
          <Text style={styles.helpText}>
            {theme === 'child'
              ? 'みんなにおなじやることをあげるよ'
              : 'グループメンバー全員に同じタスクを割り当てます'}
          </Text>
        </View>

        {/* タスク作成方法選択（グループタスクのみ） */}
        {isGroupTask && (
          <View style={styles.fieldContainer}>
            <Text style={styles.label}>
              {theme === 'child' ? 'つくりかた' : 'タスク作成方法'}
            </Text>
            <View style={styles.segmentContainer}>
              <TouchableOpacity
                style={[styles.segmentButton, taskMode === 'new' && styles.segmentButtonActive]}
                onPress={() => {
                  setTaskMode('new');
                  setSelectedTemplateId(null);
                }}
              >
                <Text
                  style={[
                    styles.segmentButtonText,
                    taskMode === 'new' && styles.segmentButtonTextActive,
                  ]}
                >
                  {theme === 'child' ? 'あたらしく' : '新規作成'}
                </Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.segmentButton, taskMode === 'template' && styles.segmentButtonActive]}
                onPress={() => setTaskMode('template')}
              >
                <Text
                  style={[
                    styles.segmentButtonText,
                    taskMode === 'template' && styles.segmentButtonTextActive,
                  ]}
                >
                  {theme === 'child' ? 'まえのをつかう' : 'テンプレート'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        )}

        {/* テンプレート選択（グループタスク + テンプレートモード） */}
        {isGroupTask && taskMode === 'template' && (
          <View style={styles.fieldContainer}>
            <Text style={styles.label}>
              {theme === 'child' ? 'まえのやることからえらぶ' : '過去のグループタスクから選択'}
            </Text>
            {isLoadingTemplates ? (
              <ActivityIndicator size="small" color="#4F46E5" />
            ) : templateTasks.length > 0 ? (
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={selectedTemplateId}
                  onValueChange={(value) => {
                    if (value !== null) {
                      handleTemplateSelect(value as number);
                    }
                  }}
                  style={styles.picker}
                  itemStyle={styles.pickerItem}
                >
                  <Picker.Item
                    label={theme === 'child' ? 'えらんでね' : '選択してください'}
                    value={null}
                    color={Platform.OS === 'ios' ? '#9CA3AF' : undefined}
                  />
                  {templateTasks.map((template) => (
                    <Picker.Item
                      key={template.id}
                      label={template.title}
                      value={template.id}
                      color={Platform.OS === 'ios' ? '#111827' : undefined}
                    />
                  ))}
                </Picker>
              </View>
            ) : (
              <Text style={styles.helpText}>
                {theme === 'child'
                  ? 'まえのやることがないよ'
                  : '過去のグループタスクがありません'}
              </Text>
            )}
            {selectedTemplateId && (
              <View style={styles.templatePreview}>
                <Text style={styles.templatePreviewLabel}>
                  {theme === 'child' ? 'プレビュー' : 'プレビュー'}
                </Text>
                <Text style={styles.templatePreviewText}>
                  <Text style={styles.templatePreviewKey}>
                    {theme === 'child' ? 'なまえ: ' : 'タイトル: '}
                  </Text>
                  {title}
                </Text>
                {description && (
                  <Text style={styles.templatePreviewText}>
                    <Text style={styles.templatePreviewKey}>
                      {theme === 'child' ? 'せつめい: ' : '説明: '}
                    </Text>
                    {description}
                  </Text>
                )}
              </View>
            )}
          </View>
        )}

        {/* AIタスク分解ボタン */}
        <LinearGradient
          colors={['#59B9C6', '#3b82f6']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={[styles.decomposeButton, isLoading && styles.decomposeButtonDisabled]}
        >
          <TouchableOpacity
            onPress={handleDecompose}
            disabled={isLoading}
            style={styles.buttonTouchable}
          >
            <Text style={styles.decomposeButtonText}>
              🤖 {theme === 'child' ? 'AIでこまかくする' : 'AIでタスク分解'}
            </Text>
          </TouchableOpacity>
        </LinearGradient>

        {/* 作成ボタン */}
        <LinearGradient
          colors={['#59B9C6', '#3b82f6']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={[styles.createButton, isLoading && styles.createButtonDisabled]}
        >
          <TouchableOpacity
            onPress={handleCreate}
            disabled={isLoading}
            style={styles.buttonTouchable}
          >
            {isLoading ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <Text style={styles.createButtonText}>
                {theme === 'child' ? 'つくる' : '作成する'}
              </Text>
            )}
          </TouchableOpacity>
        </LinearGradient>
      </ScrollView>

      {/* アバターウィジェット */}
      <AvatarWidget
        visible={avatarVisible}
        data={avatarData}
        onClose={hideAvatar}
        position="center"
      />

      {/* グループタスク作成上限エラーモーダル */}
      <GroupTaskLimitModal
        visible={showLimitModal}
        message={limitErrorMessage}
        onClose={() => setShowLimitModal(false)}
      />
    </View>
  );
}

const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(16, width),
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255, 255, 255, 0.2)',
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
    flexDirection: 'row',
    alignItems: 'center',
    gap: getSpacing(8, width),
  },
  headerIcon: {
    fontSize: getFontSize(20, width, theme),
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
  headerTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerSpacer: {
    width: getSpacing(40, width),
  },
  content: {
    flex: 1,
  },
  contentContainer: {
    padding: getSpacing(16, width),
  },
  fieldContainer: {
    marginBottom: getSpacing(20, width),
  },
  label: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
    marginBottom: getSpacing(8, width),
  },
  required: {
    color: '#EF4444',
  },
  input: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(10, width),
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
  },
  textArea: {
    height: getSpacing(100, width),
    paddingTop: getSpacing(10, width),
  },
  segmentContainer: {
    flexDirection: 'row',
    gap: getSpacing(8, width),
  },
  segmentButton: {
    flex: 1,
    paddingVertical: getSpacing(10, width),
    paddingHorizontal: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.border.light,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border.default,
  },
  segmentButtonActive: {
    backgroundColor: accent.primary,
    borderColor: accent.primary,
  },
  segmentButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
  },
  segmentButtonTextActive: {
    color: '#FFFFFF',
  },
  helpText: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.disabled,
    marginTop: getSpacing(4, width),
  },
  dateButton: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(12, width),
  },
  dateButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
  },
  pickerContainer: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
    minHeight: Platform.OS === 'ios' ? 150 : 50,
  },
  picker: {
    height: Platform.OS === 'ios' ? 150 : 50,
    width: '100%',
  },
  pickerItem: {
    height: Platform.OS === 'ios' ? 150 : 50,
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
  },
  tagContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: getSpacing(8, width),
  },
  tagChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(8, width),
    borderRadius: getBorderRadius(16, width),
    backgroundColor: colors.border.light,
    borderWidth: 1,
    borderColor: colors.border.default,
  },
  tagChipSelected: {
    backgroundColor: accent.primary,
    borderColor: accent.primary,
  },
  tagChipText: {
    fontSize: getFontSize(12, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
  },
  tagChipTextSelected: {
    color: '#FFFFFF',
  },
  tagRemoveIcon: {
    color: '#FFFFFF',
    fontSize: getFontSize(12, width, theme),
    marginLeft: getSpacing(4, width),
  },
  selectedTagsContainer: {
    marginBottom: getSpacing(12, width),
    padding: getSpacing(12, width),
    backgroundColor: accent.primary + '10',
    borderRadius: getBorderRadius(8, width),
    borderWidth: 1,
    borderColor: accent.primary + '30',
  },
  selectedTagsLabel: {
    fontSize: getFontSize(12, width, theme),
    fontWeight: '600',
    color: accent.primary,
    marginBottom: getSpacing(8, width),
  },
  tagSearchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(8, width),
  },
  tagSearchInput: {
    flex: 1,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(8, width),
    fontSize: getFontSize(14, width, theme),
    color: colors.text.primary,
  },
  tagSearchClear: {
    position: 'absolute',
    right: getSpacing(8, width),
    padding: getSpacing(4, width),
  },
  tagSearchClearText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.disabled,
  },
  tagExpandButton: {
    backgroundColor: colors.border.light,
    borderRadius: getBorderRadius(8, width),
    paddingVertical: getSpacing(10, width),
    paddingHorizontal: getSpacing(12, width),
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border.default,
  },
  tagExpandButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: accent.primary,
  },
  tagListContainer: {
    marginTop: getSpacing(8, width),
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(8, width),
    borderWidth: 1,
    borderColor: colors.border.default,
    maxHeight: getSpacing(200, width),
  },
  tagListItem: {
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.light,
  },
  tagListItemText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
  },
  tagListEmptyText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.disabled,
    textAlign: 'center',
    paddingVertical: getSpacing(16, width),
  },
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  switchCard: {
    padding: getSpacing(12, width),
    borderRadius: getBorderRadius(12, width),
    borderWidth: 1,
    borderColor: 'rgba(0, 0, 0, 0.1)',
  },
  switchLabel: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
  },
  buttonTouchable: {
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(14, width),
  },
  decomposeButton: {
    borderRadius: getBorderRadius(8, width),
    marginTop: getSpacing(8, width),
    overflow: 'hidden',
    ...getShadow(4),
  },
  decomposeButtonDisabled: {
    opacity: 0.5,
  },
  decomposeButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#FFFFFF',
  },
  createButton: {
    borderRadius: getBorderRadius(8, width),
    marginTop: getSpacing(12, width),
    marginBottom: getSpacing(40, width),
    overflow: 'hidden',
    ...getShadow(4),
  },
  createButtonDisabled: {
    opacity: 0.5,
  },
  createButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#FFFFFF',
  },
  templatePreview: {
    marginTop: getSpacing(12, width),
    padding: getSpacing(12, width),
    backgroundColor: accent.primary + '10',
    borderRadius: getBorderRadius(8, width),
    borderWidth: 1,
    borderColor: accent.primary + '30',
  },
  templatePreviewLabel: {
    fontSize: getFontSize(12, width, theme),
    fontWeight: '600',
    color: accent.primary,
    marginBottom: getSpacing(8, width),
  },
  templatePreviewText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(4, width),
  },
  templatePreviewKey: {
    fontWeight: '600',
    color: accent.primary,
  },
});
