/**
 * ConfirmDialog - 確認ダイアログコンポーネント
 * 
 * 機能:
 * - メンバー削除、マスター譲渡などの重要アクションの確認
 * - レスポンシブ対応
 * - 子どもテーマ対応
 */

import React from 'react';
import {
  Modal,
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
} from 'react-native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';

interface ConfirmDialogProps {
  visible: boolean;
  title: string;
  message: string;
  confirmText: string;
  cancelText: string;
  onConfirm: () => void;
  onCancel: () => void;
  isDangerous?: boolean;
}

/**
 * ConfirmDialog コンポーネント
 */
export const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
  visible,
  title,
  message,
  confirmText,
  cancelText,
  onConfirm,
  onCancel,
  isDangerous = false,
}) => {
  const { width } = useResponsive();
  const styles = React.useMemo(() => createStyles(width, isDangerous), [width, isDangerous]);

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onCancel}
    >
      <View style={styles.overlay}>
        <View style={styles.dialog}>
          <Text style={styles.title}>{title}</Text>
          <Text style={styles.message}>{message}</Text>
          <View style={styles.buttonContainer}>
            <TouchableOpacity
              style={[styles.button, styles.cancelButton]}
              onPress={onCancel}
            >
              <Text style={styles.cancelButtonText}>{cancelText}</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.button, styles.confirmButton]}
              onPress={onConfirm}
            >
              <Text style={styles.confirmButtonText}>{confirmText}</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

const createStyles = (width: number, isDangerous: boolean) =>
  StyleSheet.create({
    overlay: {
      flex: 1,
      backgroundColor: 'rgba(0, 0, 0, 0.5)',
      justifyContent: 'center',
      alignItems: 'center',
      padding: getSpacing(20, width),
    },
    dialog: {
      backgroundColor: '#ffffff',
      borderRadius: getBorderRadius(16, width),
      padding: getSpacing(24, width),
      width: '100%',
      maxWidth: 400,
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 4 },
      shadowOpacity: 0.3,
      shadowRadius: 16,
      elevation: 8,
    },
    title: {
      fontSize: getFontSize(18, width, 'adult'),
      fontWeight: 'bold',
      color: '#1e293b',
      marginBottom: getSpacing(12, width),
    },
    message: {
      fontSize: getFontSize(14, width, 'adult'),
      color: '#64748b',
      lineHeight: getFontSize(20, width, 'adult'),
      marginBottom: getSpacing(24, width),
    },
    buttonContainer: {
      flexDirection: 'row',
      gap: getSpacing(12, width),
    },
    button: {
      flex: 1,
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(16, width),
      borderRadius: getBorderRadius(8, width),
      alignItems: 'center',
      justifyContent: 'center',
    },
    cancelButton: {
      backgroundColor: '#f1f5f9',
      borderWidth: 1,
      borderColor: '#e2e8f0',
    },
    cancelButtonText: {
      fontSize: getFontSize(14, width, 'adult'),
      fontWeight: '600',
      color: '#475569',
    },
    confirmButton: {
      backgroundColor: isDangerous ? '#ef4444' : '#6366f1',
    },
    confirmButtonText: {
      fontSize: getFontSize(14, width, 'adult'),
      fontWeight: '600',
      color: '#ffffff',
    },
  });

export default ConfirmDialog;
