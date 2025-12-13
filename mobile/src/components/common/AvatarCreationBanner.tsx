/**
 * アバター作成促進バナー
 * 
 * タスク一覧画面上部に表示される、アバター未作成ユーザー向けのバナー
 * タップでアバター作成画面に遷移
 * 
 * @see /home/ktr/mtdev/definitions/mobile/NavigationFlow.md - Section 4.1
 */

import React, { useMemo } from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useTheme } from '../../contexts/ThemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';

/**
 * アバター作成促進バナーコンポーネント
 * 
 * 表示条件:
 * - `teacher_avatar_id === null` (アバター未作成)
 * 
 * デザイン:
 * - 背景色: 薄いピンク（#FDF2F8）
 * - アイコン: ユーザーアイコン（person-outline）
 * - テキスト: 「あなただけのサポートアバターを作成しましょう！」
 * 
 * @param props - コンポーネントプロパティ
 * @param props.onPress - タップ時のコールバック（オプション、未指定時はアバター作成画面に遷移）
 * @returns JSX.Element
 */
interface AvatarCreationBannerProps {
  onPress?: () => void;
}

export default function AvatarCreationBanner({ onPress }: AvatarCreationBannerProps) {
  const navigation = useNavigation<any>();
  const { theme } = useTheme();
  const { width } = useResponsive();
  const themeType = theme === 'child' ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();

  /**
   * バナータップ時の処理
   * アバター作成画面に遷移
   */
  const handlePress = () => {
    if (onPress) {
      onPress();
    } else {
      navigation.navigate('AvatarCreate');
    }
  };

  /**
   * テーマに応じたメッセージ
   */
  const message = theme === 'child'
    ? 'あなただけのサポートキャラをつくろう！'
    : 'あなただけのサポートアバターを作成しましょう！';

  const description = theme === 'child'
    ? 'たのしくタスクをかんせいできるよ！'
    : 'タスク完了時に応援してくれるキャラクターを作成できます';

  /**
   * レスポンシブスタイル
   * デバイスサイズとテーマに応じて動的に計算
   */
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  return (
    <TouchableOpacity
      style={styles.container}
      onPress={handlePress}
      activeOpacity={0.7}
      accessibilityLabel="アバター作成バナー"
      accessibilityHint="タップしてアバターを作成"
      testID="avatar-creation-banner"
    >
      <View style={styles.iconContainer}>
        <Ionicons 
          name="person-outline" 
          size={getFontSize(32, width, themeType)} 
          color={accent.primary}
        />
      </View>
      <View style={styles.textContainer}>
        <Text style={styles.message}>
          {message}
        </Text>
        <Text style={styles.description}>
          {description}
        </Text>
      </View>
      <Ionicons 
        name="chevron-forward" 
        size={getFontSize(24, width, themeType)} 
        color={accent.primary}
      />
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
 * @param colors - カラーパレット
 * @param accent - アクセントカラー
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) =>
  StyleSheet.create({
    container: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: accent.primary + '15',
      paddingVertical: getSpacing(16, width),
      paddingHorizontal: getSpacing(16, width),
      marginHorizontal: getSpacing(16, width),
      marginTop: getSpacing(12, width),
      marginBottom: getSpacing(8, width),
      borderRadius: getBorderRadius(12, width),
      borderWidth: 1,
      borderColor: accent.primary + '30',
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.1,
      shadowRadius: 4,
      elevation: 2, // Android
    },
    iconContainer: {
      marginRight: getSpacing(12, width),
    },
    textContainer: {
      flex: 1,
      justifyContent: 'center',
    },
    message: {
      fontWeight: '700',
      fontSize: getFontSize(16, width, theme),
      color: colors.text.primary,
      marginBottom: getSpacing(4, width),
      lineHeight: getFontSize(22, width, theme),
    },
    description: {
      fontSize: getFontSize(13, width, theme),
      color: colors.text.secondary,
      lineHeight: getFontSize(18, width, theme),
    },
  });
