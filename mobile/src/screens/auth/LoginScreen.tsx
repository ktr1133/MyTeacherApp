/**
 * ログイン画面
 */
import React, { useState, useMemo } from 'react';
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
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

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

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  const handleLogin = async () => {
    setError('');
    
    if (!username || !password) {
      setError('ユーザー名とパスワードを入力してください');
      return;
    }

    setLoading(true);
    try {
      const result = await login(username, password);
      
      if (result.success) {
        // アバターイベント発火
        dispatchAvatarEvent('login');
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

const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    padding: getSpacing(24, width),
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
    rowGap: getSpacing(16, width),
  },
  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    fontSize: getFontSize(16, width, theme),
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
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    paddingRight: getSpacing(50, width),
    fontSize: getFontSize(16, width, theme),
  },
  eyeIcon: {
    position: 'absolute',
    right: getSpacing(16, width),
  },
  errorText: {
    color: '#ef4444',
    fontSize: getFontSize(14, width, theme),
    textAlign: 'center',
  },
  button: {
    backgroundColor: '#3b82f6',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(16, width),
    alignItems: 'center',
    marginTop: getSpacing(8, width),
  },
  buttonDisabled: {
    backgroundColor: '#9ca3af',
  },
  buttonText: {
    color: '#fff',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
  },
  registerContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    columnGap: getSpacing(4, width),
    padding: getSpacing(8, width),
  },
  registerText: {
    color: '#6b7280',
    fontSize: getFontSize(14, width, theme),
  },
  linkText: {
    color: '#3b82f6',
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
  },
});