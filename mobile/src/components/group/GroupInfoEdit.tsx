/**
 * GroupInfoEdit - グループ基本情報編集コンポーネント
 * 
 * 機能:
 * - グループ名の編集
 * - リアルタイムバリデーション（Web版同等）
 * - レスポンシブ対応
 * 
 * 使用箇所: GroupManagementScreen
 */

import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  StyleSheet,
} from 'react-native';
import { useTheme } from '../../contexts/ThemeContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { useThemedColors } from '../../hooks/useThemedColors';

interface GroupInfoEditProps {
  groupId: number;
  initialName: string;
  onUpdateSuccess: (newName: string) => void;
  onUpdateError: (error: string) => void;
}

/**
 * GroupInfoEdit コンポーネント
 */
export const GroupInfoEdit: React.FC<GroupInfoEditProps> = ({
  groupId: _groupId, // 将来的に使用する可能性があるため保持
  initialName,
  onUpdateSuccess,
  onUpdateError,
}) => {
  const { theme } = useTheme();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();

  const [groupName, setGroupName] = useState(initialName);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [validationMessage, setValidationMessage] = useState<string | null>(null);
  const [hasChanges, setHasChanges] = useState(false);

  // スタイル生成
  const styles = React.useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  /**
   * グループ名変更時のバリデーション
   */
  useEffect(() => {
    // 変更がない場合はスキップ
    if (groupName === initialName) {
      setHasChanges(false);
      setValidationMessage(null);
      return;
    }

    setHasChanges(true);

    // 空文字チェック
    if (!groupName || groupName.trim().length === 0) {
      setValidationMessage(
        theme === 'child' ? 'グループめいをいれてね' : 'グループ名を入力してください'
      );
      return;
    }

    // 文字数チェック
    if (groupName.length > 255) {
      setValidationMessage(
        theme === 'child'
          ? 'グループめいはみじかくしてね（255もじまで）'
          : 'グループ名は255文字以内で入力してください'
      );
      return;
    }

    // バリデーション成功
    setValidationMessage(null);
  }, [groupName, initialName, theme]);

  /**
   * グループ名更新処理
   */
  const handleUpdate = async () => {
    if (!hasChanges || validationMessage || isSubmitting) {
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await fetch(`${process.env.EXPO_PUBLIC_API_URL}/api/groups`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({ name: groupName.trim() }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'グループ情報の更新に失敗しました。');
      }

      onUpdateSuccess(groupName.trim());
      setHasChanges(false);
    } catch (error: any) {
      console.error('[GroupInfoEdit] Update error:', error);
      onUpdateError(error.message || 'グループ情報の更新に失敗しました。');
    } finally {
      setIsSubmitting(false);
    }
  };

  /**
   * 保存ボタンの有効/無効判定
   */
  const isSaveDisabled = !hasChanges || !!validationMessage || isSubmitting;

  return (
    <View style={styles.container}>
      {/* ヘッダー */}
      <View style={styles.header}>
        <Text style={styles.title}>
          {theme === 'child' ? 'グループじょうほう' : 'グループ情報'}
        </Text>
        <Text style={styles.description}>
          {theme === 'child'
            ? 'グループめいをかえられるよ'
            : 'グループ名を更新できます'}
        </Text>
      </View>

      {/* グループ名入力 */}
      <View style={styles.inputContainer}>
        <Text style={styles.label}>
          {theme === 'child' ? 'グループめい' : 'グループ名'}
        </Text>
        <TextInput
          style={[
            styles.input,
            validationMessage && hasChanges ? styles.inputError : null,
          ]}
          value={groupName}
          onChangeText={setGroupName}
          placeholder={
            theme === 'child'
              ? 'グループめいをいれてね'
              : 'グループ名を入力してください'
          }
          placeholderTextColor="#94a3b8"
          maxLength={255}
          editable={!isSubmitting}
          autoCapitalize="none"
        />

        {/* バリデーションメッセージ */}
        {hasChanges && validationMessage && (
          <Text style={styles.validationError}>{validationMessage}</Text>
        )}
      </View>

      {/* 保存ボタン */}
      <TouchableOpacity
        style={[styles.saveButton, isSaveDisabled && styles.saveButtonDisabled]}
        onPress={handleUpdate}
        disabled={isSaveDisabled}
        accessibilityLabel={theme === 'child' ? 'ほぞん' : '保存'}
      >
        {isSubmitting ? (
          <ActivityIndicator size="small" color="#ffffff" />
        ) : (
          <Text style={styles.saveButtonText}>
            {theme === 'child' ? 'ほぞん' : '保存'}
          </Text>
        )}
      </TouchableOpacity>
    </View>
  );
};

/**
 * レスポンシブスタイル生成関数
 */
const createStyles = (
  width: number,
  theme: 'adult' | 'child',
  colors: ReturnType<typeof useThemedColors>['colors'],
  accent: ReturnType<typeof useThemedColors>['accent']
) =>
  StyleSheet.create({
    container: {
      backgroundColor: colors.card,
      borderRadius: getBorderRadius(12, width),
      padding: getSpacing(16, width),
      marginBottom: getSpacing(16, width),
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.1,
      shadowRadius: 8,
      elevation: 3,
    },
    header: {
      marginBottom: getSpacing(16, width),
    },
    title: {
      fontSize: getFontSize(18, width, theme),
      fontWeight: '600',
      color: colors.text.primary,
      marginBottom: getSpacing(4, width),
    },
    description: {
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
      lineHeight: getFontSize(20, width, theme),
    },
    inputContainer: {
      marginBottom: getSpacing(16, width),
    },
    label: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '500',
      color: colors.text.secondary,
      marginBottom: getSpacing(8, width),
    },
    input: {
      backgroundColor: colors.card,
      borderWidth: 1,
      borderColor: colors.border.default,
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(16, width),
      fontSize: getFontSize(16, width, theme),
      color: colors.text.primary,
    },
    inputError: {
      borderColor: '#ef4444',
      backgroundColor: '#fef2f2',
    },
    validationError: {
      fontSize: getFontSize(12, width, theme),
      color: '#ef4444',
      marginTop: getSpacing(4, width),
    },
    validatingContainer: {
      flexDirection: 'row',
      alignItems: 'center',
      marginTop: getSpacing(8, width),
    },
    validatingText: {
      fontSize: getFontSize(12, width, theme),
      color: accent.primary,
      marginLeft: getSpacing(8, width),
    },
    saveButton: {
      backgroundColor: accent.primary,
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(24, width),
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: 48,
    },
    saveButtonDisabled: {
      backgroundColor: colors.border.default,
      opacity: 0.6,
    },
    saveButtonText: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: '#ffffff',
    },
  });

export default GroupInfoEdit;
