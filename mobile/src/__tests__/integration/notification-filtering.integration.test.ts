/**
 * Notification Filtering Integration Test
 * 
 * **テスト対象**:
 * NotificationSettings更新 → Backend API → Push送信フィルタリング
 * 
 * **実API使用**:
 * - Backend API: PATCH /profile/notification-settings
 * - Backend API: POST /notifications/test (テスト通知送信)
 * - Firebase Cloud Messaging (実環境)
 * 
 * **前提条件**:
 * - FCM token登録済み
 * - Backend API稼働中
 * - テストユーザーでログイン済み
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.6
 */

import api from '../../services/api';
import { fcmService } from '../../services/fcm.service';
import messaging from '@react-native-firebase/messaging';

describe('Notification Filtering - Integration', () => {
  let testUserId: number;
  let fcmToken: string | null;

  beforeAll(async () => {
    // テスト用セットアップ: FCM token登録
    await fcmService.requestPermission();
    fcmToken = await fcmService.getFcmToken();
    await fcmService.registerToken();

    // ユーザーID取得（Backend API経由）
    const userResponse = await api.get('/profile');
    testUserId = userResponse.data.id;

    console.log('[Integration Test Setup] User ID:', testUserId);
    console.log('[Integration Test Setup] FCM Token:', fcmToken?.substring(0, 20) + '...');
  }, 60000);

  afterAll(async () => {
    // クリーンアップ: 通知設定をデフォルトに戻す
    await api.patch('/profile/notification-settings', {
      push_task_enabled: true,
      push_group_enabled: true,
      push_token_enabled: true,
      push_system_enabled: true,
    });
    console.log('[Integration Test Cleanup] Reset notification settings to defaults');
  }, 30000);

  describe('Task通知フィルタリング', () => {
    /**
     * テストケース1: Task通知無効時、push送信されないこと
     * 
     * **検証フロー**:
     * 1. push_task_enabled=false に設定
     * 2. Backend: Task作成
     * 3. Backend: UserNotification作成 (type='task')
     * 4. Backend: FCM送信ロジック確認 → スキップされること
     * 5. Mobile: onMessage() 呼び出されないこと
     */
    it('should NOT send push when push_task_enabled=false', async () => {
      console.log('[Integration Test] Disabling task push notifications...');

      // Step 1: 通知設定更新
      await api.patch('/profile/notification-settings', {
        push_task_enabled: false,
      });

      const settingsResponse = await api.get('/profile/notification-settings');
      expect(settingsResponse.data.push_task_enabled).toBe(false);
      console.log('✅ Task push disabled');

      // Step 2: テスト通知送信試行（Backend側でフィルタリングされるべき）
      // 注: このテストは実際のTask作成ではなく、テスト用エンドポイントを使用
      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'task',
        user_id: testUserId,
      });

      // Backend側でpush送信がスキップされたことを確認
      expect(testNotificationResponse.data.push_sent).toBe(false);
      expect(testNotificationResponse.data.reason).toBe('push_task_enabled=false');
      console.log('✅ Push correctly skipped for task notification');

      // Step 3: Mobile側でonMessage()が呼び出されないことを確認
      // 注: 実デバイスでの確認が必要（ユニットテストでは検証困難）
      console.log('⚠️  Mobile reception test requires real device verification');
    }, 60000);

    /**
     * テストケース2: Task通知有効時、push送信されること
     */
    it('should send push when push_task_enabled=true', async () => {
      console.log('[Integration Test] Enabling task push notifications...');

      await api.patch('/profile/notification-settings', {
        push_task_enabled: true,
      });

      const settingsResponse = await api.get('/profile/notification-settings');
      expect(settingsResponse.data.push_task_enabled).toBe(true);
      console.log('✅ Task push enabled');

      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'task',
        user_id: testUserId,
      });

      expect(testNotificationResponse.data.push_sent).toBe(true);
      console.log('✅ Push correctly sent for task notification');
    }, 60000);
  });

  describe('Group通知フィルタリング', () => {
    it('should NOT send push when push_group_enabled=false', async () => {
      console.log('[Integration Test] Disabling group push notifications...');

      await api.patch('/profile/notification-settings', {
        push_group_enabled: false,
      });

      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'group',
        user_id: testUserId,
      });

      expect(testNotificationResponse.data.push_sent).toBe(false);
      expect(testNotificationResponse.data.reason).toBe('push_group_enabled=false');
      console.log('✅ Push correctly skipped for group notification');
    }, 60000);

    it('should send push when push_group_enabled=true', async () => {
      await api.patch('/profile/notification-settings', {
        push_group_enabled: true,
      });

      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'group',
        user_id: testUserId,
      });

      expect(testNotificationResponse.data.push_sent).toBe(true);
      console.log('✅ Push correctly sent for group notification');
    }, 60000);
  });

  describe('Token通知フィルタリング', () => {
    it('should NOT send push when push_token_enabled=false', async () => {
      console.log('[Integration Test] Disabling token push notifications...');

      await api.patch('/profile/notification-settings', {
        push_token_enabled: false,
      });

      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'token',
        user_id: testUserId,
      });

      expect(testNotificationResponse.data.push_sent).toBe(false);
      expect(testNotificationResponse.data.reason).toBe('push_token_enabled=false');
      console.log('✅ Push correctly skipped for token notification');
    }, 60000);

    it('should send push when push_token_enabled=true', async () => {
      await api.patch('/profile/notification-settings', {
        push_token_enabled: true,
      });

      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'token',
        user_id: testUserId,
      });

      expect(testNotificationResponse.data.push_sent).toBe(true);
      console.log('✅ Push correctly sent for token notification');
    }, 60000);
  });

  describe('System通知フィルタリング', () => {
    it('should NOT send push when push_system_enabled=false', async () => {
      console.log('[Integration Test] Disabling system push notifications...');

      await api.patch('/profile/notification-settings', {
        push_system_enabled: false,
      });

      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'system',
        user_id: testUserId,
      });

      expect(testNotificationResponse.data.push_sent).toBe(false);
      expect(testNotificationResponse.data.reason).toBe('push_system_enabled=false');
      console.log('✅ Push correctly skipped for system notification');
    }, 60000);

    it('should send push when push_system_enabled=true', async () => {
      await api.patch('/profile/notification-settings', {
        push_system_enabled: true,
      });

      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'system',
        user_id: testUserId,
      });

      expect(testNotificationResponse.data.push_sent).toBe(true);
      console.log('✅ Push correctly sent for system notification');
    }, 60000);
  });

  describe('複合フィルタリング', () => {
    /**
     * テストケース: 複数カテゴリ無効時のフィルタリング
     * 
     * **検証項目**:
     * - Task + Group無効 → 両方スキップされること
     * - Token + System有効 → 両方送信されること
     */
    it('should respect multiple category settings simultaneously', async () => {
      console.log('[Integration Test] Testing multiple category filtering...');

      await api.patch('/profile/notification-settings', {
        push_task_enabled: false,
        push_group_enabled: false,
        push_token_enabled: true,
        push_system_enabled: true,
      });

      // Task: スキップされる
      const taskResponse = await api.post('/notifications/test', {
        type: 'task',
        user_id: testUserId,
      });
      expect(taskResponse.data.push_sent).toBe(false);

      // Group: スキップされる
      const groupResponse = await api.post('/notifications/test', {
        type: 'group',
        user_id: testUserId,
      });
      expect(groupResponse.data.push_sent).toBe(false);

      // Token: 送信される
      const tokenResponse = await api.post('/notifications/test', {
        type: 'token',
        user_id: testUserId,
      });
      expect(tokenResponse.data.push_sent).toBe(true);

      // System: 送信される
      const systemResponse = await api.post('/notifications/test', {
        type: 'system',
        user_id: testUserId,
      });
      expect(systemResponse.data.push_sent).toBe(true);

      console.log('✅ Multiple category filtering works correctly');
    }, 120000); // 2分タイムアウト（複数API呼び出しのため）
  });

  describe('エッジケース', () => {
    /**
     * テストケース: 全カテゴリ無効時、全Push停止
     */
    it('should stop all push when all categories disabled', async () => {
      console.log('[Integration Test] Testing all-disabled scenario...');

      await api.patch('/profile/notification-settings', {
        push_task_enabled: false,
        push_group_enabled: false,
        push_token_enabled: false,
        push_system_enabled: false,
      });

      const types = ['task', 'group', 'token', 'system'];
      for (const type of types) {
        const response = await api.post('/notifications/test', {
          type,
          user_id: testUserId,
        });
        expect(response.data.push_sent).toBe(false);
      }

      console.log('✅ All push correctly stopped when all disabled');
    }, 120000);

    /**
     * テストケース: 設定未保存時、デフォルト（全有効）
     */
    it('should default to enabled when settings not explicitly set', async () => {
      console.log('[Integration Test] Testing default enabled behavior...');

      // 設定を明示的に削除（Backend API側でデフォルト値を返す）
      await api.delete('/profile/notification-settings');

      const settingsResponse = await api.get('/profile/notification-settings');
      expect(settingsResponse.data.push_task_enabled).toBe(true);
      expect(settingsResponse.data.push_group_enabled).toBe(true);
      expect(settingsResponse.data.push_token_enabled).toBe(true);
      expect(settingsResponse.data.push_system_enabled).toBe(true);

      console.log('✅ Default enabled behavior confirmed');
    }, 60000);
  });
});
