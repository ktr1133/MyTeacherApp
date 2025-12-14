/**
 * グループタスク作成上限エラーモーダル
 * 
 * サブスク未加入ユーザーがグループタスク作成上限に達した際に表示し、
 * サブスク管理画面への遷移を促す
 * 
 * @module components/common/GroupTaskLimitModal
 */

import React, { useMemo } from 'react';
import {
  View,
  Text,
  Modal,
  TouchableOpacity,
  StyleSheet,
  ScrollView,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useTheme } from '../../contexts/ThemeContext';
import { useResponsive, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { useThemedColors } from '../../hooks/useThemedColors';

/**
 * Props型定義
 */
interface GroupTaskLimitModalProps {
  /** モーダル表示フラグ */
  visible: boolean;
  /** エラーメッセージ */
  message: string;
  /** モーダルを閉じる処理 */
  onClose: () => void;
}

/**
 * グループタスク作成上限エラーモーダルコンポーネント
 * 
 * @param props - Props
 * @returns モーダルコンポーネント
 */
const GroupTaskLimitModal: React.FC<GroupTaskLimitModalProps> = ({
  visible,
  message,
  onClose,
}) => {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const { theme } = useTheme();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  /**
   * サブスク管理画面へ遷移
   */
  const handleNavigateToSubscription = () => {
    onClose();
    navigation.navigate('SubscriptionManage');
  };

  // テーマ別ラベル
  const labels = theme === 'child' ? {
    title: 'じょうげんだよ！',
    benefitTitle: '✨ サブスクでむせいげん！',
    benefit1: 'みんなのやることがむせいげん',
    benefit2: 'つきのレポートじどうでできる',
    benefit3: 'ぜんぶのきのうがつかえる',
    price: 'つき ¥500〜',
    closeButton: 'とじる',
    subscriptionButton: '確認',
  } : {
    title: '作成上限に達しました',
    benefitTitle: '✨ サブスクで制限解除',
    benefit1: 'グループタスクを無制限に作成',
    benefit2: '月次レポート自動生成',
    benefit3: '全機能が使い放題',
    price: '月額 ¥500〜',
    closeButton: '閉じる',
    subscriptionButton: '確認',
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onClose}
    >
      <View style={styles.overlay}>
        <View style={styles.modalContainer}>
          {/* ヘッダー */}
          <LinearGradient
            colors={['#9333ea', '#ec4899']} // purple-600 → pink-600
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.header}
          >
            <View style={styles.headerIcon}>
              <Text style={styles.headerIconText}>⚠️</Text>
            </View>
            <Text style={styles.headerTitle}>{labels.title}</Text>
          </LinearGradient>

          <ScrollView style={styles.content} contentContainerStyle={styles.contentContainer}>
            {/* エラーメッセージ */}
            <Text style={styles.message}>{message}</Text>

            {/* サブスク特典 */}
            <View style={styles.benefitCard}>
              <Text style={styles.benefitTitle}>{labels.benefitTitle}</Text>
              <View style={styles.benefitList}>
                <Text style={styles.benefitItem}>• {labels.benefit1}</Text>
                <Text style={styles.benefitItem}>• {labels.benefit2}</Text>
                <Text style={styles.benefitItem}>• {labels.benefit3}</Text>
              </View>
              <Text style={styles.price}>{labels.price}</Text>
            </View>
          </ScrollView>

          {/* ボタン */}
          <View style={styles.buttonContainer}>
            <TouchableOpacity
              style={styles.closeButton}
              onPress={onClose}
              activeOpacity={0.7}
            >
              <Text style={styles.closeButtonText}>{labels.closeButton}</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.subscriptionButton}
              onPress={handleNavigateToSubscription}
              activeOpacity={0.7}
            >
              <LinearGradient
                colors={['#59B9C6', '#9333ea']} // primary → purple-600
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.subscriptionButtonGradient}
              >
                <Text style={styles.subscriptionButtonText}>{labels.subscriptionButton}</Text>
              </LinearGradient>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

/**
 * レスポンシブスタイル生成
 */
