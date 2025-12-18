/**
 * PrivacyPolicyScreen - プライバシーポリシー表示画面
 * 
 * 機能:
 * - Web版プライバシーポリシーをWebViewで表示
 * - カスタムヘッダー（戻るボタン、タイトル）
 * - ローディングインジケーター
 * - エラーハンドリング
 * - ダークモード対応
 * - レスポンシブデザイン
 * 
 * 参照:
 * - ResponsiveDesignGuideline.md: レスポンシブデザインガイドライン
 * - DarkModeSupport.md: ダークモード実装ガイドライン
 */

import React, { useState, useMemo } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ActivityIndicator,
  StyleSheet,
  StatusBar,
  Platform,
} from 'react-native';
import { WebView } from 'react-native-webview';
import { useNavigation } from '@react-navigation/native';
import { useResponsive, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useChildTheme } from '../../hooks/useChildTheme';
import { Ionicons } from '@expo/vector-icons';
import { WEB_APP_URL } from '../../utils/constants';

/**
 * プライバシーポリシー画面URL
 * WEB_APP_URLはAPI_CONFIG.BASE_URLから自動生成されます
 * 本番環境: https://my-teacher-app.com/privacy-policy
 * 開発環境: EXPO_PUBLIC_API_URLの設定に依存
 */
const PRIVACY_POLICY_URL = `${WEB_APP_URL}/privacy-policy`;

/**
 * PrivacyPolicyScreen コンポーネント
 */
export const PrivacyPolicyScreen: React.FC = () => {
  const navigation = useNavigation();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, colors, accent), [width, colors, accent]);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  /**
   * WebViewロード完了ハンドラー
   */
  const handleLoadEnd = () => {
    setLoading(false);
  };

  /**
   * WebViewエラーハンドラー
   */
  const handleError = () => {
    setLoading(false);
    setError(true);
  };

  /**
   * 戻るボタンハンドラー
   */
  const handleGoBack = () => {
    navigation.goBack();
  };

  /**
   * 再読み込みハンドラー
   */
  const handleReload = () => {
    setError(false);
    setLoading(true);
  };

  return (
    <View style={styles.container}>
      <StatusBar
        barStyle={themeType === 'child' ? 'light-content' : colors.background === '#000000' ? 'light-content' : 'dark-content'}
        backgroundColor={colors.background}
      />

      {/* カスタムヘッダー */}
      <View style={styles.header}>
        <TouchableOpacity onPress={handleGoBack} style={styles.backButton}>
          <Ionicons name="chevron-back" size={24} color={colors.text.primary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>
          {themeType === 'child' ? 'プライバシーについて' : 'プライバシーポリシー'}
        </Text>
        <View style={styles.headerRight} />
      </View>

      {/* ローディングインジケーター */}
      {loading && (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={accent.primary} />
          <Text style={styles.loadingText}>読み込み中...</Text>
        </View>
      )}

      {/* エラー表示 */}
      {error && (
        <View style={styles.errorContainer}>
          <Ionicons name="alert-circle" size={48} color="#EF4444" />
          <Text style={styles.errorTitle}>読み込みエラー</Text>
          <Text style={styles.errorMessage}>
            ページの読み込みに失敗しました。{'\n'}
            インターネット接続を確認してください。
          </Text>
          <TouchableOpacity onPress={handleReload} style={styles.retryButton}>
            <Text style={styles.retryButtonText}>再読み込み</Text>
          </TouchableOpacity>
        </View>
      )}

      {/* WebView */}
      {!error && (
        <WebView
          source={{ uri: PRIVACY_POLICY_URL }}
          style={styles.webview}
          onLoadEnd={handleLoadEnd}
          onError={handleError}
          startInLoadingState={true}
          renderLoading={() => (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="large" color={accent.primary} />
            </View>
          )}
          // ダークモード対応: コンテンツのテーマを同期
          injectedJavaScript={`
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
              document.documentElement.classList.add('dark');
            }
          `}
        />
      )}
    </View>
  );
};

/**
 * スタイルシート作成関数
 */
const createStyles = (
  width: number,
  colors: ReturnType<typeof useThemedColors>['colors'],
  accent: ReturnType<typeof useThemedColors>['accent']
) => {
  return StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    header: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingTop: Platform.OS === 'ios' ? getSpacing(16, width) + 20 : getSpacing(12, width),
      paddingBottom: getSpacing(12, width),
      paddingHorizontal: getSpacing(16, width),
      backgroundColor: colors.card,
      borderBottomWidth: 1,
      borderBottomColor: colors.border.default,
      ...Platform.select({
        ios: {
          shadowColor: '#000',
          shadowOffset: { width: 0, height: 2 },
          shadowOpacity: 0.1,
          shadowRadius: 3,
        },
        android: {
          elevation: 4,
        },
      }),
    },
    backButton: {
      padding: getSpacing(8, width),
      width: 44,
      height: 44,
      justifyContent: 'center',
      alignItems: 'center',
    },
    headerTitle: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text.primary,
      flex: 1,
      textAlign: 'center',
    },
    headerRight: {
      width: 44,
    },
    webview: {
      flex: 1,
      backgroundColor: colors.background,
    },
    loadingContainer: {
      position: 'absolute',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: colors.background,
      zIndex: 10,
    },
    loadingText: {
      marginTop: getSpacing(12, width),
      fontSize: 16,
      color: colors.text.primary,
    },
    errorContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      paddingHorizontal: getSpacing(24, width),
      backgroundColor: colors.background,
    },
    errorTitle: {
      fontSize: 20,
      fontWeight: '600',
      color: colors.text.primary,
      marginTop: getSpacing(12, width),
      marginBottom: getSpacing(8, width),
    },
    errorMessage: {
      fontSize: 16,
      color: colors.text.secondary,
      textAlign: 'center',
      lineHeight: 24,
      marginBottom: getSpacing(24, width),
    },
    retryButton: {
      backgroundColor: accent.primary,
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(24, width),
      borderRadius: getBorderRadius(8, width),
      ...Platform.select({
        ios: {
          shadowColor: accent.primary,
          shadowOffset: { width: 0, height: 2 },
          shadowOpacity: 0.3,
          shadowRadius: 4,
        },
        android: {
          elevation: 4,
        },
      }),
    },
    retryButtonText: {
      fontSize: 16,
      fontWeight: '600',
      color: '#FFFFFF',
    },
  });
};
