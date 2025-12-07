/**
 * トークン履歴画面
 * 
 * 月次のトークン購入・使用統計を表示
 * 
 * @module screens/tokens/TokenHistoryScreen
 */

import React, { useEffect } from 'react';
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

            {/* 使用率バー（オプション） */}
            {historyStats.monthlyPurchaseTokens > 0 && (
              <View style={styles.card}>
                <Text style={styles.cardTitle}>
                  {theme === 'child' ? 'つかったわりあい' : '使用率'}
                </Text>
                
                <View style={styles.usageBarContainer}>
                  <View
                    style={[
                      styles.usageBar,
                      {
                        width: `${Math.min(
                          (historyStats.monthlyUsage / historyStats.monthlyPurchaseTokens) * 100,
                          100
                        )}%`,
                      },
                    ]}
                  />
                </View>
                
                <Text style={styles.usagePercentage}>
                  {Math.round(
                    (historyStats.monthlyUsage / historyStats.monthlyPurchaseTokens) * 100
                  )}%
                </Text>
              </View>
            )}
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  backButton: {
    paddingVertical: 8,
    paddingRight: 16,
  },
  backButtonText: {
    fontSize: 16,
    color: '#3b82f6',
    fontWeight: '600',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#1f2937',
    flex: 1,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
  },
  errorContainer: {
    backgroundColor: '#fee2e2',
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
  },
  errorText: {
    color: '#991b1b',
    fontSize: 14,
  },
  loadingContainer: {
    padding: 32,
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: '#6b7280',
  },
  emptyContainer: {
    padding: 32,
    alignItems: 'center',
  },
  emptyText: {
    fontSize: 16,
    color: '#6b7280',
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#1f2937',
    marginBottom: 16,
  },
  statRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  statLabel: {
    fontSize: 16,
    color: '#6b7280',
  },
  statValueAmount: {
    fontSize: 24,
    fontWeight: '700',
    color: '#10b981',
  },
  statValueTokens: {
    fontSize: 24,
    fontWeight: '700',
    color: '#3b82f6',
  },
  statValueUsage: {
    fontSize: 24,
    fontWeight: '700',
    color: '#f59e0b',
  },
  usageBarContainer: {
    height: 24,
    backgroundColor: '#e5e7eb',
    borderRadius: 12,
    overflow: 'hidden',
    marginBottom: 8,
  },
  usageBar: {
    height: '100%',
    backgroundColor: '#3b82f6',
  },
  usagePercentage: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
    textAlign: 'center',
  },
});

export default TokenHistoryScreen;
