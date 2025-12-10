/**
 * トークン購入承認カード
 * 
 * トークン購入申請を表示するカードコンポーネント
 * 
 * 参照:
 * - 要件定義: /home/ktr/mtdev/definitions/mobile/PendingApprovalsScreen.md
 * - レスポンシブ設計: /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 */

import React, { useMemo } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { TokenApprovalItem } from '../../types/approval.types';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * Props型定義
 */
interface TokenApprovalCardProps {
  /** トークン購入承認アイテム */
  item: TokenApprovalItem;
  /** 承認ハンドラー */
  onApprove: (purchaseRequestId: number) => void;
  /** 却下ハンドラー */
  onReject: (purchaseRequestId: number) => void;
  /** 承認・却下処理中フラグ */
  isProcessing?: boolean;
}

/**
 * トークン購入承認カードコンポーネント
 * 
 * @example
 * ```tsx
 * <TokenApprovalCard
 *   item={tokenApprovalItem}
 *   onApprove={(id) => handleApproveToken(id)}
 *   onReject={(id) => handleRejectToken(id)}
 *   isProcessing={isProcessing}
 * />
 * ```
 */
export const TokenApprovalCard: React.FC<TokenApprovalCardProps> = ({
  item,
  onApprove,
  onReject,
  isProcessing = false,
}) => {
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  /**
   * 申請日時をフォーマット
   */
  const formatRequestedDate = (dateString: string): string => {
    try {
      const date = new Date(dateString);
      return date.toLocaleString('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      }).replace(/\//g, '/');
    } catch {
      return dateString;
    }
  };

  return (
    <View style={styles.card}>
      {/* タイプバッジ */}
      <View style={styles.typeBadge}>
        <Text style={styles.typeBadgeText}>トークン</Text>
      </View>

      {/* パッケージ名 */}
      <Text style={styles.title} numberOfLines={1}>
        {item.package_name}
      </Text>

      {/* 申請者 */}
      <View style={styles.infoRow}>
        <Text style={styles.label}>申請者:</Text>
        <Text style={styles.value}>{item.requester_name}</Text>
      </View>

      {/* 申請日時 */}
      <View style={styles.infoRow}>
        <Text style={styles.label}>申請日:</Text>
        <Text style={styles.value}>{formatRequestedDate(item.requested_at)}</Text>
      </View>

      {/* トークン数 */}
      <View style={styles.infoRow}>
        <Text style={styles.label}>トークン:</Text>
        <Text style={styles.valueHighlight}>
          {item.token_amount.toLocaleString()} トークン
        </Text>
      </View>

      {/* 金額 */}
      <View style={styles.infoRow}>
        <Text style={styles.label}>金額:</Text>
        <Text style={styles.valuePrice}>
          {item.price.toLocaleString()} 円
        </Text>
      </View>

      {/* ボタンエリア */}
      <View style={styles.buttonContainer}>
        <TouchableOpacity
          style={[styles.button, styles.approveButton, isProcessing && styles.buttonDisabled]}
          onPress={() => onApprove(item.id)}
          disabled={isProcessing}
        >
          {isProcessing ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <Text style={styles.approveButtonText}>承認する</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.button, styles.rejectButton, isProcessing && styles.buttonDisabled]}
          onPress={() => onReject(item.id)}
          disabled={isProcessing}
        >
          {isProcessing ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <Text style={styles.rejectButtonText}>却下する</Text>
          )}
        </TouchableOpacity>
      </View>
    </View>
  );
};

/**
 * レスポンシブスタイル生成関数
 * 
 * @param width - 画面幅
 * @param theme - テーマ種別
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child') => {
  const spacing = getSpacing(8, width);
  const borderRadius = getBorderRadius(8, width);
  const shadow = getShadow(2);

  return StyleSheet.create({
    card: {
      backgroundColor: '#fff',
      borderRadius: borderRadius * 1.5,
      padding: spacing * 2,
      marginBottom: spacing * 2,
      ...shadow,
    },
    typeBadge: {
      alignSelf: 'flex-start',
      backgroundColor: '#ffc107',
      paddingVertical: spacing * 0.5,
      paddingHorizontal: spacing * 1.5,
      borderRadius: borderRadius,
      marginBottom: spacing * 1.5,
    },
    typeBadgeText: {
      fontSize: getFontSize(12, width, theme),
      fontWeight: '600',
      color: '#fff',
    },
    title: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: '#333',
      marginBottom: spacing * 1.5,
    },
    infoRow: {
      flexDirection: 'row',
      marginBottom: spacing,
    },
    label: {
      fontSize: getFontSize(14, width, theme),
      color: '#666',
      width: width >= 768 ? 100 : 80,
    },
    value: {
      fontSize: getFontSize(14, width, theme),
      color: '#333',
      flex: 1,
    },
    valueHighlight: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: '#28a745',
      flex: 1,
    },
    valuePrice: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: '#ff6b6b',
      flex: 1,
    },
    buttonContainer: {
      flexDirection: 'row',
      marginTop: spacing * 1.5,
      gap: spacing * 1.5,
    },
    button: {
      flex: 1,
      paddingVertical: spacing * 1.5,
      borderRadius: borderRadius,
      alignItems: 'center',
      justifyContent: 'center',
    },
    approveButton: {
      backgroundColor: '#28a745',
    },
    approveButtonText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: '#fff',
    },
    rejectButton: {
      backgroundColor: '#dc3545',
    },
    rejectButtonText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: '#fff',
    },
    buttonDisabled: {
      opacity: 0.6,
    },
  });
};

export default TokenApprovalCard;
