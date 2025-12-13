/**
 * パスワードリセット画面
 * Web版デザイン準拠: グラスモーフィズム、グラデーション背景、フローティング装飾
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
  ScrollView,
  KeyboardAvoidingView,
  Platform,
  SafeAreaView,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import MaskedView from '@react-native-masked-view/masked-view';
import { MaterialIcons } from '@expo/vector-icons';
import { authService } from '../../services/auth.service';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

export default function ForgotPasswordScreen({ navigation }: any) {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

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

  const handleSubmit = async () => {
    setError('');
    setSuccessMessage('');
    
    if (!email) {
      setError('メールアドレスを入力してください');
      return;
    }

    // メールアドレス形式チェック
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      setError('有効なメールアドレスを入力してください');
      return;
    }

    setLoading(true);
    try {
      const response = await authService.forgotPassword(email);
      setSuccessMessage(response.message || 'パスワードリセット用のリンクをメールで送信しました');
      
      // 3秒後にログイン画面に戻る
      setTimeout(() => {
        navigation.navigate('Login');
      }, 3000);
    } catch (err: any) {
      console.error('[ForgotPassword] Error:', err);
      console.error('[ForgotPassword] Error response:', err?.response);
      console.error('[ForgotPassword] Error data:', err?.response?.data);
      
      // エラーメッセージの優先順位
      let errorMessage = 'リクエストに失敗しました。しばらく時間をおいてから再度お試しください';
      
      if (err?.response?.status === 404) {
        errorMessage = 'APIエンドポイントが見つかりません。サーバー設定を確認してください。';
      } else if (err?.response?.data?.message) {
        errorMessage = err.response.data.message;
      } else if (err?.response?.data?.errors?.email?.[0]) {
        errorMessage = err.response.data.errors.email[0];
      } else if (err?.message) {
        errorMessage = `エラー: ${err.message}`;
      }
      
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <KeyboardAvoidingView
        style={styles.container}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        {/* グラデーション背景 */}
        <LinearGradient
          colors={['#F3F3F2', '#ffffff', '#e5e7eb']}
          style={StyleSheet.absoluteFillObject}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        />

      {/* フローティング装飾 */}
      <View style={styles.floatingDecoration1} />
      <View style={styles.floatingDecoration2} />
      <View style={styles.floatingDecoration3} />

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
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
          <Text style={styles.subtitle}>パスワードをお忘れですか？</Text>
        </View>

        {/* グラスモーフィズムカード */}
        <View style={styles.card}>
          <Text style={styles.cardDescription}>
            メールアドレスを入力してください。パスワードリセット用のリンクをお送りします。
          </Text>

          {/* 成功メッセージ */}
          {successMessage ? (
            <View style={styles.successContainer}>
              <MaterialIcons name="check-circle" size={getFontSize(18, width)} color="#10B981" />
              <Text style={styles.successText}>{successMessage}</Text>
            </View>
          ) : null}

          {/* エラーメッセージ */}
          {error ? (
            <View style={styles.errorContainer}>
              <MaterialIcons name="error-outline" size={getFontSize(18, width)} color="#EF4444" />
              <Text style={styles.errorText}>{error}</Text>
            </View>
          ) : null}

          {/* メールアドレス入力 */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>メールアドレス</Text>
            <View style={styles.inputWrapper}>
              <MaterialIcons
                name="email"
                size={getFontSize(20, width)}
                color="#6B7280"
                style={styles.inputIcon}
              />
              <TextInput
                style={styles.input}
                value={email}
                onChangeText={setEmail}
                placeholder="メールアドレスを入力"
                placeholderTextColor="#9CA3AF"
                keyboardType="email-address"
                autoCapitalize="none"
                editable={!loading && !successMessage}
                accessibilityLabel="メールアドレス入力"
              />
            </View>
          </View>

          {/* 送信ボタン */}
          {!successMessage && (
            <TouchableOpacity
              onPress={handleSubmit}
              disabled={loading}
              style={styles.submitButtonWrapper}
            >
              <LinearGradient
                colors={['#59B9C6', '#9333EA']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.submitButton}
              >
                {loading ? (
                  <ActivityIndicator color="#ffffff" testID="loading-indicator" />
                ) : (
                  <>
                    <MaterialIcons name="email" size={getFontSize(18, width)} color="#ffffff" />
                    <Text style={styles.submitButtonText}>リセットリンクを送信</Text>
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>
          )}

          {/* ログインへ戻る */}
          <TouchableOpacity
            onPress={() => navigation.navigate('Login')}
            disabled={loading}
            style={styles.backButton}
          >
            <MaterialIcons name="arrow-back" size={getFontSize(16, width)} color="#6B7280" />
            <Text style={styles.backButtonText}>ログインページへ戻る</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  // SafeArea
  safeArea: {
    flex: 1,
    backgroundColor: '#F3F3F2',
  },
  // コンテナ
  container: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    padding: getSpacing(20, width),
    paddingTop: getSpacing(20, width),
    paddingBottom: getSpacing(40, width),
  },

  // フローティング装飾（Web版のblur円形）- サイズを小さく調整
  floatingDecoration1: {
    position: 'absolute',
    top: 60,
    left: -30,
    width: Math.min(width * 0.3, 150),
    height: Math.min(width * 0.3, 150),
    borderRadius: Math.min(width * 0.15, 75),
    backgroundColor: 'rgba(89, 185, 198, 0.08)',
  },
  floatingDecoration2: {
    position: 'absolute',
    top: '40%',
    right: -40,
    width: Math.min(width * 0.35, 180),
    height: Math.min(width * 0.35, 180),
    borderRadius: Math.min(width * 0.175, 90),
    backgroundColor: 'rgba(147, 51, 234, 0.08)',
  },
  floatingDecoration3: {
    position: 'absolute',
    bottom: 80,
    left: width * 0.15,
    width: Math.min(width * 0.25, 120),
    height: Math.min(width * 0.25, 120),
    borderRadius: Math.min(width * 0.125, 60),
    backgroundColor: 'rgba(89, 185, 198, 0.06)',
  },

  // ロゴエリア
  logoSection: {
    alignItems: 'center',
    marginBottom: getSpacing(32, width),
  },
  logoWrapper: {
    position: 'relative',
    marginBottom: getSpacing(16, width),
  },
  pingDot: {
    position: 'absolute',
    top: -4,
    right: -4,
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: '#9333EA',
  },
  titleText: {
    fontSize: getFontSize(28, width),
    fontWeight: 'bold',
    color: '#000000',
    textAlign: 'center',
  },
  subtitle: {
    fontSize: getFontSize(13, width),
    color: '#6B7280',
    textAlign: 'center',
    marginTop: getSpacing(6, width),
  },

  // グラスモーフィズムカード
  card: {
    backgroundColor: 'rgba(255, 255, 255, 0.85)',
    borderRadius: getBorderRadius(16, width),
    padding: getSpacing(24, width),
    ...getShadow(8),
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.3)',
  },
  cardDescription: {
    fontSize: getFontSize(13, width),
    color: '#6B7280',
    textAlign: 'center',
    marginBottom: getSpacing(20, width),
    lineHeight: getFontSize(19, width),
  },

  // 成功メッセージ
  successContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#D1FAE5',
    borderWidth: 1,
    borderColor: '#10B981',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginBottom: getSpacing(16, width),
  },
  successText: {
    fontSize: getFontSize(14, width),
    color: '#10B981',
    marginLeft: getSpacing(8, width),
    flex: 1,
  },

  // エラー表示
  errorContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FEE2E2',
    borderWidth: 1,
    borderColor: '#EF4444',
    borderRadius: getBorderRadius(8, width),
    padding: getSpacing(12, width),
    marginBottom: getSpacing(16, width),
  },
  errorText: {
    fontSize: getFontSize(14, width),
    color: '#EF4444',
    marginLeft: getSpacing(8, width),
    flex: 1,
  },

  // 入力フィールド
  inputGroup: {
    marginBottom: getSpacing(20, width),
  },
  label: {
    fontSize: getFontSize(14, width),
    fontWeight: '600',
    color: '#374151',
    marginBottom: getSpacing(8, width),
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: getBorderRadius(8, width),
    paddingHorizontal: getSpacing(12, width),
    ...getShadow(2),
  },
  inputIcon: {
    marginRight: getSpacing(8, width),
  },
  input: {
    flex: 1,
    fontSize: getFontSize(16, width),
    color: '#1F2937',
    paddingVertical: getSpacing(12, width),
  },

  // 送信ボタン
  submitButtonWrapper: {
    marginTop: getSpacing(6, width),
    marginBottom: getSpacing(20, width),
    borderRadius: getBorderRadius(8, width),
    ...getShadow(4),
  },
  submitButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(14, width),
    borderRadius: getBorderRadius(8, width),
  },
  submitButtonText: {
    fontSize: getFontSize(16, width),
    fontWeight: 'bold',
    color: '#ffffff',
    marginLeft: getSpacing(8, width),
  },

  // 戻るボタン
  backButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: getSpacing(12, width),
  },
  backButtonText: {
    fontSize: getFontSize(14, width),
    color: '#6B7280',
    fontWeight: '600',
    marginLeft: getSpacing(8, width),
  },
});
