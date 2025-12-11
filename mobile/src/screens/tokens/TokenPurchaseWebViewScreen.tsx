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
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useTheme } from '../../contexts/ThemeContext';
import { useTokens } from '../../hooks/useTokens';

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
  const styles = useMemo(() => createStyles(width, theme), [width, theme]);

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
   * Stripe Checkout URLを開く（外部ブラウザ）
   */
  const handlePurchase = async (_packageId: number) => {
    try {
      // TODO: Stripe Checkout Session作成APIを呼び出す
      // 仮実装: 外部ブラウザでLaravelのCheckout画面を開く
      Alert.alert(
        labels.error,
        '購入機能は現在開発中です。Web版をご利用ください。',
        [{ text: 'OK' }]
      );
      
      // 実装例:
      // const checkoutUrl = await tokenService.createCheckoutSession(packageId);
      // await Linking.openURL(checkoutUrl);
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
                colors={['#f59e0b', '#d97706']}
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
  emptyContainer: {
    padding: getSpacing(32, width),
    alignItems: 'center',
  },
  emptyText: {
    fontSize: getFontSize(16, width, theme),
    color: '#6b7280',
  },
  packageCard: {
    backgroundColor: 'rgba(255, 255, 255, 0.9)',
    borderWidth: 2,
    borderColor: '#e5e7eb',
    borderRadius: getBorderRadius(20, width),
    padding: getSpacing(24, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(4),
  },
  packageName: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: '700',
    color: '#1f2937',
    marginBottom: getSpacing(16, width),
    textAlign: 'center',
  },
  packageDescription: {
    fontSize: getFontSize(14, width, theme),
    color: '#6b7280',
    marginBottom: getSpacing(24, width),
    textAlign: 'center',
  },
  packageInfoRow: {
    alignItems: 'center',
    marginBottom: getSpacing(16, width),
  },
  packageInfoLabel: {
    fontSize: getFontSize(16, width, theme),
    color: '#6b7280',
    textAlign: 'center',
  },
  packageTokens: {
    fontSize: getFontSize(40, width, theme),
    fontWeight: '900',
    color: '#f59e0b',
    textAlign: 'center',
  },
  packagePrice: {
    fontSize: getFontSize(32, width, theme),
    fontWeight: '800',
    color: '#1f2937',
    textAlign: 'center',
  },
  priceLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#9ca3af',
    textAlign: 'center',
    marginTop: getSpacing(4, width),
  },
  discountBadge: {
    backgroundColor: '#fef3c7',
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(12, width),
    alignSelf: 'flex-start',
    marginBottom: getSpacing(16, width),
  },
  discountText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: '#92400e',
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
    color: '#ffffff',
  },
});

export default TokenPackageListScreen;
