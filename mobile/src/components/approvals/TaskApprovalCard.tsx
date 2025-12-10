/**
 * タスク承認カード
 * 
 * タスク承認申請を表示するカードコンポーネント
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
import { TaskApprovalItem } from '../../types/approval.types';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * Props型定義
 */
interface TaskApprovalCardProps {
  /** タスク承認アイテム */
  item: TaskApprovalItem;
  /** 承認ハンドラー */
  onApprove: (taskId: number) => void;
  /** 却下ハンドラー */
  onReject: (taskId: number) => void;
  /** タスク詳細表示ハンドラー */
  onViewDetail: (taskId: number) => void;
  /** 承認・却下処理中フラグ */
  isProcessing?: boolean;
}

/**
 * タスク承認カードコンポーネント
 * 
 * @example
 * ```tsx
 * <TaskApprovalCard
 *   item={taskApprovalItem}
 *   onApprove={(id) => handleApprove(id)}
 *   onReject={(id) => handleReject(id)}
 *   onViewDetail={(id) => navigation.navigate('TaskDetail', { taskId: id })}
 *   isProcessing={isProcessing}
 * />
 * ```
 */
export const TaskApprovalCard: React.FC<TaskApprovalCardProps> = ({
  item,
  onApprove,
  onReject,
  onViewDetail,
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

  /**
   * 期限をフォーマット
   */
  const formatDueDate = (dateString: string | null): string => {
    if (!dateString) return 'なし';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
      }).replace(/\//g, '/');
    } catch {
      return dateString;
    }
  };

  return (
    <TouchableOpacity
      style={styles.card}
      onPress={() => onViewDetail(item.id)}
      disabled={isProcessing}
      activeOpacity={0.7}
    >
      {/* タイプバッジ */}
      <View style={styles.typeBadge}>
        <Text style={styles.typeBadgeText}>タスク</Text>
      </View>

      {/* タイトル */}
      <Text style={styles.title} numberOfLines={2}>
        {item.title}
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

      {/* 期限 */}
      {item.due_date && (
        <View style={styles.infoRow}>
          <Text style={styles.label}>期限:</Text>
          <Text style={styles.value}>{formatDueDate(item.due_date)}</Text>
        </View>
      )}

      {/* 報酬 */}
      {item.reward !== null && item.reward > 0 && (
        <View style={styles.infoRow}>
          <Text style={styles.label}>報酬:</Text>
          <Text style={styles.valueHighlight}>
            {item.reward.toLocaleString()} トークン
          </Text>
        </View>
      )}

      {/* 画像添付情報 */}
      {item.has_images && (
        <View style={styles.infoRow}>
          <Text style={styles.label}>画像:</Text>
          <Text style={styles.value}>{item.images_count}枚添付済み</Text>
        </View>
      )}

      {/* 説明（あれば） */}
      {item.description && (
        <Text style={styles.description} numberOfLines={2}>
          {item.description}
        </Text>
      )}

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
    </TouchableOpacity>
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
      backgroundColor: '#007bff',
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
    description: {
      fontSize: getFontSize(13, width, theme),
      color: '#666',
      marginTop: spacing,
      marginBottom: spacing * 1.5,
      lineHeight: getFontSize(18, width, theme),
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

export default TaskApprovalCard;
