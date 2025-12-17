/**
 * 新規登録画面
 * Phase 5-2: 13歳未満の場合は保護者メール入力を要求
 */
import { useState, useMemo } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  ScrollView,
  Platform,
} from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { LinearGradient } from 'expo-linear-gradient';
import { useAuth } from '../../contexts/AuthContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { useThemedColors } from '../../hooks/useThemedColors';

export default function RegisterScreen({ navigation }: any) {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [birthdate, setBirthdate] = useState<Date | null>(null);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [parentEmail, setParentEmail] = useState('');
  const [showParentEmailField, setShowParentEmailField] = useState(false);
  const [privacyConsent, setPrivacyConsent] = useState(false);
  const [termsConsent, setTermsConsent] = useState(false);
  const [loading, setLoading] = useState(false);
  const { register } = useAuth();

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors } = useThemedColors();
  const styles = useMemo(() => createStyles(width, themeType, colors), [width, themeType, colors]);

  /**
   * 年齢計算
   */
  const calculateAge = (birthDate: Date): number => {
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    
    return age;
  };

  /**
   * 生年月日選択時のハンドラ
   */
  const handleDateChange = (event: any, selectedDate?: Date) => {
    setShowDatePicker(Platform.OS === 'ios'); // iOSはピッカーを表示したまま
    
    if (selectedDate) {
      setBirthdate(selectedDate);
      
      // 年齢計算
      const age = calculateAge(selectedDate);
      
      // 13歳未満の場合は保護者メール欄を表示
      if (age < 13) {
        setShowParentEmailField(true);
      } else {
        setShowParentEmailField(false);
        setParentEmail(''); // クリア
      }
    }
  };

  const handleRegister = async () => {
    if (!name || !email || !password || !passwordConfirm) {
      Alert.alert('エラー', 'すべての項目を入力してください');
      return;
    }

    if (password !== passwordConfirm) {
      Alert.alert('エラー', 'パスワードが一致しません');
      return;
    }

    if (password.length < 8) {
      Alert.alert('エラー', 'パスワードは8文字以上で入力してください');
      return;
    }

    // Phase 5-2: 13歳未満の場合は保護者メール必須
    if (birthdate) {
      const age = calculateAge(birthdate);
      if (age < 13 && !parentEmail) {
        Alert.alert('エラー', '13歳未満の方は保護者のメールアドレスが必要です');
        return;
      }
    }

    if (!privacyConsent || !termsConsent) {
      Alert.alert('エラー', 'プライバシーポリシーと利用規約への同意が必要です');
      return;
    }

    setLoading(true);
    const result = await register(
      email, 
      password, 
      name, 
      privacyConsent, 
      termsConsent,
      birthdate ? birthdate.toISOString().split('T')[0] : undefined,
      parentEmail || undefined
    );
    setLoading(false);

    if (!result.success) {
      Alert.alert('登録失敗', result.error || '登録に失敗しました');
    } else if (result.requiresParentConsent) {
      // Phase 5-2: 保護者同意待ち
      Alert.alert(
        '保護者の同意が必要です',
        `保護者の方のメールアドレス（${result.parentEmail}）に同意依頼メールを送信しました。\n保護者の方の同意後、ログインできるようになります。`,
        [{ text: 'OK', onPress: () => navigation.goBack() }]
      );
    }
  };

  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>新規登録</Text>
        <Text style={styles.subtitle}>MyTeacherアカウントを作成</Text>

        <View style={styles.form}>
          <TextInput
            style={styles.input}
            placeholder="名前"
            value={name}
            onChangeText={setName}
            editable={!loading}
          />

          <TextInput
            style={styles.input}
            placeholder="メールアドレス"
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            keyboardType="email-address"
            editable={!loading}
          />

          <TextInput
            style={styles.input}
            placeholder="パスワード（8文字以上）"
            value={password}
            onChangeText={setPassword}
            secureTextEntry
            editable={!loading}
          />

          <TextInput
            style={styles.input}
            placeholder="パスワード（確認）"
            value={passwordConfirm}
            onChangeText={setPasswordConfirm}
            secureTextEntry
            editable={!loading}
          />

          {/* Phase 5-2: 生年月日入力 */}
          <View>
            <Text style={styles.fieldLabel}>生年月日（任意）</Text>
            <TouchableOpacity
              style={styles.input}
              onPress={() => setShowDatePicker(true)}
              disabled={loading}
            >
              <Text style={birthdate ? styles.inputText : styles.inputPlaceholder}>
                {birthdate ? birthdate.toLocaleDateString('ja-JP') : '生年月日を選択'}
              </Text>
            </TouchableOpacity>
            
            {showDatePicker && (
              <DateTimePicker
                value={birthdate || new Date(2010, 0, 1)}
                mode="date"
                display={Platform.OS === 'ios' ? 'spinner' : 'default'}
                onChange={handleDateChange}
                maximumDate={new Date()}
                minimumDate={new Date(1900, 0, 1)}
              />
            )}
            
            <Text style={styles.helpText}>
              13歳未満の方は保護者の同意が必要です
            </Text>
          </View>

          {/* Phase 5-2: 保護者メールアドレス（13歳未満の場合のみ表示） */}
          {showParentEmailField && (
            <View>
              <Text style={styles.fieldLabel}>保護者のメールアドレス</Text>
              <TextInput
                style={styles.input}
                placeholder="保護者のメールアドレスを入力"
                value={parentEmail}
                onChangeText={setParentEmail}
                autoCapitalize="none"
                keyboardType="email-address"
                editable={!loading}
              />
              <View style={styles.infoBox}>
                <Text style={styles.infoText}>
                  <Text style={styles.infoTextBold}>13歳未満の方へ：</Text> 
                  保護者の方のメールアドレスに同意依頼メールが送信されます。
                  保護者の方が同意されるまで、アカウントは仮登録状態となり、ログインできません。
                  同意期限は7日間です。
                </Text>
              </View>
            </View>
          )}

          {/* 同意チェックボックス（Phase 6A） */}
          <View style={styles.consentContainer}>
            <TouchableOpacity
              style={styles.checkboxRow}
              onPress={() => setPrivacyConsent(!privacyConsent)}
              disabled={loading}
            >
              <View style={[styles.checkbox, privacyConsent && styles.checkboxChecked]}>
                {privacyConsent && <Text style={styles.checkmark}>✓</Text>}
              </View>
              <Text style={styles.checkboxLabel}>
                <Text style={styles.linkInline} onPress={() => {
                  // TODO: WebViewで /privacy-policy を表示
                  Alert.alert('プライバシーポリシー', 'Web版で確認できます');
                }}>
                  プライバシーポリシー
                </Text>
                に同意する
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.checkboxRow}
              onPress={() => setTermsConsent(!termsConsent)}
              disabled={loading}
            >
              <View style={[styles.checkbox, termsConsent && styles.checkboxChecked]}>
                {termsConsent && <Text style={styles.checkmark}>✓</Text>}
              </View>
              <Text style={styles.checkboxLabel}>
                <Text style={styles.linkInline} onPress={() => {
                  // TODO: WebViewで /terms-of-service を表示
                  Alert.alert('利用規約', 'Web版で確認できます');
                }}>
                  利用規約
                </Text>
                に同意する
              </Text>
            </TouchableOpacity>
          </View>

          <View style={styles.buttonWrapper}>
            <LinearGradient
              colors={['#59B9C6', '#9333EA']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.buttonGradient}
            >
              <TouchableOpacity
                style={[styles.button, loading && styles.buttonDisabled]}
                onPress={handleRegister}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Text style={styles.buttonText}>登録</Text>
                )}
              </TouchableOpacity>
            </LinearGradient>
          </View>

          <TouchableOpacity
            style={styles.linkButton}
            onPress={() => navigation.goBack()}
            disabled={loading}
          >
            <Text style={styles.linkText}>既にアカウントをお持ちの方</Text>
          </TouchableOpacity>
        </View>
      </View>
    </ScrollView>
  );
}

