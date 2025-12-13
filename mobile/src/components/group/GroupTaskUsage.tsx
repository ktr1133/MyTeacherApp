/**
 * GroupTaskUsage - グループタスク作成状況表示コンポーネント
 * 
 * 機能:
 * - 今月のタスク作成数/上限表示
 * - サブスクリプション状態表示
 * - プログレスバー表示
 * - レスポンシブ対応
 * 
 * 使用箇所: GroupManagementScreen
 */

import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useTheme } from '../../contexts/ThemeContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import type { Group, GroupTaskUsage as TaskUsage } from '../../types/group.types';

interface GroupTaskUsageProps {
  group: Group;
  taskUsage: TaskUsage;
}

/**
 * GroupTaskUsage コンポーネント
 */
export const GroupTaskUsageComponent: React.FC<GroupTaskUsageProps> = ({
  group,
  taskUsage,
}) => {
  const { theme } = useTheme();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';

  // スタイル生成
  const styles = React.useMemo(() => createStyles(width, themeType), [width, themeType]);

  // 使用率計算
  const usagePercentage = taskUsage.limit > 0 ? (taskUsage.current / taskUsage.limit) * 100 : 0;
  const isNearLimit = usagePercentage >= 80;
  const isAtLimit = taskUsage.remaining <= 0;

  // 次回リセット日の整形
  const resetDate = new Date(taskUsage.reset_at);
  const resetDateString = `${resetDate.getFullYear()}/${resetDate.getMonth() + 1}/${resetDate.getDate()}`;

  return (
    <View style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <LinearGradient
          colors={['#3b82f6', '#06b6d4']} // blue-500 → cyan-500
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.iconContainer}
        >
          <Text style={styles.iconText}>📊</Text>
        </LinearGradient>
        <View style={styles.headerTextContainer}>
          <Text style={styles.title}>
            {theme === 'child'
              ? 'グループタスクさくせいじょうきょう'
              : 'グループタスク作成状況'}
          </Text>
          <Text style={styles.subtitle}>
            {theme === 'child'
              ? 'こんげつのしようじょうきょう'
              : '今月の使用状況とサブスクリプション情報'}
          </Text>
        </View>
      </View>

      {/* サブスクリプション状態 */}
      <View
        style={[
          styles.subscriptionCard,
          group.subscription_active ? styles.subscriptionActive : styles.subscriptionInactive,
        ]}
      >
        <View style={styles.subscriptionRow}>
          <Text
            style={[
              styles.subscriptionLabel,
              group.subscription_active
                ? styles.subscriptionLabelActive
                : styles.subscriptionLabelInactive,
            ]}
          >
            {theme === 'child' ? 'サブスク:' : 'サブスクリプション:'}
          </Text>
          <View style={styles.subscriptionBadgeContainer}>
            {group.subscription_active ? (
              <>
                <View style={styles.subscriptionBadgeActive}>
                  <Text style={styles.checkIcon}>✓</Text>
                  <Text style={styles.subscriptionBadgeTextActive}>
                    {theme === 'child' ? 'ゆうこう' : '有効'}
                  </Text>
                </View>
                {group.subscription_plan && (
                  <View style={styles.planBadge}>
                    <Text style={styles.planBadgeText}>
                      {group.subscription_plan.charAt(0).toUpperCase() +
                        group.subscription_plan.slice(1)}
                      {theme === 'child' ? 'プラン' : 'プラン'}
                    </Text>
                  </View>
                )}
              </>
            ) : (
              <View style={styles.subscriptionBadgeInactive}>
                <Text style={styles.subscriptionBadgeTextInactive}>
                  {theme === 'child' ? 'むりょうプラン' : '無料プラン'}
                </Text>
              </View>
            )}
          </View>
        </View>
      </View>

      {/* タスク作成状況 */}
      <View style={styles.usageSection}>
        {/* ラベルと数値 */}
        <View style={styles.usageHeader}>
          <Text style={styles.usageLabel}>
            {theme === 'child' ? 'こんげつのさくせいすう' : '今月の作成数'}
          </Text>
          <Text
            style={[
              styles.usageValue,
              isAtLimit
                ? styles.usageValueDanger
                : isNearLimit
                ? styles.usageValueWarning
                : styles.usageValueNormal,
            ]}
          >
            {taskUsage.current} /{' '}
            {group.subscription_active
              ? theme === 'child'
                ? 'むせいげん'
                : '無制限'
              : taskUsage.limit}
          </Text>
        </View>

        {/* プログレスバー */}
        {!group.subscription_active && (
          <View style={styles.progressBarContainer}>
            <View
              style={[
                styles.progressBar,
                isAtLimit
                  ? styles.progressBarDanger
                  : isNearLimit
                  ? styles.progressBarWarning
                  : styles.progressBarNormal,
                { width: `${Math.min(usagePercentage, 100)}%` },
              ]}
            />
          </View>
        )}

        {group.subscription_active && (
          <LinearGradient
            colors={['#d1fae5', '#a7f3d0']} // green-100 → green-200
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.progressBarUnlimited}
          />
        )}

        {/* ステータスメッセージ */}
        {!group.subscription_active && (
          <Text
            style={[
              styles.statusMessage,
              isAtLimit
                ? styles.statusMessageDanger
                : isNearLimit
                ? styles.statusMessageWarning
                : styles.statusMessageNormal,
            ]}
          >
            {isAtLimit
              ? theme === 'child'
                ? '⚠️ こんげつのむりょうわくをつかいきったよ'
                : '⚠️ 今月の無料枠を使い切りました'
              : isNearLimit
              ? theme === 'child'
                ? `⚠️ のこり${taskUsage.remaining}かいだよ`
                : `⚠️ 残り${taskUsage.remaining}回です`
              : theme === 'child'
              ? `のこり${taskUsage.remaining}かいさくせいできるよ`
              : `残り${taskUsage.remaining}回作成できます`}
          </Text>
        )}

        {group.subscription_active && (
          <Text style={styles.statusMessageUnlimited}>
            {theme === 'child'
              ? '✨ サブスクかいいんはむせいげんにさくせいできるよ'
              : '✨ サブスクリプション会員は無制限に作成できます'}
          </Text>
        )}
      </View>

      {/* 次回リセット日 */}
      <View style={styles.resetCard}>
        <Text style={styles.resetLabel}>
          {theme === 'child' ? 'つぎのリセット' : '次回リセット日'}
        </Text>
        <Text style={styles.resetValue}>{resetDateString}</Text>
      </View>
    </View>
  );
};

