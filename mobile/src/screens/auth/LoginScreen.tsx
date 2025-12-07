/**
 * ログイン画面
 */
import { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { MaterialIcons } from '@expo/vector-icons';
import { useAuth } from '../../contexts/AuthContext';
import { useAvatar } from '../../hooks/useAvatar';
import AvatarWidget from '../../components/common/AvatarWidget';

export default function LoginScreen({ navigation }: any) {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const { login } = useAuth();
  const {
    isVisible: avatarVisible,
    currentData: avatarData,
    dispatchAvatarEvent,
    hideAvatar,
  } = useAvatar();

  // アバター状態をログ出力
  console.log('🎭 [LoginScreen] Avatar state:', { avatarVisible, hasAvatarData: !!avatarData });

  const handleLogin = async () => {
    setError('');
    
    if (!username || !password) {
      setError('ユーザー名とパスワードを入力してください');
      return;
    }

    setLoading(true);
    try {
      console.log('🎭 [LoginScreen] Attempting login:', { username });
      const result = await login(username, password);
      console.log('🎭 [LoginScreen] Login result:', { success: result.success });
      
      if (result.success) {
        // アバターイベント発火
        console.log('🎭 [LoginScreen] Firing avatar event: login');
        dispatchAvatarEvent('login');
        console.log('🎭 [LoginScreen] dispatchAvatarEvent called');
      } else if (result.error) {
        setError(result.error);
      }
      // 成功時はuseAuthがナビゲーションを処理
    } catch (err: any) {
      // 予期しないエラーの場合
      const errorMessage = err?.response?.data?.message || 'ログインに失敗しました';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>MyTeacher</Text>
        <Text style={styles.subtitle}>モバイルアプリ</Text>

        <View style={styles.form}>
          <TextInput
            style={styles.input}
            placeholder="ユーザー名"
            value={username}
            onChangeText={setUsername}
            autoCapitalize="none"
            editable={!loading}
            accessibilityLabel="ユーザー名入力"
          />

          <View style={styles.passwordContainer}>
            <TextInput
              style={styles.passwordInput}
              placeholder="パスワード"
              value={password}
              onChangeText={setPassword}
              secureTextEntry={!showPassword}
              editable={!loading}
              accessibilityLabel="パスワード入力"
            />
            <TouchableOpacity
              style={styles.eyeIcon}
              onPress={() => setShowPassword(!showPassword)}
              testID="toggle-password-visibility"
            >
              <MaterialIcons
                name={showPassword ? 'visibility' : 'visibility-off'}
                size={24}
                color="#6b7280"
              />
            </TouchableOpacity>
          </View>

          {error ? <Text style={styles.errorText}>{error}</Text> : null}

          <TouchableOpacity
            style={[styles.button, loading && styles.buttonDisabled]}
            onPress={handleLogin}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#fff" testID="loading-indicator" />
            ) : (
              <Text style={styles.buttonText}>ログイン</Text>
            )}
          </TouchableOpacity>

          <View style={styles.registerContainer}>
            <Text style={styles.registerText}>アカウントをお持ちでないですか？</Text>
            <TouchableOpacity
              onPress={() => navigation.navigate('Register')}
              disabled={loading}
            >
              <Text style={styles.linkText}>新規登録</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>

      {/* アバターウィジェット */}
      <AvatarWidget
        visible={avatarVisible}
        data={avatarData}
        onClose={hideAvatar}
        position="center"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#1f2937',
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: '#6b7280',
    textAlign: 'center',
    marginBottom: 48,
  },
  form: {
    rowGap: 16,
  },
  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: 8,
    padding: 16,
    fontSize: 16,
  },
  passwordContainer: {
    position: 'relative',
    flexDirection: 'row',
    alignItems: 'center',
  },
  passwordInput: {
    flex: 1,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: 8,
    padding: 16,
    paddingRight: 50,
    fontSize: 16,
  },
  eyeIcon: {
    position: 'absolute',
    right: 16,
  },
  errorText: {
    color: '#ef4444',
    fontSize: 14,
    textAlign: 'center',
  },
  button: {
    backgroundColor: '#3b82f6',
    borderRadius: 8,
    padding: 16,
    alignItems: 'center',
    marginTop: 8,
  },
  buttonDisabled: {
    backgroundColor: '#9ca3af',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  registerContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    columnGap: 4,
    padding: 8,
  },
  registerText: {
    color: '#6b7280',
    fontSize: 14,
  },
  linkText: {
    color: '#3b82f6',
    fontSize: 14,
    fontWeight: '600',
  },
});