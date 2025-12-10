/**
 * ヘッダー通知アイコン
 * 
 * 全画面のヘッダー右側に表示される通知アイコン
 * 未読通知数をバッジで表示
 * 
 * @see /home/ktr/mtdev/definitions/mobile/NavigationFlow.md - Section 3.3
 */

import React, { useMemo } from 'react';
import { TouchableOpacity, View, Text, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { useNotifications } from '../../hooks/useNotifications';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * ヘッダー通知アイコンコンポーネント
 * 
 * 機能:
 * - 未読通知数のバッジ表示（1-99、100以上は「99+」）
 * - タップで通知一覧画面に遷移
 * - 未読通知がない場合はバッジ非表示
 * - レスポンシブデザイン完全対応（ResponsiveDesignGuideline.md準拠）
 * 
 * @returns JSX.Element
 */
export default function HeaderNotificationIcon() {
  const navigation = useNavigation<any>();
  const { unreadCount } = useNotifications();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const theme = isChildTheme ? 'child' : 'adult';

  /**
   * 通知アイコンタップ時の処理
   */
  const handlePress = () => {
    navigation.navigate('NotificationList');
  };

  /**
   * バッジ表示用の未読数テキスト
   * 100以上の場合は「99+」と表示
   */
  const badgeText = unreadCount > 99 ? '99+' : unreadCount.toString();

  /**
   * レスポンシブスタイル
   * デバイスサイズとテーマに応じて動的に計算
   */
  const styles = useMemo(() => createStyles(width, theme), [width, theme]);

  return (
    <TouchableOpacity
      onPress={handlePress}
      style={styles.container}
      accessibilityLabel="通知"
      accessibilityHint={`未読通知${unreadCount}件`}
      testID="header-notification-icon"
    >
      <Ionicons 
        name="notifications-outline" 
        size={getFontSize(24, width, theme)} 
        color="#333" 
      />
      {unreadCount > 0 && (
        <View style={styles.badge} testID="notification-badge">
          <Text style={styles.badgeText}>
            {badgeText}
          </Text>
        </View>
      )}
    </TouchableOpacity>
  );
}

/**
 * スタイル定義
 * 
 * レスポンシブデザインガイドラインに準拠:
 * - getFontSize: デバイスサイズとテーマに応じた動的フォントサイズ
 * - getSpacing: デバイスサイズに応じた動的余白
 * - getBorderRadius: デバイスサイズに応じた動的角丸
 * 
 * @param width - 画面幅
 * @param theme - テーマタイプ（'adult' | 'child'）
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child') =>
  StyleSheet.create({
    container: {
      position: 'relative',
      marginRight: getSpacing(16, width),
      padding: getSpacing(4, width),
    },
    badge: {
      position: 'absolute',
      top: 0,
      right: 0,
      backgroundColor: '#EF4444', // 赤色バッジ
      borderRadius: getBorderRadius(10, width),
      minWidth: getSpacing(18, width),
      height: getSpacing(18, width),
      justifyContent: 'center',
      alignItems: 'center',
      paddingHorizontal: getSpacing(4, width),
      borderWidth: 2,
      borderColor: '#FFFFFF', // 白い縁取り
    },
    badgeText: {
      color: '#FFFFFF',
      fontWeight: '700',
      fontSize: getFontSize(10, width, theme),
      lineHeight: getFontSize(14, width, theme),
    },
  });
