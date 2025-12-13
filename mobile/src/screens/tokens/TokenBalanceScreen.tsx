/**
 * トークン残高表示画面
 * 
 * ユーザーのトークン残高、月次無料枠、残高低下警告を表示
 * 
 * @module screens/tokens/TokenBalanceScreen
 */

import React, { useEffect, useMemo } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  SafeAreaView,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useTokens } from '../../hooks/useTokens';
import { useTheme } from '../../contexts/ThemeContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { useThemedColors } from '../../hooks/useThemedColors';

/**
 * トークン残高画面コンポーネント
 * 
 * 機能:
 * - トークン残高表示（大きく強調）
 * - 月次無料枠プログレスバー
 * - 残高低下警告バナー（is_low = true時）
 * - 購入ボタン（WebView画面遷移）
 * - Pull-to-Refresh機能
 * 
 * @returns {JSX.Element} トークン残高画面
 */
const TokenBalanceScreen: React.FC = () => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const { theme } = useTheme();
  const { balance, refreshBalance, isLoading } = useTokens();

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  // 画面フォーカス時に残高を更新
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      refreshBalance();
    });
    return unsubscribe;
  }, [navigation, refreshBalance]);

  // テーマに応じたラベル
  const labels = theme === 'child' ? {
    title: 'トークンのこり',
    balance: 'いまもっているトークン',
    freeMonthly: 'まいつきもらえるトークン',
    usedThisMonth: 'こんげつつかったトークン',
    lowWarning: 'トークンがすくないよ！トークンをかってね',
    purchase: 'トークンをかう',
    history: 'つかったりれき',
  } : {
    title: 'トークン残高',
    balance: '現在のトークン残高',
    freeMonthly: '月次無料枠',
    usedThisMonth: '今月の使用量',
    lowWarning: 'トークン残高が不足しています。購入してください',
    purchase: 'トークンを購入',
    history: '履歴を見る',
  };

  // トークン残高をフォーマット（3桁カンマ区切り）
  const formatTokens = (tokens: number | undefined): string => {
    if (tokens === undefined || tokens === null) return '---';
    return tokens.toLocaleString('ja-JP');
  };

  // 月次無料枠の使用率を計算
  const calculateUsageRate = (): number => {
    if (!balance || !balance.free_balance || balance.free_balance === 0) return 0;
    const usedTokens = balance.monthly_consumed || 0;
    return Math.min((usedTokens / balance.free_balance) * 100, 100);
  };

  // 残高が低いかどうかを判定（total_consumedが大きい、またはfree_balanceが少ない）
  const isLowBalance = (): boolean => {
    if (!balance || balance.balance === undefined) return false;
    return balance.balance < 100000; // 10万トークン以下で警告
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        style={styles.scrollView}
        refreshControl={
          <RefreshControl refreshing={isLoading} onRefresh={refreshBalance} />
        }
      >
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>{labels.title}</Text>
        </View>

        {/* 残高低下警告バナー */}
        {isLowBalance() && (
          <View style={styles.warningBanner}>
            <Text style={styles.warningText}>⚠️ {labels.lowWarning}</Text>
          </View>
        )}

        {/* トークン残高カード */}
        <View style={styles.balanceCard}>
          <Text style={styles.balanceLabel}>{labels.balance}</Text>
          <Text style={styles.balanceAmount}>
            {formatTokens(balance?.balance)}
          </Text>
          <Text style={styles.balanceUnit}>tokens</Text>
        </View>

        {/* 月次無料枠カード */}
        <View style={styles.freeMonthlyCard}>
          <View style={styles.freeMonthlyHeader}>
            <Text style={styles.freeMonthlyLabel}>{labels.freeMonthly}</Text>
            <Text style={styles.freeMonthlyAmount}>
              {formatTokens(balance?.free_balance)} tokens
            </Text>
          </View>

          {/* プログレスバー */}
          <View style={styles.progressBarContainer}>
            <View
              style={[
                styles.progressBar,
                { width: `${calculateUsageRate()}%` },
              ]}
            />
          </View>

          <View style={styles.usageInfoRow}>
            <Text style={styles.usageLabel}>{labels.usedThisMonth}</Text>
            <Text style={styles.usageAmount}>
              {formatTokens(balance?.monthly_consumed)} tokens
            </Text>
          </View>

          {balance?.free_balance_reset_at && (
            <Text style={styles.resetDate}>
              次回リセット: {new Date(balance.free_balance_reset_at).toLocaleDateString('ja-JP')}
            </Text>
          )}
        </View>

        {/* 購入ボタン */}
        <TouchableOpacity
          style={styles.purchaseButton}
          onPress={() => navigation.navigate('TokenPackageList')}
        >
          <Text style={styles.purchaseButtonText}>{labels.purchase}</Text>
        </TouchableOpacity>

        {/* 履歴ボタン */}
        <TouchableOpacity
          style={styles.historyButton}
          onPress={() => navigation.navigate('TokenHistory')}
        >
          <Text style={styles.historyButtonText}>{labels.history}</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
};

