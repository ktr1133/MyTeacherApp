/**
 * タスク編集画面
 * 
 * 通常タスク専用の編集画面
 * グループタスクは編集不可（TaskDetailScreenで表示のみ）
 */
import { useState, useCallback, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Alert,
  ActivityIndicator,
  Platform,
} from 'react-native';
import { Picker } from '@react-native-picker/picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { useAvatarContext } from '../../contexts/AvatarContext';
import { TaskSpan, Task } from '../../types/task.types';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import api from '../../services/api';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  TaskList: undefined;
  TaskEdit: { taskId: number };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;
type RouteProps = RouteProp<RootStackParamList, 'TaskEdit'>;

/**
 * タスク編集画面コンポーネント
 */
export default function TaskEditScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<RouteProps>();
  const { theme } = useTheme();
  const { dispatchAvatarEvent } = useAvatarContext();
  const { tasks, updateTask, deleteTask, getTask, isLoading } = useTasks();

  const { taskId } = route.params;
  const [task, setTask] = useState<Task | null>(null);
  const [loadingTask, setLoadingTask] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // フォーム状態
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [span, setSpan] = useState<TaskSpan>(1);
  const [dueDate, setDueDate] = useState('');
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear().toString());
  const [showDatePicker, setShowDatePicker] = useState(false);
  
  // タグ状態
  const [availableTags, setAvailableTags] = useState<Array<{ id: number; name: string; color?: string }>>([]);
  const [selectedTagIds, setSelectedTagIds] = useState<number[]>([]);
  const [isLoadingTags, setIsLoadingTags] = useState(false);
  const [tagSearchQuery, setTagSearchQuery] = useState('');
  const [isTagExpanded, setIsTagExpanded] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  /**
   * Pull-to-Refresh処理
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await Promise.all([loadTask(), fetchTags()]);
    } finally {
      setRefreshing(false);
    }
  }, []);

  /**
   * 初回マウント時: タスク取得とタグ一覧取得
   */
  useEffect(() => {
    loadTask();
    fetchTags();
  }, [taskId]);

  /**
   * タスク取得処理
   */
  const loadTask = async () => {
    setLoadingTask(true);
    try {
      console.log('[TaskEditScreen] loadTask - taskId:', taskId);
      console.log('[TaskEditScreen] loadTask - tasks count:', tasks.length);
      
      let foundTask: Task | undefined = tasks.find((t) => t.id === taskId);
      
      // tasksが空、またはタスクが見つからない場合はgetTaskでAPI取得
      if (!foundTask) {
        console.log('[TaskEditScreen] Task not found in current tasks, calling getTask API...');
        const result = await getTask(taskId);
        foundTask = result ?? undefined;
        console.log('[TaskEditScreen] getTask result:', foundTask ? `id=${foundTask.id}` : 'null');
      } else {
        console.log('[TaskEditScreen] foundTask from existing tasks:', `id=${foundTask.id}`);
      }
      
      if (!foundTask) {
        console.error('[TaskEditScreen] Task not found after API call');
        Alert.alert('エラー', 'タスクが見つかりません');
        navigation.goBack();
        return;
      }

      // グループタスクは編集不可
      if (foundTask.is_group_task) {
        Alert.alert('エラー', 'グループタスクは編集できません');
        navigation.goBack();
        return;
      }

      setTask(foundTask);
      
      // フォームに既存データをセット
      setTitle(foundTask.title || '');
      setDescription(foundTask.description || '');
      setSpan(foundTask.span || 1);
      
      // タグIDをセット
      if (foundTask.tags && foundTask.tags.length > 0) {
        setSelectedTagIds(foundTask.tags.map(tag => tag.id));
      }
      
      // 期限データをセット
      if (foundTask.due_date) {
        setDueDate(foundTask.due_date);
        
        // span別に初期値を設定
        if (foundTask.span === 1) {
          // 短期: YYYY-MM-DD形式をDateオブジェクトに変換
          try {
            const date = new Date(foundTask.due_date);
            setSelectedDate(date);
          } catch (e) {
            console.error('日付パースエラー:', e);
          }
        } else if (foundTask.span === 2) {
          // 中期: YYYY-MM-DDから年を抽出
          try {
            const year = new Date(foundTask.due_date).getFullYear().toString();
            setSelectedYear(year);
          } catch (e) {
            console.error('年パースエラー:', e);
          }
        }
        // 長期: そのまま文字列として扱う
      }
    } catch (error) {
      console.error('[TaskEditScreen] タスク取得エラー:', error);
      Alert.alert('エラー', 'タスクの読み込みに失敗しました');
      navigation.goBack();
    } finally {
      setLoadingTask(false);
    }
  };

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
      console.error('[TaskEditScreen] タグ取得エラー:', error);
    } finally {
      setIsLoadingTags(false);
    }
  };

  /**
   * タグ選択/解除ハンドラー
   */
  const toggleTagSelection = useCallback((tagId: number) => {
    setSelectedTagIds(prev => {
      if (prev.includes(tagId)) {
        return prev.filter(id => id !== tagId);
      } else {
        return [...prev, tagId];
      }
    });
  }, []);

  /**
   * span変更時: 期限フィールドの初期化とフォーマット設定
   */
  useEffect(() => {
    console.log('[TaskEditScreen] span changed:', span);
    
    // 既存のdue_dateをspan別に変換
    if (dueDate) {
      if (span === 1) {
        // 短期: YYYY-MM-DD形式を維持
        try {
          const date = new Date(dueDate);
          setSelectedDate(date);
          setDueDate(date.toISOString().split('T')[0]);
        } catch (e) {
          // パース失敗時は今日の日付
          const today = new Date();
          setSelectedDate(today);
          setDueDate(today.toISOString().split('T')[0]);
        }
      } else if (span === 2) {
        // 中期: 年を抽出してYYYY年形式
        try {
          const year = new Date(dueDate).getFullYear();
          setSelectedYear(year.toString());
          setDueDate(`${year}年`);
        } catch (e) {
          const currentYear = new Date().getFullYear();
          setSelectedYear(currentYear.toString());
          setDueDate(`${currentYear}年`);
        }
      } else if (span === 3) {
        // 長期: 既存の文字列を維持（YYYY-MM-DDの場合は変換）
        if (/^\d{4}-\d{2}-\d{2}$/.test(dueDate)) {
          const date = new Date(dueDate);
          const year = date.getFullYear();
          setDueDate(`${year}年後`);
        }
      }
    }
  }, [span]);

  /**
   * 更新処理
   */
  const handleUpdate = useCallback(async () => {
    if (!title.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'なまえをいれてね' : 'タスク名を入力してください'
      );
      return;
    }

    setIsSubmitting(true);
    try {
      // タスクデータ作成
      // 中期の場合、due_dateから「年」を削除（例: 2027年 → 2027）
      let formattedDueDate = dueDate.trim() || undefined;
      if (span === 2 && formattedDueDate) {
        formattedDueDate = formattedDueDate.replace('年', '');
      }

      const taskData: Partial<Task> & { tag_ids?: number[] } = {
        title: title.trim(),
        description: description.trim() || undefined,
        span,
        due_date: formattedDueDate,
        tag_ids: selectedTagIds.length > 0 ? selectedTagIds : undefined,
      };

      console.log('[TaskEditScreen] Updating task:', taskData);

      const updatedTask = await updateTask(taskId, taskData as any);

      if (updatedTask) {
        // アバターイベント発火
        dispatchAvatarEvent('task_updated');
        
        // アバター表示後にアラート表示（3秒待機）
        setTimeout(() => {
          setIsSubmitting(false);
          Alert.alert(
            theme === 'child' ? 'できた!' : '更新完了',
            theme === 'child' ? 'タスクをこうしんしたよ!' : 'タスクを更新しました',
            [
              {
                text: 'OK',
                onPress: () => navigation.goBack(),
              },
            ]
          );
        }, 3000);
      } else {
        setIsSubmitting(false);
        Alert.alert('エラー', 'タスクの更新に失敗しました');
      }
    } catch (error: any) {
      console.error('[TaskEditScreen] Update error:', error);
      setIsSubmitting(false);
      Alert.alert('エラー', 'タスクの更新に失敗しました');
    }
  }, [title, description, span, dueDate, selectedTagIds, taskId, updateTask, theme, navigation, dispatchAvatarEvent]);

  /**
   * 削除処理
   */
  const handleDelete = useCallback(async () => {
    Alert.alert(
      theme === 'child' ? 'けす?' : '削除確認',
      theme === 'child' ? 'ほんとうにけしてもいい?' : '本当にこのタスクを削除しますか?',
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'けす' : '削除',
          style: 'destructive',
          onPress: async () => {
            setIsSubmitting(true);
            const success = await deleteTask(taskId);
            if (success) {
              // アバターイベント発火
              dispatchAvatarEvent('task_deleted');
              
              // アバター表示後に画面遷移（3秒待機）
              setTimeout(() => {
                setIsSubmitting(false);
                navigation.navigate('TaskList');
              }, 3000);
            } else {
              setIsSubmitting(false);
            }
          },
        },
      ]
    );
  }, [taskId, deleteTask, theme, navigation, dispatchAvatarEvent]);

  /**
   * DateTimePicker変更ハンドラー（短期のみ）
   */
  const onDateChange = (_event: any, selectedDate?: Date) => {
    setShowDatePicker(Platform.OS === 'ios');
    if (selectedDate) {
      setSelectedDate(selectedDate);
      const formattedDate = selectedDate.toISOString().split('T')[0];
      setDueDate(formattedDate);
      console.log('[TaskEditScreen] Date changed:', formattedDate);
    }
  };

  /**
   * 年選択ハンドラー（中期のみ）
   */
  const onYearChange = (year: string) => {
    setSelectedYear(year);
    setDueDate(`${year}年`);
    console.log('[TaskEditScreen] Year changed:', year);
  };

  if (loadingTask) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4F46E5" />
      </View>
    );
  }

  if (!task) {
    return null;
  }

  return (
    <>
      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.contentContainer}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={['#4F46E5']}
            tintColor="#4F46E5"
          />
        }
      >
      {/* タイトル */}
      <View style={styles.formGroup}>
        <Text style={styles.label}>
          {theme === 'child' ? 'なまえ' : 'タスク名'} <Text style={styles.required}>*</Text>
        </Text>
        <TextInput
          style={styles.input}
          value={title}
          onChangeText={setTitle}
          placeholder={theme === 'child' ? 'やることのなまえ' : 'タスク名を入力'}
          placeholderTextColor="#9CA3AF"
        />
      </View>

      {/* 説明 */}
      <View style={styles.formGroup}>
        <Text style={styles.label}>
          {theme === 'child' ? 'せつめい' : '説明'}
        </Text>
        <TextInput
          style={[styles.input, styles.textArea]}
          value={description}
          onChangeText={setDescription}
          placeholder={theme === 'child' ? 'くわしくかいてね' : 'タスクの説明を入力'}
          placeholderTextColor="#9CA3AF"
          multiline
          numberOfLines={4}
        />
      </View>

      {/* スパン */}
      <View style={styles.formGroup}>
        <Text style={styles.label}>
          {theme === 'child' ? 'ながさ' : 'スパン'} <Text style={styles.required}>*</Text>
        </Text>
        <View style={styles.spanButtonGroup}>
          <TouchableOpacity
            style={[styles.spanButton, span === 1 && styles.spanButtonActive]}
            onPress={() => setSpan(1)}
          >
            <Text style={[styles.spanButtonText, span === 1 && styles.spanButtonTextActive]}>
              {theme === 'child' ? 'みじかい' : '短期'}
            </Text>
            <Text style={[styles.spanButtonSubText, span === 1 && styles.spanButtonTextActive]}>
              {theme === 'child' ? '1しゅうかん' : '1週間'}
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.spanButton, span === 2 && styles.spanButtonActive]}
            onPress={() => setSpan(2)}
          >
            <Text style={[styles.spanButtonText, span === 2 && styles.spanButtonTextActive]}>
              {theme === 'child' ? 'ちゅうくらい' : '中期'}
            </Text>
            <Text style={[styles.spanButtonSubText, span === 2 && styles.spanButtonTextActive]}>
              {theme === 'child' ? '1ねん' : '1年'}
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.spanButton, span === 3 && styles.spanButtonActive]}
            onPress={() => setSpan(3)}
          >
            <Text style={[styles.spanButtonText, span === 3 && styles.spanButtonTextActive]}>
              {theme === 'child' ? 'ながい' : '長期'}
            </Text>
            <Text style={[styles.spanButtonSubText, span === 3 && styles.spanButtonTextActive]}>
              {theme === 'child' ? '5ねんいじょう' : '5年以上'}
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* 期限（span別条件分岐） */}
      <View style={styles.formGroup}>
        <Text style={styles.label}>
          {theme === 'child' ? 'いつまで?' : '期限'}
        </Text>
        
        {/* 短期: DateTimePicker */}
        {span === 1 && (
          <View>
            {Platform.OS === 'android' ? (
              <TouchableOpacity
                style={styles.dateButton}
                onPress={() => setShowDatePicker(true)}
              >
                <Text style={styles.dateButtonText}>
                  {dueDate || (theme === 'child' ? 'ひにちをえらぶ' : '日付を選択')}
                </Text>
              </TouchableOpacity>
            ) : (
              <TouchableOpacity
                style={styles.dateButton}
                onPress={() => setShowDatePicker(true)}
              >
                <Text style={styles.dateButtonText}>
                  {dueDate || (theme === 'child' ? 'ひにちをえらぶ' : '日付を選択')}
                </Text>
              </TouchableOpacity>
            )}
            
            {showDatePicker && (
              <DateTimePicker
                value={selectedDate}
                mode="date"
                display={Platform.OS === 'ios' ? 'spinner' : 'default'}
                onChange={onDateChange}
                minimumDate={new Date()}
              />
            )}
          </View>
        )}

        {/* 中期: 年選択 */}
        {span === 2 && (
          <View style={[styles.pickerContainer, { height: Platform.OS === 'ios' ? 150 : 50 }]}>
            <Picker
              selectedValue={selectedYear}
              onValueChange={onYearChange}
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
                    color={Platform.OS === 'ios' ? '#000' : undefined}
                  />
                );
              })}
            </Picker>
          </View>
        )}

        {/* 長期: テキスト入力 */}
        {span === 3 && (
          <TextInput
            style={styles.input}
            value={dueDate}
            onChangeText={setDueDate}
            placeholder={theme === 'child' ? '「5ねんご」など' : '例：5年後'}
            placeholderTextColor="#9CA3AF"
          />
        )}
      </View>

      {/* タグ選択 */}
      {availableTags.length > 0 && (
        <View style={styles.formGroup}>
          <Text style={styles.label}>
            {theme === 'child' ? 'タグ' : 'タグ'}
            {selectedTagIds.length > 0 && (
              <Text style={styles.tagCount}> ({selectedTagIds.length})</Text>
            )}
          </Text>

          {isLoadingTags ? (
            <ActivityIndicator size="small" color="#4F46E5" />
          ) : (
            <View>
              {/* 検索ボックス */}
              <TextInput
                style={styles.tagSearchInput}
                value={tagSearchQuery}
                onChangeText={setTagSearchQuery}
                placeholder={theme === 'child' ? '🔍 タグをさがす...' : '🔍 タグを検索...'}
                placeholderTextColor="#9CA3AF"
              />

              {/* 選択済みタグ */}
              {selectedTagIds.length > 0 && (
                <View style={styles.selectedTagsContainer}>
                  <Text style={styles.selectedTagsLabel}>
                    {theme === 'child' ? 'えらんだタグ' : '選択中'}
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
                          <Text style={[styles.tagChipText, { color: '#fff' }]}>
                            {tag.name}
                          </Text>
                          <Text style={styles.tagRemoveIcon}> ×</Text>
                        </TouchableOpacity>
                      ))}
                  </View>
                </View>
              )}

              {/* 展開可能なタグリスト */}
              <TouchableOpacity
                style={styles.tagExpandButton}
                onPress={() => setIsTagExpanded(!isTagExpanded)}
              >
                <Text style={styles.tagExpandButtonText}>
                  {theme === 'child' ? 'タグをついか' : 'タグを追加'} {isTagExpanded ? '▲' : '▼'}
                </Text>
              </TouchableOpacity>

              {isTagExpanded && (
                <ScrollView
                  style={styles.tagScrollView}
                  nestedScrollEnabled
                  showsVerticalScrollIndicator={true}
                >
                  <View style={styles.tagContainer}>
                    {availableTags
                      .filter(
                        (tag) =>
                          !selectedTagIds.includes(tag.id) &&
                          tag.name.toLowerCase().includes(tagSearchQuery.toLowerCase())
                      )
                      .map((tag) => (
                        <TouchableOpacity
                          key={tag.id}
                          style={styles.tagChip}
                          onPress={() => toggleTagSelection(tag.id)}
                        >
                          <Text style={styles.tagChipText}>{tag.name}</Text>
                        </TouchableOpacity>
                      ))}
                  </View>
                  {availableTags.filter(
                    (tag) =>
                      !selectedTagIds.includes(tag.id) &&
                      tag.name.toLowerCase().includes(tagSearchQuery.toLowerCase())
                  ).length === 0 && (
                    <Text style={styles.noResultsText}>
                      {theme === 'child' ? 'タグがみつかりません' : 'タグが見つかりません'}
                    </Text>
                  )}
                </ScrollView>
              )}
            </View>
          )}
        </View>
      )}

      {/* 更新ボタン */}
      <TouchableOpacity
        style={[styles.button, styles.updateButton]}
        onPress={handleUpdate}
        disabled={isLoading || isSubmitting}
      >
        {isLoading ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.buttonText}>
            {theme === 'child' ? 'こうしんする' : '更新する'}
          </Text>
        )}
      </TouchableOpacity>

      {/* 削除ボタン */}
      <TouchableOpacity
        style={[styles.button, styles.deleteButton]}
        onPress={handleDelete}
        disabled={isLoading || isSubmitting}
      >
        <Text style={styles.buttonText}>
          {theme === 'child' ? 'けす' : '削除'}
        </Text>
      </TouchableOpacity>
    </ScrollView>

    {/* ローディングオーバーレイ（アバター待機中） */}
    {isSubmitting && (
      <View style={styles.loadingOverlay}>
        <View style={styles.loadingBox}>
          <ActivityIndicator size="large" color="#4F46E5" />
          <Text style={styles.loadingText}>処理中</Text>
        </View>
      </View>
    )}
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  contentContainer: {
    padding: 16,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F9FAFB',
  },
  formGroup: {
    marginBottom: 16,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 8,
  },
  required: {
    color: '#EF4444',
  },
  input: {
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    backgroundColor: '#fff',
    color: '#111827',
  },
  textArea: {
    height: 100,
    textAlignVertical: 'top',
  },
  spanButtonGroup: {
    flexDirection: 'row',
    gap: 8,
  },
  spanButton: {
    flex: 1,
    paddingVertical: 12,
    paddingHorizontal: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D1D5DB',
    backgroundColor: '#fff',
    alignItems: 'center',
  },
  spanButtonActive: {
    backgroundColor: '#4F46E5',
    borderColor: '#4F46E5',
  },
  spanButtonText: {
    fontSize: 14,
    color: '#374151',
    fontWeight: '600',
  },
  spanButtonSubText: {
    fontSize: 12,
    color: '#6B7280',
    marginTop: 2,
  },
  spanButtonTextActive: {
    color: '#fff',
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    backgroundColor: '#fff',
    overflow: 'hidden',
  },
  picker: {
    height: Platform.OS === 'ios' ? 150 : 50,
  },
  pickerItem: {
    height: 150,
    fontSize: 16,
  },
  dateButton: {
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 12,
    backgroundColor: '#fff',
  },
  dateButtonText: {
    fontSize: 16,
    color: '#111827',
  },
  tagCount: {
    fontSize: 14,
    color: '#4F46E5',
    fontWeight: '600',
  },
  tagSearchInput: {
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 12,
    fontSize: 14,
    backgroundColor: '#fff',
    color: '#111827',
    marginBottom: 12,
  },
  selectedTagsContainer: {
    marginBottom: 12,
    padding: 12,
    backgroundColor: '#F9FAFB',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  selectedTagsLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: '#6B7280',
    marginBottom: 8,
  },
  tagExpandButton: {
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 12,
    backgroundColor: '#fff',
    alignItems: 'center',
    marginBottom: 8,
  },
  tagExpandButtonText: {
    fontSize: 14,
    color: '#4F46E5',
    fontWeight: '600',
  },
  tagScrollView: {
    maxHeight: 200,
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 12,
    backgroundColor: '#FAFAFA',
  },
  tagContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  tagChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
    backgroundColor: '#E5E7EB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
  },
  tagChipSelected: {
    backgroundColor: '#4F46E5',
    borderColor: '#4F46E5',
  },
  tagChipText: {
    fontSize: 14,
    color: '#374151',
  },
  tagRemoveIcon: {
    fontSize: 16,
    color: '#fff',
    fontWeight: 'bold',
    marginLeft: 4,
  },
  noResultsText: {
    fontSize: 14,
    color: '#9CA3AF',
    textAlign: 'center',
    paddingVertical: 16,
  },
  button: {
    padding: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 8,
  },
  updateButton: {
    backgroundColor: '#4F46E5',
  },
  deleteButton: {
    backgroundColor: '#EF4444',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
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
