/**
 * トークンパッケージ一覧画面
 * 
 * トークンパッケージをネイティブUIで表示し、購入ボタンでStripe Checkoutに遷移
 * 
 * @module screens/tokens/TokenPackageListScreen
 */

import React, { useEffect } from 'react';
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
              <Text style={styles.packageInfoLabel}>{labels.tokens}:</Text>
              <Text style={styles.packageTokens}>
                {formatTokens(pkg.token_amount)}
              </Text>
            </View>

            {/* 価格 */}
            <View style={styles.packageInfoRow}>
              <Text style={styles.packageInfoLabel}>{labels.price}:</Text>
              <Text style={styles.packagePrice}>{formatPrice(pkg.price)}</Text>
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
            <TouchableOpacity
              style={styles.purchaseButton}
              onPress={() => handlePurchase(pkg.id)}
            >
              <Text style={styles.purchaseButtonText}>{labels.purchase}</Text>
            </TouchableOpacity>
          </View>
        ))}
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
  emptyContainer: {
    padding: 32,
    alignItems: 'center',
  },
  emptyText: {
    fontSize: 16,
    color: '#6b7280',
  },
  packageCard: {
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
  packageName: {
    fontSize: 20,
    fontWeight: '700',
    color: '#1f2937',
    marginBottom: 8,
  },
  packageDescription: {
    fontSize: 14,
    color: '#6b7280',
    marginBottom: 16,
  },
  packageInfoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  packageInfoLabel: {
    fontSize: 16,
    color: '#6b7280',
  },
  packageTokens: {
    fontSize: 24,
    fontWeight: '700',
    color: '#3b82f6',
  },
  packagePrice: {
    fontSize: 20,
    fontWeight: '600',
    color: '#1f2937',
  },
  discountBadge: {
    backgroundColor: '#fef3c7',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
    alignSelf: 'flex-start',
    marginBottom: 16,
  },
  discountText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#92400e',
  },
  purchaseButton: {
    backgroundColor: '#3b82f6',
    paddingVertical: 14,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 8,
  },
  purchaseButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#ffffff',
  },
});

export default TokenPackageListScreen;
