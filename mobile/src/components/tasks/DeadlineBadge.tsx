/**
 * 期限バッジコンポーネント
 * 
 * タスクの期限状態に応じたバッジを表示
 * Web版のタスクカード（task-card.blade.php）のバナーデザインに準拠
 * 
 * @see /home/ktr/mtdev/resources/views/components/task-card.blade.php
 */

import { useEffect, useRef } from 'react';
import { View, Text, StyleSheet, Animated } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { DeadlineInfo } from '../../utils/taskDeadline';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { useThemedColors } from '../../hooks/useThemedColors';

interface DeadlineBadgeProps {
  /** 期限情報 */
  deadlineInfo: DeadlineInfo;
  /** 表示位置（default: カード右上、inline: インライン表示） */
  variant?: 'absolute' | 'inline';
}

/**
 * 期限バッジコンポーネント
 */
export default function DeadlineBadge({ deadlineInfo, variant = 'absolute' }: DeadlineBadgeProps) {
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors } = useThemedColors();
  
  // アニメーション用
  const pulseAnim = useRef(new Animated.Value(1)).current;

  // ステータスがnoneまたはsafeの場合は表示しない
  if (deadlineInfo.status === 'none' || deadlineInfo.status === 'safe') {
    return null;
  }

  // approaching（期限が迫っている）の場合はパルスアニメーション
  useEffect(() => {
    if (deadlineInfo.status === 'approaching') {
      const animation = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.1,
            duration: 800,
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 1,
            duration: 800,
            useNativeDriver: true,
          }),
        ])
      );
      animation.start();
      
      return () => animation.stop();
    }
    return undefined;
  }, [deadlineInfo.status, pulseAnim]);

  // ステータス別のスタイル
  const getBadgeStyle = () => {
    switch (deadlineInfo.status) {
      case 'completed':
        return {
          backgroundColor: '#10B981', // 緑
          colors: ['#10B981', '#059669'],
        };
      case 'overdue':
        return {
          backgroundColor: '#EF4444', // 赤
          colors: ['#EF4444', '#DC2626'],
        };
      case 'approaching':
        return {
          backgroundColor: '#F59E0B', // 黄色
          colors: ['#F59E0B', '#D97706'],
        };
      default:
        return {
          backgroundColor: '#6B7280', // グレー
          colors: ['#6B7280', '#4B5563'],
        };
    }
  };

  // アイコン選択
  const getIcon = () => {
    switch (deadlineInfo.status) {
      case 'completed':
        return 'checkmark-circle';
      case 'overdue':
        return 'close-circle';
      case 'approaching':
        return 'warning';
      default:
        return 'time';
    }
  };

  const badgeStyle = getBadgeStyle();
  const iconName = getIcon();

  const styles = StyleSheet.create({
    badgeAbsolute: {
      position: 'absolute',
      top: 0,
      right: 0,
      backgroundColor: badgeStyle.backgroundColor,
      paddingHorizontal: getSpacing(12, width),
      paddingVertical: getSpacing(6, width),
      borderBottomLeftRadius: getBorderRadius(8, width),
      flexDirection: 'row',
      alignItems: 'center',
      gap: getSpacing(4, width),
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.25,
      shadowRadius: 3.84,
      elevation: 5,
      zIndex: 10,
    },
    badgeInline: {
      backgroundColor: badgeStyle.backgroundColor,
      paddingHorizontal: getSpacing(10, width),
      paddingVertical: getSpacing(4, width),
      borderRadius: getBorderRadius(6, width),
      flexDirection: 'row',
      alignItems: 'center',
      gap: getSpacing(4, width),
      alignSelf: 'flex-start',
    },
    badgeText: {
      color: '#FFFFFF',
      fontSize: getFontSize(11, width, themeType),
      fontWeight: '700',
      letterSpacing: 0.3,
    },
  });

  const BadgeContent = (
    <>
      <Ionicons name={iconName as any} size={getFontSize(14, width, themeType)} color="#FFFFFF" />
      <Text style={styles.badgeText}>{deadlineInfo.message}</Text>
    </>
  );

  if (variant === 'absolute') {
    // approaching の場合はアニメーション付き
    if (deadlineInfo.status === 'approaching') {
      return (
        <Animated.View style={[styles.badgeAbsolute, { transform: [{ scale: pulseAnim }] }]}>
          {BadgeContent}
        </Animated.View>
      );
    }
    
    return <View style={styles.badgeAbsolute}>{BadgeContent}</View>;
  }

  // inline表示
  return <View style={styles.badgeInline}>{BadgeContent}</View>;
}
