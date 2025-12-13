/**
 * AIタスク分解画面
 * 
 * AIを使用してタスクを分解・提案し、採用する機能を提供
 * - 初回提案: タイトル・期間・コンテキストを入力
 * - 再提案: 追加の改善要望を入力して再度提案
 * - 採用: 提案されたタスクを選択して一括作成
 */
import { useState, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ScrollView,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useTheme } from '../../contexts/ThemeContext';
import { useAvatar } from '../../hooks/useAvatar';
import { taskService } from '../../services/task.service';
import {
  TaskSpan,
  ProposedTask,
  ProposeTaskData,
  ProposeTaskResponse,
  AdoptProposalData,
} from '../../types/task.types';
import { getErrorMessage } from '../../utils/errorMessages';

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

type NavigationProp = NativeStackNavigationProp<RootStackParamList, 'TaskDecomposition'>;
type TaskDecompositionRouteProp = RouteProp<RootStackParamList, 'TaskDecomposition'>;

/**
 * 画面の状態（ステップ管理）
 */
type ScreenState = 'input' | 'decomposition' | 'refine';

/**
 * 編集可能なタスク情報
 */
interface EditableTask extends ProposedTask {
  span: TaskSpan;
  due_date?: string;
}

/**
 * AIタスク分解画面コンポーネント
 */
