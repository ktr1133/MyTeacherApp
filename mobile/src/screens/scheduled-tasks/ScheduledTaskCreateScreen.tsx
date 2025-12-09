/**
 * スケジュールタスク作成画面
 * 
 * 定期実行タスクの新規作成フォーム
 * 複数スケジュール設定、祝日スキップ、期限設定に対応
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
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
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
  ScheduledTaskCreate: { groupId: number };
  ScheduledTaskList: { groupId: number };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;
type ScreenRouteProp = RouteProp<RootStackParamList, 'ScheduledTaskCreate'>;

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
 * スケジュールタスク作成画面コンポーネント
 */
export default function ScheduledTaskCreateScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<ScreenRouteProp>();
  const { width } = useResponsive();
  const { theme } = useTheme();
  const styles = useMemo(() => createStyles(width, theme), [width, theme]);
  const { createScheduledTask, isLoading, error } = useScheduledTasks();

  const groupId = route.params?.groupId || 1;

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
  const [dueDurationDays, setDueDurationDays] = useState('3');
  const [dueDurationHours, setDueDurationHours] = useState('0');

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
   * 作成処理
   */
  const handleCreate = async () => {
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

    console.log('[ScheduledTaskCreateScreen] Creating scheduled task:', requestData);

    const result = await createScheduledTask(requestData);

    if (result) {
      Alert.alert(
        theme === 'child' ? 'できた！' : '作成完了',
        theme === 'child' ? 'スケジュールをつくったよ！' : 'スケジュールタスクを作成しました',
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

  if (isLoading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3B82F6" />
        <Text style={styles.loadingText}>
          {theme === 'child' ? 'つくってるよ...' : '作成中...'}
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

        <TouchableOpacity style={styles.submitButton} onPress={handleCreate}>
          <Text style={styles.submitButtonText}>
            {theme === 'child' ? 'つくる' : '作成'}
          </Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

/**
 * スタイル定義
 */
const createStyles = (width: number, theme: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  contentContainer: {
    padding: getSpacing(16, width),
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
  },
  loadingText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
  },
  section: {
    backgroundColor: '#FFFFFF',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(3, width),
  },
  sectionTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: '#1F2937',
    marginBottom: getSpacing(16, width),
  },
  label: {
    fontSize: getFontSize(14, width, theme),
    color: '#374151',
    marginBottom: getSpacing(8, width),
    marginTop: getSpacing(12, width),
  },
  required: {
    color: '#EF4444',
  },
  input: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    color: '#1F2937',
  },
  textArea: {
    height: getSpacing(80, width),
    textAlignVertical: 'top',
  },
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: getSpacing(12, width),
    paddingVertical: getSpacing(8, width),
  },
  switchLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#374151',
  },
  scheduleCard: {
    backgroundColor: '#F9FAFB',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginBottom: getSpacing(12, width),
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  scheduleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(8, width),
  },
  scheduleLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#374151',
    width: getSpacing(60, width),
  },
  pickerContainer: {
    flex: 1,
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  picker: {
    height: getSpacing(40, width),
  },
  timeInput: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(8, width),
    fontSize: getFontSize(16, width, theme),
    color: '#1F2937',
  },
  weekdayContainer: {
    marginTop: getSpacing(12, width),
  },
  weekdayLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#374151',
    marginBottom: getSpacing(8, width),
  },
  weekdayButtons: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  weekdayButton: {
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(8, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    marginRight: getSpacing(8, width),
    marginBottom: getSpacing(8, width),
  },
  weekdayButtonSelected: {
    backgroundColor: '#3B82F6',
    borderColor: '#3B82F6',
  },
  weekdayButtonText: {
    fontSize: getFontSize(14, width, theme),
    color: '#374151',
  },
  weekdayButtonTextSelected: {
    color: '#FFFFFF',
  },
  monthDateContainer: {
    marginTop: getSpacing(12, width),
  },
  monthDateLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#374151',
    marginBottom: getSpacing(8, width),
  },
  monthDateScroll: {
    maxHeight: getSpacing(120, width),
  },
  monthDateButtons: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  monthDateButton: {
    width: getSpacing(40, width),
    height: getSpacing(40, width),
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    marginRight: getSpacing(8, width),
    marginBottom: getSpacing(8, width),
  },
  monthDateButtonSelected: {
    backgroundColor: '#3B82F6',
    borderColor: '#3B82F6',
  },
  monthDateButtonText: {
    fontSize: getFontSize(14, width, theme),
    color: '#374151',
  },
  monthDateButtonTextSelected: {
    color: '#FFFFFF',
  },
  removeScheduleButton: {
    marginTop: getSpacing(12, width),
    paddingVertical: getSpacing(8, width),
    paddingHorizontal: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#FEE2E2',
    alignSelf: 'flex-start',
  },
  removeScheduleButtonText: {
    fontSize: getFontSize(12, width, theme),
    color: '#991B1B',
  },
  addScheduleButton: {
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#DBEAFE',
    alignItems: 'center',
  },
  addScheduleButtonText: {
    fontSize: getFontSize(14, width, theme),
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
    marginRight: getSpacing(8, width),
  },
  durationField: {
    flex: 1,
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(8, width),
    fontSize: getFontSize(16, width, theme),
    color: '#1F2937',
    marginLeft: getSpacing(8, width),
  },
  durationUnit: {
    fontSize: getFontSize(14, width, theme),
    color: '#6B7280',
    marginLeft: getSpacing(4, width),
  },
  dateButton: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
  },
  dateButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: '#1F2937',
  },
  errorContainer: {
    backgroundColor: '#FEE2E2',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginBottom: getSpacing(16, width),
  },
  errorText: {
    fontSize: getFontSize(14, width, theme),
    color: '#991B1B',
  },
  buttonContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: getSpacing(16, width),
    marginBottom: getSpacing(32, width),
  },
  cancelButton: {
    flex: 1,
    paddingVertical: getSpacing(14, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#F3F4F6',
    marginRight: getSpacing(8, width),
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: '#374151',
    fontWeight: 'bold',
  },
  submitButton: {
    flex: 1,
    paddingVertical: getSpacing(14, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#3B82F6',
    marginLeft: getSpacing(8, width),
    alignItems: 'center',
  },
  submitButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
});