const createStyles = (width: number, theme: 'adult' | 'child', colors: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  content: {
    padding: getSpacing(24, width),
    paddingTop: getSpacing(48, width),
  },
  title: {
    fontSize: getFontSize(32, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
    textAlign: 'center',
    marginBottom: getSpacing(8, width),
  },
  subtitle: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.secondary,
    textAlign: 'center',
    marginBottom: getSpacing(48, width),
  },
  form: {
    gap: getSpacing(16, width),
  },
  input: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
  },
  inputText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
  },
  inputPlaceholder: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.tertiary,
  },
  fieldLabel: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    marginBottom: getSpacing(8, width),
  },
  helpText: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    marginTop: getSpacing(4, width),
  },
  infoBox: {
    backgroundColor: colors.info.background,
    borderWidth: 1,
    borderColor: colors.info.border,
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginTop: getSpacing(8, width),
  },
  infoText: {
    fontSize: getFontSize(12, width, theme),
    color: colors.info.text,
    lineHeight: getFontSize(18, width, theme),
  },
  infoTextBold: {
    fontWeight: '700',
  },
  consentContainer: {
    gap: getSpacing(12, width),
    marginTop: getSpacing(8, width),
  },
  checkboxRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: getSpacing(12, width),
  },
  checkbox: {
    width: getSpacing(24, width),
    height: getSpacing(24, width),
    borderWidth: 2,
    borderColor: colors.border,
    borderRadius: getBorderRadius(4, width),
    backgroundColor: colors.card,
    justifyContent: 'center',
    alignItems: 'center',
  },
  checkboxChecked: {
    backgroundColor: '#3b82f6',
    borderColor: '#3b82f6',
  },
  checkmark: {
    color: '#fff',
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
  },
  checkboxLabel: {
    flex: 1,
    fontSize: getFontSize(14, width, theme),
    color: colors.text.primary,
  },
  linkInline: {
    color: '#3b82f6',
    textDecorationLine: 'underline',
  },
  buttonWrapper: {
    marginTop: getSpacing(8, width),
    borderRadius: getBorderRadius(12, width),
    overflow: 'hidden',
  },
  buttonGradient: {
    borderRadius: getBorderRadius(12, width),
  },
  button: {
    padding: getSpacing(16, width),
    alignItems: 'center',
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  buttonText: {
    color: '#fff',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
  },
  linkButton: {
    padding: getSpacing(8, width),
    alignItems: 'center',
  },
  linkText: {
    color: '#3b82f6',
    fontSize: getFontSize(14, width, theme),
  },
});