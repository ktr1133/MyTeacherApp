/**
 * PrivacyPolicyScreen - プライバシーポリシー表示画面
 * 
 * 機能:
 * - Laravel APIからプライバシーポリシーテキストを取得
 * - ScrollView内にMarkdown形式で表示
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

import React, { useState, useEffect, useMemo } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ActivityIndicator,
  StyleSheet,
  StatusBar,
  Platform,
  ScrollView,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { useResponsive, getSpacing, getBorderRadius, getFontSize } from '../../utils/responsive';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useChildTheme } from '../../hooks/useChildTheme';
import { Ionicons } from '@expo/vector-icons';
import legalService from '../../services/legal.service';

/**
 * PrivacyPolicyScreen コンポーネント
 */
export const PrivacyPolicyScreen: React.FC = () => {
  const navigation = useNavigation();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [content, setContent] = useState('');

  /**
   * プライバシーポリシーを取得
   */
  useEffect(() => {
    const fetchPolicy = async () => {
      try {
        setLoading(true);
        setError(false);
        const data = await legalService.getPrivacyPolicy();
        setContent(data.content);
      } catch (err) {
        console.error('[PrivacyPolicyScreen] Failed to fetch policy:', err);
        setError(true);
        Alert.alert(
          themeType === 'child' ? 'エラー' : 'エラー',
          themeType === 'child' 
            ? 'プライバシーポリシーを よみこめなかったよ'
            : 'プライバシーポリシーの読み込みに失敗しました'
        );
      } finally {
        setLoading(false);
      }
    };

    fetchPolicy();
  }, [themeType]);

  /**
   * 戻るボタンハンドラー - 設定画面に戻る
   */
  const handleGoBack = () => {
    navigation.navigate('Settings' as never);
  };

  /**
   * 再読み込みハンドラー
   */
  const handleReload = async () => {
    try {
      setLoading(true);
      setError(false);
      const data = await legalService.getPrivacyPolicy();
      setContent(data.content);
    } catch (err) {
      console.error('[PrivacyPolicyScreen] Failed to reload policy:', err);
      setError(true);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container} edges={['bottom']}>
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

      {/* コンテンツ表示 */}
      {!loading && !error && (
        <ScrollView 
          style={styles.scrollView}
          contentContainerStyle={styles.contentContainer}
        >
          <Text style={styles.contentText}>{content}</Text>
        </ScrollView>
      )}
    </SafeAreaView>
  );
};

/**
 * スタイルシート作成関数
 */
const createStyles = (
  width: number,
  themeType: 'adult' | 'child',
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
      paddingTop: Platform.OS === 'ios' ? 50 : getSpacing(12, width),
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
    scrollView: {
      flex: 1,
    },
    contentContainer: {
      padding: getSpacing(16, width),
    },
    contentText: {
      fontSize: getFontSize(14, width, themeType),
      color: colors.text.primary,
      lineHeight: getFontSize(22, width, themeType),
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
