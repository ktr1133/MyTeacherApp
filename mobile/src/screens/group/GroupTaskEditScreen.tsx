/**
 * グループタスク編集画面
 * 
 * Web版（/group-tasks/edit）と同等の機能を提供
 * グループタスク（複数ユーザーへの同時割当タスク）の情報を編集
 */
import { useState, useCallback, useEffect, useMemo } from 'react';
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
import { useNavigation, useRoute } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RouteProp } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import api from '../../services/api';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  GroupTaskEdit: { groupTaskId: string };
  TaskList: undefined;
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;
type ScreenRouteProp = RouteProp<RootStackParamList, 'GroupTaskEdit'>;

/**
 * グループタスク編集画面コンポーネント
 */
export default function GroupTaskEditScreen() {
  const navigation = useNavigation<NavigationProp>();
  const route = useRoute<ScreenRouteProp>();
  const { theme } = useTheme();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();

  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  const groupTaskId = route.params?.groupTaskId;

  // フォーム状態
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [span, setSpan] = useState<1 | 2 | 3>(1);
  const [dueDate, setDueDate] = useState(''); // 短期: YYYY-MM-DD、中期: YYYY年、長期: 任意文字列
  const [selectedDate, setSelectedDate] = useState(new Date()); // DateTimePicker用（短期のみ）
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear().toString()); // 年選択用（中期のみ）
  const [showDatePicker, setShowDatePicker] = useState(false); // DateTimePicker表示フラグ
  const [reward, setReward] = useState('0');
  const [requiresApproval, setRequiresApproval] = useState(false);
  const [requiresImage, setRequiresImage] = useState(false);

  // ローディング・エラー状態
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingData, setIsLoadingData] = useState(true);

  /**
   * 初回マウント時にデータ取得
   */
  useEffect(() => {
    loadGroupTaskData();
  }, [groupTaskId]);

  /**
   * span変更時の処理（期限入力をリセット）
   */
  useEffect(() => {
    if (!isLoadingData) {
      // データ読み込み中はスキップ（初期化時の意図しないリセット防止）
      if (span === 1) {
        // 短期: 今日の日付を初期値として設定
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const dateStr = `${year}-${month}-${day}`;
        setDueDate(dateStr);
        setSelectedDate(today);
      } else if (span === 2) {
        // 中期: 今年の年を初期値として設定
        const currentYear = new Date().getFullYear().toString();
        const dueDateStr = `${currentYear}年`;
        setDueDate(dueDateStr);
        setSelectedYear(currentYear);
      } else {
        // 長期: 空文字
        setDueDate('');
      }
    }
  }, [span, isLoadingData]);

  /**
   * グループタスクデータ取得
   */
  const loadGroupTaskData = async () => {
    if (!groupTaskId) return;

    setIsLoadingData(true);
    try {
      const response = await api.get(`/group-tasks/${groupTaskId}/edit`);
      const task = response.data;
      
      // spanの変換（DB: 1,3,6 → UI: 1,2,3）
      const uiSpan = task.span === 1 ? 1 : task.span === 3 ? 2 : 3;
      
      // 期限の準備
      let preparedDueDate = '';
      let preparedSelectedDate = new Date();
      let preparedSelectedYear = new Date().getFullYear().toString();
      
      if (task.due_date) {
        const dateObj = new Date(task.due_date);
        if (uiSpan === 1) {
          // 短期: YYYY-MM-DD形式
          const year = dateObj.getFullYear();
          const month = String(dateObj.getMonth() + 1).padStart(2, '0');
          const day = String(dateObj.getDate()).padStart(2, '0');
          preparedDueDate = `${year}-${month}-${day}`;
          preparedSelectedDate = dateObj;
        } else if (uiSpan === 2) {
          // 中期: YYYY年形式
          const year = dateObj.getFullYear().toString();
          preparedDueDate = `${year}年`;
          preparedSelectedYear = year;
        } else {
          // 長期: そのまま
          preparedDueDate = task.due_date;
        }
      }
      
      // 全ての状態を一度に更新
      setTitle(task.title);
      setDescription(task.description || '');
      setSpan(uiSpan as 1 | 2 | 3);
      setDueDate(preparedDueDate);
      setSelectedDate(preparedSelectedDate);
      setSelectedYear(preparedSelectedYear);
      setReward(task.reward.toString());
      setRequiresApproval(task.requires_approval);
      setRequiresImage(task.requires_image);
      setIsLoadingData(false);
    } catch (err: any) {
      console.error('[GroupTaskEditScreen] データ取得エラー:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'データがよめなかったよ' : 'データの取得に失敗しました',
        [{ text: 'OK', onPress: () => navigation.goBack() }]
      );
      setIsLoadingData(false);
    }
  };

  /**
   * グループタスク更新
   */
  const handleUpdate = useCallback(async () => {
    // バリデーション
    if (!title.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'なまえをいれてね' : 'タスク名を入力してください'
      );
      return;
    }

    const rewardNum = parseInt(reward, 10);
    if (isNaN(rewardNum) || rewardNum < 0) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'ほうしゅうは0いじょうにしてね' : '報酬は0以上の数値を入力してください'
      );
      return;
    }

    setIsLoading(true);
    try {
      // spanの変換（UI: 1,2,3 → DB: 1,3,6）
      const dbSpan = span === 1 ? 1 : span === 2 ? 3 : 6;
      
      // due_dateの整形（中期の場合「年」を削除）
      let formattedDueDate = dueDate.trim() || null;
      if (span === 2 && formattedDueDate) {
        formattedDueDate = formattedDueDate.replace('年', '');
      }
      
      const updateData = {
        title: title.trim(),
        description: description.trim() || null,
        span: dbSpan,
        due_date: formattedDueDate,
        reward: rewardNum,
        requires_approval: requiresApproval,
        requires_image: requiresImage,
      };
      
      await api.put(`/group-tasks/${groupTaskId}`, updateData);

      // 更新成功（200レスポンス）
      Alert.alert(
        theme === 'child' ? 'せいこう' : '成功',
        theme === 'child' ? 'へんしゅうできたよ！' : 'グループタスクを更新しました',
        [{ text: 'OK', onPress: () => navigation.goBack() }]
      );
    } catch (err: any) {
      console.error('[GroupTaskEditScreen] 更新エラー:', err);
      const errorMessage = err.response?.data?.message || (theme === 'child' ? 'エラーがおきたよ' : '更新に失敗しました');
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        errorMessage
      );
    } finally {
      setIsLoading(false);
    }
  }, [groupTaskId, title, description, span, dueDate, reward, requiresApproval, requiresImage, theme, navigation]);

  if (isLoadingData) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background }}>
        <ActivityIndicator size="large" color={typeof accent.primary === 'string' ? accent.primary : '#59B9C6'} />
        <Text style={{ marginTop: 12, fontSize: 14, color: colors.text?.secondary || '#6B7280' }}>
          {theme === 'child' ? 'よみこみちゅう...' : '読み込み中...'}
        </Text>
      </View>
    );
  }

  if (!styles) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: colors.background }}>
        <ActivityIndicator size="large" color={typeof accent.primary === 'string' ? accent.primary : '#59B9C6'} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
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
          <Text style={styles.headerTitle}>
            {theme === 'child' ? 'へんしゅう' : 'グループタスク編集'}
          </Text>
        </View>
        
        <View style={styles.headerSpacer} />
      </LinearGradient>

      <ScrollView style={styles.content} contentContainerStyle={styles.contentContainer}>
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            {theme === 'child' ? 'きほんじょうほう' : '基本情報'}
          </Text>

          <Text style={styles.label}>
            {theme === 'child' ? 'なまえ' : 'タスク名'} <Text style={styles.required}>*</Text>
          </Text>
          <TextInput
            style={styles.input}
            value={title}
            onChangeText={setTitle}
            placeholder={theme === 'child' ? 'なにをするの？' : '例：部屋の掃除'}
            maxLength={255}
          />

          <Text style={styles.label}>
            {theme === 'child' ? 'せつめい' : '説明'}
          </Text>
          <TextInput
            style={[styles.input, styles.textArea]}
            value={description}
            onChangeText={setDescription}
          placeholder={theme === 'child' ? 'くわしくせつめいしてね' : 'タスクの詳細'}
          multiline
          numberOfLines={4}
          textAlignVertical="top"
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          {theme === 'child' ? 'きかんときげん' : '期間と期限'}
        </Text>

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

        {span === 1 && (
          <>
            <Text style={styles.label}>
              {theme === 'child' ? 'きげん' : '期限日'}
            </Text>
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
                onChange={(_event, date) => {
                  setShowDatePicker(Platform.OS === 'ios');
                  if (date) {
                    setSelectedDate(date);
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    setDueDate(`${year}-${month}-${day}`);
                  }
                }}
              />
            )}
          </>
        )}

        {span === 2 && (
          <>
            <Text style={styles.label}>
              {theme === 'child' ? 'きげん' : '期限日'}
            </Text>
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
          </>
        )}

        {span === 3 && (
          <>
            <Text style={styles.label}>
              {theme === 'child' ? 'きげん' : '期限日'}
            </Text>
            <TextInput
              style={styles.input}
              value={dueDate}
              onChangeText={setDueDate}
              placeholder={theme === 'child' ? 'れい: 5ねんご' : '例: 5年後'}
              placeholderTextColor="#9CA3AF"
            />
          </>
        )}

        <Text style={styles.label}>
          {theme === 'child' ? 'ごほうび' : '報酬'} <Text style={styles.required}>*</Text>
        </Text>
        <TextInput
          style={styles.input}
          value={reward}
          onChangeText={setReward}
          placeholder="0"
          keyboardType="numeric"
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>
          {theme === 'child' ? 'せってい' : '設定'}
        </Text>

        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>
            {theme === 'child' ? 'かくにんがひつよう' : '承認が必要'}
          </Text>
          <Switch
            value={requiresApproval}
            onValueChange={setRequiresApproval}
            trackColor={{ false: colors.border.default as string, true: accent.primary as string }}
            thumbColor="#FFFFFF"
          />
        </View>

        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>
            {theme === 'child' ? 'しゃしんがひつよう' : '画像が必要'}
          </Text>
          <Switch
            value={requiresImage}
            onValueChange={setRequiresImage}
            trackColor={{ false: colors.border.default as string, true: accent.primary as string }}
            thumbColor="#FFFFFF"
          />
        </View>
      </View>
      <LinearGradient
          colors={['#9333ea', '#ec4899']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={[styles.updateButton, isLoading && styles.updateButtonDisabled]}
        >
          <TouchableOpacity
            onPress={handleUpdate}
            disabled={isLoading}
            style={styles.buttonTouchable}
          >
            {isLoading ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <Text style={styles.updateButtonText}>
                {theme === 'child' ? 'こうしん' : '更新する'}
              </Text>
            )}
          </TouchableOpacity>
        </LinearGradient>
      </ScrollView>
    </View>
  );
}

