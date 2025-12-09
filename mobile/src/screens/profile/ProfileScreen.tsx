/**
 * ProfileScreen - プロフィール画面
 * 
 * 機能:
 * - プロフィール情報表示（ユーザー名、email、表示名）
 * - プロフィール編集（テキスト情報のみ）
 * - パスワード変更（別画面へ遷移）
 * - アカウント削除（確認ダイアログ付き）
 * - テーマ対応UI（adult/child）
 */

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  RefreshControl,
  StyleSheet,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useProfile } from '../../hooks/useProfile';
import { useTheme } from '../../contexts/ThemeContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * ProfileScreen コンポーネント
 */
export const ProfileScreen: React.FC = () => {
  const navigation = useNavigation();
  const { theme } = useTheme();
  const {
    profile,
    isLoading,
    error,
    getProfile,
    updateProfile,
    deleteProfile,
  } = useProfile(theme);

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  const [isEditing, setIsEditing] = useState(false);
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [name, setName] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  /**
   * Pull-to-Refresh処理
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await getProfile();
    } finally {
      setRefreshing(false);
    }
  }, [getProfile]);

  // 初回プロフィール取得
  useEffect(() => {
    loadProfile();
  }, []);

  // プロフィール情報をフォームにセット
  useEffect(() => {
    if (profile) {
      setUsername(profile.username || '');
      setEmail(profile.email || '');
      setName(profile.name || '');
    }
  }, [profile]);

  /**
   * プロフィール読み込み
   */
  const loadProfile = async () => {
    try {
      await getProfile();
    } catch (err) {
      console.error('Failed to load profile', err);
    }
  };

  /**
   * プロフィール保存
   */
  const handleSave = async () => {
    if (!username.trim() || !email.trim()) {
      Alert.alert(
        theme === 'child' ? 'にゅうりょくエラー' : '入力エラー',
        theme === 'child'
          ? 'なまえとメールをいれてね'
          : 'ユーザー名とメールアドレスは必須です',
      );
      return;
    }

    try {
      await updateProfile({
        username: username.trim(),
        email: email.trim(),
        name: name.trim(),
      });

      setIsEditing(false);
      Alert.alert(
        theme === 'child' ? 'ほぞんしたよ' : '保存完了',
        theme === 'child'
          ? 'じぶんのじょうほうをほぞんしたよ'
          : 'プロフィールを更新しました',
      );
    } catch (err) {
      console.error('Failed to update profile', err);
      // エラーは useProfile Hook 内で error ステートにセット済み
    }
  };

  /**
   * 編集キャンセル
   */
  const handleCancel = () => {
    if (profile) {
      setUsername(profile.username || '');
      setEmail(profile.email || '');
      setName(profile.name || '');
    }
    setIsEditing(false);
  };

  /**
   * アカウント削除
   */
  const handleDelete = () => {
    Alert.alert(
      theme === 'child' ? 'ほんとうにけすの？' : 'アカウント削除確認',
      theme === 'child'
        ? 'アカウントをけすと、もとにもどせないよ。ほんとうにけしてもいい？'
        : 'アカウントを削除すると、全てのデータが失われます。本当に削除しますか？',
      [
        {
          text: theme === 'child' ? 'やめる' : 'キャンセル',
          style: 'cancel',
        },
        {
          text: theme === 'child' ? 'けす' : '削除する',
          style: 'destructive',
          onPress: async () => {
            try {
              await deleteProfile();
              Alert.alert(
                theme === 'child' ? 'けしたよ' : '削除完了',
                theme === 'child'
                  ? 'アカウントをけしたよ'
                  : 'アカウントを削除しました',
              );
              // ログアウト処理は別途実装（AuthContext経由）
            } catch (err) {
              console.error('Failed to delete profile', err);
            }
          },
        },
      ],
    );
  };

  if (isLoading && !profile) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3b82f6" />
        <Text style={styles.loadingText}>
          {theme === 'child' ? 'よみこみちゅう...' : '読み込み中...'}
        </Text>
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={onRefresh}
          colors={['#4F46E5']}
          tintColor="#4F46E5"
        />
      }
    >
      <View style={styles.content}>
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={styles.title}>
            {theme === 'child' ? 'じぶんのじょうほう' : 'プロフィール'}
          </Text>
          {!isEditing && (
            <TouchableOpacity
              style={styles.editButton}
              onPress={() => setIsEditing(true)}
              accessibilityLabel={theme === 'child' ? 'へんしゅうする' : '編集する'}
            >
              <Text style={styles.editButtonText}>
                {theme === 'child' ? 'へんしゅう' : '編集'}
              </Text>
            </TouchableOpacity>
          )}
        </View>

        {/* エラー表示 */}
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
          </View>
        )}

        {/* フォーム */}
        <View style={styles.form}>
          {/* ユーザー名 */}
          <View style={styles.fieldGroup}>
            <Text style={styles.label}>
              {theme === 'child' ? 'ユーザーめい' : 'ユーザー名'}
            </Text>
            <TextInput
              style={[styles.input, !isEditing && styles.inputDisabled]}
              value={username}
              onChangeText={setUsername}
              editable={isEditing}
              placeholder={theme === 'child' ? 'なまえをいれてね' : 'ユーザー名を入力'}
              accessibilityLabel="ユーザー名"
            />
          </View>

          {/* メールアドレス */}
          <View style={styles.fieldGroup}>
            <Text style={styles.label}>
              {theme === 'child' ? 'メールアドレス' : 'メールアドレス'}
            </Text>
            <TextInput
              style={[styles.input, !isEditing && styles.inputDisabled]}
              value={email}
              onChangeText={setEmail}
              editable={isEditing}
              keyboardType="email-address"
              autoCapitalize="none"
              placeholder={theme === 'child' ? 'メールをいれてね' : 'メールアドレスを入力'}
              accessibilityLabel="メールアドレス"
            />
          </View>

          {/* 表示名 */}
          <View style={styles.fieldGroup}>
            <Text style={styles.label}>
              {theme === 'child' ? 'ひょうじめい' : '表示名'}
            </Text>
            <TextInput
              style={[styles.input, !isEditing && styles.inputDisabled]}
              value={name}
              onChangeText={setName}
              editable={isEditing}
              placeholder={theme === 'child' ? 'よびかた' : '表示名'}
              accessibilityLabel="表示名"
            />
          </View>
        </View>

        {/* 編集モード時のボタン */}
        {isEditing && (
          <View style={styles.buttonGroup}>
            <TouchableOpacity
              style={[styles.button, styles.cancelButton]}
              onPress={handleCancel}
              disabled={isLoading}
              accessibilityLabel="キャンセル"
            >
              <Text style={styles.buttonText}>
                {theme === 'child' ? 'やめる' : 'キャンセル'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.button, styles.saveButton]}
              onPress={handleSave}
              disabled={isLoading}
              accessibilityLabel="保存"
            >
              {isLoading ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <Text style={[styles.buttonText, styles.saveButtonText]}>
                  {theme === 'child' ? 'ほぞんする' : '保存'}
                </Text>
              )}
            </TouchableOpacity>
          </View>
        )}

        {/* アカウント削除ボタン */}
        {!isEditing && (
          <>
            {/* グループ管理ボタン */}
            <TouchableOpacity
              style={styles.groupButton}
              onPress={() => navigation.navigate('GroupManagement' as never)}
              accessibilityLabel="グループ管理"
            >
              <Text style={styles.groupButtonText}>
                {theme === 'child' ? 'グループかんり' : 'グループ管理'}
              </Text>
            </TouchableOpacity>

            {/* パスワード変更ボタン */}
            <TouchableOpacity
              style={styles.passwordButton}
              onPress={() => navigation.navigate('PasswordChange' as never)}
              accessibilityLabel="パスワード変更"
            >
              <Text style={styles.passwordButtonText}>
                {theme === 'child' ? 'パスワードをかえる' : 'パスワードを変更'}
              </Text>
            </TouchableOpacity>

            {/* アカウント削除ボタン */}
            <TouchableOpacity
              style={styles.deleteButton}
              onPress={handleDelete}
              accessibilityLabel="アカウント削除"
            >
              <Text style={styles.deleteButtonText}>
                {theme === 'child' ? 'アカウントをけす' : 'アカウントを削除'}
              </Text>
            </TouchableOpacity>
          </>
        )}
      </View>
    </ScrollView>
  );
};

