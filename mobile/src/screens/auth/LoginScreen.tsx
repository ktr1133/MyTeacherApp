/**
 * ログイン画面
 */
import { useState, useMemo, useEffect, useRef } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Animated,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import MaskedView from '@react-native-masked-view/masked-view';
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

  // ロゴのアニメーション
  const pingAnim = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    // pingアニメーション（Web版のanimate-pingに相当）
    Animated.loop(
      Animated.sequence([
        Animated.timing(pingAnim, {
          toValue: 1.4,
          duration: 1000,
          useNativeDriver: true,
        }),
        Animated.timing(pingAnim, {
          toValue: 1,
          duration: 0,
          useNativeDriver: true,
        }),
      ])
    ).start();
  }, []);

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

  const handleForgotPassword = () => {
    navigation.navigate('ForgotPassword');
  };

  return (
    <View style={styles.container}>
      {/* グラデーション背景 */}
      <LinearGradient
        colors={['#F3F3F2', '#ffffff', '#e5e7eb']}
        style={StyleSheet.absoluteFillObject}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
      />

      <View style={styles.content}>
        {/* ロゴとタイトル */}
        <View style={styles.logoSection}>
          <View style={styles.logoWrapper}>
            <MaterialIcons name="school" size={64} color="#59B9C6" />
            {/* pingアニメーション（Web版のanimate-ping） */}
            <Animated.View
              style={[
                styles.pingDot,
                {
                  transform: [{ scale: pingAnim }],
                  opacity: pingAnim.interpolate({
                    inputRange: [1, 1.4],
                    outputRange: [0.75, 0],
                  }),
                },
              ]}
            />
          </View>
          {/* グラデーションテキスト（Web版のbg-clip-text） */}
          <MaskedView
            maskElement={
              <Text style={styles.titleText}>MyTeacher</Text>
            }
          >
            <LinearGradient
              colors={['#59B9C6', '#9333EA']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
            >
              <Text style={[styles.titleText, { opacity: 0 }]}>MyTeacher</Text>
            </LinearGradient>
          </MaskedView>
          <Text style={styles.subtitle}>アカウントにログイン</Text>
        </View>

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

          {/* パスワードを忘れた場合 */}
          <View style={styles.forgotPasswordContainer}>
            <TouchableOpacity
              onPress={handleForgotPassword}
              disabled={loading}
            >
              <Text style={styles.forgotPasswordText}>パスワードを忘れた?</Text>
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
                onPress={handleLogin}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator color="#fff" testID="loading-indicator" />
                ) : (
                  <Text style={styles.buttonText}>ログイン</Text>
                )}
              </TouchableOpacity>
            </LinearGradient>
          </View>

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
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    padding: getSpacing(24, width),
  },
  logoSection: {
    alignItems: 'center',
    marginBottom: getSpacing(48, width),
  },
  logoWrapper: {
    position: 'relative',
    marginBottom: getSpacing(24, width),
  },
  pingDot: {
    position: 'absolute',
    top: -4,
    right: -4,
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#9333EA',
  },
  titleText: {
    fontSize: getFontSize(32, width, theme),
    fontWeight: 'bold',
    color: '#000000',
    textAlign: 'center',
  },
  subtitle: {
    fontSize: getFontSize(14, width, theme),
    color: '#6b7280',
    textAlign: 'center',
    marginTop: getSpacing(8, width),
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
  forgotPasswordContainer: {
    alignItems: 'flex-end',
    marginTop: getSpacing(8, width),
  },
  forgotPasswordText: {
    color: '#6b7280',
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
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