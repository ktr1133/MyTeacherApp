/**
 * サブスクリプション管理画面
 * 
 * プラン一覧、現在のサブスク情報、請求履歴を表示し、
 * プラン変更、キャンセル、Stripe連携を管理
 * 
 * @module screens/subscriptions/SubscriptionManageScreen
 */

import React, { useEffect, useState, useMemo } from 'react';
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

  // 追加メンバー数（エンタープライズプラン用）
  const [additionalMembers, setAdditionalMembers] = useState(0);

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
      const session = await createCheckout(planType, additionalMembers);
      
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
    
    return (
      <View
        key={plan.name}
        style={[
          styles.planCard,
          isCurrent && styles.currentPlanCard,
        ]}
      >
        <View style={styles.planHeader}>
          <Text style={styles.planName}>{plan.displayName}</Text>
          {isCurrent && (
            <View style={styles.currentBadge}>
              <Text style={styles.currentBadgeText}>契約中</Text>
            </View>
          )}
        </View>

        <Text style={styles.planPrice}>
          ¥{plan.price.toLocaleString('ja-JP')}/月
        </Text>

        <Text style={styles.planDescription}>{plan.description}</Text>

        <View style={styles.featuresContainer}>
          {plan.features.map((feature, index) => (
            <Text key={index} style={styles.featureText}>
              ✓ {feature}
            </Text>
          ))}
        </View>

        <TouchableOpacity
          style={[
            styles.selectButton,
            isCurrent && styles.selectButtonDisabled,
          ]}
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
              <TouchableOpacity
                style={[styles.cancelButton, styles.cancelButtonDisabled]}
                disabled={true}
              >
                <Text style={[styles.cancelButtonText, styles.cancelButtonTextDisabled]}>
                  {labels.cancelPlan}
                </Text>
              </TouchableOpacity>
            ) : (
              !currentSubscription.cancel_at_period_end && (
                <TouchableOpacity
                  style={styles.cancelButton}
                  onPress={handleCancelSubscription}
                >
                  <Text style={styles.cancelButtonText}>
                    {labels.cancelPlan}
                  </Text>
                </TouchableOpacity>
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
          <TouchableOpacity
            style={styles.invoicesButton}
            onPress={() => navigation.navigate('SubscriptionInvoices')}
          >
            <Text style={styles.invoicesButtonText}>
              {labels.viewInvoices}
            </Text>
          </TouchableOpacity>
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
  cancelButton: {
    backgroundColor: '#DC3545',
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(24, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
  },
  cancelButtonDisabled: {
    backgroundColor: '#CCCCCC',
    opacity: 0.6,
  },
  cancelButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
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
    padding: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    marginBottom: getSpacing(16, width),
    ...getShadow(4),
  },
  currentPlanCard: {
    borderWidth: 2,
    borderColor: '#4A90E2',
  },
  planHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: getSpacing(8, width),
  },
  planName: {
    fontSize: getFontSize(20, width, theme),
    fontWeight: 'bold',
    color: '#333333',
  },
  currentBadge: {
    backgroundColor: '#4A90E2',
    paddingHorizontal: getSpacing(8, width),
    paddingVertical: getSpacing(4, width),
    borderRadius: getBorderRadius(4, width),
  },
  currentBadgeText: {
    color: '#FFFFFF',
    fontSize: getFontSize(12, width, theme),
    fontWeight: 'bold',
  },
  planPrice: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: '#4A90E2',
    marginBottom: getSpacing(8, width),
  },
  planDescription: {
    fontSize: getFontSize(14, width, theme),
    color: '#666666',
    marginBottom: getSpacing(12, width),
  },
  featuresContainer: {
    marginBottom: getSpacing(16, width),
  },
  featureText: {
    fontSize: getFontSize(14, width, theme),
    color: '#333333',
    marginBottom: getSpacing(4, width),
  },
  selectButton: {
    backgroundColor: '#4A90E2',
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(24, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
  },
  selectButtonDisabled: {
    backgroundColor: '#CCCCCC',
  },
  selectButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
  },
  selectButtonTextDisabled: {
    color: '#999999',
  },
  invoicesButton: {
    backgroundColor: '#FFFFFF',
    margin: getSpacing(16, width),
    paddingVertical: getSpacing(16, width),
    paddingHorizontal: getSpacing(24, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
    ...getShadow(4),
  },
  invoicesButtonText: {
    color: '#4A90E2',
    fontSize: getFontSize(16, width, theme),
    fontWeight: 'bold',
  },
});

export default SubscriptionManageScreen;