const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  content: {
    padding: getSpacing(16, width),
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f8fafc',
  },
  loadingText: {
    marginTop: getSpacing(12, width),
    fontSize: getFontSize(16, width, theme),
    color: '#64748b',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: getSpacing(24, width),
  },
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: '#1e293b',
  },
  editButton: {
    paddingHorizontal: getSpacing(16, width),
    paddingVertical: getSpacing(8, width),
    backgroundColor: '#3b82f6',
    borderRadius: getBorderRadius(8, width),
  },
  editButtonText: {
    color: '#fff',
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
  },
  errorContainer: {
    padding: getSpacing(12, width),
    backgroundColor: '#fef2f2',
    borderRadius: getBorderRadius(8, width),
    borderLeftWidth: 4,
    borderLeftColor: '#ef4444',
    marginBottom: getSpacing(16, width),
  },
  errorText: {
    color: '#dc2626',
    fontSize: getFontSize(14, width, theme),
  },
  form: {
    marginBottom: getSpacing(24, width),
  },
  fieldGroup: {
    marginBottom: getSpacing(20, width),
  },
  label: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: '#475569',
    marginBottom: getSpacing(8, width),
  },
  input: {
    padding: getSpacing(12, width),
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: getBorderRadius(8, width),
    fontSize: getFontSize(16, width, theme),
    color: '#1e293b',
  },
  inputDisabled: {
    backgroundColor: '#f8fafc',
    color: '#64748b',
  },
  textArea: {
    height: getSpacing(100, width),
    textAlignVertical: 'top',
  },
  buttonGroup: {
    flexDirection: 'row',
    gap: getSpacing(12, width),
    marginBottom: getSpacing(24, width),
  },
  button: {
    flex: 1,
    paddingVertical: getSpacing(14, width),
    borderRadius: getBorderRadius(8, width),
    alignItems: 'center',
  },
  cancelButton: {
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: '#cbd5e1',
  },
  saveButton: {
    backgroundColor: '#3b82f6',
  },
  buttonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#475569',
  },
  saveButtonText: {
    color: '#fff',
  },
  groupButton: {
    paddingVertical: getSpacing(14, width),
    backgroundColor: '#f0fdf4',
    borderRadius: getBorderRadius(8, width),
    borderWidth: 1,
    borderColor: '#bbf7d0',
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  groupButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#16a34a',
  },
  passwordButton: {
    paddingVertical: getSpacing(14, width),
    backgroundColor: '#f0f9ff',
    borderRadius: getBorderRadius(8, width),
    borderWidth: 1,
    borderColor: '#bae6fd',
    alignItems: 'center',
    marginBottom: getSpacing(12, width),
  },
  passwordButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#0284c7',
  },
  deleteButton: {
    paddingVertical: getSpacing(14, width),
    backgroundColor: '#fef2f2',
    borderRadius: getBorderRadius(8, width),
    borderWidth: 1,
    borderColor: '#fecaca',
    alignItems: 'center',
  },
  deleteButtonText: {
    color: '#dc2626',
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
  },
});

export default ProfileScreen;
