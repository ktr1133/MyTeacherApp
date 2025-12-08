/**
 * スケジュールタスク編集画面
 * 
 * 既存のスケジュールタスクを編集
 * フォーム構造はScheduledTaskCreateScreenと同じ
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
  Switch,
  Platform,
} from 'react-native';
import { Picker } from '@react-native-picker/picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useScheduledTasks } from '../../hooks/useScheduledTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { Schedule, ScheduleType } from '../../types/scheduled-task.types';
import { useNavigation, useRoute } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RouteProp } from '@react-navigation/native';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  ScheduledTaskEdit: { scheduledTaskId: number };
  ScheduledTaskList: { groupId: number };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;
type ScreenRouteProp = RouteProp<RootStackParamList, 'ScheduledTaskEdit'>;

/**
 * 曜日定義
 */
const WEEKDAYS = [
  { value: 0, label: '日', labelChild: 'にち' },
  { value: 1, label: '月', labelChild: 'げつ' },
  { value: 2, label: '火', labelChild: 'か' },
  { value: 3, label: '水', labelChild: 'すい' },
  { value: 4, label: '木', labelChild: 'もく' },
  { value: 5, label: '金', labelChild: 'きん' },
  { value: 6, label: '土', labelChild: 'ど' },
];

/**
 * 月の日付リスト（1～31）
 */
const MONTH_DATES = Array.from({ length: 31 }, (_, i) => i + 1);

/**
 * スケジュールタスク編集画面コンポーネント
 */
