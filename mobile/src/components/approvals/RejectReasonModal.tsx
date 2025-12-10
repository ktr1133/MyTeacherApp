/**
 * 却下理由入力モーダル
 * 
 * タスク承認・トークン購入申請の却下理由を入力するモーダル
 * 
 * 参照:
 * - 要件定義: /home/ktr/mtdev/definitions/mobile/PendingApprovalsScreen.md
 * - レスポンシブ設計: /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 */

import React, { useState, useMemo } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  Modal,
  StyleSheet,
} from 'react-native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * Props型定義
 */
interface RejectReasonModalProps {
  /** モーダル表示状態 */
  visible: boolean;
  /** 却下対象タイトル（タスク名 or パッケージ名） */
  targetTitle: string;
  /** 却下実行ハンドラー */
  onReject: (reason?: string) => void;
  /** キャンセルハンドラー */
  onCancel: () => void;
  /** 送信中フラグ */
  isSubmitting?: boolean;
}

/**
 * 却下理由入力モーダルコンポーネント
 * 
 * @example
 * ```tsx
 * <RejectReasonModal
 *   visible={showRejectModal}
 *   targetTitle="部屋の掃除"
 *   onReject={(reason) => handleReject(taskId, reason)}
 *   onCancel={() => setShowRejectModal(false)}
 *   isSubmitting={isSubmitting}
 * />
 * ```
 */
export const RejectReasonModal: React.FC<RejectReasonModalProps> = ({
  visible,
  targetTitle,
  onReject,
  onCancel,
  isSubmitting = false,
}) => {
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const [reason, setReason] = useState('');

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  /**
   * 却下実行
   */
  const handleReject = () => {
    onReject(reason.trim() || undefined);
    setReason(''); // 入力をクリア
  };

  /**
   * キャンセル
   */
  const handleCancel = () => {
    setReason(''); // 入力をクリア
    onCancel();
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={handleCancel}
    >
      <View style={styles.overlay}>
        <View style={styles.modalContent}>
          {/* ヘッダー */}
          <View style={styles.header}>
            <Text style={styles.title}>却下理由の入力</Text>
          </View>

          {/* 対象タイトル */}
          <Text style={styles.targetText}>
            「{targetTitle}」を却下します
          </Text>

          {/* 却下理由入力 */}
          <TextInput
            style={styles.input}
            placeholder="却下理由を入力してください...（任意）"
            placeholderTextColor="#999"
            value={reason}
            onChangeText={setReason}
            multiline
            numberOfLines={4}
            textAlignVertical="top"
            editable={!isSubmitting}
          />

          {/* ボタンエリア */}
          <View style={styles.buttonContainer}>
            <TouchableOpacity
              style={[styles.button, styles.cancelButton]}
              onPress={handleCancel}
              disabled={isSubmitting}
            >
              <Text style={styles.cancelButtonText}>キャンセル</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.button, styles.rejectButton, isSubmitting && styles.buttonDisabled]}
              onPress={handleReject}
              disabled={isSubmitting}
            >
              <Text style={styles.rejectButtonText}>
                {isSubmitting ? '送信中...' : '却下する'}
              </Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
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

  // モーダル幅の決定（タブレットでは固定幅）
  const modalWidth = width >= 768 ? 400 : width * 0.9;

  return StyleSheet.create({
    overlay: {
      flex: 1,
      backgroundColor: 'rgba(0, 0, 0, 0.5)',
      justifyContent: 'center',
      alignItems: 'center',
      padding: spacing * 2,
    },
    modalContent: {
      width: modalWidth,
      maxWidth: 500,
      backgroundColor: '#fff',
      borderRadius: borderRadius * 2,
      padding: spacing * 3,
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.25,
      shadowRadius: 4,
      elevation: 5,
    },
    header: {
      marginBottom: spacing * 2,
    },
    title: {
      fontSize: getFontSize(18, width, theme),
      fontWeight: '600',
      color: '#333',
      textAlign: 'center',
    },
    targetText: {
      fontSize: getFontSize(14, width, theme),
      color: '#666',
      textAlign: 'center',
      marginBottom: spacing * 2,
    },
    input: {
      borderWidth: 1,
      borderColor: '#ddd',
      borderRadius: borderRadius,
      padding: spacing * 1.5,
      fontSize: getFontSize(14, width, theme),
      color: '#333',
      minHeight: 100,
      maxHeight: 150,
      marginBottom: spacing * 3,
      backgroundColor: '#f9f9f9',
    },
    buttonContainer: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      gap: spacing * 1.5,
    },
    button: {
      flex: 1,
      paddingVertical: spacing * 1.5,
      paddingHorizontal: spacing * 2,
      borderRadius: borderRadius,
      alignItems: 'center',
      justifyContent: 'center',
    },
    cancelButton: {
      backgroundColor: '#e0e0e0',
    },
    cancelButtonText: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: '#333',
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

export default RejectReasonModal;
