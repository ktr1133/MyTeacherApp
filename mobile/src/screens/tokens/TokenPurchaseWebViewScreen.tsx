/**
 * トークンパッケージ一覧画面
 * 
 * トークンパッケージをネイティブUIで表示し、購入ボタンでStripe Checkoutに遷移
 * 
 * @module screens/tokens/TokenPackageListScreen
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
  Alert,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useThemedColors } from '../../hooks/useThemedColors';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useTheme } from '../../contexts/ThemeContext';
import { useTokens } from '../../hooks/useTokens';
import { tokenService } from '../../services/token.service';

/**
 * トークンパッケージ一覧画面コンポーネント
 * 
 * 機能:
 * - トークンパッケージ一覧表示（カード形式）
 * - パッケージごとの詳細情報（トークン量、価格、割引率）
 * - 購入ボタン → Stripe Checkout（外部ブラウザ）
 * - Pull-to-Refresh機能
 * 
 * @returns {JSX.Element} トークンパッケージ一覧画面
 */
const TokenPackageListScreen: React.FC = () => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const { theme } = useTheme();
  const { packages, loadPackages, isLoading, error } = useTokens();
  const { width } = useResponsive();
  const { colors, accent } = useThemedColors();
  const styles = useMemo(() => createStyles(width, theme, colors, accent), [width, theme, colors, accent]);

  // 画面フォーカス時にパッケージを更新
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      loadPackages();
    });
    return unsubscribe;
  }, [navigation, loadPackages]);

  // 初回読み込み
  useEffect(() => {
    loadPackages();
  }, []);

  // テーマに応じたラベル
  const labels = theme === 'child' ? {
    title: 'トークンをかう',
    loading: 'よみこみちゅう...',
    noPackages: 'トークンパッケージがありません',
    tokens: 'トークン',
    price: 'おねだん',
    discount: 'おとく',
    purchase: 'かう',
    back: 'もどる',
    error: 'エラー',
    purchaseError: 'こうにゅうできませんでした',
  } : {
    title: 'トークン購入',
    loading: '読み込み中...',
    noPackages: 'トークンパッケージがありません',
    tokens: 'トークン',
    price: '価格',
    discount: '割引',
    purchase: '購入する',
    back: '戻る',
    error: 'エラー',
    purchaseError: '購入処理を開始できませんでした',
  };

  /**
   * トークン数をフォーマット（3桁カンマ区切り）
   */
  const formatTokens = (tokens: number | undefined): string => {
    if (tokens === undefined || tokens === null) {
      return '0';
    }
    return tokens.toLocaleString('ja-JP');
  };

  /**
   * 価格をフォーマット
   */
  const formatPrice = (price: number): string => {
    return `¥${price.toLocaleString('ja-JP')}`;
  };

  /**
   * 購入ボタンハンドラー
   * 
   * Stripe Checkout Session作成APIを呼び出し、WebView画面に遷移
   */
  const handlePurchase = async (packageId: number) => {
    try {
      // Stripe Checkout Session作成APIを呼び出す
      const { url } = await tokenService.createCheckoutSession(packageId);
      
      // WebView画面に遷移
      navigation.navigate('TokenCheckoutWebView', { url });
    } catch (err) {
      console.error('[TokenPackageList] Purchase error:', err);
      Alert.alert(labels.error, labels.purchaseError);
    }
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

      {/* パッケージ一覧 */}
      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl refreshing={isLoading} onRefresh={loadPackages} />
        }
      >
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>⚠️ {error}</Text>
          </View>
        )}

        {!isLoading && packages.length === 0 && (
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyText}>{labels.noPackages}</Text>
          </View>
        )}

        {packages.map((pkg) => (
          <View key={pkg.id} style={styles.packageCard}>
            {/* パッケージ名 */}
            <Text style={styles.packageName}>{pkg.name}</Text>

            {/* 説明 */}
            {pkg.description && (
              <Text style={styles.packageDescription}>{pkg.description}</Text>
            )}

            {/* トークン量 */}
            <View style={styles.packageInfoRow}>
              <Text style={styles.packageTokens}>
                {formatTokens(pkg.token_amount)}
              </Text>
              <Text style={styles.packageInfoLabel}>{labels.tokens}</Text>
            </View>

            {/* 価格 */}
            <View style={styles.packageInfoRow}>
              <Text style={styles.packagePrice}>{formatPrice(pkg.price)}</Text>
              <Text style={styles.priceLabel}>(税込)</Text>
            </View>

            {/* 割引率 */}
            {pkg.discount_rate && pkg.discount_rate > 0 && (
              <View style={styles.discountBadge}>
                <Text style={styles.discountText}>
                  {labels.discount} {pkg.discount_rate}%
                </Text>
              </View>
            )}

            {/* 購入ボタン */}
            <View style={styles.purchaseButtonWrapper}>
              <LinearGradient
                colors={[accent.primary, accent.primary] as const}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.purchaseButtonGradient}
              >
                <TouchableOpacity
                  style={styles.purchaseButton}
                  onPress={() => handlePurchase(pkg.id)}
                >
                  <Text style={styles.purchaseButtonText}>{labels.purchase}</Text>
                </TouchableOpacity>
              </LinearGradient>
            </View>
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
};

const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border.default,
  },
  backButton: {
    paddingVertical: getSpacing(8, width),
    paddingRight: getSpacing(16, width),
  },
  backButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: accent.primary,
    fontWeight: '600',
  },
  headerTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    flex: 1,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: getSpacing(16, width),
  },
  errorContainer: {
    backgroundColor: colors.status.error + '20',
    padding: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(16, width),
  },
  errorText: {
    color: colors.status.error,
    fontSize: getFontSize(14, width, theme),
  },
  emptyContainer: {
    padding: getSpacing(32, width),
    alignItems: 'center',
  },
  emptyText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.secondary,
  },
  packageCard: {
    backgroundColor: colors.card,
    borderWidth: 2,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(20, width),
    padding: getSpacing(24, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(4),
  },
  packageName: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: '700',
    color: colors.text.primary,
    marginBottom: getSpacing(16, width),
    textAlign: 'center',
  },
  packageDescription: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(24, width),
    textAlign: 'center',
  },
  packageInfoRow: {
    alignItems: 'center',
    marginBottom: getSpacing(16, width),
  },
  packageInfoLabel: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.secondary,
    textAlign: 'center',
  },
  packageTokens: {
    fontSize: getFontSize(40, width, theme),
    fontWeight: '900',
    color: accent.primary,
    textAlign: 'center',
  },
  packagePrice: {
    fontSize: getFontSize(32, width, theme),
    fontWeight: '800',
    color: colors.text.primary,
    textAlign: 'center',
  },
  priceLabel: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.tertiary,
    textAlign: 'center',
    marginTop: getSpacing(4, width),
  },
  discountBadge: {
    backgroundColor: accent.primary + '20',
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(12, width),
    alignSelf: 'flex-start',
    marginBottom: getSpacing(16, width),
  },
  discountText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: accent.primary,
  },
  purchaseButtonWrapper: {
    marginTop: getSpacing(16, width),
    borderRadius: getBorderRadius(12, width),
    overflow: 'hidden',
  },
  purchaseButtonGradient: {
    borderRadius: getBorderRadius(12, width),
  },
  purchaseButton: {
    paddingVertical: getSpacing(16, width),
    paddingHorizontal: getSpacing(24, width),
    alignItems: 'center',
  },
  purchaseButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: '#ffffff', // LinearGradient上のテキストは常に白
  },
});

export default TokenPackageListScreen;
