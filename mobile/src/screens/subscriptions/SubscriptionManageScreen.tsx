/**
 * サブスクリプション管理画面
 * 
 * プラン一覧、現在のサブスク情報、請求履歴を表示し、
 * プラン変更、キャンセル、Stripe連携を管理
 * 
 * @module screens/subscriptions/SubscriptionManageScreen
 */

import { useEffect, useMemo } from 'react';
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
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useSubscription } from '../../hooks/useSubscription';
import { useTheme } from '../../contexts/ThemeContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow, getHeaderTitleProps } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import type { SubscriptionPlan } from '../../types/subscription.types';

/**
 * サブスクリプション管理画面コンポーネント
 * 
 * 機能:
 * - プラン一覧表示（カード形式）
 * - 現在のサブスク情報表示
 * - プラン変更ボタン（WebView遷移）
 * - キャンセルボタン
 * - 請求履歴ボタン（別画面遷移）
 * - Pull-to-Refresh機能
 * 
 * @returns {JSX.Element} サブスクリプション管理画面
 */
const SubscriptionManageScreen: React.FC = () => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const { theme } = useTheme();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const {
    plans,
    currentSubscription,
    currentPlan,
    loadPlans,
    loadCurrentSubscription,
    createCheckout,
    cancel,
    isLoading,
  } = useSubscription();

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  // 画面フォーカス時にデータ更新
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', async () => {
      await loadPlans();
      await loadCurrentSubscription();
    });
    return unsubscribe;
  }, [navigation, loadPlans, loadCurrentSubscription]);

  // 初回ロード
  useEffect(() => {
    loadPlans();
    loadCurrentSubscription();
  }, []);

  // テーマに応じたラベル
  const labels = theme === 'child' ? {
    title: 'サブスク管理（こども用なし）',
    currentPlan: 'いまのプラン',
    noPlan: 'プランにはいってないよ',
    choosePlan: 'プランをえらぶ',
    cancelPlan: 'やめる',
    viewInvoices: 'りょうきんりれき',
  } : {
    title: 'サブスクリプション管理',
    currentPlan: '現在のプラン',
    noPlan: 'サブスクリプション未加入',
    choosePlan: 'プランを選択',
    cancelPlan: 'キャンセル',
    viewInvoices: '請求履歴を見る',
  };

  /**
   * プラン購入処理
   * Checkout Session作成 → WebView遷移
   */
  const handlePurchasePlan = async (planType: 'family' | 'enterprise') => {
    try {
      const session = await createCheckout(planType, 0);
      
      // WebView遷移（既存のTokenPurchaseWebViewScreenを参考）
      navigation.navigate('SubscriptionWebView', {
        url: session.url,
        title: 'サブスクリプション購入',
      });
    } catch (err) {
      console.error('[SubscriptionManageScreen] handlePurchasePlan error:', err);
    }
  };

  /**
   * サブスクキャンセル処理
   * 確認ダイアログ → API呼び出し
   */
  const handleCancelSubscription = () => {
    Alert.alert(
      'キャンセル確認',
      '本当にサブスクリプションをキャンセルしますか？期間終了時に解約されます。',
      [
        { text: 'キャンセル', style: 'cancel' },
        {
          text: 'OK',
          onPress: async () => {
            try {
              await cancel();
            } catch (err) {
              console.error('[SubscriptionManageScreen] handleCancelSubscription error:', err);
            }
          },
        },
      ]
    );
  };

  /**
   * プランカード描画
   */
  const renderPlanCard = (plan: SubscriptionPlan) => {
    const isCurrent = currentPlan === plan.name;
    const isFeatured = plan.name === 'family' && !isCurrent;
    
    return (
      <View
        key={plan.name}
        style={[
          styles.planCard,
          isCurrent && styles.currentPlanCard,
          isFeatured && styles.featuredPlanCard,
        ]}
      >
        {/* バッジ（右上絶対配置） */}
        {isCurrent ? (
          <View style={styles.currentBadge}>
            <Text style={styles.currentBadgeText}>契約中</Text>
          </View>
        ) : isFeatured && (
          <View style={styles.featuredBadge}>
            <Text style={styles.featuredBadgeText}>おすすめ</Text>
          </View>
        )}

        {/* ヘッダー */}
        <View style={styles.planHeader}>
          <Text style={styles.planName}>{plan.displayName}</Text>
          <View style={styles.planPriceContainer}>
            <Text style={styles.planPriceAmount}>
              ¥{plan.price.toLocaleString('ja-JP')}
            </Text>
            <Text style={styles.planPricePeriod}>/月</Text>
          </View>
        </View>

        {/* 機能リスト */}
        <View style={styles.featuresContainer}>
          {plan.features.map((feature, index) => (
            <View key={index} style={styles.featureItem}>
              <Text style={styles.featureIcon}>✓</Text>
              <Text style={styles.featureText}>{feature}</Text>
            </View>
          ))}
        </View>

        <View style={styles.selectButtonWrapper}>
          <LinearGradient
            colors={isCurrent ? ['#CCCCCC', '#CCCCCC'] : ['#6366F1', '#8B5CF6']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.selectButtonGradient}
          >
            <TouchableOpacity
              style={styles.selectButton}
              onPress={() => !isCurrent && handlePurchasePlan(plan.name as 'family' | 'enterprise')}
              disabled={isCurrent}
            >
              <Text style={[
                styles.selectButtonText,
                isCurrent && styles.selectButtonTextDisabled,
              ]}>
                {isCurrent ? '契約中のプラン' : 'このプランを選択'}
              </Text>
            </TouchableOpacity>
          </LinearGradient>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        style={styles.scrollView}
        refreshControl={
          <RefreshControl
            refreshing={isLoading}
            onRefresh={async () => {
              await loadPlans();
              await loadCurrentSubscription();
            }}
          />
        }
      >
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={styles.headerTitle} {...getHeaderTitleProps()}>
            {labels.title}
          </Text>
        </View>

        {/* 現在のサブスク情報（加入中のみ表示） */}
        {currentSubscription && (
          <View style={styles.currentSubscriptionCard}>
            <Text style={styles.currentSubscriptionLabel}>
              {labels.currentPlan}
            </Text>
            <Text style={styles.currentSubscriptionPlan}>
              {currentSubscription.plan === 'family' ? 'ファミリープラン' : 'エンタープライズプラン'}
            </Text>
            
            {currentSubscription.trial_ends_at && (
              <Text style={styles.currentSubscriptionEnd}>
                トライアル終了日: {new Date(currentSubscription.trial_ends_at).toLocaleDateString('ja-JP')}
              </Text>
            )}

            {currentSubscription.ends_at ? (
              <View style={styles.endsAtWarning}>
                <Text style={styles.endsAtWarningTitle}>
                  ⚠️ サブスクリプション終了予定日
                </Text>
                <Text style={styles.endsAtWarningDate}>
                  {new Date(currentSubscription.ends_at).toLocaleDateString('ja-JP')}
                </Text>
                <Text style={styles.endsAtWarningNote}>
                  この日まで引き続きご利用いただけます
                </Text>
              </View>
            ) : (
              currentSubscription.current_period_end && !currentSubscription.cancel_at_period_end && (
                <Text style={styles.currentSubscriptionEnd}>
                  次回更新: {new Date(currentSubscription.current_period_end).toLocaleDateString('ja-JP')}
                </Text>
              )
            )}

            {currentSubscription.cancel_at_period_end && !currentSubscription.ends_at && (
              <View style={styles.cancelWarning}>
                <Text style={styles.cancelWarningText}>
                  ⚠️ 期間終了時に解約されます
                </Text>
              </View>
            )}

            {/* キャンセルボタン */}
            {currentSubscription.ends_at ? (
              <View style={styles.cancelButtonWrapper}>
                <LinearGradient
                  colors={['#CCCCCC', '#CCCCCC']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.cancelButtonGradient}
                >
                  <TouchableOpacity
                    style={styles.cancelButton}
                    disabled={true}
                  >
                    <Text style={[styles.cancelButtonText, styles.cancelButtonTextDisabled]}>
                      {labels.cancelPlan}
                    </Text>
                  </TouchableOpacity>
                </LinearGradient>
              </View>
            ) : (
              !currentSubscription.cancel_at_period_end && (
                <View style={styles.cancelButtonWrapper}>
                  <LinearGradient
                    colors={['#EF4444', '#DC2626']}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 0 }}
                    style={styles.cancelButtonGradient}
                  >
                    <TouchableOpacity
                      style={styles.cancelButton}
                      onPress={handleCancelSubscription}
                    >
                      <Text style={styles.cancelButtonText}>
                        {labels.cancelPlan}
                      </Text>
                    </TouchableOpacity>
                  </LinearGradient>
                </View>
              )
            )}
          </View>
        )}

        {/* プラン一覧 */}
        <View style={styles.plansContainer}>
          <Text style={styles.sectionTitle}>{labels.choosePlan}</Text>
          {plans.map(renderPlanCard)}
        </View>

        {/* 請求履歴ボタン */}
        {currentSubscription && (
          <View style={styles.invoicesButtonWrapper}>
            <LinearGradient
              colors={['#59B9C6', '#9333EA']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.invoicesButtonGradient}
            >
              <TouchableOpacity
                style={styles.invoicesButton}
                onPress={() => navigation.navigate('SubscriptionInvoices')}
              >
                <Text style={styles.invoicesButtonText}>
                  {labels.viewInvoices}
                </Text>
              </TouchableOpacity>
            </LinearGradient>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

/**
 * レスポンシブスタイル生成関数
 * 
 * @param width - 画面幅
 * @param theme - テーマ (adult | child)
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F5F5',
  },
  scrollView: {
    flex: 1,
  },
  header: {
    backgroundColor: '#4A90E2',
    paddingVertical: getSpacing(20, width),
    paddingHorizontal: getSpacing(16, width),
  },
  headerTitle: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  currentSubscriptionCard: {
    backgroundColor: '#FFFFFF',
    margin: getSpacing(16, width),
    padding: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    ...getShadow(4),
  },
  currentSubscriptionLabel: {
    fontSize: getFontSize(14, width, theme),
    color: '#666666',
    marginBottom: getSpacing(8, width),
  },
  currentSubscriptionPlan: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: 'bold',
    color: '#333333',
    marginBottom: getSpacing(8, width),
  },
  currentSubscriptionStatus: {
    fontSize: getFontSize(14, width, theme),
    color: '#666666',
    marginBottom: getSpacing(4, width),
  },
  currentSubscriptionEnd: {
    fontSize: getFontSize(14, width, theme),
    color: '#666666',
    marginBottom: getSpacing(12, width),
  },
  cancelWarning: {
    backgroundColor: '#FFF3CD',
    padding: getSpacing(12, width),
    borderRadius: getBorderRadius(4, width),
    marginBottom: getSpacing(12, width),
  },
  cancelWarningText: {
    fontSize: getFontSize(14, width, theme),
    color: '#856404',
  },
  endsAtWarning: {
    marginTop: getSpacing(12, width),
    marginBottom: getSpacing(16, width),
    padding: getSpacing(16, width),
    backgroundColor: '#FEE2E2',
    borderRadius: getBorderRadius(8, width),
    borderWidth: 1,
    borderColor: '#EF4444',
  },
  endsAtWarningTitle: {
    fontSize: getFontSize(14, width, theme),
    color: '#991B1B',
    fontWeight: '600',
    marginBottom: getSpacing(8, width),
  },
  endsAtWarningDate: {
    fontSize: getFontSize(18, width, theme),
    color: '#DC2626',
    fontWeight: 'bold',
    marginBottom: getSpacing(8, width),
  },
  endsAtWarningNote: {
    fontSize: getFontSize(12, width, theme),
    color: '#991B1B',
  },
  cancelButtonWrapper: {
    marginTop: getSpacing(12, width),
  },
  cancelButtonGradient: {
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  cancelButton: {
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(24, width),
    alignItems: 'center',
  },
  cancelButtonDisabled: {
    // 使用しない（LinearGradientで制御）
  },
  cancelButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
  },
  cancelButtonTextDisabled: {
    color: '#888888',
  },
  noSubscriptionCard: {
    backgroundColor: '#FFFFFF',
    margin: getSpacing(16, width),
    padding: getSpacing(24, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    ...getShadow(4),
  },
  noSubscriptionText: {
    fontSize: getFontSize(16, width, theme),
    color: '#666666',
  },
  plansContainer: {
    paddingHorizontal: getSpacing(16, width),
  },
  sectionTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: '#333333',
    marginBottom: getSpacing(16, width),
  },
  planCard: {
    backgroundColor: '#FFFFFF',
    padding: getSpacing(28, width),
    borderRadius: getBorderRadius(16, width),
    marginBottom: getSpacing(16, width),
    borderWidth: 2,
    borderColor: '#e5e7eb',
    ...getShadow(2),
    position: 'relative',
  },
  currentPlanCard: {
    borderColor: '#10b981',
    ...getShadow(6),
  },
  featuredPlanCard: {
    borderColor: '#4f46e5',
    ...getShadow(6),
  },
  planHeader: {
    marginBottom: getSpacing(24, width),
    paddingBottom: getSpacing(20, width),
    borderBottomWidth: 2,
    borderBottomColor: '#f3f4f6',
  },
  planName: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: '700',
    color: '#111827',
    marginBottom: getSpacing(12, width),
  },
  planPriceContainer: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: 4,
  },
  planPriceAmount: {
    fontSize: getFontSize(40, width, theme),
    fontWeight: '800',
    color: '#4f46e5',
    lineHeight: getFontSize(40, width, theme),
  },
  planPricePeriod: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '500',
    color: '#6b7280',
  },
  currentBadge: {
    position: 'absolute',
    top: 12,
    right: 12,
    backgroundColor: '#10b981',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(16, width),
    zIndex: 10,
    ...getShadow(4),
  },
  currentBadgeText: {
    color: '#FFFFFF',
    fontSize: getFontSize(12, width, theme),
    fontWeight: '700',
  },
  featuredBadge: {
    position: 'absolute',
    top: 12,
    right: 12,
    backgroundColor: '#4f46e5',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(6, width),
    borderRadius: getBorderRadius(16, width),
    zIndex: 10,
    ...getShadow(4),
  },
  featuredBadgeText: {
    color: '#FFFFFF',
    fontSize: getFontSize(12, width, theme),
    fontWeight: '700',
  },
  featuresContainer: {
    marginTop: getSpacing(24, width),
    marginBottom: getSpacing(24, width),
    gap: getSpacing(12, width),
  },
  featureItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: getSpacing(12, width),
  },
  featureIcon: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: '#10b981',
    width: 20,
  },
  featureText: {
    fontSize: getFontSize(15, width, theme),
    color: '#374151',
    flex: 1,
  },
  selectButtonWrapper: {
    marginTop: getSpacing(12, width),
  },
  selectButtonGradient: {
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  selectButton: {
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(24, width),
    alignItems: 'center',
  },
  selectButtonDisabled: {
    // 使用しない（LinearGradientで制御）
  },
  selectButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
  },
  selectButtonTextDisabled: {
    color: '#999999',
  },
  invoicesButtonWrapper: {
    margin: getSpacing(16, width),
  },
  invoicesButtonGradient: {
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
    ...getShadow(4),
  },
  invoicesButton: {
    paddingVertical: getSpacing(16, width),
    paddingHorizontal: getSpacing(24, width),
    alignItems: 'center',
  },
  invoicesButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
  },
});

export default SubscriptionManageScreen;