export default function ScheduledTaskEditScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<ScreenRouteProp>();
  const { theme } = useTheme();
  const { getEditFormData, updateScheduledTask, isLoading, error } = useScheduledTasks();

  const scheduledTaskId = route.params?.scheduledTaskId;

  // 基本情報
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [requiresImage, setRequiresImage] = useState(false);
  const [requiresApproval, setRequiresApproval] = useState(false);
  const [reward, setReward] = useState('0');

  // スケジュール設定
  const [schedules, setSchedules] = useState<Schedule[]>([
    { type: 'daily', time: '09:00' },
  ]);

  // 期限設定
  const [dueDurationDays, setDueDurationDays] = useState('');
  const [dueDurationHours, setDueDurationHours] = useState('');

  // 実行期間
  const [startDate, setStartDate] = useState(new Date());
  const [endDate, setEndDate] = useState<Date | null>(null);
  const [showStartDatePicker, setShowStartDatePicker] = useState(false);
  const [showEndDatePicker, setShowEndDatePicker] = useState(false);

  // その他設定
  const [skipHolidays, setSkipHolidays] = useState(false);
  const [executeOnNextBusinessDay, setExecuteOnNextBusinessDay] = useState(false);

  // タグ
  const [tagsInput, setTagsInput] = useState('');

  // グループID（更新成功時のナビゲーション用）
  const [groupId, setGroupId] = useState<number>(1);

  // データロード状態
  const [isLoadingData, setIsLoadingData] = useState(true);

  /**
   * 初回マウント時にデータ取得
   */
  useEffect(() => {
    loadScheduledTaskData();
  }, [scheduledTaskId]);

  /**
   * スケジュールタスクデータ取得
   */
  const loadScheduledTaskData = async () => {
    if (!scheduledTaskId) return;

    setIsLoadingData(true);
    try {
      const data = await getEditFormData(scheduledTaskId);
      if (data && data.scheduled_task) {
        const task = data.scheduled_task;
        
        setTitle(task.title);
        setDescription(task.description || '');
        setRequiresImage(task.requires_image);
        setRequiresApproval(task.requires_approval);
        setReward(task.reward.toString());
        setSchedules(task.schedules);
        setDueDurationDays(task.due_duration_days ? task.due_duration_days.toString() : '');
        setDueDurationHours(task.due_duration_hours ? task.due_duration_hours.toString() : '');
        setStartDate(new Date(task.start_date));
        setEndDate(task.end_date ? new Date(task.end_date) : null);
        setSkipHolidays(task.skip_holidays);
        setExecuteOnNextBusinessDay(task.execute_on_next_business_day);
        setTagsInput(task.tags ? task.tags.join(', ') : '');
        setGroupId(task.group_id);

        console.log('[ScheduledTaskEditScreen] Loaded task data:', task);
      }
    } catch (err) {
      console.error('[ScheduledTaskEditScreen] Error loading task data:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'データがよめなかったよ' : 'データの取得に失敗しました',
        [{ text: 'OK', onPress: () => navigation.goBack() }]
      );
    } finally {
      setIsLoadingData(false);
    }
  };

  /**
   * スケジュール追加
   */
  const handleAddSchedule = useCallback(() => {
    setSchedules((prev) => [...prev, { type: 'daily', time: '09:00' }]);
  }, []);

  /**
   * スケジュール削除
   */
  const handleRemoveSchedule = useCallback((index: number) => {
    setSchedules((prev) => prev.filter((_, i) => i !== index));
  }, []);

  /**
   * スケジュールタイプ変更
   */
  const handleScheduleTypeChange = useCallback((index: number, type: ScheduleType) => {
    setSchedules((prev) => {
      const newSchedules = [...prev];
      newSchedules[index] = { type, time: newSchedules[index].time };
      return newSchedules;
    });
  }, []);

  /**
   * スケジュール時刻変更
   */
  const handleScheduleTimeChange = useCallback((index: number, time: string) => {
    setSchedules((prev) => {
      const newSchedules = [...prev];
      newSchedules[index] = { ...newSchedules[index], time };
      return newSchedules;
    });
  }, []);

  /**
   * 週次スケジュールの曜日トグル
   */
  const handleToggleWeekday = useCallback((index: number, day: number) => {
    setSchedules((prev) => {
      const newSchedules = [...prev];
      const schedule = newSchedules[index];
      if (schedule.type === 'weekly') {
        const days = schedule.days || [];
        newSchedules[index] = {
          ...schedule,
          days: days.includes(day) ? days.filter((d) => d !== day) : [...days, day].sort(),
        };
      }
      return newSchedules;
    });
  }, []);

  /**
   * 月次スケジュールの日付トグル
   */
  const handleToggleMonthDate = useCallback((index: number, date: number) => {
    setSchedules((prev) => {
      const newSchedules = [...prev];
      const schedule = newSchedules[index];
      if (schedule.type === 'monthly') {
        const dates = schedule.dates || [];
        newSchedules[index] = {
          ...schedule,
          dates: dates.includes(date) ? dates.filter((d) => d !== date) : [...dates, date].sort((a, b) => a - b),
        };
      }
      return newSchedules;
    });
  }, []);

  /**
   * 開始日変更
   */
  const handleStartDateChange = useCallback((_event: any, date?: Date) => {
    setShowStartDatePicker(Platform.OS === 'ios');
    if (date) {
      setStartDate(date);
    }
  }, []);

  /**
   * 終了日変更
   */
  const handleEndDateChange = useCallback((_event: any, date?: Date) => {
    setShowEndDatePicker(Platform.OS === 'ios');
    if (date) {
      setEndDate(date);
    }
  }, []);

  /**
   * 日付フォーマット（YYYY-MM-DD）
   */
  const formatDate = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  /**
   * バリデーション
   */
  const validateForm = (): boolean => {
    if (!title.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'タイトルをいれてね' : 'タイトルを入力してください'
      );
      return false;
    }

    if (schedules.length === 0) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'スケジュールをせっていしてね' : 'スケジュールを設定してください'
      );
      return false;
    }

    // 週次スケジュールの曜日チェック
    for (const schedule of schedules) {
      if (schedule.type === 'weekly' && (!schedule.days || schedule.days.length === 0)) {
        Alert.alert(
          theme === 'child' ? 'エラー' : 'エラー',
          theme === 'child' ? 'ようびをえらんでね' : '曜日を選択してください'
        );
        return false;
      }
      if (schedule.type === 'monthly' && (!schedule.dates || schedule.dates.length === 0)) {
        Alert.alert(
          theme === 'child' ? 'エラー' : 'エラー',
          theme === 'child' ? 'ひづけをえらんでね' : '日付を選択してください'
        );
        return false;
      }
    }

    return true;
  };

  /**
   * 更新処理
   */
  const handleUpdate = async () => {
    if (!validateForm()) return;

    const requestData = {
      title: title.trim(),
      description: description.trim() || undefined,
      requires_image: requiresImage,
      requires_approval: requiresApproval,
      reward: parseInt(reward, 10) || 0,
      schedules,
      due_duration_days: dueDurationDays ? parseInt(dueDurationDays, 10) : undefined,
      due_duration_hours: dueDurationHours ? parseInt(dueDurationHours, 10) : undefined,
      start_date: formatDate(startDate),
      end_date: endDate ? formatDate(endDate) : undefined,
      skip_holidays: skipHolidays,
      execute_on_next_business_day: executeOnNextBusinessDay,
      tags: tagsInput.trim() ? tagsInput.split(',').map((t) => t.trim()).filter((t) => t) : undefined,
    };

    console.log('[ScheduledTaskEditScreen] Updating scheduled task:', requestData);

    const result = await updateScheduledTask(scheduledTaskId!, requestData);

    if (result) {
      Alert.alert(
        theme === 'child' ? 'できた！' : '更新完了',
        theme === 'child' ? 'スケジュールをかえたよ！' : 'スケジュールタスクを更新しました',
        [
          {
            text: 'OK',
            onPress: () => navigation.navigate('ScheduledTaskList', { groupId }),
          },
        ]
      );
    }
  };

  /**
   * キャンセル処理
   */
  const handleCancel = () => {
    navigation.goBack();
  };

  /**
   * スケジュールカードのレンダリング
   */
  const renderScheduleCard = (schedule: Schedule, index: number) => {
    return (
      <View key={index} style={styles.scheduleCard}>
        {/* スケジュールタイプ選択 */}
        <View style={styles.scheduleRow}>
          <Text style={styles.scheduleLabel}>
            {theme === 'child' ? 'しゅるい' : 'タイプ'}:
          </Text>
          <View style={styles.pickerContainer}>
            <Picker
              selectedValue={schedule.type}
              onValueChange={(value) => handleScheduleTypeChange(index, value as ScheduleType)}
              style={styles.picker}
            >
              <Picker.Item label={theme === 'child' ? 'まいにち' : '日次'} value="daily" />
              <Picker.Item label={theme === 'child' ? 'まいしゅう' : '週次'} value="weekly" />
              <Picker.Item label={theme === 'child' ? 'まいつき' : '月次'} value="monthly" />
            </Picker>
          </View>
        </View>

        {/* 時刻入力 */}
        <View style={styles.scheduleRow}>
          <Text style={styles.scheduleLabel}>
            {theme === 'child' ? 'じこく' : '時刻'}:
          </Text>
          <TextInput
            style={styles.timeInput}
            value={schedule.time}
            onChangeText={(text) => handleScheduleTimeChange(index, text)}
            placeholder="09:00"
            keyboardType="default"
          />
        </View>

        {/* 週次: 曜日選択 */}
        {schedule.type === 'weekly' && (
          <View style={styles.weekdayContainer}>
            <Text style={styles.weekdayLabel}>
              {theme === 'child' ? 'ようび:' : '曜日:'}
            </Text>
            <View style={styles.weekdayButtons}>
              {WEEKDAYS.map((weekday) => {
                const isSelected = schedule.days?.includes(weekday.value) || false;
                return (
                  <TouchableOpacity
                    key={weekday.value}
                    style={[styles.weekdayButton, isSelected && styles.weekdayButtonSelected]}
                    onPress={() => handleToggleWeekday(index, weekday.value)}
                  >
                    <Text
                      style={[styles.weekdayButtonText, isSelected && styles.weekdayButtonTextSelected]}
                    >
                      {theme === 'child' ? weekday.labelChild : weekday.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>
        )}

        {/* 月次: 日付選択 */}
        {schedule.type === 'monthly' && (
          <View style={styles.monthDateContainer}>
            <Text style={styles.monthDateLabel}>
              {theme === 'child' ? 'ひづけ:' : '日付:'}
            </Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.monthDateScroll}>
              <View style={styles.monthDateButtons}>
                {MONTH_DATES.map((date) => {
                  const isSelected = schedule.dates?.includes(date) || false;
                  return (
                    <TouchableOpacity
                      key={date}
                      style={[styles.monthDateButton, isSelected && styles.monthDateButtonSelected]}
                      onPress={() => handleToggleMonthDate(index, date)}
                    >
                      <Text
                        style={[styles.monthDateButtonText, isSelected && styles.monthDateButtonTextSelected]}
                      >
                        {date}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
            </ScrollView>
          </View>
        )}

        {/* 削除ボタン */}
        {schedules.length > 1 && (
          <TouchableOpacity
            style={styles.removeScheduleButton}
            onPress={() => handleRemoveSchedule(index)}
          >
            <Text style={styles.removeScheduleButtonText}>
              🗑️ {theme === 'child' ? 'けす' : '削除'}
            </Text>
          </TouchableOpacity>
        )}
      </View>
    );
  };

  if (isLoadingData || isLoading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3B82F6" />
        <Text style={styles.loadingText}>
          {isLoadingData
            ? (theme === 'child' ? 'よみこみちゅう...' : '読み込み中...')
            : (theme === 'child' ? 'こうしんちゅう...' : '更新中...')}
        </Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.contentContainer}>
      {/* 基本情報 */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          📋 {theme === 'child' ? 'きほんじょうほう' : '基本情報'}
        </Text>

        <Text style={styles.label}>
          {theme === 'child' ? 'タイトル' : 'タイトル'} <Text style={styles.required}>*</Text>
        </Text>
        <TextInput
          style={styles.input}
          value={title}
          onChangeText={setTitle}
          placeholder={theme === 'child' ? 'なにをするの？' : 'タスクのタイトル'}
          maxLength={255}
        />

        <Text style={styles.label}>{theme === 'child' ? 'せつめい' : '説明'}</Text>
        <TextInput
          style={[styles.input, styles.textArea]}
          value={description}
          onChangeText={setDescription}
          placeholder={theme === 'child' ? 'どんなことをするの？' : '詳しい説明'}
          multiline
          numberOfLines={3}
          maxLength={5000}
        />

        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>
            {theme === 'child' ? 'しゃしんがひつよう' : '画像添付必須'}
          </Text>
          <Switch value={requiresImage} onValueChange={setRequiresImage} />
        </View>

        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>
            {theme === 'child' ? 'かくにんがひつよう' : '承認必須'}
          </Text>
          <Switch value={requiresApproval} onValueChange={setRequiresApproval} />
        </View>

        <Text style={styles.label}>
          {theme === 'child' ? 'ごほうび' : '報酬トークン'}
        </Text>
        <TextInput
          style={styles.input}
          value={reward}
          onChangeText={setReward}
          placeholder="0"
          keyboardType="numeric"
        />
      </View>

      {/* スケジュール設定 */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          📅 {theme === 'child' ? 'スケジュール' : 'スケジュール設定'} <Text style={styles.required}>*</Text>
        </Text>

        {schedules.map((schedule, index) => renderScheduleCard(schedule, index))}

        <TouchableOpacity style={styles.addScheduleButton} onPress={handleAddSchedule}>
          <Text style={styles.addScheduleButtonText}>
            ➕ {theme === 'child' ? 'スケジュールをふやす' : 'スケジュールを追加'}
          </Text>
        </TouchableOpacity>
      </View>

      {/* 期限設定 */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          ⏱️ {theme === 'child' ? 'きげん' : '期限設定'}
        </Text>

        <View style={styles.durationRow}>
          <View style={styles.durationInput}>
            <Text style={styles.label}>{theme === 'child' ? 'にっすう' : '日数'}:</Text>
            <TextInput
              style={styles.durationField}
              value={dueDurationDays}
              onChangeText={setDueDurationDays}
              placeholder="3"
              keyboardType="numeric"
            />
            <Text style={styles.durationUnit}>{theme === 'child' ? 'にち' : '日'}</Text>
          </View>

          <View style={styles.durationInput}>
            <Text style={styles.label}>{theme === 'child' ? 'じかん' : '時間'}:</Text>
            <TextInput
              style={styles.durationField}
              value={dueDurationHours}
              onChangeText={setDueDurationHours}
              placeholder="0"
              keyboardType="numeric"
            />
            <Text style={styles.durationUnit}>{theme === 'child' ? 'じかん' : '時間'}</Text>
          </View>
        </View>
      </View>

      {/* 実行期間 */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          📆 {theme === 'child' ? 'いつからいつまで' : '実行期間'}
        </Text>

        <Text style={styles.label}>
          {theme === 'child' ? 'はじまるひ' : '開始日'} <Text style={styles.required}>*</Text>
        </Text>
        <TouchableOpacity style={styles.dateButton} onPress={() => setShowStartDatePicker(true)}>
          <Text style={styles.dateButtonText}>{formatDate(startDate)}</Text>
        </TouchableOpacity>

        {showStartDatePicker && (
          <DateTimePicker
            value={startDate}
            mode="date"
            display="default"
            onChange={handleStartDateChange}
          />
        )}

        <Text style={styles.label}>{theme === 'child' ? 'おわるひ' : '終了日'}</Text>
        <TouchableOpacity style={styles.dateButton} onPress={() => setShowEndDatePicker(true)}>
          <Text style={styles.dateButtonText}>
            {endDate ? formatDate(endDate) : theme === 'child' ? 'えらんでね' : '選択してください'}
          </Text>
        </TouchableOpacity>

        {showEndDatePicker && (
          <DateTimePicker
            value={endDate || new Date()}
            mode="date"
            display="default"
            onChange={handleEndDateChange}
          />
        )}
      </View>

      {/* その他設定 */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          ⚙️ {theme === 'child' ? 'そのほか' : 'その他設定'}
        </Text>

        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>
            {theme === 'child' ? 'しゅくじつはやすみ' : '祝日をスキップ'}
          </Text>
          <Switch value={skipHolidays} onValueChange={setSkipHolidays} />
        </View>

        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>
            {theme === 'child' ? 'しゅくじつはつぎのひ' : '祝日時は翌営業日に実行'}
          </Text>
          <Switch value={executeOnNextBusinessDay} onValueChange={setExecuteOnNextBusinessDay} />
        </View>
      </View>

      {/* タグ */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          🏷️ {theme === 'child' ? 'タグ' : 'タグ'}
        </Text>

        <Text style={styles.label}>
          {theme === 'child' ? 'カンマでくぎってね' : 'カンマ区切りで入力'}
        </Text>
        <TextInput
          style={styles.input}
          value={tagsInput}
          onChangeText={setTagsInput}
          placeholder={theme === 'child' ? 'かじ, そうじ' : '家事, 掃除'}
        />
      </View>

      {/* エラー表示 */}
      {error && (
        <View style={styles.errorContainer}>
          <Text style={styles.errorText}>{error}</Text>
        </View>
      )}

      {/* ボタン */}
      <View style={styles.buttonContainer}>
        <TouchableOpacity style={styles.cancelButton} onPress={handleCancel}>
          <Text style={styles.cancelButtonText}>
            {theme === 'child' ? 'やめる' : 'キャンセル'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.submitButton} onPress={handleUpdate}>
          <Text style={styles.submitButtonText}>
            {theme === 'child' ? 'こうしん' : '更新'}
          </Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

/**
 * スタイル定義（ScheduledTaskCreateScreenと同じ）
 */
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  contentContainer: {
    padding: 16,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
  },
  loadingText: {
    marginTop: 12,
    fontSize: 14,
    color: '#6B7280',
  },
  section: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: 16,
  },
  label: {
    fontSize: 14,
    color: '#374151',
    marginBottom: 8,
    marginTop: 12,
  },
  required: {
    color: '#EF4444',
  },
  input: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    color: '#1F2937',
  },
  textArea: {
    height: 80,
    textAlignVertical: 'top',
  },
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 12,
    paddingVertical: 8,
  },
  switchLabel: {
    fontSize: 14,
    color: '#374151',
  },
  scheduleCard: {
    backgroundColor: '#F9FAFB',
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  scheduleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  scheduleLabel: {
    fontSize: 14,
    color: '#374151',
    width: 60,
  },
  pickerContainer: {
    flex: 1,
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    overflow: 'hidden',
  },
  picker: {
    height: 40,
  },
  timeInput: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 8,
    fontSize: 16,
    color: '#1F2937',
  },
  weekdayContainer: {
    marginTop: 12,
  },
  weekdayLabel: {
    fontSize: 14,
    color: '#374151',
    marginBottom: 8,
  },
  weekdayButtons: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  weekdayButton: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    marginRight: 8,
    marginBottom: 8,
  },
  weekdayButtonSelected: {
    backgroundColor: '#3B82F6',
    borderColor: '#3B82F6',
  },
  weekdayButtonText: {
    fontSize: 14,
    color: '#374151',
  },
  weekdayButtonTextSelected: {
    color: '#FFFFFF',
  },
  monthDateContainer: {
    marginTop: 12,
  },
  monthDateLabel: {
    fontSize: 14,
    color: '#374151',
    marginBottom: 8,
  },
  monthDateScroll: {
    maxHeight: 120,
  },
  monthDateButtons: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  monthDateButton: {
    width: 40,
    height: 40,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 8,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    marginRight: 8,
    marginBottom: 8,
  },
  monthDateButtonSelected: {
    backgroundColor: '#3B82F6',
    borderColor: '#3B82F6',
  },
  monthDateButtonText: {
    fontSize: 14,
    color: '#374151',
  },
  monthDateButtonTextSelected: {
    color: '#FFFFFF',
  },
  removeScheduleButton: {
    marginTop: 12,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    backgroundColor: '#FEE2E2',
    alignSelf: 'flex-start',
  },
  removeScheduleButtonText: {
    fontSize: 12,
    color: '#991B1B',
  },
  addScheduleButton: {
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 8,
    backgroundColor: '#DBEAFE',
    alignItems: 'center',
  },
  addScheduleButtonText: {
    fontSize: 14,
    color: '#1E40AF',
    fontWeight: 'bold',
  },
  durationRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  durationInput: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: 8,
  },
  durationField: {
    flex: 1,
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 8,
    fontSize: 16,
    color: '#1F2937',
    marginLeft: 8,
  },
  durationUnit: {
    fontSize: 14,
    color: '#6B7280',
    marginLeft: 4,
  },
  dateButton: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    padding: 12,
  },
  dateButtonText: {
    fontSize: 16,
    color: '#1F2937',
  },
  errorContainer: {
    backgroundColor: '#FEE2E2',
    borderRadius: 8,
    padding: 12,
    marginBottom: 16,
  },
  errorText: {
    fontSize: 14,
    color: '#991B1B',
  },
  buttonContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 16,
    marginBottom: 32,
  },
  cancelButton: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 8,
    backgroundColor: '#F3F4F6',
    marginRight: 8,
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: 16,
    color: '#374151',
    fontWeight: 'bold',
  },
  submitButton: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 8,
    backgroundColor: '#3B82F6',
    marginLeft: 8,
    alignItems: 'center',
  },
  submitButtonText: {
    fontSize: 16,
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
});