const createStyles = (
  width: number,
  themeType: 'child' | 'adult',
  colors: any,
  accent: any
) => {
  const isChild = themeType === 'child';
  const accentColor = typeof accent === 'string' ? accent : accent.primary;

  return StyleSheet.create({
    overlay: {
      flex: 1,
      backgroundColor: 'rgba(0, 0, 0, 0.5)',
      justifyContent: 'center',
      alignItems: 'center',
      padding: getSpacing(16, width),
    },
    modalContainer: {
      width: '100%',
      maxWidth: 450,
      backgroundColor: colors.card,
      borderRadius: getBorderRadius(24, width),
      overflow: 'hidden',
      elevation: 5,
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 4 },
      shadowOpacity: 0.3,
      shadowRadius: 10,
    },
    header: {
      paddingVertical: getSpacing(4 * 4, width),
      paddingHorizontal: getSpacing(6 * 4, width),
      flexDirection: 'row',
      alignItems: 'center',
      gap: getSpacing(3 * 4, width),
    },
    headerIcon: {
      width: 40,
      height: 40,
      borderRadius: getBorderRadius(16, width),
      backgroundColor: 'rgba(255, 255, 255, 0.2)', // グラデーション上のアイコン背景（固定色）
      justifyContent: 'center',
      alignItems: 'center',
    },
    headerIconText: {
      fontSize: 24,
    },
    headerTitle: {
      fontSize: isChild ? 20 : 18,
      fontWeight: isChild ? '800' : 'bold',
      color: '#ffffff',
    },
    content: {
      maxHeight: 400,
    },
    contentContainer: {
      padding: getSpacing(6 * 4, width),
      gap: getSpacing(4 * 4, width),
    },
    message: {
      fontSize: isChild ? 16 : 15,
      fontWeight: isChild ? '700' : 'normal',
      color: colors.text,
      lineHeight: isChild ? 26 : 24,
      marginBottom: getSpacing(2 * 4, width),
    },
    benefitCard: {
      backgroundColor: `${accentColor}15`, // 15% opacity
      borderRadius: getBorderRadius(12, width),
      padding: getSpacing(4 * 4, width),
      borderWidth: 1,
      borderColor: `${accentColor}30`, // 30% opacity
    },
    benefitTitle: {
      fontSize: isChild ? 16 : 14,
      fontWeight: isChild ? '800' : '600',
      color: colors.text,
      marginBottom: getSpacing(2 * 4, width),
    },
    benefitList: {
      gap: getSpacing(1 * 4, width),
      marginBottom: getSpacing(3 * 4, width),
    },
    benefitItem: {
      fontSize: isChild ? 14 : 13,
      fontWeight: isChild ? '700' : 'normal',
      color: colors.text,
      lineHeight: isChild ? 22 : 20,
    },
    price: {
      fontSize: isChild ? 24 : 22,
      fontWeight: isChild ? '900' : 'bold',
      color: accentColor,
    },
    buttonContainer: {
      flexDirection: 'row',
      gap: getSpacing(3 * 4, width),
      padding: getSpacing(4 * 4, width),
      backgroundColor: colors.background,
      borderTopWidth: 1,
      borderTopColor: colors.border,
    },
    closeButton: {
      flex: 1,
      paddingVertical: getSpacing(3 * 4, width),
      paddingHorizontal: getSpacing(4 * 4, width),
      borderRadius: getBorderRadius(12, width),
      backgroundColor: colors.card,
      borderWidth: 1,
      borderColor: colors.border,
      alignItems: 'center',
    },
    closeButtonText: {
      fontSize: isChild ? 16 : 14,
      fontWeight: isChild ? '800' : '600',
      color: colors.text,
    },
    subscriptionButton: {
      flex: 1,
      borderRadius: getBorderRadius(12, width),
      overflow: 'hidden',
      elevation: 3,
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.2,
      shadowRadius: 4,
    },
    subscriptionButtonGradient: {
      paddingVertical: getSpacing(3 * 4, width),
      paddingHorizontal: getSpacing(4 * 4, width),
      alignItems: 'center',
    },
    subscriptionButtonText: {
      fontSize: isChild ? 16 : 14,
      fontWeight: isChild ? '800' : '600',
      color: '#ffffff',
    },
  });
};

export default GroupTaskLimitModal;
