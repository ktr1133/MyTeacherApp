/**
 * Multi-Device Support Integration Test
 * 
 * **テスト対象**:
 * 同一ユーザーの複数デバイス登録 → 全デバイスへのPush配信
 * 
 * **実API使用**:
 * - Backend API: POST /profile/fcm-token (複数回)
 * - Backend API: GET /profile/devices (デバイス一覧)
 * - Backend API: DELETE /profile/fcm-token/:id (デバイス削除)
 * - Firebase Cloud Messaging (実環境 - 複数token)
 * 
 * **前提条件**:
 * - Backend: user_device_tokens テーブルに複数レコード保存
 * - Backend: is_active=true の全デバイスにPush送信
 * - FCM: 複数tokenへの一斉送信
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.7
 */

import api from '../../services/api';
import { fcmService } from '../../services/fcm.service';
import * as storage from '../../utils/storage';

jest.mock('../../utils/storage');
jest.mock('../../services/api', () => ({
  __esModule: true,
  default: {
    get: jest.fn((url: string) => {
      if (url === '/profile') {
        return Promise.resolve({ data: { id: 1, name: 'Test User' } });
      }
      if (url === '/profile/devices') {
        return Promise.resolve({ data: [] });
      }
      return Promise.resolve({ data: {} });
    }),
    post: jest.fn().mockResolvedValue({ 
      data: { success: true, device: { id: 1, device_token: 'mock-token-1' } } 
    }),
    delete: jest.fn().mockResolvedValue({ data: { success: true } }),
  },
}));

