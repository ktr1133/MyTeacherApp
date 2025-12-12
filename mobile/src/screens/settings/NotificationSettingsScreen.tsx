/**
 * NotificationSettingsScreen - 通知設定画面
 * 
 * 機能:
 * - Push通知全体のON/OFF切り替え
 * - カテゴリ別通知設定（タスク、グループ、トークン、システム）
 * - 通知音・バイブレーション設定
 * - 楽観的UI更新（即座に反映）
 * - テーマ対応UI（adult/child）
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 */

import React, { useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  Alert,
  ActivityIndicator,
  Switch,
  Platform,
} from 'react-native';
import { useTheme } from '../../contexts/ThemeContext';
import { useNotificationSettings } from '../../hooks/useNotificationSettings';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

/**
 * NotificationSettingsScreen コンポーネント
 */
export const NotificationSettingsScreen: React.FC = () => {
  const { theme } = useTheme();
  const {
    settings,
    isLoading,
    error,
    togglePushEnabled,
    toggleTaskEnabled,
    toggleGroupEnabled,
    toggleTokenEnabled,
    toggleSystemEnabled,
    toggleSoundEnabled,
    toggleVibrationEnabled,
  } = useNotificationSettings();

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  /**
   * Push通知全体のON/OFF切り替え
   */
  const handlePushEnabledToggle = async (enabled: boolean) => {
    try {
      await togglePushEnabled(enabled);
      
      Alert.alert(
        theme === 'child' ? 'せっていしたよ' : '設定変更',
        enabled
          ? (theme === 'child' ? 'つうちをONにしたよ' : 'Push通知を有効にしました')
          : (theme === 'child' ? 'つうちをOFFにしたよ' : 'Push通知を無効にしました'),
      );
    } catch (err) {
      console.error('[NotificationSettingsScreen] Failed to toggle push enabled:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'せっていがかえられなかったよ' : '設定の更新に失敗しました',
      );
    }
  };

  /**
   * タスク通知のON/OFF切り替え
   */
  const handleTaskEnabledToggle = async (enabled: boolean) => {
    try {
      await toggleTaskEnabled(enabled);
    } catch (err) {
      console.error('[NotificationSettingsScreen] Failed to toggle task enabled:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'せっていがかえられなかったよ' : '設定の更新に失敗しました',
      );
    }
  };

  /**
   * グループ通知のON/OFF切り替え
   */
  const handleGroupEnabledToggle = async (enabled: boolean) => {
    try {
      await toggleGroupEnabled(enabled);
    } catch (err) {
      console.error('[NotificationSettingsScreen] Failed to toggle group enabled:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'せっていがかえられなかったよ' : '設定の更新に失敗しました',
      );
    }
  };

  /**
   * トークン通知のON/OFF切り替え
   */
  const handleTokenEnabledToggle = async (enabled: boolean) => {
    try {
      await toggleTokenEnabled(enabled);
    } catch (err) {
      console.error('[NotificationSettingsScreen] Failed to toggle token enabled:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'せっていがかえられなかったよ' : '設定の更新に失敗しました',
      );
    }
  };

  /**
   * システム通知のON/OFF切り替え
   */
  const handleSystemEnabledToggle = async (enabled: boolean) => {
    try {
      await toggleSystemEnabled(enabled);
    } catch (err) {
      console.error('[NotificationSettingsScreen] Failed to toggle system enabled:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'せっていがかえられなかったよ' : '設定の更新に失敗しました',
      );
    }
  };

  /**
   * 通知音のON/OFF切り替え
   */
  const handleSoundEnabledToggle = async (enabled: boolean) => {
    try {
      await toggleSoundEnabled(enabled);
    } catch (err) {
      console.error('[NotificationSettingsScreen] Failed to toggle sound enabled:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'せっていがかえられなかったよ' : '設定の更新に失敗しました',
      );
    }
  };

  /**
   * バイブレーションのON/OFF切り替え（Android専用）
   */
  const handleVibrationEnabledToggle = async (enabled: boolean) => {
    try {
      await toggleVibrationEnabled(enabled);
    } catch (err) {
      console.error('[NotificationSettingsScreen] Failed to toggle vibration enabled:', err);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child' ? 'せっていがかえられなかったよ' : '設定の更新に失敗しました',
      );
    }
  };

  // ローディング中
  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3b82f6" />
        <Text style={styles.loadingText}>
          {theme === 'child' ? 'よみこみちゅう...' : '読み込み中...'}
        </Text>
      </View>
    );
  }

  // 設定データがない場合
  if (!settings) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorText}>
          {theme === 'child' ? 'せっていがよみこめなかったよ' : '設定の読み込みに失敗しました'}
        </Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        {/* ヘッダー */}
        <Text style={styles.title}>
          {theme === 'child' ? 'つうちのせってい' : '通知設定'}
        </Text>

        {/* エラー表示 */}
        {error && (
          <View style={styles.errorBanner}>
            <Text style={styles.errorBannerText}>{error}</Text>
          </View>
        )}

        {/* 全体設定セクション */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            {theme === 'child' ? 'つうちをうけとる' : 'Push通知'}
          </Text>
          <Text style={styles.sectionDescription}>
            {theme === 'child'
              ? 'つうちをうけとるかどうかをきめられるよ'
              : 'すべてのPush通知のON/OFFを切り替えます'}
          </Text>

          <View style={styles.settingRow}>
            <View style={styles.settingInfo}>
              <Text style={styles.settingTitle}>
                {theme === 'child' ? 'つうちをうけとる' : 'Push通知を受け取る'}
              </Text>
              <Text style={styles.settingDescription}>
                {theme === 'child'
                  ? 'OFFにすると、つうちがこなくなるよ'
                  : 'OFFにすると、すべての通知が届かなくなります'}
              </Text>
            </View>
            <Switch
              value={settings.push_enabled}
              onValueChange={handlePushEnabledToggle}
              trackColor={{ false: '#cbd5e1', true: '#93c5fd' }}
              thumbColor={settings.push_enabled ? '#3b82f6' : '#f1f5f9'}
            />
          </View>
        </View>

        {/* カテゴリ別通知設定 */}
        {settings.push_enabled && (
          <>
            {/* タスク通知 */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>
                {theme === 'child' ? 'タスクのつうち' : 'タスク通知'}
              </Text>
              <Text style={styles.sectionDescription}>
                {theme === 'child'
                  ? 'タスクのつうちをうけとるよ'
                  : 'タスク完了申請、承認、却下などの通知'}
              </Text>

              <View style={styles.settingRow}>
                <View style={styles.settingInfo}>
                  <Text style={styles.settingTitle}>
                    {theme === 'child' ? 'タスクつうち' : 'タスク通知'}
                  </Text>
                </View>
                <Switch
                  value={settings.push_task_enabled}
                  onValueChange={handleTaskEnabledToggle}
                  trackColor={{ false: '#cbd5e1', true: '#93c5fd' }}
                  thumbColor={settings.push_task_enabled ? '#3b82f6' : '#f1f5f9'}
                />
              </View>
            </View>

            {/* グループ通知 */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>
                {theme === 'child' ? 'グループのつうち' : 'グループ通知'}
              </Text>
              <Text style={styles.sectionDescription}>
                {theme === 'child'
                  ? 'グループのつうちをうけとるよ'
                  : 'グループ招待、参加承認などの通知'}
              </Text>

              <View style={styles.settingRow}>
                <View style={styles.settingInfo}>
                  <Text style={styles.settingTitle}>
                    {theme === 'child' ? 'グループつうち' : 'グループ通知'}
                  </Text>
                </View>
                <Switch
                  value={settings.push_group_enabled}
                  onValueChange={handleGroupEnabledToggle}
                  trackColor={{ false: '#cbd5e1', true: '#93c5fd' }}
                  thumbColor={settings.push_group_enabled ? '#3b82f6' : '#f1f5f9'}
                />
              </View>
            </View>

            {/* トークン通知 */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>
                {theme === 'child' ? 'トークンのつうち' : 'トークン通知'}
              </Text>
              <Text style={styles.sectionDescription}>
                {theme === 'child'
                  ? 'トークンのつうちをうけとるよ'
                  : 'トークン残高低下、購入完了などの通知'}
              </Text>

              <View style={styles.settingRow}>
                <View style={styles.settingInfo}>
                  <Text style={styles.settingTitle}>
                    {theme === 'child' ? 'トークンつうち' : 'トークン通知'}
                  </Text>
                </View>
                <Switch
                  value={settings.push_token_enabled}
                  onValueChange={handleTokenEnabledToggle}
                  trackColor={{ false: '#cbd5e1', true: '#93c5fd' }}
                  thumbColor={settings.push_token_enabled ? '#3b82f6' : '#f1f5f9'}
                />
              </View>
            </View>

            {/* システム通知 */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>
                {theme === 'child' ? 'システムのつうち' : 'システム通知'}
              </Text>
              <Text style={styles.sectionDescription}>
                {theme === 'child'
                  ? 'おしらせやアップデートのつうちをうけとるよ'
                  : 'メンテナンス、機能アップデートなどの通知'}
              </Text>

              <View style={styles.settingRow}>
                <View style={styles.settingInfo}>
                  <Text style={styles.settingTitle}>
                    {theme === 'child' ? 'システムつうち' : 'システム通知'}
                  </Text>
                </View>
                <Switch
                  value={settings.push_system_enabled}
                  onValueChange={handleSystemEnabledToggle}
                  trackColor={{ false: '#cbd5e1', true: '#93c5fd' }}
                  thumbColor={settings.push_system_enabled ? '#3b82f6' : '#f1f5f9'}
                />
              </View>
            </View>

            {/* サウンド・バイブレーション設定 */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>
                {theme === 'child' ? 'おとやしんどう' : '音・振動設定'}
              </Text>
              <Text style={styles.sectionDescription}>
                {theme === 'child'
                  ? 'おとやしんどうのせっていだよ'
                  : '通知音や振動の設定を変更できます'}
              </Text>

              {/* 通知音 */}
              <View style={[styles.settingRow, styles.settingRowWithBorder]}>
                <View style={styles.settingInfo}>
                  <Text style={styles.settingTitle}>
                    {theme === 'child' ? 'つうちおん' : '通知音'}
                  </Text>
                  <Text style={styles.settingDescription}>
                    {theme === 'child' ? 'つうちがきたらおとがなるよ' : '通知時に音を鳴らします'}
                  </Text>
                </View>
                <Switch
                  value={settings.push_sound_enabled}
                  onValueChange={handleSoundEnabledToggle}
                  trackColor={{ false: '#cbd5e1', true: '#93c5fd' }}
                  thumbColor={settings.push_sound_enabled ? '#3b82f6' : '#f1f5f9'}
                />
              </View>

              {/* バイブレーション（Android専用） */}
              {Platform.OS === 'android' && (
                <View style={styles.settingRow}>
                  <View style={styles.settingInfo}>
                    <Text style={styles.settingTitle}>
                      {theme === 'child' ? 'しんどう' : 'バイブレーション'}
                    </Text>
                    <Text style={styles.settingDescription}>
                      {theme === 'child'
                        ? 'つうちがきたらぶるぶるするよ'
                        : '通知時に振動します（Android専用）'}
                    </Text>
                  </View>
                  <Switch
                    value={settings.push_vibration_enabled}
                    onValueChange={handleVibrationEnabledToggle}
                    trackColor={{ false: '#cbd5e1', true: '#93c5fd' }}
                    thumbColor={settings.push_vibration_enabled ? '#3b82f6' : '#f1f5f9'}
                  />
                </View>
              )}
            </View>
          </>
        )}

        {/* 注意事項 */}
        <View style={styles.notice}>
          <Text style={styles.noticeTitle}>
            {theme === 'child' ? 'ちゅうい' : '⚠️ 注意事項'}
          </Text>
          <Text style={styles.noticeText}>
            {theme === 'child'
              ? 'たんまつのせっていで、つうちをOFFにしていると、つうちがこないよ'
              : 'デバイスの設定で通知がOFFになっている場合、Push通知は届きません'}
          </Text>
        </View>
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
    marginTop: getSpacing(16, width),
    fontSize: getFontSize(16, width, theme),
    color: '#64748b',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    padding: getSpacing(24, width),
  },
  errorText: {
    fontSize: getFontSize(16, width, theme),
    color: '#dc2626',
    textAlign: 'center',
  },
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: '#1e293b',
    marginBottom: getSpacing(24, width),
  },
  errorBanner: {
    padding: getSpacing(12, width),
    backgroundColor: '#fef2f2',
    borderRadius: getBorderRadius(8, width),
    borderLeftWidth: 4,
    borderLeftColor: '#ef4444',
    marginBottom: getSpacing(16, width),
  },
  errorBannerText: {
    color: '#dc2626',
    fontSize: getFontSize(14, width, theme),
  },
  section: {
    marginBottom: getSpacing(24, width),
    padding: getSpacing(16, width),
    backgroundColor: '#fff',
    borderRadius: getBorderRadius(12, width),
    ...getShadow(2),
  },
  sectionTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
    color: '#1e293b',
    marginBottom: getSpacing(4, width),
  },
  sectionDescription: {
    fontSize: getFontSize(14, width, theme),
    color: '#64748b',
    marginBottom: getSpacing(16, width),
  },
  settingRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  settingRowWithBorder: {
    paddingBottom: getSpacing(12, width),
    marginBottom: getSpacing(12, width),
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  settingInfo: {
    flex: 1,
    marginRight: getSpacing(16, width),
  },
  settingTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#1e293b',
    marginBottom: getSpacing(4, width),
  },
  settingDescription: {
    fontSize: getFontSize(14, width, theme),
    color: '#64748b',
  },
  notice: {
    padding: getSpacing(16, width),
    backgroundColor: '#fffbeb',
    borderRadius: getBorderRadius(12, width),
    borderLeftWidth: 4,
    borderLeftColor: '#f59e0b',
    marginBottom: getSpacing(24, width),
  },
  noticeTitle: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#92400e',
    marginBottom: getSpacing(8, width),
  },
  noticeText: {
    fontSize: getFontSize(14, width, theme),
    color: '#78350f',
    lineHeight: getFontSize(20, width, theme),
  },
});

export default NotificationSettingsScreen;