export default function TaskDecompositionScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<TaskDecompositionRouteProp>();
  const { width } = useResponsive();
  const { theme } = useTheme();
  const styles = useMemo(() => createStyles(width, theme), [width, theme]);
  const { dispatchAvatarEvent } = useAvatar();

  // ルートパラメータから初期値を取得
  const { initialTitle = '', initialSpan = 2, initialDueDate = '' } = route.params || {};

  // 画面状態
  const [screenState, setScreenState] = useState<ScreenState>('input');
  const [isProposing, setIsProposing] = useState(false);

  // 入力フォーム状態
  const [title, setTitle] = useState(initialTitle);
  const [span, setSpan] = useState<TaskSpan>(initialSpan);
  const [dueDate, setDueDate] = useState(initialDueDate);
  const [context, setContext] = useState('');
  const [refinementPoints, setRefinementPoints] = useState('');

  // 提案結果状態
  const [proposalId, setProposalId] = useState<number | null>(null);
  const [proposedTasks, setProposedTasks] = useState<ProposedTask[]>([]);
  const [editableTasks, setEditableTasks] = useState<EditableTask[]>([]); // 編集可能なタスク情報
  const [selectedTaskIndices, setSelectedTaskIndices] = useState<Set<number>>(new Set());
  const [tokensUsed, setTokensUsed] = useState<{ prompt: number; completion: number; total: number } | null>(null);

  /**
   * タスク分解提案を実行
   */
  const handlePropose = useCallback(async (isRefinement: boolean = false) => {
    // バリデーション
    if (!title.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'やることのなまえをいれてね' : 'タイトルを入力してください'
      );
      return;
    }

    if (isRefinement && !refinementPoints.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' 
          ? 'なおしてほしいところをおしえてね' 
          : '改善要望を入力してください'
      );
      return;
    }

    setIsProposing(true);

    try {
      const requestData: ProposeTaskData = {
        title: title.trim(),
        span,
        due_date: dueDate.trim() || undefined,
        context: isRefinement ? refinementPoints.trim() : context.trim() || undefined,
        is_refinement: isRefinement,
      };

      console.log('[TaskDecompositionScreen] Proposing task:', requestData);

      const response: ProposeTaskResponse = await taskService.proposeTask(requestData);

      console.log('[TaskDecompositionScreen] Proposal response:', response);

      if (response.success && response.proposed_tasks) {
        setProposalId(response.proposal_id || null);
        setProposedTasks(response.proposed_tasks);
        setTokensUsed(response.tokens_used || null);
        
        // spanに応じたデフォルトdue_dateを計算
        const getDefaultDueDate = (taskSpan: TaskSpan): string => {
          const today = new Date();
          if (taskSpan === 1) {
            return today.toISOString().split('T')[0]; // YYYY-MM-DD
          } else if (taskSpan === 2) {
            return today.getFullYear().toString(); // YYYY
          } else {
            return ''; // 空欄
          }
        };
        
        // 編集可能なタスク情報を初期化（spanとdue_dateを設定）
        const editable: EditableTask[] = response.proposed_tasks.map((task) => ({
          ...task,
          span: task.span || span,
          due_date: dueDate.trim() || getDefaultDueDate(task.span || span),
        }));
        setEditableTasks(editable);
        
        // 全タスクを初期選択状態にする
        const allIndices = new Set(response.proposed_tasks.map((_, idx) => idx));
        setSelectedTaskIndices(allIndices);
        
        // 提案表示画面に遷移
        setScreenState('decomposition');
        
        // 成功メッセージ
        Alert.alert(
          theme === 'child' ? 'できたよ!' : '提案完了',
          theme === 'child' 
            ? `${response.proposed_tasks.length}このやることをかんがえたよ!` 
            : `${response.proposed_tasks.length}件のタスクを提案しました`
        );
      } else {
        throw new Error(response.error || 'TASK_PROPOSE_FAILED');
      }
    } catch (error: any) {
      console.error('[TaskDecompositionScreen] Propose error:', error);
      
      const errorMessage = getErrorMessage(error.message || 'TASK_PROPOSE_FAILED', theme);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        errorMessage
      );
    } finally {
      setIsProposing(false);
    }
  }, [title, span, dueDate, context, refinementPoints, theme]);

  /**
   * 再提案画面に遷移
   */
  const handleRefine = useCallback(() => {
    setRefinementPoints('');
    setScreenState('refine');
  }, []);

  /**
   * タスク選択切り替え
   */
  const toggleTaskSelection = useCallback((index: number) => {
    setSelectedTaskIndices((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(index)) {
        newSet.delete(index);
      } else {
        newSet.add(index);
      }
      return newSet;
    });
  }, []);

  /**
   * タスクのspan更新（due_dateフォーマットも自動調整）
   */
  const updateTaskSpan = useCallback((index: number, newSpan: TaskSpan) => {
    setEditableTasks((prev) => {
      const updated = [...prev];
      const currentTask = updated[index];
      
      // spanに応じたデフォルトdue_dateを計算
      const getDefaultDueDate = (taskSpan: TaskSpan): string => {
        const today = new Date();
        if (taskSpan === 1) {
          return today.toISOString().split('T')[0]; // YYYY-MM-DD
        } else if (taskSpan === 2) {
          return today.getFullYear().toString(); // YYYY
        } else {
          return ''; // 空欄
        }
      };
      
      // 既存のdue_dateをspan変更に応じて変換
      let newDueDate = currentTask.due_date || '';
      if (!newDueDate || currentTask.span !== newSpan) {
        // 空欄または異なるspanに変更する場合はデフォルト値を設定
        newDueDate = getDefaultDueDate(newSpan);
      }
      
      updated[index] = { ...currentTask, span: newSpan, due_date: newDueDate };
      return updated;
    });
  }, []);

  /**
   * タスクのdue_date更新
   */
  const updateTaskDueDate = useCallback((index: number, newDueDate: string) => {
    setEditableTasks((prev) => {
      const updated = [...prev];
      updated[index] = { ...updated[index], due_date: newDueDate };
      return updated;
    });
  }, []);

  /**
   * 提案採用（タスク一括作成）
   */
  const handleAdopt = useCallback(async () => {
    if (!proposalId) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'ていあんIDがないよ' : '提案IDが見つかりません'
      );
      return;
    }

    if (selectedTaskIndices.size === 0) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' 
          ? 'つくるやることをえらんでね' 
          : '作成するタスクを選択してください'
      );
      return;
    }

    setIsProposing(true);

    try {
      // 選択されたタスクのみを採用リクエストに含める
      // editableTasksから編集済みのspan/due_dateを使用
      // タグとして分解元のタイトルを設定
      const selectedTasks = editableTasks
        .filter((_, idx) => selectedTaskIndices.has(idx))
        .map((task) => ({
          title: task.title,
          span: task.span,
          priority: task.priority || 3,
          due_date: task.due_date || undefined,
          tags: [title.trim()], // 分解元のタイトルをタグとして設定
        }));

      const adoptData: AdoptProposalData = {
        proposal_id: proposalId,
        tasks: selectedTasks,
      };

      console.log('[TaskDecompositionScreen] Adopting proposal:', adoptData);

      const response = await taskService.adoptProposal(adoptData);

      console.log('[TaskDecompositionScreen] Adopt response:', response);

      if (response.success) {
        // アバターイベント発火（複数タスク作成）
        dispatchAvatarEvent('task_created');
        
        // アバター表示後にタスク一覧画面に遷移（3秒待機）
        setTimeout(() => {
          navigation.navigate('TaskList');
        }, 3000);
      } else {
        throw new Error(response.error || 'TASK_ADOPT_FAILED');
      }
    } catch (error: any) {
      console.error('[TaskDecompositionScreen] Adopt error:', error);
      
      const errorMessage = getErrorMessage(error.message || 'TASK_ADOPT_FAILED', theme);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        errorMessage
      );
    } finally {
      setIsProposing(false);
    }
  }, [proposalId, editableTasks, selectedTaskIndices, title, theme, navigation]);

  /**
   * 入力画面に戻る
   */
  const handleBackToInput = useCallback(() => {
    setScreenState('input');
    setRefinementPoints('');
  }, []);

  /**
   * 提案画面に戻る
   */
  const handleBackToDecomposition = useCallback(() => {
    setScreenState('decomposition');
    setRefinementPoints('');
  }, []);

  /**
   * レンダリング: 入力画面
   */
  const renderInputScreen = () => (
    <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
      <View style={styles.container}>
        <Text style={[styles.title, theme === 'child' && styles.titleChild]}>
          {theme === 'child' ? 'やることをこまかくする' : 'AIタスク分解'}
        </Text>

        <Text style={styles.description}>
          {theme === 'child' 
            ? 'おおきなやることを、ちいさなやることにわけるよ!' 
            : '大きなタスクを複数の小タスクに分解します'}
        </Text>

        {/* タイトル入力 */}
        <View style={styles.inputGroup}>
          <Text style={styles.label}>
            {theme === 'child' ? 'やることのなまえ' : 'タスクタイトル'}
            <Text style={styles.required}> *</Text>
          </Text>
          <TextInput
            style={styles.input}
            value={title}
            onChangeText={setTitle}
            placeholder={theme === 'child' ? 'れい: なつやすみのしゅくだい' : '例: 夏休みの宿題を終わらせる'}
            maxLength={255}
          />
        </View>

        {/* 期間選択 */}
        <View style={styles.inputGroup}>
          <Text style={styles.label}>
            {theme === 'child' ? 'きかん' : '期間'}
            <Text style={styles.required}> *</Text>
          </Text>
          <View style={styles.spanButtonGroup}>
            <TouchableOpacity
              style={[styles.spanButton, span === 1 && styles.spanButtonActive]}
              onPress={() => setSpan(1)}
            >
              <Text style={[styles.spanButtonText, span === 1 && styles.spanButtonTextActive]}>
                {theme === 'child' ? 'みじかい' : '短期'}
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.spanButton, span === 2 && styles.spanButtonActive]}
              onPress={() => setSpan(2)}
            >
              <Text style={[styles.spanButtonText, span === 2 && styles.spanButtonTextActive]}>
                {theme === 'child' ? 'ふつう' : '中期'}
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.spanButton, span === 3 && styles.spanButtonActive]}
              onPress={() => setSpan(3)}
            >
              <Text style={[styles.spanButtonText, span === 3 && styles.spanButtonTextActive]}>
                {theme === 'child' ? 'ながい' : '長期'}
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* 期限入力（任意） */}
        <View style={styles.inputGroup}>
          <Text style={styles.label}>
            {theme === 'child' ? 'いつまで（にゅうりょくしなくてもOK）' : '期限（任意）'}
          </Text>
          {span === 1 && (
            <Text style={styles.helperText}>
              {theme === 'child' ? 'ひづけをいれてね（れい: 2025-12-31）' : '日付を入力（例: 2025-12-31）'}
            </Text>
          )}
          {span === 2 && (
            <Text style={styles.helperText}>
              {theme === 'child' ? 'ねんをいれてね（れい: 2025）' : '年を入力（例: 2025）'}
            </Text>
          )}
          {span === 3 && (
            <Text style={styles.helperText}>
              {theme === 'child' ? 'すきなもじをいれてね（れい: 2ねんご）' : '任意の文字列を入力（例: 2年後）'}
            </Text>
          )}
          <TextInput
            style={styles.input}
            value={dueDate}
            onChangeText={setDueDate}
            placeholder={
              span === 1 
                ? (theme === 'child' ? 'れい: 2025-12-31' : '例: 2025-12-31')
                : span === 2
                ? (theme === 'child' ? 'れい: 2025' : '例: 2025')
                : (theme === 'child' ? 'れい: 2ねんご' : '例: 2年後')
            }
            keyboardType={span === 1 || span === 2 ? 'numeric' : 'default'}
          />
        </View>

        {/* コンテキスト入力（任意） */}
        <View style={styles.inputGroup}>
          <Text style={styles.label}>
            {theme === 'child' ? 'くわしいじょうほう（にゅうりょくしなくてもOK）' : '詳細情報（任意）'}
          </Text>
          <TextInput
            style={[styles.input, styles.textArea]}
            value={context}
            onChangeText={setContext}
            placeholder={theme === 'child' ? 'れい: さんすう、こくご、りかの3つ' : '例: 算数、国語、理科の3科目があります'}
            multiline
            numberOfLines={4}
          />
        </View>

        {/* トークン消費情報 */}
        <View style={styles.infoBox}>
          <Text style={styles.infoText}>
            {theme === 'child' 
              ? '💡 やく1000トークンをつかうよ' 
              : '💡 推定トークン消費量: 1000トークン'}
          </Text>
        </View>

        {/* 実行ボタン */}
        <View style={[styles.button, styles.primaryButton, isProposing && styles.buttonDisabled]}>
          <LinearGradient
            colors={['#59B9C6', '#3b82f6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={{ width: '100%', height: '100%', borderRadius: 8 }}
          >
            <TouchableOpacity
              style={styles.primaryButtonTouchable}
              onPress={() => handlePropose(false)}
              disabled={isProposing || !title.trim()}
            >
              {isProposing ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.buttonText}>
                  {theme === 'child' ? 'わけてもらう' : 'タスクを分解する'}
                </Text>
              )}
            </TouchableOpacity>
          </LinearGradient>
        </View>

        {/* キャンセルボタン */}
        <TouchableOpacity
          style={[styles.button, styles.secondaryButton]}
          onPress={() => navigation.goBack()}
          disabled={isProposing}
        >
          <Text style={styles.buttonTextSecondary}>
            {theme === 'child' ? 'もどる' : 'キャンセル'}
          </Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );

  /**
   * レンダリング: 提案表示画面
   */
  const renderDecompositionScreen = () => (
    <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
      <View style={styles.container}>
        <Text style={[styles.title, theme === 'child' && styles.titleChild]}>
          {theme === 'child' ? 'わけたやること' : '提案されたタスク'}
        </Text>

        <Text style={styles.description}>
          {theme === 'child' 
            ? `${proposedTasks.length}このやることをかんがえたよ！つくるものにチェックをつけてね。` 
            : `${proposedTasks.length}件のタスクを提案しました。作成するタスクを選択してください。`}
        </Text>

        {/* トークン使用量表示 */}
        {tokensUsed && (
          <View style={styles.infoBox}>
            <Text style={styles.infoText}>
              {theme === 'child' 
                ? `🎉 ${tokensUsed.total}トークンつかったよ` 
                : `🎉 使用トークン: ${tokensUsed.total}`}
            </Text>
          </View>
        )}

        {/* 提案タスク一覧 */}
        <View style={styles.taskList}>
          {editableTasks.map((task, index) => (
            <View
              key={index}
              style={[
                styles.taskCard,
                selectedTaskIndices.has(index) && styles.taskCardSelected,
              ]}
            >
              <TouchableOpacity
                style={styles.taskCardHeader}
                onPress={() => toggleTaskSelection(index)}
              >
                <View style={styles.checkbox}>
                  {selectedTaskIndices.has(index) && (
                    <Text style={styles.checkboxChecked}>✓</Text>
                  )}
                </View>
                <Text style={styles.taskTitle}>{task.title}</Text>
              </TouchableOpacity>
              
              {task.description && (
                <Text style={styles.taskDescription}>{task.description}</Text>
              )}
              
              {/* 期間選択 */}
              <View style={styles.taskEditGroup}>
                <Text style={styles.taskEditLabel}>
                  {theme === 'child' ? 'きかん:' : '期間:'}
                </Text>
                <View style={styles.spanButtonGroup}>
                  <TouchableOpacity
                    style={[
                      styles.spanButton,
                      task.span === 1 && styles.spanButtonActive,
                    ]}
                    onPress={() => updateTaskSpan(index, 1)}
                  >
                    <Text style={[
                      styles.spanButtonText,
                      task.span === 1 && styles.spanButtonTextActive,
                    ]}>
                      {theme === 'child' ? 'みじかい' : '短期'}
                    </Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[
                      styles.spanButton,
                      task.span === 2 && styles.spanButtonActive,
                    ]}
                    onPress={() => updateTaskSpan(index, 2)}
                  >
                    <Text style={[
                      styles.spanButtonText,
                      task.span === 2 && styles.spanButtonTextActive,
                    ]}>
                      {theme === 'child' ? 'ふつう' : '中期'}
                    </Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[
                      styles.spanButton,
                      task.span === 3 && styles.spanButtonActive,
                    ]}
                    onPress={() => updateTaskSpan(index, 3)}
                  >
                    <Text style={[
                      styles.spanButtonText,
                      task.span === 3 && styles.spanButtonTextActive,
                    ]}>
                      {theme === 'child' ? 'ながい' : '長期'}
                    </Text>
                  </TouchableOpacity>
                </View>
              </View>
              
              {/* 期限入力 */}
              <View style={styles.taskEditGroup}>
                <Text style={styles.taskEditLabel}>
                  {theme === 'child' ? 'いつまで:' : '期限:'}
                </Text>
                {task.span === 1 && (
                  <Text style={styles.taskHelperText}>
                    {theme === 'child' ? 'ひづけ' : '日付形式'}
                  </Text>
                )}
                {task.span === 2 && (
                  <Text style={styles.taskHelperText}>
                    {theme === 'child' ? 'ねん' : '年形式'}
                  </Text>
                )}
                {task.span === 3 && (
                  <Text style={styles.taskHelperText}>
                    {theme === 'child' ? 'すきなもじ' : '任意の文字列'}
                  </Text>
                )}
                <TextInput
                  style={styles.taskInput}
                  value={task.due_date || ''}
                  onChangeText={(text) => updateTaskDueDate(index, text)}
                  placeholder={
                    task.span === 1 
                      ? (theme === 'child' ? 'れい: 2025-12-31' : '例: 2025-12-31')
                      : task.span === 2
                      ? (theme === 'child' ? 'れい: 2025' : '例: 2025')
                      : (theme === 'child' ? 'れい: 2ねんご' : '例: 2年後')
                  }
                  keyboardType={task.span === 1 || task.span === 2 ? 'numeric' : 'default'}
                />
              </View>
              
              <View style={styles.taskMeta}>
                <Text style={styles.taskMetaText}>
                  {theme === 'child' ? 'たいせつさ: ' : '優先度: '}
                  {task.priority || 3}
                </Text>
              </View>
            </View>
          ))}
        </View>

        {/* 採用ボタン */}
        <View style={[styles.button, styles.primaryButton, isProposing && styles.buttonDisabled]}>
          <LinearGradient
            colors={['#59B9C6', '#3b82f6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={{ width: '100%', height: '100%', borderRadius: 8 }}
          >
            <TouchableOpacity
              style={styles.primaryButtonTouchable}
              onPress={handleAdopt}
              disabled={isProposing || selectedTaskIndices.size === 0}
            >
              {isProposing ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.buttonText}>
                  {theme === 'child' 
                    ? `${selectedTaskIndices.size}このやることをつくる` 
                    : `${selectedTaskIndices.size}件のタスクを作成`}
                </Text>
              )}
            </TouchableOpacity>
          </LinearGradient>
        </View>

        {/* 再提案ボタン */}
        <TouchableOpacity
          style={[styles.button, styles.secondaryButton]}
          onPress={handleRefine}
          disabled={isProposing}
        >
          <Text style={styles.buttonTextSecondary}>
            {theme === 'child' ? 'もういちどかんがえてもらう' : '再提案'}
          </Text>
        </TouchableOpacity>

        {/* 戻るボタン */}
        <TouchableOpacity
          style={[styles.button, styles.secondaryButton]}
          onPress={handleBackToInput}
          disabled={isProposing}
        >
          <Text style={styles.buttonTextSecondary}>
            {theme === 'child' ? 'さいしょにもどる' : '最初に戻る'}
          </Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );

  /**
   * レンダリング: 再提案入力画面
   */
  const renderRefineScreen = () => (
    <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
      <View style={styles.container}>
        <Text style={[styles.title, theme === 'child' && styles.titleChild]}>
          {theme === 'child' ? 'もういちどかんがえてもらう' : '再提案'}
        </Text>

        <Text style={styles.description}>
          {theme === 'child' 
            ? 'どこをなおしてほしいかおしえてね！' 
            : '改善してほしい点を入力してください'}
        </Text>

        {/* 改善要望入力 */}
        <View style={styles.inputGroup}>
          <Text style={styles.label}>
            {theme === 'child' ? 'なおしてほしいところ' : '改善要望'}
            <Text style={styles.required}> *</Text>
          </Text>
          <TextInput
            style={[styles.input, styles.textArea]}
            value={refinementPoints}
            onChangeText={setRefinementPoints}
            placeholder={theme === 'child' ? 'れい: もっとかんたんにして' : '例: もっと細かく分けてください'}
            multiline
            numberOfLines={4}
          />
        </View>

        {/* トークン消費情報 */}
        <View style={styles.infoBox}>
          <Text style={styles.infoText}>
            {theme === 'child' 
              ? '💡 またやく1000トークンをつかうよ' 
              : '💡 推定トークン消費量: 1000トークン'}
          </Text>
        </View>

        {/* 再提案ボタン */}
        <View style={[styles.button, styles.primaryButton, isProposing && styles.buttonDisabled]}>
          <LinearGradient
            colors={['#59B9C6', '#3b82f6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={{ width: '100%', height: '100%', borderRadius: 8 }}
          >
            <TouchableOpacity
              style={styles.primaryButtonTouchable}
              onPress={() => handlePropose(true)}
              disabled={isProposing || !refinementPoints.trim()}
            >
              {isProposing ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.buttonText}>
                  {theme === 'child' ? 'もういちどわけてもらう' : '再提案する'}
                </Text>
              )}
            </TouchableOpacity>
          </LinearGradient>
        </View>

        {/* 戻るボタン */}
        <TouchableOpacity
          style={[styles.button, styles.secondaryButton]}
          onPress={handleBackToDecomposition}
          disabled={isProposing}
        >
          <Text style={styles.buttonTextSecondary}>
            {theme === 'child' ? 'まえのがめんにもどる' : '提案画面に戻る'}
          </Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );

  /**
   * メインレンダリング
   */
  return (
    <View style={styles.root}>
      {screenState === 'input' && renderInputScreen()}
      {screenState === 'decomposition' && renderDecompositionScreen()}
      {screenState === 'refine' && renderRefineScreen()}
    </View>
  );
}

