/**
 * SettingsScreen - 設定画面
 * 
 * 機能:
 * - テーマ設定（adult/child切り替え）
 * - タイムゾーン設定
 * - 通知設定（プッシュ通知ON/OFF）
 * - アプリ情報表示（バージョン）
 * - テーマ対応UI（adult/child）
 */

import React, { useState, useEffect, useMemo } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  Alert,
  ActivityIndicator,
  Switch,
  Modal,
  FlatList,
  TextInput,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useAuth } from '../../contexts/AuthContext';
import { useProfile } from '../../hooks/useProfile';
import { useTheme } from '../../contexts/ThemeContext';
import { useColorScheme } from '../../contexts/ColorSchemeContext';
import { useThemedColors } from '../../hooks/useThemedColors';
import { userService } from '../../services/user.service';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { Ionicons, MaterialIcons } from '@expo/vector-icons';

/**
 * SettingsScreen コンポーネント
 */
export const SettingsScreen: React.FC<{ navigation: any }> = ({ navigation }) => {
  const { user } = useAuth();
  const { theme, setTheme } = useTheme();
  const {
    isLoading,
    error,
    getTimezoneSettings,
    updateTimezone,
    updateProfile,
  } = useProfile(theme);
  const { mode: colorSchemeMode, setMode: setColorSchemeMode } = useColorScheme();
  const { colors, accent } = useThemedColors();

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  const [currentTheme, setCurrentTheme] = useState<'adult' | 'child'>(theme);
  const [timezone, setTimezone] = useState('');
  const [timezones, setTimezones] = useState<Array<{ value: string; label: string }>>([]);
  const [notificationsEnabled, setNotificationsEnabled] = useState(false);
  const [isLoadingTimezone, setIsLoadingTimezone] = useState(false);

  // タイムゾーン選択モーダル用state
  const [showTimezoneModal, setShowTimezoneModal] = useState(false);

  // プロフィール編集用state
  const [showProfileModal, setShowProfileModal] = useState(false);
  const [editUsername, setEditUsername] = useState(user?.username || '');
  const [editName, setEditName] = useState(user?.name || '');
  const [editEmail, setEditEmail] = useState(user?.email || '');
  const [isUpdatingProfile, setIsUpdatingProfile] = useState(false);

  // 初回タイムゾーン設定取得
  useEffect(() => {
    loadTimezoneSettings();
    loadNotificationSettings();
  }, []);

  // ユーザー情報更新時にフォームを同期
  useEffect(() => {
    if (user) {
      setEditUsername(user.username || '');
      setEditName(user.name || '');
      setEditEmail(user.email || '');
    }
  }, [user]);

  /**
   * タイムゾーン設定読み込み
   */
  const loadTimezoneSettings = async () => {
    setIsLoadingTimezone(true);
    try {
      const data = await getTimezoneSettings();
      setTimezone(data.timezone);
      setTimezones(data.timezones || []); // 安全性チェック追加
    } catch (err) {
      console.error('Failed to load timezone settings', err);
      // エラー時はデフォルト値を設定
      setTimezone('Asia/Tokyo');
      setTimezones([{ value: 'Asia/Tokyo', label: '東京 (UTC+9)' }]);
    } finally {
      setIsLoadingTimezone(false);
    }
  };

  /**
   * 通知設定読み込み（AsyncStorageから）
   */
  const loadNotificationSettings = async () => {
    // TODO: 通知設定の実装（Phase 2.B-5で実装予定）
    setNotificationsEnabled(false);
  };

  /**
   * プロフィール編集モーダルを開く
   */
  const openProfileModal = () => {
    if (user) {
      setEditUsername(user.username || '');
      setEditName(user.name || '');
      setEditEmail(user.email || '');
      setShowProfileModal(true);
    }
  };

  /**
   * プロフィール情報を更新
   */
  const handleUpdateProfile = async () => {
    if (!editUsername.trim() || !editEmail.trim()) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'ユーザーめいとメールアドレスはひつようだよ'
          : 'ユーザー名とメールアドレスは必須です',
      );
      return;
    }

    setIsUpdatingProfile(true);
    try {
      await updateProfile({
        username: editUsername.trim(),
        name: editName.trim() || undefined,
        email: editEmail.trim(),
      });

      // キャッシュクリア
      await userService.getCurrentUser();

      Alert.alert(
        theme === 'child' ? 'ほぞんしたよ' : '更新完了',
        theme === 'child'
          ? 'プロフィールをこうしんしたよ'
          : 'プロフィール情報を更新しました',
      );
      setShowProfileModal(false);
    } catch (err: any) {
      console.error('Failed to update profile', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        err.message || (theme === 'child'
          ? 'プロフィールのこうしんができなかったよ'
          : 'プロフィールの更新に失敗しました'),
      );
    } finally {
      setIsUpdatingProfile(false);
    }
  };

  /**
   * テーマ変更
   */
  const handleThemeChange = async (newTheme: 'adult' | 'child') => {
    setCurrentTheme(newTheme);
    setTheme(newTheme);

    // Laravel APIでユーザーのテーマを更新（ProfileService経由）
    try {
      await updateProfile({ theme: newTheme });
      
      // キャッシュクリア
      await userService.getCurrentUser();
      
      Alert.alert(
        newTheme === 'child' ? 'きりかえたよ' : 'テーマ変更完了',
        newTheme === 'child'
          ? 'こどもモードにきりかえたよ'
          : '大人モードに切り替えました',
      );
    } catch (err) {
      console.error('Failed to update theme', err);
      Alert.alert(
        theme === 'child' ? 'エラーがおきたよ' : 'エラー',
        theme === 'child'
          ? 'テーマのへんこうができなかったよ'
          : 'テーマの変更に失敗しました',
      );
      // エラー時は元に戻す
      setCurrentTheme(theme);
    }
  };

  /**
   * タイムゾーン変更
   */
  const handleTimezoneChange = async (newTimezone: string) => {
    setTimezone(newTimezone);

    try {
      await updateTimezone(newTimezone);
      Alert.alert(
        theme === 'child' ? 'へんこうしたよ' : '更新完了',
        theme === 'child'
          ? 'じかんのせっていをかえたよ'
          : 'タイムゾーンを更新しました',
      );
    } catch (err) {
      console.error('Failed to update timezone', err);
      // エラーは useProfile Hook 内で error ステートにセット済み
    }
  };

  /**
   * 通知設定変更
   */
  const handleNotificationToggle = async (enabled: boolean) => {
    setNotificationsEnabled(enabled);
    // TODO: 通知設定の保存（Phase 2.B-5で実装予定）
    Alert.alert(
      theme === 'child' ? 'せっていしたよ' : '設定変更',
      enabled
        ? (theme === 'child' ? 'つうちをONにしたよ' : '通知を有効にしました')
        : (theme === 'child' ? 'つうちをOFFにしたよ' : '通知を無効にしました'),
    );
  };

  /**
   * カラースキーマ変更
   */
  const handleColorSchemeChange = async (newMode: 'light' | 'dark' | 'auto') => {
    await setColorSchemeMode(newMode);
    const modeLabel = newMode === 'light' ? 'ライト' : newMode === 'dark' ? 'ダーク' : '自動';
    Alert.alert(
      theme === 'child' ? 'きりかえたよ' : 'カラーモード変更',
      theme === 'child'
        ? `${modeLabel}モードにきりかえたよ`
        : `${modeLabel}モードに切り替えました`,
    );
  };

  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        {/* エラー表示 */}
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
          </View>
        )}

        {/* プロフィール情報 */}
        <View style={styles.card}>
          <LinearGradient
            colors={['rgba(59, 130, 246, 0.05)', 'rgba(147, 51, 234, 0.05)']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.cardHeader}
          >
            <View style={styles.cardHeaderIcon}>
              <LinearGradient
                colors={['#3b82f6', '#9333ea']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.iconGradient}
              >
                <Ionicons name="person-outline" size={16} color="#fff" />
              </LinearGradient>
            </View>
            <Text style={styles.cardTitle}>
              {theme === 'child' ? 'プロフィール' : 'プロフィール情報'}
            </Text>
          </LinearGradient>
          <View style={styles.cardContent}>
            <Text style={styles.sectionDescription}>
              {theme === 'child'
                ? 'あなたのじょうほうだよ'
                : 'アカウント情報を確認・編集できます'}
            </Text>

            {user && (
              <View style={styles.profileInfo}>
                <View style={styles.profileRow}>
                  <Text style={styles.profileLabel}>
                    {theme === 'child' ? 'ユーザーめい' : 'ユーザー名'}
                  </Text>
                  <Text style={styles.profileValue}>{user.username}</Text>
                </View>

                {user.name && (
                  <View style={styles.profileRow}>
                    <Text style={styles.profileLabel}>
                      {theme === 'child' ? 'なまえ' : '表示名'}
                    </Text>
                    <Text style={styles.profileValue}>{user.name}</Text>
                  </View>
                )}

                <View style={styles.profileRow}>
                  <Text style={styles.profileLabel}>
                    {theme === 'child' ? 'メールアドレス' : 'メールアドレス'}
                  </Text>
                  <Text style={styles.profileValue}>{user.email}</Text>
                </View>
              </View>
            )}

            <TouchableOpacity
              style={styles.editButton}
              onPress={openProfileModal}
              disabled={isLoading}
            >
              <LinearGradient
                colors={['#3b82f6', '#9333ea']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.editButtonGradient}
              >
                <Ionicons name="create-outline" size={18} color="#fff" />
                <Text style={styles.editButtonText}>
                  {theme === 'child' ? 'へんこうする' : '編集する'}
                </Text>
              </LinearGradient>
            </TouchableOpacity>
          </View>
        </View>

        {/* テーマ設定 */}
        <View style={styles.card}>
          <LinearGradient
            colors={['rgba(59, 130, 246, 0.05)', 'rgba(147, 51, 234, 0.05)']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.cardHeader}
          >
            <View style={styles.cardHeaderIcon}>
              <LinearGradient
                colors={['#3b82f6', '#9333ea']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.iconGradient}
              >
                <Ionicons name="color-palette-outline" size={16} color="#fff" />
              </LinearGradient>
            </View>
            <Text style={styles.cardTitle}>
              {theme === 'child' ? 'がめんのモード' : 'テーマ設定'}
            </Text>
          </LinearGradient>
          <View style={styles.cardContent}>
            <Text style={styles.sectionDescription}>
              {theme === 'child'
                ? 'おとなモードとこどもモードをきりかえられるよ'
                : '大人モードと子供モードを切り替えます'}
            </Text>

            <View style={styles.themeButtons}>
              <TouchableOpacity
                style={[
                  styles.themeButton,
                  currentTheme === 'adult' && styles.themeButtonActive,
                ]}
                onPress={() => handleThemeChange('adult')}
                accessibilityLabel="大人モード"
              >
                <Text
                  style={[
                    styles.themeButtonText,
                    currentTheme === 'adult' && styles.themeButtonTextActive,
                  ]}
                >
                  {theme === 'child' ? 'おとなモード' : '大人モード'}
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.themeButton,
                  currentTheme === 'child' && styles.themeButtonActive,
                ]}
                onPress={() => handleThemeChange('child')}
                accessibilityLabel="子供モード"
              >
                <Text
                  style={[
                    styles.themeButtonText,
                    currentTheme === 'child' && styles.themeButtonTextActive,
                  ]}
                >
                  {theme === 'child' ? 'こどもモード' : '子供モード'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>

        {/* カラースキーマ設定 */}
        <View style={styles.card}>
          <LinearGradient
            colors={['rgba(59, 130, 246, 0.05)', 'rgba(147, 51, 234, 0.05)']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.cardHeader}
          >
            <View style={styles.cardHeaderIcon}>
              <LinearGradient
                colors={['#3b82f6', '#9333ea']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.iconGradient}
              >
                <Ionicons name="moon-outline" size={16} color="#fff" />
              </LinearGradient>
            </View>
            <Text style={styles.cardTitle}>
              {theme === 'child' ? 'がめんのあかるさ' : 'カラーモード'}
            </Text>
          </LinearGradient>
          <View style={styles.cardContent}>
            <Text style={styles.sectionDescription}>
              {theme === 'child'
                ? 'ライトモード、ダークモード、じどうをえらべるよ'
                : 'ライトモード、ダークモード、または自動を選択します'}
            </Text>

            <View style={styles.colorSchemeButtons}>
              <TouchableOpacity
                style={[
                  styles.colorSchemeButton,
                  colorSchemeMode === 'light' && styles.colorSchemeButtonActive,
                ]}
                onPress={() => handleColorSchemeChange('light')}
                accessibilityLabel="ライトモード"
              >
                <Ionicons 
                  name="sunny" 
                  size={20} 
                  color={colorSchemeMode === 'light' ? '#FFFFFF' : colors.text.secondary}
                  style={styles.colorSchemeIcon}
                />
                <Text
                  style={[
                    styles.colorSchemeButtonText,
                    colorSchemeMode === 'light' && styles.colorSchemeButtonTextActive,
                  ]}
                >
                  {theme === 'child' ? 'ライト' : 'ライト'}
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.colorSchemeButton,
                  colorSchemeMode === 'dark' && styles.colorSchemeButtonActive,
                ]}
                onPress={() => handleColorSchemeChange('dark')}
                accessibilityLabel="ダークモード"
              >
                <Ionicons 
                  name="moon" 
                  size={20} 
                  color={colorSchemeMode === 'dark' ? '#FFFFFF' : colors.text.secondary}
                  style={styles.colorSchemeIcon}
                />
                <Text
                  style={[
                    styles.colorSchemeButtonText,
                    colorSchemeMode === 'dark' && styles.colorSchemeButtonTextActive,
                  ]}
                >
                  {theme === 'child' ? 'ダーク' : 'ダーク'}
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.colorSchemeButton,
                  colorSchemeMode === 'auto' && styles.colorSchemeButtonActive,
                ]}
                onPress={() => handleColorSchemeChange('auto')}
                accessibilityLabel="自動"
              >
                <Ionicons 
                  name="phone-portrait-outline" 
                  size={20} 
                  color={colorSchemeMode === 'auto' ? '#FFFFFF' : colors.text.secondary}
                  style={styles.colorSchemeIcon}
                />
                <Text
                  style={[
                    styles.colorSchemeButtonText,
                    colorSchemeMode === 'auto' && styles.colorSchemeButtonTextActive,
                  ]}
                >
                  {theme === 'child' ? 'じどう' : '自動'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>

        {/* タイムゾーン設定 */}
        <View style={styles.card}>
          <LinearGradient
            colors={['rgba(20, 184, 166, 0.05)', 'rgba(6, 182, 212, 0.05)']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.cardHeader}
          >
            <View style={styles.cardHeaderIcon}>
              <LinearGradient
                colors={['#14b8a6', '#06b6d4']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.iconGradient}
              >
                <Ionicons name="globe-outline" size={16} color="#fff" />
              </LinearGradient>
            </View>
            <Text style={styles.cardTitle}>
              {theme === 'child' ? 'じかんのせってい' : 'タイムゾーン'}
            </Text>
          </LinearGradient>
          <View style={styles.cardContent}>
            <Text style={styles.sectionDescription}>
              {theme === 'child'
                ? 'あなたのすんでいるばしょのじかんをせっていするよ'
                : 'お住まいの地域のタイムゾーンを設定します'}
            </Text>

            {isLoadingTimezone ? (
              <ActivityIndicator size="small" color="#3b82f6" />
            ) : (
              <TouchableOpacity
                style={styles.timezoneButton}
                onPress={() => setShowTimezoneModal(true)}
                disabled={isLoading}
              >
                <Text style={styles.timezoneButtonText}>
                  {timezone
                    ? timezones.find((tz) => tz.value === timezone)?.label || timezone
                    : theme === 'child'
                    ? 'タイムゾーンをえらぶ'
                    : 'タイムゾーンを選択'}
                </Text>
                <MaterialIcons name="arrow-drop-down" size={24} color="#64748b" />
              </TouchableOpacity>
            )}
          </View>
        </View>

        {/* グループ管理 (子どもユーザー以外) */}
        {user && user.theme !== 'child' && (
          <View style={styles.card}>
            <LinearGradient
              colors={['rgba(147, 51, 234, 0.05)', 'rgba(236, 72, 153, 0.05)']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.cardHeader}
            >
              <View style={styles.cardHeaderIcon}>
                <LinearGradient
                  colors={['#9333ea', '#ec4899']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                  style={styles.iconGradient}
                >
                  <Ionicons name="people-outline" size={16} color="#fff" />
                </LinearGradient>
              </View>
              <Text style={styles.cardTitle}>
                {theme === 'child' ? 'グループ' : 'グループ管理'}
              </Text>
            </LinearGradient>
            <View style={styles.cardContent}>
              <Text style={styles.sectionDescription}>
                {theme === 'child'
                  ? 'グループをせっていできるよ'
                  : 'グループを作成・編集できます'}
              </Text>
              
              <TouchableOpacity
                style={styles.linkButton}
                onPress={() => navigation.navigate('GroupManagement')}
              >
                <Text style={styles.linkButtonText}>
                  {theme === 'child' ? 'グループのせってい' : 'グループ管理画面へ'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        )}

        {/* 通知設定 */}
        <View style={styles.card}>
          <LinearGradient
            colors={['rgba(59, 130, 246, 0.05)', 'rgba(147, 51, 234, 0.05)']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.cardHeader}
          >
            <View style={styles.cardHeaderIcon}>
              <LinearGradient
                colors={['#3b82f6', '#9333ea']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.iconGradient}
              >
                <Ionicons name="notifications-outline" size={16} color="#fff" />
              </LinearGradient>
            </View>
            <Text style={styles.cardTitle}>
              {theme === 'child' ? 'つうちのせってい' : '通知設定'}
            </Text>
          </LinearGradient>
          <View style={styles.cardContent}>
            <TouchableOpacity
              style={styles.linkButton}
              onPress={() => navigation.navigate('NotificationSettings')}
            >
              <Text style={styles.linkButtonText}>
                {theme === 'child' ? 'つうちのせっていをひらく' : '通知設定を開く'}
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* アプリ情報 */}
        <View style={styles.card}>
          <LinearGradient
            colors={['rgba(59, 130, 246, 0.05)', 'rgba(147, 51, 234, 0.05)']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.cardHeader}
          >
            <View style={styles.cardHeaderIcon}>
              <LinearGradient
                colors={['#3b82f6', '#9333ea']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.iconGradient}
              >
                <Ionicons name="information-circle-outline" size={16} color="#fff" />
              </LinearGradient>
            </View>
            <Text style={styles.cardTitle}>
              {theme === 'child' ? 'アプリのじょうほう' : 'アプリ情報'}
            </Text>
          </LinearGradient>
          <View style={styles.cardContent}>
            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>
                {theme === 'child' ? 'バージョン' : 'バージョン'}
              </Text>
              <Text style={styles.infoValue}>1.0.0</Text>
            </View>

            <TouchableOpacity
              style={styles.linkButton}
              onPress={() => navigation.navigate('PrivacyPolicy')}
            >
              <Text style={styles.linkButtonText}>
                {theme === 'child' ? 'プライバシーポリシー' : 'プライバシーポリシー'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.linkButton}
              onPress={() => navigation.navigate('TermsOfService')}
            >
              <Text style={styles.linkButtonText}>
                {theme === 'child' ? 'りようきやく' : '利用規約'}
              </Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>

      {/* プロフィール編集モーダル */}
      <Modal
        visible={showProfileModal}
        transparent={true}
        animationType="slide"
        onRequestClose={() => !isUpdatingProfile && setShowProfileModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {theme === 'child' ? 'プロフィールへんこう' : 'プロフィール編集'}
              </Text>
              <TouchableOpacity 
                onPress={() => setShowProfileModal(false)}
                disabled={isUpdatingProfile}
              >
                <Text style={styles.modalClose}>✕</Text>
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.modalForm}>
              {/* ユーザー名 */}
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>
                  {theme === 'child' ? 'ユーザーめい' : 'ユーザー名'}
                  <Text style={styles.required}> *</Text>
                </Text>
                <TextInput
                  style={styles.formInput}
                  value={editUsername}
                  onChangeText={setEditUsername}
                  placeholder={theme === 'child' ? 'ユーザーめいをにゅうりょく' : 'ユーザー名を入力'}
                  placeholderTextColor={colors.textSecondary}
                  editable={!isUpdatingProfile}
                  autoCapitalize="none"
                />
              </View>

              {/* 表示名 */}
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>
                  {theme === 'child' ? 'なまえ' : '表示名'}
                  <Text style={styles.optionalLabel}> (任意)</Text>
                </Text>
                <TextInput
                  style={styles.formInput}
                  value={editName}
                  onChangeText={setEditName}
                  placeholder={theme === 'child' ? 'なまえをにゅうりょく' : '表示名を入力'}
                  placeholderTextColor={colors.textSecondary}
                  editable={!isUpdatingProfile}
                />
                <Text style={styles.formHint}>
                  {theme === 'child'
                    ? 'なまえをせっていしないときは、ユーザーめいがつかわれるよ'
                    : '表示名を設定しない場合、ユーザー名が使用されます'}
                </Text>
              </View>

              {/* メールアドレス */}
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>
                  {theme === 'child' ? 'メールアドレス' : 'メールアドレス'}
                  <Text style={styles.required}> *</Text>
                </Text>
                <TextInput
                  style={styles.formInput}
                  value={editEmail}
                  onChangeText={setEditEmail}
                  placeholder={theme === 'child' ? 'メールアドレスをにゅうりょく' : 'メールアドレスを入力'}
                  placeholderTextColor={colors.textSecondary}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  editable={!isUpdatingProfile}
                />
              </View>

              {/* 保存ボタン */}
              <TouchableOpacity
                style={[styles.modalSaveButton, isUpdatingProfile && styles.modalSaveButtonDisabled]}
                onPress={handleUpdateProfile}
                disabled={isUpdatingProfile}
              >
                <LinearGradient
                  colors={isUpdatingProfile ? ['#9CA3AF', '#9CA3AF'] : ['#3b82f6', '#9333ea']}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.modalSaveButtonGradient}
                >
                  {isUpdatingProfile ? (
                    <ActivityIndicator color="#fff" />
                  ) : (
                    <>
                      <Ionicons name="checkmark-circle-outline" size={20} color="#fff" />
                      <Text style={styles.modalSaveButtonText}>
                        {theme === 'child' ? 'ほぞんする' : '保存する'}
                      </Text>
                    </>
                  )}
                </LinearGradient>
              </TouchableOpacity>

              {/* キャンセルボタン */}
              <TouchableOpacity
                style={styles.modalCancelButton}
                onPress={() => setShowProfileModal(false)}
                disabled={isUpdatingProfile}
              >
                <Text style={styles.modalCancelButtonText}>
                  {theme === 'child' ? 'キャンセル' : 'キャンセル'}
                </Text>
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* タイムゾーン選択モーダル */}
      <Modal
        visible={showTimezoneModal}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowTimezoneModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {theme === 'child' ? 'タイムゾーンをえらぶ' : 'タイムゾーンを選択'}
              </Text>
              <TouchableOpacity onPress={() => setShowTimezoneModal(false)}>
                <Text style={styles.modalClose}>✕</Text>
              </TouchableOpacity>
            </View>
            <FlatList
              data={timezones}
              keyExtractor={(item) => item.value}
              renderItem={({ item }) => (
                <TouchableOpacity
                  style={[
                    styles.modalOption,
                    item.value === timezone && styles.modalOptionSelected,
                  ]}
                  onPress={() => {
                    handleTimezoneChange(item.value);
                    setShowTimezoneModal(false);
                  }}
                >
                  <Text
                    style={[
                      styles.modalOptionText,
                      item.value === timezone && styles.modalOptionTextSelected,
                    ]}
                  >
                    {item.label}
                  </Text>
                  {item.value === timezone && (
                    <MaterialIcons name="check" size={24} color="#3b82f6" />
                  )}
                </TouchableOpacity>
              )}
            />
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
};

