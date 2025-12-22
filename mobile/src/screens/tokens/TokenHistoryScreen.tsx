/**
 * トークン履歴画面
 * 
 * 月次のトークン購入・使用統計を表示
 * 
 * @module screens/tokens/TokenHistoryScreen
 */

import React, { useEffect, useMemo } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  SafeAreaView,
  TouchableOpacity,
  ActivityIndicator,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useTheme } from '../../contexts/ThemeContext';
import { useTokens } from '../../hooks/useTokens';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * トークン履歴画面コンポーネント
 * 
 * 機能:
 * - 月次トークン購入金額・トークン数
 * - 月次トークン使用量
 * - Pull-to-Refresh機能
 * - エラーハンドリング
 * 
 * @returns {JSX.Element} トークン履歴画面
 */
const TokenHistoryScreen: React.FC = () => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const { theme } = useTheme();
  const { historyStats, loadHistoryStats, isLoading, error } = useTokens();

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  // 画面フォーカス時に履歴を更新
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      loadHistoryStats();
    });
    return unsubscribe;
  }, [navigation, loadHistoryStats]);

  // 初回読み込み
  useEffect(() => {
    loadHistoryStats();
  }, []);

  // テーマに応じたラベル
  const labels = theme === 'child' ? {
    title: 'トークンのりれき',
    loading: 'よみこみちゅう...',
    monthlyPurchase: 'こんげつかったトークン',
    monthlyUsage: 'こんげつつかったトークン',
    amount: 'おかね',
    tokens: 'トークン',
    usage: 'つかったかず',
    noData: 'まだりれきがありません',
    back: 'もどる',
    error: 'エラー',
    purchaseHistory: 'かったりれき',
    noPurchases: 'まだかっていません',
    date: 'ひづけ',
  } : {
    title: 'トークン履歴',
    loading: '読み込み中...',
    monthlyPurchase: '今月の購入',
    monthlyUsage: '今月の使用',
    amount: '購入金額',
    tokens: 'トークン数',
    usage: '使用量',
    noData: '履歴がありません',
    back: '戻る',
    error: 'エラー',
    purchaseHistory: '購入履歴',
    noPurchases: '購入履歴がありません',
    date: '購入日時',
  };

  /**
   * 金額をフォーマット
   */
  const formatAmount = (amount: number): string => {
    return `¥${amount.toLocaleString('ja-JP')}`;
  };

  /**
   * トークン数をフォーマット（3桁カンマ区切り）
   */
  const formatTokens = (tokens: number): string => {
    return tokens.toLocaleString('ja-JP');
  };

  /**
   * 日時をフォーマット
   */
  const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${year}/${month}/${day} ${hours}:${minutes}`;
  };

  /**
   * descriptionから価格情報を抽出
   */
  const extractPrice = (description: string): string | null => {
    const match = description.match(/¥[\d,]+/);
    return match ? match[0] : null;
  };

  /**
   * 戻るボタンハンドラー
   */
  const handleGoBack = () => {
    navigation.goBack();
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backButton} onPress={handleGoBack}>
          <Text style={styles.backButtonText}>← {labels.back}</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{labels.title}</Text>
      </View>

      {/* コンテンツ */}
      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl refreshing={isLoading} onRefresh={loadHistoryStats} />
        }
      >
        {/* エラー表示 */}
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>⚠️ {error}</Text>
          </View>
        )}

        {/* ローディング */}
        {isLoading && !historyStats && (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color="#3b82f6" />
            <Text style={styles.loadingText}>{labels.loading}</Text>
          </View>
        )}

        {/* データなし */}
        {!isLoading && !error && !historyStats && (
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyText}>{labels.noData}</Text>
          </View>
        )}

        {/* 統計カード */}
        {historyStats && (
          <>
            {/* 今月の購入 */}
            <View style={styles.card}>
              <Text style={styles.cardTitle}>{labels.monthlyPurchase}</Text>
              
              <View style={styles.statRow}>
                <Text style={styles.statLabel}>{labels.amount}:</Text>
                <Text style={styles.statValueAmount}>
                  {formatAmount(historyStats.monthlyPurchaseAmount)}
                </Text>
              </View>

              <View style={styles.statRow}>
                <Text style={styles.statLabel}>{labels.tokens}:</Text>
                <Text style={styles.statValueTokens}>
                  {formatTokens(historyStats.monthlyPurchaseTokens)}
                </Text>
              </View>
            </View>

            {/* 今月の使用 */}
            <View style={styles.card}>
              <Text style={styles.cardTitle}>{labels.monthlyUsage}</Text>
              
              <View style={styles.statRow}>
                <Text style={styles.statLabel}>{labels.usage}:</Text>
                <Text style={styles.statValueUsage}>
                  {formatTokens(historyStats.monthlyUsage)}
                </Text>
              </View>
            </View>

            {/* 購入履歴 */}
            {historyStats.transactions && historyStats.transactions.data.length > 0 && (
              <View style={styles.card}>
                <Text style={styles.cardTitle}>{labels.purchaseHistory}</Text>
                
                {historyStats.transactions.data
                  .filter(tx => tx.type === 'purchase')
                  .map((tx, index) => {
                    const price = extractPrice(tx.description);
                    return (
                      <View key={tx.id} style={[styles.historyItem, index > 0 && styles.historyItemBorder]}>
                        <View style={styles.historyHeader}>
                          <Text style={styles.historyDate}>{formatDate(tx.created_at)}</Text>
                        </View>
                        <View style={styles.historyBody}>
                          {price && (
                            <View style={styles.historyRow}>
                              <Text style={styles.historyLabel}>{labels.amount}:</Text>
                              <Text style={styles.historyAmountValue}>{price}</Text>
                            </View>
                          )}
                          <View style={styles.historyRow}>
                            <Text style={styles.historyLabel}>{labels.tokens}:</Text>
                            <Text style={styles.historyTokenValue}>
                              {formatTokens(tx.amount)}
                            </Text>
                          </View>
                        </View>
                      </View>
                    );
                  })
                }
                
                {historyStats.transactions.data.filter(tx => tx.type === 'purchase').length === 0 && (
                  <Text style={styles.noPurchasesText}>{labels.noPurchases}</Text>
                )}
              </View>
            )}
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  backButton: {
    paddingVertical: getSpacing(8, width),
    paddingRight: getSpacing(16, width),
  },
  backButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: '#3b82f6',
    fontWeight: '600',
  },
  headerTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
    color: '#1f2937',
    flex: 1,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: getSpacing(16, width),
  },
  errorContainer: {
    backgroundColor: '#fee2e2',
    padding: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(16, width),
  },
  errorText: {
    color: '#991b1b',
    fontSize: getFontSize(14, width, theme),
  },
  loadingContainer: {
    padding: getSpacing(32, width),
    alignItems: 'center',
  },
  loadingText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    color: '#6b7280',
  },
  emptyContainer: {
    padding: getSpacing(32, width),
    alignItems: 'center',
  },
  emptyText: {
    fontSize: getFontSize(16, width, theme),
    color: '#6b7280',
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(20, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(3),
  },
  cardTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '700',
    color: '#1f2937',
    marginBottom: getSpacing(16, width),
  },
  statRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  statLabel: {
    fontSize: getFontSize(16, width, theme),
    color: '#6b7280',
  },
  statValueAmount: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: '700',
    color: '#10b981',
  },
  statValueTokens: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: '700',
    color: '#3b82f6',
  },
  statValueUsage: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: '700',
    color: '#f59e0b',
  },
  historyItem: {
    paddingVertical: getSpacing(12, width),
  },
  historyItemBorder: {
    borderTopWidth: 1,
    borderTopColor: '#e5e7eb',
  },
  historyHeader: {
    marginBottom: getSpacing(8, width),
  },
  historyDate: {
    fontSize: getFontSize(14, width, theme),
    color: '#6b7280',
    fontWeight: '600',
  },
  historyBody: {
    gap: getSpacing(4, width),
  },
  historyRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  historyLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#6b7280',
  },
  historyAmountValue: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '700',
    color: '#10b981',
  },
  historyTokenValue: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '700',
    color: '#3b82f6',
  },
  noPurchasesText: {
    fontSize: getFontSize(14, width, theme),
    color: '#6b7280',
    textAlign: 'center',
    paddingVertical: getSpacing(16, width),
  },
});

export default TokenHistoryScreen;