/**
 * スタイル定義
 */
const createStyles = (width: number, theme: any) => StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: getSpacing(16, width),
  },
  container: {
    flex: 1,
  },
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    marginBottom: getSpacing(8, width),
    color: '#333',
  },
  titleChild: {
    fontSize: getFontSize(28, width, theme),
    color: '#FF6B6B',
  },
  description: {
    fontSize: getFontSize(14, width, theme),
    color: '#666',
    marginBottom: getSpacing(24, width),
  },
  inputGroup: {
    marginBottom: getSpacing(20, width),
  },
  label: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    marginBottom: getSpacing(8, width),
    color: '#333',
  },
  required: {
    color: '#FF6B6B',
  },
  helperText: {
    fontSize: getFontSize(12, width, theme),
    color: '#999',
    marginBottom: getSpacing(4, width),
    fontStyle: 'italic',
  },
  input: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    backgroundColor: '#fff',
  },
  textArea: {
    height: getSpacing(100, width),
    textAlignVertical: 'top',
  },
  infoBox: {
    backgroundColor: '#E3F2FD',
    padding: getSpacing(12, width),
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(20, width),
  },
  infoText: {
    fontSize: getFontSize(14, width, theme),
    color: '#1976D2',
  },
  taskList: {
    marginBottom: getSpacing(20, width),
  },
  taskCard: {
    backgroundColor: '#fff',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(12, width),
    borderWidth: 2,
    borderColor: '#ddd',
  },
  taskCardSelected: {
    borderColor: '#59B9C6',
    backgroundColor: '#E0F2F7',
  },
  taskCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(8, width),
  },
  checkbox: {
    width: getSpacing(24, width),
    height: getSpacing(24, width),
    borderWidth: 2,
    borderColor: '#59B9C6',
    borderRadius: getBorderRadius(4, width),
    marginRight: getSpacing(12, width),
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkboxChecked: {
    color: '#59B9C6',
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
  },
  taskTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#333',
    flex: 1,
  },
  taskDescription: {
    fontSize: getFontSize(14, width, theme),
    color: '#666',
    marginBottom: getSpacing(8, width),
    marginLeft: getSpacing(36, width),
  },
  taskMeta: {
    flexDirection: 'row',
    marginLeft: getSpacing(36, width),
    marginTop: getSpacing(8, width),
  },
  taskMetaText: {
    fontSize: getFontSize(12, width, theme),
    color: '#999',
    marginRight: getSpacing(16, width),
  },
  taskEditGroup: {
    marginTop: getSpacing(8, width),
    marginLeft: getSpacing(36, width),
  },
  taskEditLabel: {
    fontSize: getFontSize(12, width, theme),
    fontWeight: '600',
    marginBottom: getSpacing(4, width),
    color: '#666',
  },
  taskHelperText: {
    fontSize: getFontSize(10, width, theme),
    color: '#999',
    marginBottom: getSpacing(4, width),
    fontStyle: 'italic',
  },
  spanButtonGroup: {
    flexDirection: 'row',
    gap: getSpacing(8, width),
  },
  spanButton: {
    flex: 1,
    paddingVertical: getSpacing(8, width),
    paddingHorizontal: getSpacing(12, width),
    borderRadius: getBorderRadius(4, width),
    borderWidth: 1,
    borderColor: '#ddd',
    backgroundColor: '#fff',
    alignItems: 'center',
  },
  spanButtonActive: {
    backgroundColor: '#59B9C6',
    borderColor: '#59B9C6',
  },
  spanButtonText: {
    fontSize: getFontSize(12, width, theme),
    color: '#666',
    fontWeight: '500',
  },
  spanButtonSubText: {
    fontSize: getFontSize(10, width, theme),
    color: '#999',
    marginTop: getSpacing(2, width),
  },
  spanButtonTextActive: {
    color: '#fff',
    fontWeight: '600',
  },
  taskInput: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: getBorderRadius(4, width),
    padding: getSpacing(8, width),
    fontSize: getFontSize(14, width, theme),
    backgroundColor: '#fff',
  },
  button: {
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(12, width),
  },
  primaryButton: {
    overflow: 'hidden',
    ...getShadow(2),
  },
  primaryButtonTouchable: {
    padding: getSpacing(16, width),
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryButton: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#ddd',
    padding: getSpacing(16, width),
    alignItems: 'center',
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  buttonText: {
    color: '#fff',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
  },
  buttonTextSecondary: {
    color: '#333',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
  },
});
