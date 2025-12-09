/**
 * PasswordChangeScreen
 * 
 * パスワード変更画面
 * 
 * Web版: resources/views/profile/partials/update-password-form.blade.php
 * 
 * 機能:
 * - 現在のパスワード入力
 * - 新しいパスワード入力（確認フィールド付き）
 * - バリデーション（8文字以上、確認一致）
 * - パスワード表示切替（目アイコン）
 * - テーマ対応UI（adult/child）
 */

import React, { useState, useMemo } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import useProfile from '../../hooks/useProfile';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * パスワード変更画面コンポーネント
 */
const PasswordChangeScreen: React.FC = () => {
  const navigation = useNavigation();
  const { theme } = useTheme();
  const { updatePassword, isLoading, error } = useProfile(theme);

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  // フォーム状態
  const [currentPassword, setCurrentPassword] = useState<string>('');
  const [newPassword, setNewPassword] = useState<string>('');
  const [confirmPassword, setConfirmPassword] = useState<string>('');

  // パスワード表示状態
  const [showCurrentPassword, setShowCurrentPassword] = useState<boolean>(false);
  const [showNewPassword, setShowNewPassword] = useState<boolean>(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState<boolean>(false);

  // バリデーション状態
  const [errors, setErrors] = useState<{
    currentPassword?: string;
    newPassword?: string;
    confirmPassword?: string;
  }>({});

  /**
   * クライアント側バリデーション
   */
  const validate = (): boolean => {
    const newErrors: typeof errors = {};

    if (!currentPassword) {
      newErrors.currentPassword =
        theme === 'child'
          ? 'いまのパスワードをいれてね'
          : '現在のパスワードを入力してください';
    }

    if (!newPassword) {
      newErrors.newPassword =
        theme === 'child'
          ? 'あたらしいパスワードをいれてね'
          : '新しいパスワードを入力してください';
    } else if (newPassword.length < 8) {
      newErrors.newPassword =
        theme === 'child'
          ? 'パスワードは8もじいじょうにしてね'
          : 'パスワードは8文字以上で入力してください';
    }

    if (!confirmPassword) {
      newErrors.confirmPassword =
        theme === 'child'
          ? 'かくにんようパスワードをいれてね'
          : '確認用パスワードを入力してください';
    } else if (newPassword !== confirmPassword) {
      newErrors.confirmPassword =
        theme === 'child'
          ? 'パスワードがあっていないよ'
          : 'パスワードが一致しません';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  /**
   * パスワード更新処理
   */
  const handleSubmit = async () => {
    // クライアント側バリデーション
    if (!validate()) {
      return;
    }

    try {
      const result = await updatePassword(
        currentPassword,
        newPassword,
        confirmPassword
      );

      // 成功メッセージ表示
      Alert.alert(
        theme === 'child' ? 'せいこう！' : '成功',
        result.message,
        [
          {
            text: 'OK',
            onPress: () => navigation.goBack(),
          },
        ]
      );

      // フォームクリア
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setErrors({});
    } catch (err: any) {
      // エラーメッセージ表示（useProfile内でtheme対応済み）
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        error || (theme === 'child' ? 'しっぱいしちゃった' : '更新に失敗しました')
      );
    }
  };

  // テーマカラー
  const colors = {
    background: theme === 'child' ? '#FFF9E6' : '#FFFFFF',
    text: theme === 'child' ? '#5A4A3A' : '#374151',
    inputBg: theme === 'child' ? '#FFFFFF' : '#F9FAFB',
    inputBorder: theme === 'child' ? '#FFB84D' : '#D1D5DB',
    primary: theme === 'child' ? '#FF6B35' : '#3B82F6',
    error: '#EF4444',
  };

  return (
    <KeyboardAvoidingView
      style={[styles.container, { backgroundColor: colors.background }]}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 100 : 0}
    >
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={[styles.title, { color: colors.text }]}>
            {theme === 'child' ? 'パスワードをかえる' : 'パスワード更新'}
          </Text>
          <Text style={[styles.subtitle, { color: colors.text }]}>
            {theme === 'child'
              ? 'あたらしいパスワードをいれてね'
              : 'アカウントのセキュリティを保つために、長くランダムなパスワードを使用してください。'}
          </Text>
        </View>

        {/* 現在のパスワード */}
        <View style={styles.fieldGroup}>
          <Text style={[styles.label, { color: colors.text }]}>
            {theme === 'child' ? 'いまのパスワード' : '現在のパスワード'}
          </Text>
          <View style={styles.inputWrapper}>
            <TextInput
              style={[
                styles.input,
                {
                  backgroundColor: colors.inputBg,
                  borderColor: errors.currentPassword ? colors.error : colors.inputBorder,
                  color: colors.text,
                },
              ]}
              value={currentPassword}
              onChangeText={(text) => {
                setCurrentPassword(text);
                if (errors.currentPassword) {
                  setErrors({ ...errors, currentPassword: undefined });
                }
              }}
              placeholder={
                theme === 'child' ? 'いまのパスワード' : '現在のパスワード'
              }
              placeholderTextColor="#9CA3AF"
              secureTextEntry={!showCurrentPassword}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TouchableOpacity
              style={styles.eyeIcon}
              onPress={() => setShowCurrentPassword(!showCurrentPassword)}
            >
              <Text style={styles.eyeIconText}>
                {showCurrentPassword ? '🙈' : '👁️'}
              </Text>
            </TouchableOpacity>
          </View>
          {errors.currentPassword && (
            <Text style={[styles.errorText, { color: colors.error }]}>
              {errors.currentPassword}
            </Text>
          )}
        </View>

        {/* 新しいパスワード */}
        <View style={styles.fieldGroup}>
          <Text style={[styles.label, { color: colors.text }]}>
            {theme === 'child' ? 'あたらしいパスワード' : '新規パスワード'}
          </Text>
          <View style={styles.inputWrapper}>
            <TextInput
              style={[
                styles.input,
                {
                  backgroundColor: colors.inputBg,
                  borderColor: errors.newPassword ? colors.error : colors.inputBorder,
                  color: colors.text,
                },
              ]}
              value={newPassword}
              onChangeText={(text) => {
                setNewPassword(text);
                if (errors.newPassword) {
                  setErrors({ ...errors, newPassword: undefined });
                }
              }}
              placeholder={
                theme === 'child'
                  ? 'あたらしいパスワード（8もじいじょう）'
                  : '新しいパスワード（8文字以上）'
              }
              placeholderTextColor="#9CA3AF"
              secureTextEntry={!showNewPassword}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TouchableOpacity
              style={styles.eyeIcon}
              onPress={() => setShowNewPassword(!showNewPassword)}
            >
              <Text style={styles.eyeIconText}>
                {showNewPassword ? '🙈' : '👁️'}
              </Text>
            </TouchableOpacity>
          </View>
          {errors.newPassword && (
            <Text style={[styles.errorText, { color: colors.error }]}>
              {errors.newPassword}
            </Text>
          )}
        </View>

        {/* 確認用パスワード */}
        <View style={styles.fieldGroup}>
          <Text style={[styles.label, { color: colors.text }]}>
            {theme === 'child' ? 'かくにんよう' : '確認用'}
          </Text>
          <View style={styles.inputWrapper}>
            <TextInput
              style={[
                styles.input,
                {
                  backgroundColor: colors.inputBg,
                  borderColor: errors.confirmPassword ? colors.error : colors.inputBorder,
                  color: colors.text,
                },
              ]}
              value={confirmPassword}
              onChangeText={(text) => {
                setConfirmPassword(text);
                if (errors.confirmPassword) {
                  setErrors({ ...errors, confirmPassword: undefined });
                }
              }}
              placeholder={
                theme === 'child'
                  ? 'もういちどパスワードをいれてね'
                  : '新しいパスワード（確認）'
              }
              placeholderTextColor="#9CA3AF"
              secureTextEntry={!showConfirmPassword}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TouchableOpacity
              style={styles.eyeIcon}
              onPress={() => setShowConfirmPassword(!showConfirmPassword)}
            >
              <Text style={styles.eyeIconText}>
                {showConfirmPassword ? '🙈' : '👁️'}
              </Text>
            </TouchableOpacity>
          </View>
          {errors.confirmPassword && (
            <Text style={[styles.errorText, { color: colors.error }]}>
              {errors.confirmPassword}
            </Text>
          )}
        </View>

        {/* 保存ボタン */}
        <TouchableOpacity
          style={[
            styles.submitButton,
            { backgroundColor: colors.primary },
            isLoading && styles.submitButtonDisabled,
          ]}
          onPress={handleSubmit}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator color="#FFFFFF" />
          ) : (
            <Text style={styles.submitButtonText}>
              {theme === 'child' ? 'ほぞん' : '保存'}
            </Text>
          )}
        </TouchableOpacity>

        {/* キャンセルボタン */}
        <TouchableOpacity
          style={styles.cancelButton}
          onPress={() => navigation.goBack()}
          disabled={isLoading}
        >
          <Text style={[styles.cancelButtonText, { color: colors.text }]}>
            {theme === 'child' ? 'もどる' : 'キャンセル'}
          </Text>
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
  );
};