const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme === 'child' ? '#FFF8E1' : colors.background,
  },
  content: {
    padding: getSpacing(16, width),
  },
  errorContainer: {
    padding: getSpacing(12, width),
    backgroundColor: colors.status.error + '15',
    borderRadius: getBorderRadius(8, width),
    borderLeftWidth: 4,
    borderLeftColor: colors.status.error,
    marginBottom: getSpacing(16, width),
  },
  errorText: {
    color: colors.status.error,
    fontSize: getFontSize(14, width, theme),
  },
  card: {
    backgroundColor: colors.card,
    borderRadius: getBorderRadius(16, width),
    marginBottom: getSpacing(16, width),
    borderWidth: theme === 'child' ? 3 : 0,
    borderColor: theme === 'child' ? '#FF6B6B' : 'transparent',
    ...getShadow(2),
    overflow: 'hidden',
  },
  cardHeader: {
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    flexDirection: 'row',
    alignItems: 'center',
    gap: getSpacing(12, width),
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(0, 0, 0, 0.05)',
  },
  cardHeaderIcon: {
    width: 32,
    height: 32,
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  iconGradient: {
    width: '100%',
    height: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },
  cardTitle: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '700',
    color: colors.text.primary,
  },
  cardContent: {
    padding: getSpacing(16, width),
  },
  sectionDescription: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(16, width),
  },
  themeButtons: {
    flexDirection: 'row',
    gap: getSpacing(12, width),
  },
  themeButton: {
    flex: 1,
    paddingVertical: getSpacing(12, width),
    backgroundColor: colors.surface,
    borderRadius: getBorderRadius(8, width),
    borderWidth: 2,
    borderColor: colors.border.default,
    alignItems: 'center',
  },
  themeButtonActive: {
    backgroundColor: accent.primary + '20',
    borderColor: accent.primary,
  },
  themeButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
  },
  themeButtonTextActive: {
    color: accent.primary,
  },
  // カラースキーマボタンスタイル
  colorSchemeButtons: {
    flexDirection: 'row',
    gap: getSpacing(12, width),
  },
  colorSchemeButton: {
    flex: 1,
    paddingVertical: getSpacing(12, width),
    backgroundColor: colors.surface,
    borderRadius: getBorderRadius(8, width),
    borderWidth: 2,
    borderColor: colors.border.default,
    alignItems: 'center',
    justifyContent: 'center',
  },
  colorSchemeButtonActive: {
    backgroundColor: accent.primary,
    borderColor: accent.primary,
  },
  colorSchemeIcon: {
    marginBottom: getSpacing(4, width),
  },
  colorSchemeButtonText: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.secondary,
  },
  colorSchemeButtonTextActive: {
    color: '#FFFFFF',
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    backgroundColor: colors.card,
    overflow: 'hidden',
  },
  picker: {
    height: getSpacing(50, width),
  },
  settingRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  settingInfo: {
    flex: 1,
    marginRight: getSpacing(16, width),
  },
  settingTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    marginBottom: getSpacing(4, width),
  },
  settingDescription: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: getSpacing(12, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.subtle,
  },
  infoLabel: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.secondary,
  },
  infoValue: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
  },
  linkButton: {
    paddingVertical: getSpacing(12, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.subtle,
  },
  linkButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: accent.primary,
    fontWeight: '500',
  },
  // タイムゾーン選択ボタン
  timezoneButton: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
  },
  timezoneButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
    flex: 1,
  },
  // モーダルスタイル
  modalOverlay: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: colors.card,
    borderTopLeftRadius: getBorderRadius(20, width),
    borderTopRightRadius: getBorderRadius(20, width),
    paddingBottom: getSpacing(20, width),
    maxHeight: '80%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: getSpacing(16, width),
    paddingHorizontal: getSpacing(20, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.default,
  },
  modalTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: 'bold',
    color: colors.text.primary,
  },
  modalClose: {
    fontSize: getFontSize(24, width, theme),
    color: colors.text.secondary,
    fontWeight: 'bold',
  },
  modalOption: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: getSpacing(16, width),
    paddingHorizontal: getSpacing(20, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.subtle,
  },
  modalOptionSelected: {
    backgroundColor: accent.primary + '15',
  },
  modalOptionText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
    flex: 1,
  },
  modalOptionTextSelected: {
    color: accent.primary,
    fontWeight: '600',
  },
  // プロフィール情報スタイル
  profileInfo: {
    gap: getSpacing(12, width),
  },
  profileRow: {
    paddingVertical: getSpacing(12, width),
    borderBottomWidth: 1,
    borderBottomColor: colors.border.subtle,
  },
  profileLabel: {
    fontSize: getFontSize(14, width, theme),
    color: colors.text.secondary,
    marginBottom: getSpacing(4, width),
  },
  profileValue: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
  },
  editButton: {
    marginTop: getSpacing(16, width),
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  editButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(12, width),
    gap: getSpacing(8, width),
  },
  editButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#fff',
  },
  // プロフィール編集モーダルスタイル
  modalForm: {
    paddingHorizontal: getSpacing(20, width),
  },
  formGroup: {
    marginTop: getSpacing(16, width),
  },
  formLabel: {
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600',
    color: colors.text.primary,
    marginBottom: getSpacing(8, width),
  },
  required: {
    color: colors.status.error,
  },
  optionalLabel: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    fontWeight: 'normal',
  },
  formInput: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border.default,
    borderRadius: getBorderRadius(8, width),
    paddingVertical: getSpacing(12, width),
    paddingHorizontal: getSpacing(16, width),
    fontSize: getFontSize(16, width, theme),
    color: colors.text.primary,
  },
  formHint: {
    fontSize: getFontSize(12, width, theme),
    color: colors.text.secondary,
    marginTop: getSpacing(4, width),
  },
  modalSaveButton: {
    marginTop: getSpacing(24, width),
    borderRadius: getBorderRadius(8, width),
    overflow: 'hidden',
  },
  modalSaveButtonDisabled: {
    opacity: 0.6,
  },
  modalSaveButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: getSpacing(14, width),
    gap: getSpacing(8, width),
  },
  modalSaveButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '700',
    color: '#fff',
  },
  modalCancelButton: {
    marginTop: getSpacing(12, width),
    paddingVertical: getSpacing(12, width),
    alignItems: 'center',
  },
  modalCancelButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: colors.text.secondary,
    fontWeight: '500',
  },
});

export default SettingsScreen;
