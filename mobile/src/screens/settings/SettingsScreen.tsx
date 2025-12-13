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
} from 'react-native';
import { Picker } from '@react-native-picker/picker';
import { useAuth } from '../../contexts/AuthContext';
import { useProfile } from '../../hooks/useProfile';
import { useTheme } from '../../contexts/ThemeContext';
import { userService } from '../../services/user.service';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

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
  } = useProfile(theme);

  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  const [currentTheme, setCurrentTheme] = useState<'adult' | 'child'>(theme);
  const [timezone, setTimezone] = useState('');
  const [timezones, setTimezones] = useState<Array<{ value: string; label: string }>>([]);
  const [notificationsEnabled, setNotificationsEnabled] = useState(false);
  const [isLoadingTimezone, setIsLoadingTimezone] = useState(false);

  // 初回タイムゾーン設定取得
  useEffect(() => {
    loadTimezoneSettings();
    loadNotificationSettings();
  }, []);

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
   * テーマ変更
   */
  const handleThemeChange = async (newTheme: 'adult' | 'child') => {
    setCurrentTheme(newTheme);
    setTheme(newTheme);

    // Laravel APIでユーザーのテーマを更新（UserService経由）
    try {
      await userService.getCurrentUser(); // キャッシュクリア
      Alert.alert(
        newTheme === 'child' ? 'きりかえたよ' : 'テーマ変更完了',
        newTheme === 'child'
          ? 'こどもモードにきりかえたよ'
          : '大人モードに切り替えました',
      );
    } catch (err) {
      console.error('Failed to update theme', err);
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

  return (
    <ScrollView style={styles.container}>
      <View style={styles.content}>
        {/* ヘッダー */}
        <Text style={styles.title}>
          {theme === 'child' ? 'せってい' : '設定'}
        </Text>

        {/* エラー表示 */}
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
          </View>
        )}

        {/* テーマ設定 */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            {theme === 'child' ? 'がめんのモード' : 'テーマ設定'}
          </Text>
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

        {/* タイムゾーン設定 */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            {theme === 'child' ? 'じかんのせってい' : 'タイムゾーン'}
          </Text>
          <Text style={styles.sectionDescription}>
            {theme === 'child'
              ? 'あなたのすんでいるばしょのじかんをせっていするよ'
              : 'お住まいの地域のタイムゾーンを設定します'}
          </Text>

          {isLoadingTimezone ? (
            <ActivityIndicator size="small" color="#3b82f6" />
          ) : (
            <View style={styles.pickerContainer}>
              <Picker
                selectedValue={timezone}
                onValueChange={handleTimezoneChange}
                style={styles.picker}
                enabled={!isLoading}
              >
                {(timezones || []).map((tz) => (
                  <Picker.Item
                    key={tz.value}
                    label={tz.label}
                    value={tz.value}
                  />
                ))}
              </Picker>
            </View>
          )}
        </View>

        {/* グループ管理 (子どもユーザー以外) */}
        {user && user.theme !== 'child' && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>
              {theme === 'child' ? 'グループ' : 'グループ管理'}
            </Text>
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
        )}

        {/* 通知設定 */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            {theme === 'child' ? 'つうちのせってい' : '通知設定'}
          </Text>
          
          <TouchableOpacity
            style={styles.linkButton}
            onPress={() => navigation.navigate('NotificationSettings')}
          >
            <Text style={styles.linkButtonText}>
              {theme === 'child' ? 'つうちのせっていをひらく' : '通知設定を開く'}
            </Text>
          </TouchableOpacity>
        </View>

        {/* アプリ情報 */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            {theme === 'child' ? 'アプリのじょうほう' : 'アプリ情報'}
          </Text>

          <View style={styles.infoRow}>
            <Text style={styles.infoLabel}>
              {theme === 'child' ? 'バージョン' : 'バージョン'}
            </Text>
            <Text style={styles.infoValue}>1.0.0</Text>
          </View>

          <TouchableOpacity
            style={styles.linkButton}
            onPress={() => {
              Alert.alert(
                theme === 'child' ? 'リンク' : 'リンク',
                theme === 'child'
                  ? 'ブラウザでひらくよ'
                  : 'プライバシーポリシーを開きます',
              );
            }}
          >
            <Text style={styles.linkButtonText}>
              {theme === 'child' ? 'プライバシーポリシー' : 'プライバシーポリシー'}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.linkButton}
            onPress={() => {
              Alert.alert(
                theme === 'child' ? 'リンク' : 'リンク',
                theme === 'child'
                  ? 'ブラウザでひらくよ'
                  : '利用規約を開きます',
              );
            }}
          >
            <Text style={styles.linkButtonText}>
              {theme === 'child' ? 'りようきやく' : '利用規約'}
            </Text>
          </TouchableOpacity>
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
  title: {
    fontSize: getFontSize(24, width, theme),
    fontWeight: 'bold',
    color: '#1e293b',
    marginBottom: getSpacing(24, width),
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
  section: {
    marginBottom: getSpacing(32, width),
    padding: getSpacing(16, width),
    backgroundColor: '#fff',
    borderRadius: getBorderRadius(12, width),
    ...getShadow(2),
  },
  sectionTitle: {
    fontSize: getFontSize(18, width, theme),
    fontWeight: '600',
    color: '#1e293b',
    marginBottom: getSpacing(8, width),
  },
  sectionDescription: {
    fontSize: getFontSize(14, width, theme),
    color: '#64748b',
    marginBottom: getSpacing(16, width),
  },
  themeButtons: {
    flexDirection: 'row',
    gap: getSpacing(12, width),
  },
  themeButton: {
    flex: 1,
    paddingVertical: getSpacing(12, width),
    backgroundColor: '#f1f5f9',
    borderRadius: getBorderRadius(8, width),
    borderWidth: 2,
    borderColor: '#cbd5e1',
    alignItems: 'center',
  },
  themeButtonActive: {
    backgroundColor: '#dbeafe',
    borderColor: '#3b82f6',
  },
  themeButtonText: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#475569',
  },
  themeButtonTextActive: {
    color: '#3b82f6',
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: getBorderRadius(8, width),
    backgroundColor: '#fff',
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
    color: '#1e293b',
    marginBottom: getSpacing(4, width),
  },
  settingDescription: {
    fontSize: getFontSize(14, width, theme),
    color: '#64748b',
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: getSpacing(12, width),
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  infoLabel: {
    fontSize: getFontSize(16, width, theme),
    color: '#475569',
  },
  infoValue: {
    fontSize: getFontSize(16, width, theme),
    fontWeight: '600',
    color: '#1e293b',
  },
  linkButton: {
    paddingVertical: getSpacing(12, width),
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  linkButtonText: {
    fontSize: getFontSize(16, width, theme),
    color: '#3b82f6',
    fontWeight: '500',
  },
});

export default SettingsScreen;