const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    padding: getSpacing(20, width),
  },
  header: {
    marginBottom: getSpacing(24, width),
  },
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    marginBottom: getSpacing(8, width),
  },
  subtitle: {
    fontSize: getFontSize(14, width, theme),
    lineHeight: getFontSize(20, width, theme),
  },
  fieldGroup: {
    marginBottom: getSpacing(20, width),
  },
  label: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    marginBottom: getSpacing(8, width),
  },
  inputWrapper: {
    position: 'relative',
  },
  input: {
    height: getSpacing(48, width),
    borderWidth: 1,
    borderRadius: getBorderRadius(8, width),
    paddingHorizontal: getSpacing(12, width),
    paddingRight: getSpacing(48, width),
    fontSize: getFontSize(16, width, theme),
  },
  eyeIcon: {
    position: 'absolute',
    right: getSpacing(12, width),
    top: getSpacing(12, width),
    padding: getSpacing(4, width),
  },
  eyeIconText: {
    fontSize: getFontSize(20, width, theme),
  },
  errorText: {
    fontSize: getFontSize(12, width, theme),
    marginTop: getSpacing(4, width),
  },
  submitButton: {
    height: getSpacing(48, width),
    borderRadius: getBorderRadius(8, width),
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: getSpacing(8, width),
  },
  submitButtonDisabled: {
    opacity: 0.6,
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
  },
  cancelButton: {
    height: getSpacing(48, width),
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: getSpacing(12, width),
  },
  cancelButtonText: {
    fontSize: getFontSize(16, width, theme),
  },
});

export default PasswordChangeScreen;
