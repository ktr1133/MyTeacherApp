/**
 * 実績画面
 * 
 * ユーザーのタスク実績をグラフ表示
 * Web版Performance.mdの要件定義に基づく
 */

import React, { useState, useEffect, useRef } from 'react';
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
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { MaterialIcons } from '@expo/vector-icons';
import { usePerformance } from '../../hooks/usePerformance';
import { PerformanceChart } from '../../components/charts/PerformanceChart';
import { PeriodType, TaskType } from '../../types/performance.types';
import { useAvatarContext } from '../../contexts/AvatarContext';
import { useTheme } from '../../contexts/ThemeContext';

export default function PerformanceScreen() {
  const navigation = useNavigation();
  const { theme } = useTheme();
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
          <ActivityIndicator size="large" color="#59B9C6" testID="loading-indicator" />
          <Text style={styles.loadingText}>読み込み中...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.errorContainer}>
          <MaterialIcons name="error-outline" size={48} color="#ef4444" />
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={refresh}>
            <Text style={styles.retryButtonText}>再試行</Text>
          </TouchableOpacity>
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
          <TouchableOpacity
            style={styles.monthlyReportButton}
            onPress={goToMonthlyReport}
          >
            <MaterialIcons name="description" size={20} color="#fff" />
            <Text style={styles.monthlyReportButtonText}>月次レポート</Text>
          </TouchableOpacity>
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
              <MaterialIcons name="lock" size={12} color="#8B5CF6" />
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
              <MaterialIcons name="lock" size={12} color="#8B5CF6" />
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
              <MaterialIcons name="people" size={18} color="#8B5CF6" />
              <Text style={styles.memberSelectLabel}>メンバー選択</Text>
              {data?.restrictions?.member_restricted && (
                <View style={styles.lockBadge}>
                  <MaterialIcons name="lock" size={12} color="#8B5CF6" />
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
                  <MaterialIcons name="close" size={24} color="#6b7280" />
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
                  <MaterialIcons name="people" size={20} color="#8B5CF6" />
                  <Text
                    style={[
                      styles.memberItemText,
                      selectedUserId === 0 && styles.memberItemTextSelected,
                    ]}
                  >
                    グループ全体
                  </Text>
                  {selectedUserId === 0 && (
                    <MaterialIcons name="check" size={20} color="#8B5CF6" />
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
                    <MaterialIcons name="person" size={20} color="#59B9C6" />
                    <Text
                      style={[
                        styles.memberItemText,
                        selectedUserId === member.id && styles.memberItemTextSelected,
                      ]}
                    >
                      {member.username}
                    </Text>
                    {selectedUserId === member.id && (
                      <MaterialIcons name="check" size={20} color="#8B5CF6" />
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
                  ? '#d1d5db'
                  : '#59B9C6'
              }
            />
            {data?.restrictions?.navigation_restricted && (
              <MaterialIcons
                name="lock"
                size={12}
                color="#8B5CF6"
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
              color={!data?.can_navigate_next ? '#d1d5db' : '#59B9C6'}
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
              <MaterialIcons name="check-circle" size={24} color="#10b981" />
              <Text style={styles.summaryLabel}>完了</Text>
              <Text style={styles.summaryValue}>
                {data.summary.total_completed}件
              </Text>
            </View>
            <View style={styles.summaryCard}>
              <MaterialIcons name="pending" size={24} color="#f59e0b" />
              <Text style={styles.summaryLabel}>未完了</Text>
              <Text style={styles.summaryValue}>
                {data.summary.total_incomplete}件
              </Text>
            </View>
            <View style={styles.summaryCard}>
              <MaterialIcons name="stars" size={24} color="#8B5CF6" />
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
            <MaterialIcons name="star" size={24} color="#8B5CF6" />
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

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  scrollContent: {
    paddingBottom: 24,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: '#6b7280',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  errorText: {
    marginTop: 12,
    fontSize: 16,
    color: '#ef4444',
    textAlign: 'center',
  },
  retryButton: {
    marginTop: 16,
    paddingHorizontal: 24,
    paddingVertical: 12,
    backgroundColor: '#59B9C6',
    borderRadius: 8,
  },
  retryButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: '700',
    color: '#1f2937',
  },
  monthlyReportButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: '#59B9C6',
    borderRadius: 8,
    gap: 4,
  },
  monthlyReportButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
  tabContainer: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#fff',
    gap: 8,
  },
  tab: {
    flex: 1,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#f3f4f6',
    gap: 4,
  },
  tabActive: {
    backgroundColor: '#59B9C6',
  },
  tabLocked: {
    opacity: 0.6,
  },
  tabText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6b7280',
  },
  tabTextActive: {
    color: '#fff',
  },
  taskTypeContainer: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#fff',
    gap: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  taskTypeTab: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
  },
  taskTypeTabActive: {
    backgroundColor: '#8B5CF6',
  },
  taskTypeTabText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6b7280',
  },
  taskTypeTabTextActive: {
    color: '#fff',
  },
  navigationContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: '#fff',
    marginBottom: 8,
  },
  navButton: {
    padding: 8,
    borderRadius: 8,
    backgroundColor: '#f3f4f6',
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
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
  },
  summaryContainer: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    marginTop: 8,
    gap: 8,
  },
  summaryCard: {
    flex: 1,
    padding: 16,
    backgroundColor: '#fff',
    borderRadius: 12,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  summaryLabel: {
    marginTop: 8,
    fontSize: 12,
    color: '#6b7280',
  },
  summaryValue: {
    marginTop: 4,
    fontSize: 18,
    fontWeight: '700',
    color: '#1f2937',
  },
  subscriptionBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: 16,
    marginTop: 16,
    padding: 16,
    backgroundColor: '#f3e8ff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#8B5CF6',
  },
  subscriptionBannerContent: {
    flex: 1,
    marginLeft: 12,
  },
  subscriptionBannerTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6b21a8',
  },
  subscriptionBannerText: {
    fontSize: 12,
    color: '#7c3aed',
    marginTop: 2,
  },
  subscriptionBannerButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    backgroundColor: '#8B5CF6',
    borderRadius: 8,
  },
  subscriptionBannerButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
  // メンバー選択
  memberSelectContainer: {
    marginHorizontal: 16,
    marginBottom: 16,
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  memberSelectHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  memberSelectLabel: {
    marginLeft: 8,
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  lockBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: 8,
    paddingHorizontal: 8,
    paddingVertical: 4,
    backgroundColor: '#f3e8ff',
    borderRadius: 12,
  },
  lockBadgeText: {
    marginLeft: 4,
    fontSize: 10,
    fontWeight: '600',
    color: '#8B5CF6',
  },
  memberSelectButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
    paddingHorizontal: 16,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: 8,
  },
  memberSelectButtonText: {
    fontSize: 14,
    color: '#374151',
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
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '70%',
    paddingBottom: 20,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#1f2937',
  },
  modalList: {
    paddingHorizontal: 20,
  },
  memberItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#f3f4f6',
  },
  memberItemSelected: {
    backgroundColor: '#f9fafb',
  },
  memberItemText: {
    flex: 1,
    marginLeft: 12,
    fontSize: 16,
    color: '#374151',
  },
  memberItemTextSelected: {
    fontWeight: '600',
    color: '#8B5CF6',
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: 8,
    overflow: 'hidden',
    backgroundColor: '#f9fafb',
  },
  picker: {
    height: 50,
  },
  pickerItem: {
    fontSize: 14,
  },
  lockedMemberSelect: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
    paddingHorizontal: 16,
    backgroundColor: '#f9fafb',
    borderWidth: 2,
    borderColor: '#d8b4fe',
    borderRadius: 8,
  },
  lockedMemberSelectText: {
    fontSize: 14,
    color: '#6b7280',
  },
});
