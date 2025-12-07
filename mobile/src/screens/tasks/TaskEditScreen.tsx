/**
 * タスク編集画面
 * 
 * 通常タスク専用の編集画面
 * グループタスクは編集不可（TaskDetailScreenで表示のみ）
 */
import React, { useState, useCallback, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ScrollView,
  Alert,
  ActivityIndicator,
  Platform,
} from 'react-native';
import { Picker } from '@react-native-picker/picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { TaskSpan, TaskPriority, Task } from '../../types/task.types';
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
  const { tasks, updateTask, deleteTask, fetchTasks, isLoading } = useTasks();

  const { taskId } = route.params;
  const [task, setTask] = useState<Task | null>(null);
  const [loadingTask, setLoadingTask] = useState(true);

  // フォーム状態
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [span, setSpan] = useState<TaskSpan>(1);
  const [dueDate, setDueDate] = useState('');
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear().toString());
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [priority, setPriority] = useState<TaskPriority>(3);
  
  // タグ状態
  const [availableTags, setAvailableTags] = useState<Array<{ id: number; name: string; color?: string }>>([]);
  const [selectedTagIds, setSelectedTagIds] = useState<number[]>([]);
  const [isLoadingTags, setIsLoadingTags] = useState(false);

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
      if (tasks.length === 0) {
        await fetchTasks();
      }
      const foundTask = tasks.find((t) => t.id === taskId);
      
      if (!foundTask) {
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
      setPriority(foundTask.priority || 3);
      
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
        priority,
        tag_ids: selectedTagIds.length > 0 ? selectedTagIds : undefined,
      };

      console.log('[TaskEditScreen] Updating task:', taskData);

      const updatedTask = await updateTask(taskId, taskData as any);

      if (updatedTask) {
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
      } else {
        Alert.alert('エラー', 'タスクの更新に失敗しました');
      }
    } catch (error: any) {
      console.error('[TaskEditScreen] Update error:', error);
      Alert.alert('エラー', 'タスクの更新に失敗しました');
    }
  }, [title, description, span, dueDate, priority, selectedTagIds, taskId, updateTask, theme, navigation]);

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
            const success = await deleteTask(taskId);
            if (success) {
              navigation.navigate('TaskList');
            }
          },
        },
      ]
    );
  }, [taskId, deleteTask, theme, navigation]);

  /**
   * DateTimePicker変更ハンドラー（短期のみ）
   */
  const onDateChange = (event: any, selectedDate?: Date) => {
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
    <ScrollView style={styles.container} contentContainerStyle={styles.contentContainer}>
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
        <View style={styles.pickerContainer}>
          <Picker
            selectedValue={span}
            onValueChange={(value) => setSpan(value as TaskSpan)}
            style={styles.picker}
            itemStyle={styles.pickerItem}
          >
            <Picker.Item label={theme === 'child' ? 'みじかい（1しゅうかん）' : '短期（1週間）'} value={1} />
            <Picker.Item label={theme === 'child' ? 'ちゅうくらい（1ねん）' : '中期（1年）'} value={2} />
            <Picker.Item label={theme === 'child' ? 'ながい（5ねんいじょう）' : '長期（5年以上）'} value={3} />
          </Picker>
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

      {/* 優先度 */}
      <View style={styles.formGroup}>
        <Text style={styles.label}>
          {theme === 'child' ? 'だいじさ' : '優先度'}
        </Text>
        <View style={styles.pickerContainer}>
          <Picker
            selectedValue={priority}
            onValueChange={(value) => setPriority(value as TaskPriority)}
            style={styles.picker}
            itemStyle={styles.pickerItem}
          >
            <Picker.Item label={theme === 'child' ? 'とてもだいじ' : '高'} value={1} />
            <Picker.Item label={theme === 'child' ? 'だいじ' : '中'} value={2} />
            <Picker.Item label={theme === 'child' ? 'ふつう' : '低'} value={3} />
          </Picker>
        </View>
      </View>

      {/* タグ選択 */}
      {availableTags.length > 0 && (
        <View style={styles.formGroup}>
          <Text style={styles.label}>
            {theme === 'child' ? 'タグ' : 'タグ'}
          </Text>
          {isLoadingTags ? (
            <ActivityIndicator size="small" color="#4F46E5" />
          ) : (
            <View style={styles.tagContainer}>
              {availableTags.map((tag) => (
                <TouchableOpacity
                  key={tag.id}
                  style={[
                    styles.tagChip,
                    selectedTagIds.includes(tag.id) && styles.tagChipSelected,
                  ]}
                  onPress={() => toggleTagSelection(tag.id)}
                >
                  <Text
                    style={[
                      styles.tagChipText,
                      selectedTagIds.includes(tag.id) && { color: '#fff' },
                    ]}
                  >
                    {tag.name}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          )}
        </View>
      )}

      {/* 更新ボタン */}
      <TouchableOpacity
        style={[styles.button, styles.updateButton]}
        onPress={handleUpdate}
        disabled={isLoading}
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
        disabled={isLoading}
      >
        <Text style={styles.buttonText}>
          {theme === 'child' ? 'けす' : '削除'}
        </Text>
      </TouchableOpacity>
    </ScrollView>
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
  tagContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  tagChip: {
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
});
