/**
 * 新規登録画面
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
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useAuth } from '../../contexts/AuthContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

export default function RegisterScreen({ navigation }: any) {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const { register } = useAuth();

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

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

    setLoading(true);
    const result = await register(email, password, name);
    setLoading(false);

    if (!result.success) {
      Alert.alert('登録失敗', result.error || '登録に失敗しました');
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

const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  content: {
    padding: getSpacing(24, width),
    paddingTop: getSpacing(48, width),
  },
  title: {
    fontSize: getFontSize(32, width, theme),
    fontWeight: 'bold',
    color: '#1f2937',
    textAlign: 'center',
    marginBottom: getSpacing(8, width),
  },
  subtitle: {
    fontSize: getFontSize(16, width, theme),
    color: '#6b7280',
    textAlign: 'center',
    marginBottom: getSpacing(48, width),
  },
  form: {
    gap: getSpacing(16, width),
  },
  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    fontSize: getFontSize(16, width, theme),
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