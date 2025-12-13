/**
 * 実績画面
 * 
 * ユーザーのタスク実績をグラフ表示
 * Web版Performance.mdの要件定義に基づく
 */

import React, { useState, useEffect, useRef, useMemo } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  Alert,
  ActivityIndicator,
  Modal,
} from 'react-native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { MaterialIcons } from '@expo/vector-icons';
import { usePerformance } from '../../hooks/usePerformance';
import { PerformanceChart } from '../../components/charts/PerformanceChart';
import { PeriodType, TaskType } from '../../types/performance.types';
import { useAvatarContext } from '../../contexts/AvatarContext';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { LinearGradient } from 'expo-linear-gradient';

export default function PerformanceScreen() {
  const navigation = useNavigation();
  const { theme, themeType } = useTheme();
  const { width } = useResponsive();
  const { colors, accent } = useThemedColors();
  const { dispatchAvatarEvent } = useAvatarContext();
  const {
    data,
    isLoading,
    error,
    period,
    taskType,
    offset,
    selectedUserId,
    changePeriod,
    changeTaskType,
    changeSelectedUser,
    navigatePrev,
    navigateNext,
    refresh,
  } = usePerformance();

  const [refreshing, setRefreshing] = useState(false);
  const [showMemberModal, setShowMemberModal] = useState(false);
  const hasShownAvatar = useRef(false);

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, theme, colors, accent), [width, theme, colors, accent]);

  /**
   * 初回マウント時のアバター表示
   * Web版と同じく、テーマに応じたアバターイベントを表示
   * - 子ども向けテーマ: performance_group_viewed（今月の報酬累計）
   * - 大人向けテーマ: performance_personal_viewed（今週の完了件数）
   */
  useEffect(() => {
    // 初回マウント時のみ実行（2回目以降は実行しない）
    if (hasShownAvatar.current) return;
    
    // データが読み込まれ、サブスクリプション有効な場合のみ表示
    if (data && data.has_subscription) {
      hasShownAvatar.current = true;
      
      // Web版と同じくテーマに応じてイベントを選択
      const eventType = theme === 'child' 
        ? 'performance_group_viewed' 
        : 'performance_personal_viewed';
      
      console.log('[PerformanceScreen] Dispatching avatar event:', { 
        eventType, 
        theme,
        hasSubscription: data.has_subscription 
      });
      
      dispatchAvatarEvent(eventType);
    }
  }, [data, theme, dispatchAvatarEvent]);

  /**
   * Pull to Refresh
   */
  const onRefresh = async () => {
    setRefreshing(true);
    await refresh();
    setRefreshing(false);
  };

  /**
   * 期間選択（サブスク制限チェック）
   */
  const handlePeriodChange = (newPeriod: PeriodType) => {
    if (data?.restrictions?.period_restricted && newPeriod !== 'week') {
      Alert.alert(
        'プレミアム機能',
        '月間・年間の実績表示はサブスクリプションプランでご利用いただけます',
        [
          { text: 'キャンセル', style: 'cancel' },
          {
            text: 'プランを見る',
            onPress: () => navigation.navigate('SubscriptionManage' as never),
          },
        ]
      );
      return;
    }
    changePeriod(newPeriod);
  };

  /**
   * 期間ナビゲーション（サブスク制限チェック）
   */
  const handleNavigatePrev = () => {
    if (data?.restrictions?.navigation_restricted) {
      Alert.alert(
        'プレミアム機能',
        '過去期間の実績閲覧はサブスクリプションプランでご利用いただけます',
        [
          { text: 'キャンセル', style: 'cancel' },
          {
            text: 'プランを見る',
            onPress: () => navigation.navigate('SubscriptionManage' as never),
          },
        ]
      );
      return;
    }
    navigatePrev();
  };

  const handleNavigateNext = () => {
    if (data?.restrictions?.navigation_restricted) {
      return; // 次へは制限表示のみ
    }
    navigateNext();
  };

  /**
   * メンバー選択（サブスク制限チェック）
   */
  const handleMemberChange = (userId: number) => {
    if (data?.restrictions?.member_restricted && userId !== 0) {
      Alert.alert(
        'プレミアム機能',
        'メンバー個別選択はサブスクリプションプランでご利用いただけます',
        [
          { text: 'キャンセル', style: 'cancel' },
          {
            text: 'プランを見る',
            onPress: () => navigation.navigate('SubscriptionManage' as never),
          },
        ]
      );
      return;
    }
    changeSelectedUser(userId);
    setShowMemberModal(false);
  };

  /**
   * メンバー選択モーダルを開く
   */
  const openMemberModal = () => {
    if (data?.restrictions?.member_restricted) {
      Alert.alert(
        'プレミアム機能',
        'メンバー個別選択はサブスクリプションプランでご利用いただけます',
        [
          { text: 'キャンセル', style: 'cancel' },
          {
            text: 'プランを見る',
            onPress: () => navigation.navigate('SubscriptionManage' as never),
          },
        ]
      );
      return;
    }
    setShowMemberModal(true);
  };

  /**
   * 選択中のメンバー名を取得
   */
  const getSelectedMemberName = () => {
    if (selectedUserId === 0 || !data?.members) {
      return 'グループ全体';
    }
    const member = data.members.find((m) => m.id === selectedUserId);
    return member ? member.username : 'グループ全体';
  };

  /**
   * 月次レポート画面へ遷移
   */
  const goToMonthlyReport = () => {
    navigation.navigate('MonthlyReport' as never);
  };

  if (isLoading && !data) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={accent.primary} testID="loading-indicator" />
          <Text style={styles.loadingText}>読み込み中...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.errorContainer}>
          <MaterialIcons name="error-outline" size={48} color={colors.status.error} />
          <Text style={styles.errorText}>{error}</Text>
          <View style={styles.retryButtonWrapper}>
            <LinearGradient
              colors={[accent.primary, accent.primary] as const}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.retryButtonGradient}
            >
              <TouchableOpacity style={styles.retryButton} onPress={refresh}>
                <Text style={styles.retryButtonText}>再試行</Text>
              </TouchableOpacity>
            </LinearGradient>
          </View>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container} edges={['bottom']}>
      <ScrollView
        testID="performance-scroll-view"
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>実績</Text>
          <View style={styles.monthlyReportButtonWrapper}>
            <LinearGradient
              colors={[accent.primary, accent.primary] as const}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.monthlyReportButtonGradient}
            >
              <TouchableOpacity
                style={styles.monthlyReportButton}
                onPress={goToMonthlyReport}
              >
                <MaterialIcons name="description" size={20} color={colors.background} />
                <Text style={styles.monthlyReportButtonText}>月次レポート</Text>
              </TouchableOpacity>
            </LinearGradient>
          </View>
        </View>

        {/* 期間選択タブ */}
        <View style={styles.tabContainer}>
          <TouchableOpacity
            style={[styles.tab, period === 'week' && styles.tabActive]}
            onPress={() => handlePeriodChange('week')}
          >
            <Text
              style={[
                styles.tabText,
                period === 'week' && styles.tabTextActive,
              ]}
            >
              週間
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[
              styles.tab,
              period === 'month' && styles.tabActive,
              data?.restrictions?.period_restricted && styles.tabLocked,
            ]}
            onPress={() => handlePeriodChange('month')}
          >
            <Text
              style={[
                styles.tabText,
                period === 'month' && styles.tabTextActive,
              ]}
            >
              月間
            </Text>
            {data?.restrictions?.period_restricted && (
              <MaterialIcons name="lock" size={12} color={accent.primary} />
            )}
          </TouchableOpacity>
          <TouchableOpacity
            style={[
              styles.tab,
              period === 'year' && styles.tabActive,
              data?.restrictions?.period_restricted && styles.tabLocked,
            ]}
            onPress={() => handlePeriodChange('year')}
          >
            <Text
              style={[
                styles.tabText,
                period === 'year' && styles.tabTextActive,
              ]}
            >
              年間
            </Text>
            {data?.restrictions?.period_restricted && (
              <MaterialIcons name="lock" size={12} color={accent.primary} />
            )}
          </TouchableOpacity>
        </View>

        {/* タスク種別タブ */}
        <View style={styles.taskTypeContainer}>
          <TouchableOpacity
            style={[
              styles.taskTypeTab,
              taskType === 'normal' && styles.taskTypeTabActive,
            ]}
            onPress={() => changeTaskType('normal')}
          >
            <Text
              style={[
                styles.taskTypeTabText,
                taskType === 'normal' && styles.taskTypeTabTextActive,
              ]}
            >
              通常タスク
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[
              styles.taskTypeTab,
              taskType === 'group' && styles.taskTypeTabActive,
            ]}
            onPress={() => changeTaskType('group')}
          >
            <Text
              style={[
                styles.taskTypeTabText,
                taskType === 'group' && styles.taskTypeTabTextActive,
              ]}
            >
              グループタスク
            </Text>
          </TouchableOpacity>
        </View>

        {/* グループタスク時のメンバー選択 */}
        {taskType === 'group' && data?.members && data.members.length > 0 && (
          <View style={styles.memberSelectContainer}>
            <View style={styles.memberSelectHeader}>
              <MaterialIcons name="people" size={18} color={accent.primary} />
              <Text style={styles.memberSelectLabel}>メンバー選択</Text>
              {data?.restrictions?.member_restricted && (
                <View style={styles.lockBadge}>
                  <MaterialIcons name="lock" size={12} color={accent.primary} />
                  <Text style={styles.lockBadgeText}>サブスク限定</Text>
                </View>
              )}
            </View>
            
            <TouchableOpacity
              style={styles.memberSelectButton}
              onPress={openMemberModal}
            >
              <Text style={styles.memberSelectButtonText}>
                {getSelectedMemberName()}
              </Text>
              <MaterialIcons
                name={data?.restrictions?.member_restricted ? 'lock' : 'arrow-drop-down'}
                size={20}
                color={data?.restrictions?.member_restricted ? '#8B5CF6' : '#6b7280'}
              />
            </TouchableOpacity>
          </View>
        )}

        {/* メンバー選択モーダル */}
        <Modal
          visible={showMemberModal}
          transparent
          animationType="slide"
          onRequestClose={() => setShowMemberModal(false)}
        >
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>メンバーを選択</Text>
                <TouchableOpacity onPress={() => setShowMemberModal(false)}>
                  <MaterialIcons name="close" size={24} color={colors.text.secondary} />
                </TouchableOpacity>
              </View>
              
              <ScrollView style={styles.modalList}>
                {/* グループ全体 */}
                <TouchableOpacity
                  style={[
                    styles.memberItem,
                    selectedUserId === 0 && styles.memberItemSelected,
                  ]}
                  onPress={() => handleMemberChange(0)}
                >
                  <MaterialIcons name="people" size={20} color={accent.primary} />
                  <Text
                    style={[
                      styles.memberItemText,
                      selectedUserId === 0 && styles.memberItemTextSelected,
                    ]}
                  >
                    グループ全体
                  </Text>
                  {selectedUserId === 0 && (
                    <MaterialIcons name="check" size={20} color={accent.primary} />
                  )}
                </TouchableOpacity>

                {/* 各メンバー */}
                {data?.members?.map((member) => (
                  <TouchableOpacity
                    key={member.id}
                    style={[
                      styles.memberItem,
                      selectedUserId === member.id && styles.memberItemSelected,
                    ]}
                    onPress={() => handleMemberChange(member.id)}
                  >
                    <MaterialIcons name="person" size={20} color={accent.primary} />
                    <Text
                      style={[
                        styles.memberItemText,
                        selectedUserId === member.id && styles.memberItemTextSelected,
                      ]}
                    >
                      {member.name}
                    </Text>
                    {selectedUserId === member.id && (
                      <MaterialIcons name="check" size={20} color={accent.primary} />
                    )}
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </View>
          </View>
        </Modal>

        {/* 期間ナビゲーション */}
        <View style={styles.navigationContainer}>
          <TouchableOpacity
            testID="navigate-prev-button"
            style={[
              styles.navButton,
              (!data?.can_navigate_prev || data?.restrictions?.navigation_restricted) &&
                styles.navButtonDisabled,
            ]}
            onPress={handleNavigatePrev}
            disabled={!data?.can_navigate_prev || data?.restrictions?.navigation_restricted}
          >
            <MaterialIcons
              name="chevron-left"
              size={24}
              color={
                !data?.can_navigate_prev || data?.restrictions?.navigation_restricted
                  ? colors.border
                  : accent.primary
              }
            />
            {data?.restrictions?.navigation_restricted && (
              <MaterialIcons
                name="lock"
                size={12}
                color={accent.primary}
                style={styles.navLockIcon}
              />
            )}
          </TouchableOpacity>
          <Text style={styles.periodLabel}>{data?.period_label}</Text>
          <TouchableOpacity
            testID="navigate-next-button"
            style={[
              styles.navButton,
              !data?.can_navigate_next && styles.navButtonDisabled,
            ]}
            onPress={handleNavigateNext}
            disabled={!data?.can_navigate_next}
          >
            <MaterialIcons
              name="chevron-right"
              size={24}
              color={!data?.can_navigate_next ? colors.border : accent.primary}
            />
          </TouchableOpacity>
        </View>

        {/* グラフ表示 */}
        {data && data.chart_data && (
          <PerformanceChart
            data={data.chart_data}
            taskType={taskType}
            period={period}
          />
        )}

        {/* 集計データ */}
        {data && data.summary && (
          <View style={styles.summaryContainer}>
            <View style={styles.summaryCard}>
              <MaterialIcons name="check-circle" size={24} color={colors.status.success} />
              <Text style={styles.summaryLabel}>完了</Text>
              <Text style={styles.summaryValue}>
                {data.summary.total_completed}件
              </Text>
            </View>
            <View style={styles.summaryCard}>
              <MaterialIcons name="pending" size={24} color={colors.status.warning} />
              <Text style={styles.summaryLabel}>未完了</Text>
              <Text style={styles.summaryValue}>
                {data.summary.total_incomplete}件
              </Text>
            </View>
            <View style={styles.summaryCard}>
              <MaterialIcons name="stars" size={24} color={accent.primary} />
              <Text style={styles.summaryLabel}>報酬合計</Text>
              <Text style={styles.summaryValue}>
                {data.summary.total_reward.toLocaleString()}
              </Text>
            </View>
          </View>
        )}

        {/* サブスク促進バナー */}
        {!data?.has_subscription && (
          <View style={styles.subscriptionBanner}>
            <MaterialIcons name="star" size={24} color={accent.primary} />
            <View style={styles.subscriptionBannerContent}>
              <Text style={styles.subscriptionBannerTitle}>
                プレミアムプランで全機能を利用
              </Text>
              <Text style={styles.subscriptionBannerText}>
                月間・年間実績、過去データ閲覧、メンバー別表示など
              </Text>
            </View>
            <TouchableOpacity
              style={styles.subscriptionBannerButton}
              onPress={() => navigation.navigate('SubscriptionManage' as never)}
            >
              <Text style={styles.subscriptionBannerButtonText}>詳細</Text>
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const createStyles = (width: number, theme: any, colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  scrollContent: {
    paddingBottom: getSpacing(24, width),
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    color: colors.text.secondary,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: getSpacing(24, width),
  },
  errorText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    color: colors.status.error,
    textAlign: 'center',
  },
  retryButtonWrapper: {
    marginTop: getSpacing(16, width),
    alignSelf: 'center',
  },
  retryButtonGradient: {
    borderRadius: getBorderRadius(12, width),
    overflow: 'hidden',
  },
  retryButton: {
    paddingHorizontal: getSpacing(24, width),
    paddingVertical: getSpacing(12, width),
  },
  retryButtonText: {
    color: colors.background,
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(16, width),
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: '700',
    color: colors.text.primary,
  },
  monthlyReportButtonWrapper: {
    // Wrapper for gradient
  },
  monthlyReportButtonGradient: {
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  monthlyReportButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: getSpacing(12, width),
    paddingVertical: getSpacing(8, width),
    gap: getSpacing(4, width),
  },
  monthlyReportButtonText: {
    color: colors.background,
    fontSize: getFontSize(14, width, theme),
    fontWeight: '700',
  },
  tabContainer: {
    flexDirection: 'row',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    backgroundColor: colors.card,
    gap: getSpacing(8, width),
  },
  tab: {
    flex: 1,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: getSpacing(8, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.background,
    gap: getSpacing(4, width),
  },
  tabActive: {
    backgroundColor: accent.primary,
  },
  tabLocked: {
    opacity: 0.6,
  },
  tabText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
  },
  tabTextActive: {
    color: colors.background,
  },
  taskTypeContainer: {
    flexDirection: 'row',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(12, width),
    backgroundColor: colors.card,
    gap: getSpacing(8, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  taskTypeTab: {
    flex: 1,
    paddingVertical: getSpacing(8, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.background,
    alignItems: 'center',
  },
  taskTypeTabActive: {
    backgroundColor: accent.primary,
  },
  taskTypeTabText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
  },
  taskTypeTabTextActive: {
    color: colors.background,
  },
  navigationContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(16, width),
    backgroundColor: colors.card,
    marginBottom: getSpacing(8, width),
  },
  navButton: {
    padding: getSpacing(8, width),
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.background,
    position: 'relative',
  },
  navButtonDisabled: {
    opacity: 0.5,
  },
  navLockIcon: {
    position: 'absolute',
    top: 2,
    right: 2,
  },
  periodLabel: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
  },
  summaryContainer: {
    flexDirection: 'row',
    paddingHorizontal: getSpacing(16, width),
    marginTop: getSpacing(8, width),
    gap: getSpacing(8, width),
  },
  summaryCard: {
    flex: 1,
    padding: getSpacing(16, width),
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(12, width),
    alignItems: 'center',
    ...getShadow(2, width),
  },
  summaryLabel: {
    marginTop: getSpacing(8, width),
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
  },
  summaryValue: {
    marginTop: getSpacing(4, width),
    fontSize: getFontSize(18, width, theme),
    fontWeight: '700',
    color: colors.text.primary,
  },
  subscriptionBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: getSpacing(16, width),
    marginTop: getSpacing(16, width),
    padding: getSpacing(16, width),
    backgroundColor: accent.primary + '20',
    borderRadius: getBorderRadius(12, width),
    borderWidth: 1,
    borderColor: accent.primary,
  },
  subscriptionBannerContent: {
    flex: 1,
    marginLeft: getSpacing(12, width),
  },
  subscriptionBannerTitle: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
  },
  subscriptionBannerText: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    marginTop: getSpacing(2, width),
  },
  subscriptionBannerButton: {
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(8, width),
    backgroundColor: accent.primary,
    borderRadius: getBorderRadius(8, width),
  },
  subscriptionBannerButtonText: {
    color: colors.background,
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
  },
  // メンバー選択
  memberSelectContainer: {
    marginHorizontal: getSpacing(16, width),
    marginBottom: getSpacing(16, width),
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    ...getShadow(3, width),
  },
  memberSelectHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  memberSelectLabel: {
    marginLeft: getSpacing(8, width),
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
  },
  lockBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: getSpacing(8, width),
    paddingHorizontal: getSpacing(8, width),
    paddingVertical: getSpacing(4, width),
    backgroundColor: accent.primary + '20',
    borderRadius: getBorderRadius(12, width),
  },
  lockBadgeText: {
    marginLeft: getSpacing(4, width),
    fontSize: getFontSize(10, width, theme),
    fontWeight: '600',
    color: accent.primary,
  },
  memberSelectButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    backgroundColor: colors.background,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: getBorderRadius(8, width),
  },
  memberSelectButtonText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.primary,
    fontWeight: '500',
  },
  // モーダル
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#fff',
    borderTopLeftRadius: getBorderRadius(20, width),
    borderTopRightRadius: getBorderRadius(20, width),
    maxHeight: '70%',
    paddingBottom: getSpacing(20, width),
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: getSpacing(20, width),
    paddingVertical: getSpacing(16, width),
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  modalTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '700',
    color: '#1f2937',
  },
  modalList: {
    paddingHorizontal: getSpacing(20, width),
  },
  memberItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: getSpacing(16, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  memberItemSelected: {
    backgroundColor: accent.primary + '10',
  },
  memberItemText: {
    flex: 1,
    marginLeft: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
  },
  memberItemTextSelected: {
    fontWeight: '600',
    color: accent.primary,
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
    backgroundColor: colors.background,
  },
  picker: {
    height: 50,
  },
  pickerItem: {
    fontSize: getFontSize(14, width, theme),
  },
  lockedMemberSelect: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    backgroundColor: accent.primary + '10',
    borderWidth: 2,
    borderColor: accent.primary,
    borderRadius: getBorderRadius(8, width),
  },
  lockedMemberSelectText: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
  },
});
