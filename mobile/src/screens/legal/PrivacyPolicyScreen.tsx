/**
 * PrivacyPolicyScreen - プライバシーポリシー表示画面
 * 
 * 機能:
 * - Laravel APIからプライバシーポリシーHTML取得
 * - react-native-render-htmlでスタイル付き表示
 * - 目次リンクによるオートスクロール
 * - 「トップへ戻る」ボタン
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

import React, { useState, useEffect, useMemo, useRef } from 'react';
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
import RenderHtml from 'react-native-render-html';
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
  
  const scrollViewRef = useRef<ScrollView>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [htmlContent, setHtmlContent] = useState('');
  const [sectionOffsets, setSectionOffsets] = useState<{ [key: string]: number }>({});

  /**
   * プライバシーポリシーを取得
   */
  useEffect(() => {
    const fetchPolicy = async () => {
      try {
        setLoading(true);
        setError(false);
        const data = await legalService.getPrivacyPolicy();
        setHtmlContent(data.html);
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
      setHtmlContent(data.html);
    } catch (err) {
      console.error('[PrivacyPolicyScreen] Failed to reload policy:', err);
      setError(true);
    } finally {
      setLoading(false);
    }
  };

  /**
   * トップへ戻るハンドラー
   */
  const handleScrollToTop = () => {
    scrollViewRef.current?.scrollTo({ y: 0, animated: true });
  };

  /**
   * セクションスクロールハンドラー
   */
  const handleSectionPress = (sectionId: string) => {
    const offset = sectionOffsets[sectionId];
    if (offset !== undefined) {
      scrollViewRef.current?.scrollTo({ y: offset - 100, animated: true });
    }
  };

  /**
   * HTML用のタグ描画カスタマイズ
   */
  const renderers = {
    a: ({ tnode, onLinkPress }: any) => {
      const href = tnode.attributes.href || '';
      
      // 内部アンカーリンク（#intro等）
      if (href.startsWith('#')) {
        const sectionId = href.substring(1);
        return (
          <Text
            style={styles.link}
            onPress={() => handleSectionPress(sectionId)}
          >
            {tnode.children[0]?.data || ''}
          </Text>
        );
      }
      
      // トップへ戻るリンク
      if (href === '/' || href.includes('トップへ戻る')) {
        return (
          <Text
            style={styles.link}
            onPress={handleScrollToTop}
          >
            {tnode.children[0]?.data || 'トップへ戻る'}
          </Text>
        );
      }
      
      // 外部リンク（デフォルト処理）
      return (
        <Text style={styles.link} onPress={onLinkPress}>
          {tnode.children[0]?.data || ''}
        </Text>
      );
    },
  };

  /**
   * HTML用のタグスタイル
   */
  const tagsStyles = useMemo(() => ({
    body: {
      color: colors.text.primary,
      fontSize: getFontSize(14, width, themeType),
      lineHeight: getFontSize(22, width, themeType),
    },
    h1: {
      fontSize: getFontSize(28, width, themeType),
      fontWeight: '700' as const,
      color: colors.text.primary,
      marginBottom: getSpacing(16, width),
    },
    h2: {
      fontSize: getFontSize(22, width, themeType),
      fontWeight: '600' as const,
      color: colors.text.primary,
      marginTop: getSpacing(24, width),
      marginBottom: getSpacing(12, width),
      paddingBottom: getSpacing(8, width),
      borderBottomWidth: 2,
      borderBottomColor: accent.primary,
    },
    h3: {
      fontSize: getFontSize(18, width, themeType),
      fontWeight: '600' as const,
      color: colors.text.primary,
      marginTop: getSpacing(16, width),
      marginBottom: getSpacing(8, width),
    },
    p: {
      color: colors.text.primary,
      marginBottom: getSpacing(12, width),
      lineHeight: getFontSize(22, width, themeType),
    },
    a: {
      color: accent.primary,
      textDecorationLine: 'underline' as const,
    },
    ul: {
      marginLeft: getSpacing(16, width),
      marginBottom: getSpacing(12, width),
    },
    ol: {
      marginLeft: getSpacing(16, width),
      marginBottom: getSpacing(12, width),
    },
    li: {
      color: colors.text.primary,
      marginBottom: getSpacing(6, width),
    },
    strong: {
      fontWeight: '600' as const,
      color: colors.text.primary,
    },
    nav: {
      backgroundColor: colors.card,
      padding: getSpacing(16, width),
      borderRadius: getBorderRadius(8, width),
      borderWidth: 1,
      borderColor: colors.border.default,
      marginBottom: getSpacing(16, width),
    },
    section: {
      marginBottom: getSpacing(24, width),
    },
  }), [colors, accent, width, themeType]);

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
          ref={scrollViewRef}
          style={styles.scrollView}
          contentContainerStyle={styles.contentContainer}
        >
          <RenderHtml
            contentWidth={width - getSpacing(32, width)}
            source={{ html: htmlContent }}
            tagsStyles={tagsStyles}
            renderers={renderers}
            enableExperimentalMarginCollapsing={true}
          />
          
          {/* トップへ戻るボタン */}
          <TouchableOpacity
            onPress={handleScrollToTop}
            style={styles.scrollToTopButton}
          >
            <Ionicons name="arrow-up" size={20} color="#FFFFFF" />
            <Text style={styles.scrollToTopText}>トップへ戻る</Text>
          </TouchableOpacity>
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
    link: {
      color: accent.primary,
      textDecorationLine: 'underline',
    },
    scrollToTopButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: accent.primary,
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(24, width),
      borderRadius: getBorderRadius(8, width),
      marginTop: getSpacing(24, width),
      marginBottom: getSpacing(16, width),
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
    scrollToTopText: {
      fontSize: 16,
      fontWeight: '600',
      color: '#FFFFFF',
      marginLeft: getSpacing(8, width),
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
