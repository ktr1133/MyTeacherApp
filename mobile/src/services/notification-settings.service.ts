/**
 * 通知設定サービス
 * Push通知の設定取得・更新を処理
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 * @see https://rnfirebase.io/messaging/usage - React Native Firebase公式ドキュメント
 */
import api from './api';
import { NotificationSettings, NotificationSettingsUpdateRequest } from '../types/api.types';

/**
 * 通知設定サービスクラス
 */
class NotificationSettingsService {
  /**
   * 通知設定を取得
   * 
   * **エンドポイント**: GET /profile/notification-settings
   * 
   * **レスポンス例**:
   * ```json
   * {
   *   "push_enabled": true,
   *   "push_task_enabled": true,
   *   "push_group_enabled": true,
   *   "push_token_enabled": true,
   *   "push_system_enabled": true,
   *   "push_sound_enabled": true,
   *   "push_vibration_enabled": true
   * }
   * ```
   * 
   * @returns {Promise<NotificationSettings>} 通知設定オブジェクト
   * @throws {Error} API呼び出しエラー、ネットワークエラー、認証エラー
   */
  async getNotificationSettings(): Promise<NotificationSettings> {
    try {
      console.log('[NotificationSettingsService] Fetching notification settings');

      const response = await api.get<{ success: boolean; data: NotificationSettings }>('/profile/notification-settings');

      console.log('[NotificationSettingsService] Raw response:', response.data);
      console.log('[NotificationSettingsService] Notification settings fetched successfully:', response.data.data);
      return response.data.data;
    } catch (error: any) {
      console.error('[NotificationSettingsService] Failed to fetch notification settings:', error);
      
      // エラー詳細をログに記録
      if (error.response) {
        console.error('[NotificationSettingsService] Response error:', {
          status: error.response.status,
          data: error.response.data,
        });
      }

      throw error;
    }
  }

  /**
   * 通知設定を更新（部分更新可能）
   * 
   * **エンドポイント**: PUT /profile/notification-settings
   * 
   * **リクエスト例**:
   * ```json
   * {
   *   "push_enabled": true,
   *   "push_task_enabled": false
   * }
   * ```
   * 
   * **レスポンス例**:
   * ```json
   * {
   *   "push_enabled": true,
   *   "push_task_enabled": false,
   *   "push_group_enabled": true,
   *   "push_token_enabled": true,
   *   "push_system_enabled": true,
   *   "push_sound_enabled": true,
   *   "push_vibration_enabled": true
   * }
   * ```
   * 
   * @param {NotificationSettingsUpdateRequest} settings 更新する設定（部分更新可能）
   * @returns {Promise<NotificationSettings>} 更新後の通知設定オブジェクト
   * @throws {Error} API呼び出しエラー、ネットワークエラー、認証エラー、バリデーションエラー
   */
  async updateNotificationSettings(
    settings: NotificationSettingsUpdateRequest
  ): Promise<NotificationSettings> {
    try {
      console.log('[NotificationSettingsService] Updating notification settings:', settings);

      const response = await api.put<{ success: boolean; message: string; data: NotificationSettings }>(
        '/profile/notification-settings',
        settings
      );

      console.log('[NotificationSettingsService] Raw response:', response.data);
      console.log('[NotificationSettingsService] Notification settings updated successfully:', response.data.data);
      return response.data.data;
    } catch (error: any) {
      console.error('[NotificationSettingsService] Failed to update notification settings:', error);
      
      // エラー詳細をログに記録
      if (error.response) {
        console.error('[NotificationSettingsService] Response error:', {
          status: error.response.status,
          data: error.response.data,
        });
      }

      throw error;
    }
  }

  /**
   * Push通知全体のON/OFFを切り替え
   * 
   * **用途**: 「Push通知を受け取る」トグルスイッチの処理
   * 
   * @param {boolean} enabled Push通知の有効/無効
   * @returns {Promise<NotificationSettings>} 更新後の通知設定オブジェクト
   */
  async togglePushEnabled(enabled: boolean): Promise<NotificationSettings> {
    return this.updateNotificationSettings({ push_enabled: enabled });
  }

  /**
   * タスク通知のON/OFFを切り替え
   * 
   * @param {boolean} enabled タスク通知の有効/無効
   * @returns {Promise<NotificationSettings>} 更新後の通知設定オブジェクト
   */
  async toggleTaskEnabled(enabled: boolean): Promise<NotificationSettings> {
    return this.updateNotificationSettings({ push_task_enabled: enabled });
  }

  /**
   * グループ通知のON/OFFを切り替え
   * 
   * @param {boolean} enabled グループ通知の有効/無効
   * @returns {Promise<NotificationSettings>} 更新後の通知設定オブジェクト
   */
  async toggleGroupEnabled(enabled: boolean): Promise<NotificationSettings> {
    return this.updateNotificationSettings({ push_group_enabled: enabled });
  }

  /**
   * トークン通知のON/OFFを切り替え
   * 
   * @param {boolean} enabled トークン通知の有効/無効
   * @returns {Promise<NotificationSettings>} 更新後の通知設定オブジェクト
   */
  async toggleTokenEnabled(enabled: boolean): Promise<NotificationSettings> {
    return this.updateNotificationSettings({ push_token_enabled: enabled });
  }

  /**
   * システム通知のON/OFFを切り替え
   * 
   * @param {boolean} enabled システム通知の有効/無効
   * @returns {Promise<NotificationSettings>} 更新後の通知設定オブジェクト
   */
  async toggleSystemEnabled(enabled: boolean): Promise<NotificationSettings> {
    return this.updateNotificationSettings({ push_system_enabled: enabled });
  }

  /**
   * 通知音のON/OFFを切り替え
   * 
   * @param {boolean} enabled 通知音の有効/無効
   * @returns {Promise<NotificationSettings>} 更新後の通知設定オブジェクト
   */
  async toggleSoundEnabled(enabled: boolean): Promise<NotificationSettings> {
    return this.updateNotificationSettings({ push_sound_enabled: enabled });
  }

  /**
   * バイブレーションのON/OFFを切り替え（Android専用）
   * 
   * @param {boolean} enabled バイブレーションの有効/無効
   * @returns {Promise<NotificationSettings>} 更新後の通知設定オブジェクト
   */
  async toggleVibrationEnabled(enabled: boolean): Promise<NotificationSettings> {
    return this.updateNotificationSettings({ push_vibration_enabled: enabled });
  }
}

// シングルトンインスタンスをエクスポート
export const notificationSettingsService = new NotificationSettingsService();