/**
 * レスポンシブスタイル生成関数
 */
const createStyles = (width: number, theme: 'adult' | 'child') =>
  StyleSheet.create({
    container: {
      backgroundColor: '#ffffff',
      borderRadius: getBorderRadius(16, width),
      padding: getSpacing(16, width),
      marginBottom: getSpacing(16, width),
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.05,
      shadowRadius: 8,
      elevation: 2,
      borderWidth: 1,
      borderColor: '#e2e8f0',
    },
    header: {
      flexDirection: 'row',
      alignItems: 'center',
      marginBottom: getSpacing(16, width),
    },
    iconContainer: {
      width: 40,
      height: 40,
      borderRadius: getBorderRadius(12, width),
      alignItems: 'center',
      justifyContent: 'center',
      marginRight: getSpacing(12, width),
    },
    iconText: {
      fontSize: getFontSize(20, width, theme),
    },
    headerTextContainer: {
      flex: 1,
    },
    title: {
      fontSize: getFontSize(18, width, theme),
      fontWeight: 'bold',
      color: '#1e293b',
      marginBottom: getSpacing(2, width),
    },
    subtitle: {
      fontSize: getFontSize(13, width, theme),
      color: '#64748b',
    },
    subscriptionCard: {
      padding: getSpacing(12, width),
      borderRadius: getBorderRadius(12, width),
      marginBottom: getSpacing(16, width),
      borderWidth: 1,
    },
    subscriptionActive: {
      backgroundColor: '#f0fdf4',
      borderColor: '#bbf7d0',
    },
    subscriptionInactive: {
      backgroundColor: '#f8fafc',
      borderColor: '#e2e8f0',
    },
    subscriptionRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
    },
    subscriptionLabel: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
    },
    subscriptionLabelActive: {
      color: '#15803d',
    },
    subscriptionLabelInactive: {
      color: '#475569',
    },
    subscriptionBadgeContainer: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: getSpacing(8, width),
    },
    subscriptionBadgeActive: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: '#dcfce7',
      paddingVertical: getSpacing(4, width),
      paddingHorizontal: getSpacing(10, width),
      borderRadius: getBorderRadius(16, width),
      gap: getSpacing(4, width),
    },
    checkIcon: {
      fontSize: getFontSize(14, width, theme),
      color: '#15803d',
    },
    subscriptionBadgeTextActive: {
      fontSize: getFontSize(12, width, theme),
      fontWeight: 'bold',
      color: '#15803d',
    },
    planBadge: {
      backgroundColor: '#dbeafe',
      paddingVertical: getSpacing(4, width),
      paddingHorizontal: getSpacing(8, width),
      borderRadius: getBorderRadius(8, width),
    },
    planBadgeText: {
      fontSize: getFontSize(11, width, theme),
      color: '#1e40af',
    },
    subscriptionBadgeInactive: {
      backgroundColor: '#f1f5f9',
      paddingVertical: getSpacing(4, width),
      paddingHorizontal: getSpacing(10, width),
      borderRadius: getBorderRadius(16, width),
    },
    subscriptionBadgeTextInactive: {
      fontSize: getFontSize(12, width, theme),
      fontWeight: 'bold',
      color: '#475569',
    },
    usageSection: {
      marginBottom: getSpacing(16, width),
    },
    usageHeader: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: getSpacing(8, width),
    },
    usageLabel: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '500',
      color: '#475569',
    },
    usageValue: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: 'bold',
    },
    usageValueNormal: {
      color: '#1e293b',
    },
    usageValueWarning: {
      color: '#ca8a04',
    },
    usageValueDanger: {
      color: '#dc2626',
    },
    progressBarContainer: {
      width: '100%',
      height: 12,
      backgroundColor: '#e2e8f0',
      borderRadius: getBorderRadius(16, width),
      overflow: 'hidden',
      marginBottom: getSpacing(8, width),
    },
    progressBar: {
      height: '100%',
      borderRadius: getBorderRadius(16, width),
    },
    progressBarNormal: {
      backgroundColor: '#3b82f6',
    },
    progressBarWarning: {
      backgroundColor: '#eab308',
    },
    progressBarDanger: {
      backgroundColor: '#ef4444',
    },
    progressBarUnlimited: {
      width: '100%',
      height: 12,
      borderRadius: getBorderRadius(16, width),
      marginBottom: getSpacing(8, width),
    },
    statusMessage: {
      fontSize: getFontSize(13, width, theme),
    },
    statusMessageNormal: {
      color: '#64748b',
    },
    statusMessageWarning: {
      color: '#ca8a04',
      fontWeight: '500',
    },
    statusMessageDanger: {
      color: '#dc2626',
      fontWeight: '500',
    },
    statusMessageUnlimited: {
      fontSize: getFontSize(13, width, theme),
      color: '#16a34a',
      fontWeight: '500',
    },
    resetCard: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      backgroundColor: '#f8fafc',
      padding: getSpacing(12, width),
      borderRadius: getBorderRadius(8, width),
    },
    resetLabel: {
      fontSize: getFontSize(13, width, theme),
      color: '#64748b',
    },
    resetValue: {
      fontSize: getFontSize(13, width, theme),
      fontWeight: '600',
      color: '#1e293b',
    },
  });

export default GroupTaskUsageComponent;
