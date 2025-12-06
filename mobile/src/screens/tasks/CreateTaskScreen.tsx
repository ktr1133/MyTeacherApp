/**
 * タスク作成画面
 * 
 * グループタスク作成対応、テーマに応じたラベル表示
 */
import React, { useState, useCallback } from 'react';
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
} from 'react-native';
import { useTasks } from '../../hooks/useTasks';
import { useTheme } from '../../contexts/ThemeContext';
import { CreateTaskData, TaskSpan, TaskPriority } from '../../types/task.types';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  TaskList: undefined;
  CreateTask: undefined;
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;

/**
 * タスク作成画面コンポーネント
 */
export default function CreateTaskScreen() {
  const navigation = useNavigation<NavigationProp>();
  const { theme } = useTheme();
  const { createTask, isLoading, error, clearError } = useTasks();

  // フォーム状態
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [span, setSpan] = useState<TaskSpan>(1);
  const [dueDate, setDueDate] = useState('');
  const [priority, setPriority] = useState<TaskPriority>(3);
  const [reward, setReward] = useState('10');
  const [requiresApproval, setRequiresApproval] = useState(false);
  const [requiresImage, setRequiresImage] = useState(false);
  const [isGroupTask, setIsGroupTask] = useState(false);

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

    const taskData: CreateTaskData = {
      title: title.trim(),
      description: description.trim() || undefined,
      span,
      due_date: dueDate.trim() || undefined,
      priority,
      reward: parseInt(reward, 10) || 10,
      requires_approval: requiresApproval,
      requires_image: requiresImage,
      is_group_task: isGroupTask,
    };

    const newTask = await createTask(taskData);

    if (newTask) {
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
    }
  }, [
    title,
    description,
    span,
    dueDate,
    priority,
    reward,
    requiresApproval,
    requiresImage,
    isGroupTask,
    createTask,
    theme,
    navigation,
  ]);

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

  return (
    <View style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backButtonText}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>
          {theme === 'child' ? 'やることをつくる' : 'タスク作成'}
        </Text>
        <View style={styles.headerSpacer} />
      </View>

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
          <TextInput
            style={styles.input}
            value={dueDate}
            onChangeText={setDueDate}
            placeholder={
              theme === 'child' ? '2025-12-31 か 2ねんご' : 'YYYY-MM-DD または 2年後'
            }
            placeholderTextColor="#9CA3AF"
          />
        </View>

        {/* 優先度 */}
        <View style={styles.fieldContainer}>
          <Text style={styles.label}>
            {theme === 'child' ? 'だいじさ' : '優先度'}
          </Text>
          <View style={styles.priorityContainer}>
            {[1, 2, 3, 4, 5].map((p) => (
              <TouchableOpacity
                key={p}
                style={[
                  styles.priorityButton,
                  priority === p && styles.priorityButtonActive,
                ]}
                onPress={() => setPriority(p as TaskPriority)}
              >
                <Text
                  style={[
                    styles.priorityButtonText,
                    priority === p && styles.priorityButtonTextActive,
                  ]}
                >
                  {p}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
          <Text style={styles.helpText}>
            {theme === 'child'
              ? '1がいちばんだいじ、5がすこしだいじ'
              : '1が最高優先度、5が最低優先度'}
          </Text>
        </View>

        {/* 報酬 */}
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

        {/* スイッチ類 */}
        <View style={styles.fieldContainer}>
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>
              {theme === 'child' ? 'かくにんがひつよう' : '承認が必要'}
            </Text>
            <Switch
              value={requiresApproval}
              onValueChange={setRequiresApproval}
              trackColor={{ false: '#D1D5DB', true: '#A5B4FC' }}
              thumbColor={requiresApproval ? '#4F46E5' : '#F3F4F6'}
            />
          </View>
          <Text style={styles.helpText}>
            {theme === 'child'
              ? 'できたらおとなにみせてね'
              : '完了時に親が承認する必要があります'}
          </Text>
        </View>

        <View style={styles.fieldContainer}>
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>
              {theme === 'child' ? 'しゃしんがひつよう' : '画像が必要'}
            </Text>
            <Switch
              value={requiresImage}
              onValueChange={setRequiresImage}
              trackColor={{ false: '#D1D5DB', true: '#A5B4FC' }}
              thumbColor={requiresImage ? '#4F46E5' : '#F3F4F6'}
            />
          </View>
          <Text style={styles.helpText}>
            {theme === 'child'
              ? 'できたらしゃしんをとってね'
              : '完了時に写真の添付が必要です'}
          </Text>
        </View>

        <View style={styles.fieldContainer}>
          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>
              {theme === 'child' ? 'みんなのやること' : 'グループタスク'}
            </Text>
            <Switch
              value={isGroupTask}
              onValueChange={setIsGroupTask}
              trackColor={{ false: '#D1D5DB', true: '#A5B4FC' }}
              thumbColor={isGroupTask ? '#4F46E5' : '#F3F4F6'}
            />
          </View>
          <Text style={styles.helpText}>
            {theme === 'child'
              ? 'みんなにおなじやることをあげるよ'
              : 'グループメンバー全員に同じタスクを割り当てます'}
          </Text>
        </View>

        {/* 作成ボタン */}
        <TouchableOpacity
          style={[styles.createButton, isLoading && styles.createButtonDisabled]}
          onPress={handleCreate}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator color="#FFFFFF" />
          ) : (
            <Text style={styles.createButtonText}>
              {theme === 'child' ? 'つくる' : '作成する'}
            </Text>
          )}
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

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
  headerSpacer: {
    width: 40,
  },
  content: {
    flex: 1,
  },
  contentContainer: {
    padding: 16,
  },
  fieldContainer: {
    marginBottom: 20,
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
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
    color: '#111827',
  },
  textArea: {
    height: 100,
    paddingTop: 10,
  },
  segmentContainer: {
    flexDirection: 'row',
    gap: 8,
  },
  segmentButton: {
    flex: 1,
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 8,
    backgroundColor: '#F3F4F6',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  segmentButtonActive: {
    backgroundColor: '#4F46E5',
    borderColor: '#4F46E5',
  },
  segmentButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6B7280',
  },
  segmentButtonTextActive: {
    color: '#FFFFFF',
  },
  priorityContainer: {
    flexDirection: 'row',
    gap: 8,
  },
  priorityButton: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 8,
    backgroundColor: '#F3F4F6',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  priorityButtonActive: {
    backgroundColor: '#4F46E5',
    borderColor: '#4F46E5',
  },
  priorityButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6B7280',
  },
  priorityButtonTextActive: {
    color: '#FFFFFF',
  },
  helpText: {
    fontSize: 12,
    color: '#9CA3AF',
    marginTop: 4,
  },
  switchRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  switchLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  createButton: {
    backgroundColor: '#4F46E5',
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 12,
    marginBottom: 40,
  },
  createButtonDisabled: {
    backgroundColor: '#9CA3AF',
  },
  createButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
  },
});