/**
 * スタイル定義
 */
const createStyles = (width: number, theme: 'adult' | 'child', colors: any, _accent: any) => {
  const accentPrimary = typeof _accent.primary === 'string' ? _accent.primary : '#59B9C6';
  const borderLight = colors.border?.light || colors.card || '#F3F4F6';
  const borderDefault = colors.border?.default || '#E5E7EB';
  
  return StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    centerContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: colors.background,
    },
    loadingText: {
      marginTop: getSpacing(12, width),
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
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
    content: {
      flex: 1,
    },
    contentContainer: {
      padding: getSpacing(16, width),
    },
    section: {
      backgroundColor: colors.card,
      borderRadius: getBorderRadius(12, width),
      padding: getSpacing(16, width),
      marginBottom: getSpacing(16, width),
    },
    sectionTitle: {
      fontSize: getFontSize(18, width, theme),
      fontWeight: 'bold',
      color: colors.text.primary,
      marginBottom: getSpacing(16, width),
    },
    label: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: colors.text.secondary,
      marginBottom: getSpacing(8, width),
      marginTop: getSpacing(12, width),
    },
    required: {
      color: '#EF4444',
    },
    input: {
      backgroundColor: colors.background,
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
    switchRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      paddingVertical: getSpacing(12, width),
    },
    switchLabel: {
      fontSize: getFontSize(14, width, theme),
      color: colors.text.primary,
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
      backgroundColor: borderLight,
      alignItems: 'center',
      borderWidth: 1,
      borderColor: borderDefault,
    },
    segmentButtonActive: {
      backgroundColor: accentPrimary,
      borderColor: accentPrimary,
    },
    segmentButtonText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: colors.text.secondary,
    },
    segmentButtonTextActive: {
      color: '#FFFFFF',
    },
    pickerContainer: {
      backgroundColor: colors.background,
      borderWidth: 1,
      borderColor: colors.border.default,
      borderRadius: getBorderRadius(8, width),
      overflow: 'hidden',
      minHeight: Platform.OS === 'ios' ? 150 : 50,
    },
    picker: {
      height: Platform.OS === 'ios' ? 150 : 50,
      width: '100%',
      fontSize: getFontSize(16, width, theme),
      color: colors.text.primary,
    },
    pickerItem: {
      height: Platform.OS === 'ios' ? 150 : 50,
      fontSize: getFontSize(16, width, theme),
      color: colors.text.primary,
    },
    dateButton: {
      backgroundColor: colors.background,
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
    infoBox: {
      backgroundColor: colors.accent?.secondary || '#FEF3C7',
      borderRadius: getBorderRadius(8, width),
      padding: getSpacing(12, width),
      marginVertical: getSpacing(8, width),
    },
    infoText: {
      fontSize: getFontSize(13, width, theme),
      color: colors.text.secondary,
      lineHeight: getFontSize(18, width, theme),
    },
    currentDateText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: colors.text.primary,
      marginTop: getSpacing(8, width),
    },
    updateButton: {
      borderRadius: getBorderRadius(12, width),
      overflow: 'hidden',
      marginTop: getSpacing(8, width),
      marginBottom: getSpacing(32, width),
    },
    updateButtonDisabled: {
      opacity: 0.5,
    },
    buttonTouchable: {
      paddingVertical: getSpacing(16, width),
      alignItems: 'center',
    },
    updateButtonText: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: 'bold',
      color: '#FFFFFF',
    },
  });
};