const createStyles = (
  width: number,
  theme: 'adult' | 'child',
  colors: ReturnType<typeof useThemedColors>['colors'],
  accent: ReturnType<typeof useThemedColors>['accent']
) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  scrollView: {
    flex: 1,
  },
  header: {
    paddingTop: getSpacing(12, width),
    paddingBottom: getSpacing(16, width),
    paddingHorizontal: getSpacing(16, width),
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.default,
  },
  headerTitle: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
  },
  warningBanner: {
    backgroundColor: '#fff3cd',
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    marginHorizontal: getSpacing(16, width),
    marginTop: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    borderWidth: 1,
    borderColor: '#ffc107',
  },
  warningText: {
    fontSize: getFontSize(14, width, theme),
    color: '#856404',
    fontWeight: '600',
  },
  balanceCard: {
    backgroundColor: colors.card,
    marginHorizontal: getSpacing(16, width),
    marginTop: getSpacing(16, width),
    padding: getSpacing(24, width),
    borderRadius: getBorderRadius(12, width),
    alignItems: 'center',
    ...getShadow(3),
  },
  balanceLabel: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(8, width),
  },
  balanceAmount: {
    fontSize: getFontSize(48, width, theme),
    fontWeight: 'bold',
    color: accent.primary,
    marginBottom: getSpacing(4, width),
  },
  balanceUnit: {
    fontSize: getFontSize(18, width, theme),
    color: colors.text.disabled,
  },
  freeMonthlyCard: {
    backgroundColor: colors.card,
    marginHorizontal: getSpacing(16, width),
    marginTop: getSpacing(16, width),
    padding: getSpacing(20, width),
    borderRadius: getBorderRadius(12, width),
    ...getShadow(3),
  },
  freeMonthlyHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  freeMonthlyLabel: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
  },
  freeMonthlyAmount: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
    color: '#10b981',
  },
  progressBarContainer: {
    height: getSpacing(8, width),
    backgroundColor: colors.border.default,
    borderRadius: getBorderRadius(4, width),
    overflow: 'hidden',
    marginBottom: getSpacing(12, width),
  },
  progressBar: {
    height: '100%',
    backgroundColor: '#10b981',
  },
  usageInfoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: getSpacing(8, width),
  },
  usageLabel: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
  },
  usageAmount: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
  },
  resetDate: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.disabled,
    marginTop: getSpacing(4, width),
  },
  purchaseButton: {
    backgroundColor: accent.primary,
    marginHorizontal: getSpacing(16, width),
    marginTop: getSpacing(24, width),
    paddingVertical: getSpacing(16, width),
    borderRadius: getBorderRadius(12, width),
    alignItems: 'center',
    ...getShadow(5),
  },
  purchaseButtonText: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: '#ffffff',
  },
  historyButton: {
    backgroundColor: colors.card,
    marginHorizontal: getSpacing(16, width),
    marginTop: getSpacing(12, width),
    marginBottom: getSpacing(24, width),
    paddingVertical: getSpacing(14, width),
    borderRadius: getBorderRadius(12, width),
    alignItems: 'center',
    borderWidth: 1,
    borderColor: accent.primary,
  },
  historyButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: accent.primary,
  },
});

export default TokenBalanceScreen;