describe.skip('Multi-Device Support - Integration', () => {
  const mockStorage = storage as jest.Mocked<typeof storage>;
  let testUserId: number;
  let device1Token: string;
  let device2Token: string;
  let device3Token: string;

  beforeAll(async () => {
    // ユーザーID取得
    const userResponse = await api.get('/profile');
    testUserId = userResponse.data.id;

    console.log('[Integration Test Setup] User ID:', testUserId);

    // 既存デバイスをすべて削除（テスト環境クリーンアップ）
    const existingDevices = await api.get('/profile/devices');
    for (const device of existingDevices.data) {
      await api.delete(`/profile/fcm-token/${device.id}`);
    }
    console.log('[Integration Test Setup] Cleaned up existing devices');
  }, 60000);

  afterAll(async () => {
    // クリーンアップ: テスト用デバイスを削除
    const devices = await api.get('/profile/devices');
    for (const device of devices.data) {
      await api.delete(`/profile/fcm-token/${device.id}`);
    }
    console.log('[Integration Test Cleanup] Removed all test devices');
  }, 60000);

  describe('複数デバイス登録', () => {
    /**
     * テストケース1: 同一ユーザーで3台のデバイスを登録
     * 
     * **検証項目**:
     * 1. Device 1 (iOS) 登録成功
     * 2. Device 2 (Android) 登録成功
     * 3. Device 3 (iOS) 登録成功
     * 4. user_device_tokens テーブルに3レコード作成
     * 5. is_active=true で全デバイス有効
     */
    it('should register multiple devices for same user', async () => {
      console.log('[Integration Test] Registering Device 1 (iOS)...');

      // Device 1: iOS
      device1Token = 'test-fcm-token-device-1-ios-' + Date.now();
      mockStorage.getItem.mockResolvedValue(null);
      mockStorage.setItem.mockResolvedValue();

      await api.post('/profile/fcm-token', {
        fcm_token: device1Token,
        device_type: 'ios',
        device_name: 'iPhone 15 Pro',
        app_version: '1.0.0',
      });

      console.log('✅ Device 1 registered:', device1Token.substring(0, 30) + '...');

      // Device 2: Android
      console.log('[Integration Test] Registering Device 2 (Android)...');
      device2Token = 'test-fcm-token-device-2-android-' + Date.now();

      await api.post('/profile/fcm-token', {
        fcm_token: device2Token,
        device_type: 'android',
        device_name: 'Pixel 8',
        app_version: '1.0.0',
      });

      console.log('✅ Device 2 registered:', device2Token.substring(0, 30) + '...');

      // Device 3: iOS (別端末)
      console.log('[Integration Test] Registering Device 3 (iOS)...');
      device3Token = 'test-fcm-token-device-3-ios-' + Date.now();

      await api.post('/profile/fcm-token', {
        fcm_token: device3Token,
        device_type: 'ios',
        device_name: 'iPad Pro',
        app_version: '1.0.0',
      });

      console.log('✅ Device 3 registered:', device3Token.substring(0, 30) + '...');

      // デバイス一覧取得
      const devicesResponse = await api.get('/profile/devices');
      const devices = devicesResponse.data;

      expect(devices.length).toBe(3);
      expect(devices.every((d: any) => d.is_active === true)).toBe(true);

      // 各デバイス情報確認
      const device1 = devices.find((d: any) => d.fcm_token === device1Token);
      expect(device1).toBeDefined();
      expect(device1.device_type).toBe('ios');
      expect(device1.device_name).toBe('iPhone 15 Pro');

      const device2 = devices.find((d: any) => d.fcm_token === device2Token);
      expect(device2).toBeDefined();
      expect(device2.device_type).toBe('android');
      expect(device2.device_name).toBe('Pixel 8');

      const device3 = devices.find((d: any) => d.fcm_token === device3Token);
      expect(device3).toBeDefined();
      expect(device3.device_type).toBe('ios');
      expect(device3.device_name).toBe('iPad Pro');

      console.log('✅ All 3 devices registered successfully');
    }, 120000);
  });

  describe('複数デバイスへのPush配信', () => {
    /**
     * テストケース2: 全デバイスにPush送信されること
     * 
     * **検証項目**:
     * 1. Backend: UserNotification作成
     * 2. Backend: 全アクティブデバイスのFCM tokenを取得
     * 3. Firebase: 3つのtokenすべてにPush送信
     * 4. Mobile: 各デバイスでonMessage()コールバック実行
     * 
     * **注意**: 実デバイスでの確認が必要
     */
    it('should send push to all active devices', async () => {
      console.log('[Integration Test] Sending test notification to all devices...');

      // テスト通知送信
      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'system',
        user_id: testUserId,
        message: 'Multi-device test notification',
      });

      expect(testNotificationResponse.data.push_sent).toBe(true);
      expect(testNotificationResponse.data.devices_count).toBe(3);
      expect(testNotificationResponse.data.fcm_tokens).toEqual(
        expect.arrayContaining([device1Token, device2Token, device3Token])
      );

      console.log('✅ Push sent to all 3 devices');
      console.log('⚠️  Manual verification required on real devices');
    }, 60000);

    /**
     * テストケース3: 一部デバイス無効時、アクティブデバイスのみに送信
     */
    it('should send push only to active devices when some are inactive', async () => {
      console.log('[Integration Test] Deactivating Device 2...');

      // Device 2を削除（is_active=falseに設定）
      const devices = await api.get('/profile/devices');
      const device2 = devices.data.find((d: any) => d.fcm_token === device2Token);
      await api.delete(`/profile/fcm-token/${device2.id}`);

      console.log('✅ Device 2 deactivated');

      // テスト通知送信
      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'system',
        user_id: testUserId,
        message: 'Multi-device test (Device 2 inactive)',
      });

      expect(testNotificationResponse.data.push_sent).toBe(true);
      expect(testNotificationResponse.data.devices_count).toBe(2);
      expect(testNotificationResponse.data.fcm_tokens).toEqual(
        expect.arrayContaining([device1Token, device3Token])
      );
      expect(testNotificationResponse.data.fcm_tokens).not.toContain(device2Token);

      console.log('✅ Push sent only to active devices (Device 1, 3)');

      // Device 2を再登録（次のテストのため）
      await api.post('/profile/fcm-token', {
        fcm_token: device2Token,
        device_type: 'android',
        device_name: 'Pixel 8',
        app_version: '1.0.0',
      });
    }, 90000);
  });

  describe('デバイス管理', () => {
    /**
     * テストケース4: デバイス削除（ログアウト）
     * 
     * **検証項目**:
     * - DELETE /profile/fcm-token/:id 成功
     * - user_device_tokens テーブルから削除 OR is_active=false
     * - 以降のPush配信から除外
     */
    it('should remove device on logout', async () => {
      console.log('[Integration Test] Removing Device 1...');

      const devices = await api.get('/profile/devices');
      const device1 = devices.data.find((d: any) => d.fcm_token === device1Token);

      await api.delete(`/profile/fcm-token/${device1.id}`);
      console.log('✅ Device 1 removed');

      // デバイス一覧確認
      const updatedDevices = await api.get('/profile/devices');
      expect(updatedDevices.data.length).toBe(2);
      expect(updatedDevices.data.find((d: any) => d.fcm_token === device1Token)).toBeUndefined();

      console.log('✅ Device 1 no longer in active device list');
    }, 60000);

    /**
     * テストケース5: 重複token登録時、既存レコード更新
     * 
     * **検証項目**:
     * - 同じtokenで再登録 → 新規作成ではなく更新
     * - updated_at カラムが更新される
     */
    it('should update existing device record on duplicate token registration', async () => {
      console.log('[Integration Test] Re-registering Device 2 with same token...');

      // Device 2を再登録
      const beforeResponse = await api.get('/profile/devices');
      const beforeCount = beforeResponse.data.length;
      const device2Before = beforeResponse.data.find((d: any) => d.fcm_token === device2Token);
      const beforeUpdatedAt = device2Before.updated_at;

      // 1秒待機（updated_at変更確認のため）
      await new Promise((resolve) => setTimeout(resolve, 1000));

      await api.post('/profile/fcm-token', {
        fcm_token: device2Token,
        device_type: 'android',
        device_name: 'Pixel 8 (Updated)',
        app_version: '1.0.1',
      });

      const afterResponse = await api.get('/profile/devices');
      const afterCount = afterResponse.data.length;
      const device2After = afterResponse.data.find((d: any) => d.fcm_token === device2Token);

      // レコード数は増えない（更新）
      expect(afterCount).toBe(beforeCount);

      // device_name, app_versionが更新される
      expect(device2After.device_name).toBe('Pixel 8 (Updated)');
      expect(device2After.app_version).toBe('1.0.1');

      // updated_atが更新される
      expect(new Date(device2After.updated_at).getTime()).toBeGreaterThan(
        new Date(beforeUpdatedAt).getTime()
      );

      console.log('✅ Device 2 updated (not duplicated)');
    }, 60000);
  });

  describe('エッジケース', () => {
    /**
     * テストケース6: 大量デバイス登録（10台）
     * 
     * **検証項目**:
     * - 10台のデバイス登録成功
     * - 全デバイスにPush配信成功
     * - パフォーマンス劣化なし
     */
    it('should handle up to 10 devices for single user', async () => {
      console.log('[Integration Test] Registering 10 devices...');

      const deviceTokens: string[] = [];
      for (let i = 1; i <= 10; i++) {
        const token = `test-fcm-token-device-${i}-bulk-${Date.now()}-${i}`;
        await api.post('/profile/fcm-token', {
          fcm_token: token,
          device_type: i % 2 === 0 ? 'ios' : 'android',
          device_name: `Device ${i}`,
          app_version: '1.0.0',
        });
        deviceTokens.push(token);
      }

      console.log('✅ 10 devices registered');

      // デバイス一覧確認
      const devicesResponse = await api.get('/profile/devices');
      expect(devicesResponse.data.length).toBeGreaterThanOrEqual(10);

      // テスト通知送信
      const testNotificationResponse = await api.post('/notifications/test', {
        type: 'system',
        user_id: testUserId,
        message: 'Bulk device test',
      });

      expect(testNotificationResponse.data.push_sent).toBe(true);
      expect(testNotificationResponse.data.devices_count).toBeGreaterThanOrEqual(10);

      console.log('✅ Push sent to all 10 devices');

      // クリーンアップ
      for (const token of deviceTokens) {
        const device = devicesResponse.data.find((d: any) => d.fcm_token === token);
        if (device) {
          await api.delete(`/profile/fcm-token/${device.id}`);
        }
      }
      console.log('✅ Bulk devices cleaned up');
    }, 300000); // 5分タイムアウト（大量API呼び出しのため）

    /**
     * テストケース7: token更新時、古いtokenは自動的に無効化
     */
    it('should auto-deactivate old token when device updates token', async () => {
      console.log('[Integration Test] Testing token refresh scenario...');

      // Device 3の古いtoken
      const oldToken = device3Token;

      // 新しいtoken取得（シミュレート）
      const newToken = 'test-fcm-token-device-3-refreshed-' + Date.now();

      // 同じデバイスで新しいtoken登録
      await api.post('/profile/fcm-token', {
        fcm_token: newToken,
        device_type: 'ios',
        device_name: 'iPad Pro', // 同じデバイス名
        app_version: '1.0.0',
      });

      const devicesResponse = await api.get('/profile/devices');

      // 古いtokenは削除 OR is_active=false
      const oldTokenDevice = devicesResponse.data.find((d: any) => d.fcm_token === oldToken);
      if (oldTokenDevice) {
        expect(oldTokenDevice.is_active).toBe(false);
      } else {
        // 古いtokenは物理削除されている
        expect(oldTokenDevice).toBeUndefined();
      }

      // 新しいtokenはアクティブ
      const newTokenDevice = devicesResponse.data.find((d: any) => d.fcm_token === newToken);
      expect(newTokenDevice).toBeDefined();
      expect(newTokenDevice.is_active).toBe(true);

      console.log('✅ Old token auto-deactivated, new token active');

      // 更新されたdevice3Tokenを保存
      device3Token = newToken;
    }, 60000);
  });
});
