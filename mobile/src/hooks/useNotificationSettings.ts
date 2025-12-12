/**
 * 通知設定管理カスタムフック
 * 通知設定の取得・更新を管理
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 */
import { useState, useEffect } from 'react';
import { notificationSettingsService } from '../services/notification-settings.service';
import { NotificationSettings, NotificationSettingsUpdateRequest } from '../types/api.types';

/**
 * 通知設定フック戻り値の型
 */
export interface UseNotificationSettingsReturn {
  /** 通知設定オブジェクト */
  settings: NotificationSettings | null;
  /** ローディング状態 */
  isLoading: boolean;
  /** エラーメッセージ */
  error: string | null;
  /** 通知設定を再読み込み */
  refetch: () => Promise<void>;
  /** 通知設定を更新（部分更新可能） */
  updateSettings: (newSettings: NotificationSettingsUpdateRequest) => Promise<void>;
  /** Push通知全体のON/OFFを切り替え */
  togglePushEnabled: (enabled: boolean) => Promise<void>;
  /** タスク通知のON/OFFを切り替え */
  toggleTaskEnabled: (enabled: boolean) => Promise<void>;
  /** グループ通知のON/OFFを切り替え */
  toggleGroupEnabled: (enabled: boolean) => Promise<void>;
  /** トークン通知のON/OFFを切り替え */
  toggleTokenEnabled: (enabled: boolean) => Promise<void>;
  /** システム通知のON/OFFを切り替え */
  toggleSystemEnabled: (enabled: boolean) => Promise<void>;
  /** 通知音のON/OFFを切り替え */
  toggleSoundEnabled: (enabled: boolean) => Promise<void>;
  /** バイブレーションのON/OFFを切り替え（Android） */
  toggleVibrationEnabled: (enabled: boolean) => Promise<void>;
}

/**
 * 通知設定管理カスタムフック
 * 
 * **機能**:
 * - 通知設定の取得（マウント時自動取得）
 * - 通知設定の更新（楽観的UI更新 + サーバー同期）
 * - エラーハンドリング
 * - ローディング状態管理
 * 
 * **使用方法**:
 * ```tsx
 * const {
 *   settings,
 *   isLoading,
 *   error,
 *   togglePushEnabled,
 *   toggleTaskEnabled,
 * } = useNotificationSettings();
 * 
 * // Push通知全体のON/OFF
 * await togglePushEnabled(true);
 * 
 * // タスク通知のON/OFF
 * await toggleTaskEnabled(false);
 * ```
 * 
 * @returns {UseNotificationSettingsReturn} 通知設定の状態と更新関数
 */
export const useNotificationSettings = (): UseNotificationSettingsReturn => {
  const [settings, setSettings] = useState<NotificationSettings | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  /**
   * 通知設定を取得
   */
  const fetchSettings = async () => {
    try {
      setIsLoading(true);
      setError(null);

      const data = await notificationSettingsService.getNotificationSettings();
      setSettings(data);

      console.log('[useNotificationSettings] Settings fetched successfully:', data);
    } catch (err: any) {
      console.error('[useNotificationSettings] Failed to fetch settings:', err);
      
      const errorMessage = err.response?.data?.message || '通知設定の取得に失敗しました。';
      setError(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * 通知設定を再読み込み
   */
  const refetch = async () => {
    await fetchSettings();
  };

  /**
   * 通知設定を更新（楽観的UI更新）
   * 
   * **楽観的UI更新フロー**:
   * 1. 即座にローカルステートを更新（UI即反映）
   * 2. サーバーAPIを呼び出し
   * 3. 成功: サーバーから返却された最新値でステート更新
   * 4. 失敗: 元の値に戻す（ロールバック）
   * 
   * @param {NotificationSettingsUpdateRequest} newSettings 更新する設定（部分更新可能）
   */
  const updateSettings = async (newSettings: NotificationSettingsUpdateRequest) => {
    if (!settings) {
      console.error('[useNotificationSettings] Cannot update settings: settings is null');
      return;
    }

    // 楽観的UI更新: 即座にローカルステートを更新
    const previousSettings = { ...settings };
    const optimisticSettings = { ...settings, ...newSettings };
    setSettings(optimisticSettings);

    try {
      console.log('[useNotificationSettings] Updating settings:', newSettings);

      // サーバーAPI呼び出し
      const updatedSettings = await notificationSettingsService.updateNotificationSettings(newSettings);
      
      // サーバーから返却された最新値でステート更新
      setSettings(updatedSettings);
      setError(null);

      console.log('[useNotificationSettings] Settings updated successfully:', updatedSettings);
    } catch (err: any) {
      console.error('[useNotificationSettings] Failed to update settings:', err);
      
      // ロールバック: 元の値に戻す
      setSettings(previousSettings);

      const errorMessage = err.response?.data?.message || '通知設定の更新に失敗しました。';
      setError(errorMessage);

      throw err; // 呼び出し元でエラーハンドリングできるように再スロー
    }
  };

  /**
   * Push通知全体のON/OFFを切り替え
   */
  const togglePushEnabled = async (enabled: boolean) => {
    await updateSettings({ push_enabled: enabled });
  };

  /**
   * タスク通知のON/OFFを切り替え
   */
  const toggleTaskEnabled = async (enabled: boolean) => {
    await updateSettings({ push_task_enabled: enabled });
  };

  /**
   * グループ通知のON/OFFを切り替え
   */
  const toggleGroupEnabled = async (enabled: boolean) => {
    await updateSettings({ push_group_enabled: enabled });
  };

  /**
   * トークン通知のON/OFFを切り替え
   */
  const toggleTokenEnabled = async (enabled: boolean) => {
    await updateSettings({ push_token_enabled: enabled });
  };

  /**
   * システム通知のON/OFFを切り替え
   */
  const toggleSystemEnabled = async (enabled: boolean) => {
    await updateSettings({ push_system_enabled: enabled });
  };

  /**
   * 通知音のON/OFFを切り替え
   */
  const toggleSoundEnabled = async (enabled: boolean) => {
    await updateSettings({ push_sound_enabled: enabled });
  };

  /**
   * バイブレーションのON/OFFを切り替え（Android）
   */
  const toggleVibrationEnabled = async (enabled: boolean) => {
    await updateSettings({ push_vibration_enabled: enabled });
  };

  // マウント時に通知設定を取得
  useEffect(() => {
    fetchSettings();
  }, []);

  return {
    settings,
    isLoading,
    error,
    refetch,
    updateSettings,
    togglePushEnabled,
    toggleTaskEnabled,
    toggleGroupEnabled,
    toggleTokenEnabled,
    toggleSystemEnabled,
    toggleSoundEnabled,
    toggleVibrationEnabled,
  };
};
