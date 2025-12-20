/**
 * TermsOfServiceScreen - 利用規約表示画面
 * 
 * 機能:
 * - Laravel APIから利用規約HTML取得
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
  Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import RenderHtml, { HTMLElementModel, HTMLContentModel } from 'react-native-render-html';
import { useResponsive, getSpacing, getBorderRadius, getFontSize } from '../../utils/responsive';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useChildTheme } from '../../hooks/useChildTheme';
import { Ionicons } from '@expo/vector-icons';
import legalService from '../../services/legal.service';

/**
 * TermsOfServiceScreen コンポーネント
 */
export const TermsOfServiceScreen: React.FC = () => {
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
  const sectionRefs = useRef<{ [key: string]: number }>({});

  /**
   * 利用規約を取得
   */
  useEffect(() => {
    const fetchTerms = async () => {
      try {
        setLoading(true);
        setError(false);
        const data = await legalService.getTermsOfService();
        setHtmlContent(data.html);
      } catch (err) {
        console.error('[TermsOfServiceScreen] Failed to fetch terms:', err);
        setError(true);
        Alert.alert(
          themeType === 'child' ? 'エラー' : 'エラー',
          themeType === 'child' 
            ? 'おやくそくを よみこめなかったよ'
            : '利用規約の読み込みに失敗しました'
        );
      } finally {
        setLoading(false);
      }
    };

    fetchTerms();
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
      const data = await legalService.getTermsOfService();
      setHtmlContent(data.html);
    } catch (err) {
      console.error('[TermsOfServiceScreen] Failed to reload terms:', err);
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
    const offset = sectionRefs.current[sectionId];
    if (offset !== undefined) {
      // ヘッダー高さ（約80px）+ SafeArea + 余白を考慮して200px引く
      scrollViewRef.current?.scrollTo({ y: Math.max(0, offset - 200), animated: true });
    } else {
      console.warn('[TermsOfServiceScreen] Section not found:', sectionId);
    }
  };

  /**
   * セクション位置を記録
   */
  const handleSectionLayout = (sectionId: string, event: any) => {
    const { y } = event.nativeEvent.layout;
    sectionRefs.current[sectionId] = y;
  };

  /**
   * HTML用のタグ描画カスタマイズ
   */
  const renderers = useMemo(() => ({
    a: ({ TDefaultRenderer, ...props }: any) => {
      const { tnode } = props;
      const href = tnode.attributes?.href || '';
      
      // テキストコンテンツを取得（子ノードから再帰的に）
      const getTextContent = (node: any): string => {
        if (!node) return '';
        if (node.data) return node.data;
        if (node.children && node.children.length > 0) {
          return node.children.map((child: any) => getTextContent(child)).join('');
        }
        return '';
      };
      
      const linkText = getTextContent(tnode);
      
      // 内部アンカーリンク（#intro等）
      if (href.startsWith('#')) {
        const sectionId = href.substring(1);
        return (
          <Text
            style={styles.link}
            selectable={true}
            onPress={() => handleSectionPress(sectionId)}
          >
            {linkText || href}
          </Text>
        );
      }
      
      // mailto リンク
      if (href.startsWith('mailto:')) {
        const email = href.replace('mailto:', '');
        return (
          <Text
            style={styles.link}
            selectable={true}
            onPress={() => {
              Linking.openURL(href).catch(err =>
                console.error('[TermsOfServiceScreen] Failed to open mailto:', err)
              );
            }}
          >
            {linkText || email}
          </Text>
        );
      }
      
      // 外部リンク（https://）
      if (href.startsWith('http://') || href.startsWith('https://')) {
        return (
          <Text
            style={styles.link}
            selectable={true}
            onPress={() => {
              Linking.openURL(href).catch(err =>
                console.error('[TermsOfServiceScreen] Failed to open URL:', err)
              );
            }}
          >
            {linkText || href}
          </Text>
        );
      }
      
      // トップへ戻るリンク
      if (href === '/' || linkText.includes('トップへ戻る')) {
        return (
          <Text
            style={styles.link}
            selectable={true}
            onPress={handleScrollToTop}
          >
            {linkText || 'トップへ戻る'}
          </Text>
        );
      }
      
      // デフォルト処理
      return <TDefaultRenderer {...props} />;
    },
    // テーブルのカスタムレンダラー（横スクロール対応）
    table: ({ tnode }: any) => {
      console.log('✅ Table renderer called!');
      console.log('tnode.type:', tnode.type);
      console.log('tnode.tagName:', tnode.tagName);
      console.log('tnode.children count:', tnode.children?.length);
      
      // テーブル配下のすべてのtr要素を抽出
      const rows: any[] = [];
      
      const extractRows = (node: any, depth: number = 0) => {
        if (!node) return;
        if (node.type === 'tag' && node.name === 'tr') {
          rows.push(node);
        }
        if (node.children) {
          node.children.forEach((child: any) => extractRows(child, depth + 1));
        }
      };
      
      extractRows(tnode);
      
      // テキスト抽出ヘルパー
      const getTextContent = (node: any): string => {
        if (!node) return '';
        if (node.data) return node.data;
        if (node.children && node.children.length > 0) {
          return node.children.map((child: any) => getTextContent(child)).join('');
        }
        return '';
      };
      
      // rowsが見つからない場合はフォールバック（テーブルを表示しない）
      if (rows.length === 0) {
        return (
          <View style={{ marginBottom: getSpacing(16, width) }}>
            <Text style={{ color: colors.text.secondary, fontSize: getFontSize(12, width, themeType) }}>
              （テーブルデータなし）
            </Text>
          </View>
        );
      }
      
      return (
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={true}
          style={{
            marginBottom: getSpacing(16, width),
          }}
        >
          <View
            style={{
              borderWidth: 1,
              borderColor: colors.border.default,
              borderRadius: getBorderRadius(8, width),
              overflow: 'hidden',
              minWidth: width - getSpacing(32, width),
            }}
          >
            {rows.map((row, rowIndex) => {
              // 行内のth/td要素を抽出
              const cells = row.children?.filter(
                (child: any) => child.type === 'tag' && (child.name === 'th' || child.name === 'td')
              ) || [];
              
              const isHeader = cells.some((cell: any) => cell.name === 'th');
              
              return (
                <View
                  key={`row-${rowIndex}`}
                  style={{
                    flexDirection: 'row',
                    borderBottomWidth: rowIndex < rows.length - 1 ? 1 : 0,
                    borderBottomColor: colors.border.default,
                  }}
                >
                  {cells.map((cell: any, cellIndex: number) => {
                    const text = getTextContent(cell);
                    const minCellWidth = 120; // 最小セル幅
                    
                    return (
                      <View
                        key={`cell-${rowIndex}-${cellIndex}`}
                        style={{
                          minWidth: minCellWidth,
                          flex: 1,
                          padding: getSpacing(12, width),
                          backgroundColor: colors.card,
                          borderRightWidth: cellIndex < cells.length - 1 ? 1 : 0,
                          borderRightColor: colors.border.default,
                        }}
                      >
                        <Text
                          style={{
                            fontSize: getFontSize(14, width, themeType),
                            fontWeight: isHeader ? '600' : '400',
                            color: colors.text.primary,
                          }}
                          selectable={true}
                        >
                          {text}
                        </Text>
                      </View>
                    );
                  })}
                </View>
              );
            })}
          </View>
        </ScrollView>
      );
    },
    // セクションレンダラー（位置記録）
    section: ({ TDefaultRenderer, tnode, ...props }: any) => {
      const sectionId = tnode.attributes?.id;
      
      return (
        <View
          onLayout={(event) => {
            if (sectionId) {
              handleSectionLayout(sectionId, event);
            }
          }}
        >
          <TDefaultRenderer tnode={tnode} {...props} />
        </View>
      );
    },
  }), [styles.link, handleSectionPress, handleScrollToTop, colors, width, themeType, handleSectionLayout]);

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
    h4: {
      fontSize: getFontSize(16, width, themeType),
      fontWeight: '600' as const,
      color: colors.text.primary,
      marginTop: getSpacing(12, width),
      marginBottom: getSpacing(6, width),
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
    // テーブル関連のスタイル
    table: {
      width: '100%',
      borderWidth: 1,
      borderColor: colors.border.default,
      borderRadius: getBorderRadius(8, width),
      marginBottom: getSpacing(16, width),
      backgroundColor: colors.card,
    },
    thead: {
      backgroundColor: colors.card,
    },
    tbody: {
      backgroundColor: colors.card,
    },
    tr: {
      borderBottomWidth: 1,
      borderBottomColor: colors.border.default,
    },
    th: {
      padding: getSpacing(12, width),
      textAlign: 'left' as const,
      fontSize: getFontSize(14, width, themeType),
      fontWeight: '600' as const,
      color: colors.text.primary,
      backgroundColor: colors.card,
    },
    td: {
      padding: getSpacing(12, width),
      fontSize: getFontSize(14, width, themeType),
      color: colors.text.primary,
      verticalAlign: 'top' as const,
    },
    // div要素
    div: {
      marginBottom: getSpacing(8, width),
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
          {themeType === 'child' ? 'おやくそく' : '利用規約'}
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
            ignoredDomTags={[]}  // テーブルタグを無視しない
            customHTMLElementModels={{
              table: HTMLElementModel.fromCustomModel({
                tagName: 'table',
                contentModel: HTMLContentModel.block,
              }),
            }}
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
  _themeType: 'adult' | 'child',
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
