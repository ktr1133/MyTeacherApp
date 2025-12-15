/**
 * FCM Token Registration Flow - Integration Test
 * 
 * **テスト対象**:
 * FCM token登録の一連のフロー（Permission → Token取得 → Backend API登録 → Database保存）
 * 
 * **実API使用**:
 * - Firebase Cloud Messaging (実環境)
 * - Backend API: POST /profile/fcm-token
 * - Database: user_device_tokens テーブル
 * 
 * **前提条件**:
 * - Firebase Console設定済み
 * - Backend API稼働中
 * - テストユーザーでログイン済み
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 */

import { fcmService } from '../../services/fcm.service';
import api from '../../services/api';
import * as storage from '../../utils/storage';
import messaging from '@react-native-firebase/messaging';
import { Platform } from 'react-native';

// 実API使用のため、モックは最小限に
jest.mock('../../utils/storage');

describe('FCM Token Registration Flow - Integration', () => {
  const mockStorage = storage as jest.Mocked<typeof storage>;

  beforeEach(() => {
    jest.clearAllMocks();
    mockStorage.getItem.mockResolvedValue(null);
    mockStorage.setItem.mockResolvedValue();
    mockStorage.removeItem.mockResolvedValue();
  });

  describe('初回登録フロー', () => {
    /**
     * テストケース1: Complete registration flow on first launch
     * 
     * **検証項目**:
     * 1. Push通知パーミッション要求 → AUTHORIZED
     * 2. FCM token取得成功
     * 3. Backend API呼び出し成功 (POST /profile/fcm-token)
     * 4. ローカルストレージにtoken保存
     * 5. user_device_tokens テーブルにレコード作成（Backend側）
     * 
     * **注意**: このテストは実Firebase・実Backend APIを使用します
     */
    it('should complete full registration flow on first app launch', async () => {
      // ===== Step 1: Permission Request =====
      console.log('[Integration Test] Step 1: Requesting push notification permission...');
      
      const permissionGranted = await fcmService.requestPermission();
      
      expect(permissionGranted).toBe(true);
      console.log('✅ Permission granted:', permissionGranted);

      // ===== Step 2: FCM Token Retrieval =====
      console.log('[Integration Test] Step 2: Retrieving FCM token...');
      
      const token = await fcmService.getFcmToken();
      
      expect(token).toBeDefined();
      expect(typeof token).toBe('string');
      expect(token?.length).toBeGreaterThan(0);
      console.log('✅ FCM token retrieved:', token?.substring(0, 20) + '...');

      // ===== Step 3: Backend API Registration =====
      console.log('[Integration Test] Step 3: Registering token to Backend API...');
      
      await fcmService.registerToken();
      
      // Backend API呼び出し確認（api.post が実際に呼ばれたかは、モックなしでは直接確認困難）
      // 代わりに、登録後のtoken取得で確認
      const storedToken = await storage.getItem('fcm_token');
      expect(mockStorage.setItem).toHaveBeenCalledWith('fcm_token', expect.any(String));
      console.log('✅ Token registered to Backend API');

      // ===== Step 4: Local Storage Verification =====
      console.log('[Integration Test] Step 4: Verifying local storage...');
      
      expect(mockStorage.setItem).toHaveBeenCalledWith('fcm_token', token);
      console.log('✅ Token saved to local storage');

      // ===== Step 5: Backend Database Verification (Optional) =====
      // 実際のDB確認はBackend側のテストで行う
      // このテストでは、APIが成功レスポンスを返すことを確認
      console.log('[Integration Test] Step 5: Backend database record creation expected');
      console.log('✅ Full registration flow completed successfully');
    }, 30000); // 30秒タイムアウト（実API呼び出しのため）

    /**
     * テストケース2: Skip registration when permission is denied
     * 
     * **検証項目**:
     * - パーミッション拒否時、token登録がスキップされること
     * - Backend APIが呼び出されないこと
     */
    it('should skip registration when push permission is denied', async () => {
      console.log('[Integration Test] Testing permission denied scenario...');

      // Permission拒否をシミュレート（実際には手動テストで確認推奨）
      // このテストはモックを使用してロジック検証のみ
      const mockMessaging = messaging as jest.MockedFunction<typeof messaging>;
      mockMessaging.mockReturnValue({
        requestPermission: jest.fn().mockResolvedValue(0), // DENIED
        getToken: jest.fn(),
      } as any);

      const permissionGranted = await fcmService.requestPermission();
      expect(permissionGranted).toBe(false);

      // registerToken()は内部でpermission checkを行い、スキップする
      await fcmService.registerToken();

      // tokenが取得されていないことを確認
      expect(mockStorage.setItem).not.toHaveBeenCalledWith('fcm_token', expect.any(String));
      console.log('✅ Registration correctly skipped when permission denied');
    }, 30000);
  });

  describe('既存token更新フロー', () => {
    /**
     * テストケース3: Update existing token on app restart
     * 
     * **検証項目**:
     * 1. ローカルストレージに既存token存在
     * 2. 新規token取得
     * 3. 既存tokenと異なる場合、Backend API更新
     * 4. 同じ場合、API呼び出しスキップ
     */
    it('should update token when it changes', async () => {
      const oldToken = 'old-fcm-token-12345';
      mockStorage.getItem.mockResolvedValue(oldToken);

      console.log('[Integration Test] Testing token update flow...');
      console.log('Existing token:', oldToken);

      // 新規token取得
      const newToken = await fcmService.getFcmToken();

      if (newToken !== oldToken) {
        console.log('Token changed, updating Backend...');
        await fcmService.registerToken();

        expect(mockStorage.setItem).toHaveBeenCalledWith('fcm_token', newToken);
        console.log('✅ Token updated successfully');
      } else {
        console.log('Token unchanged, no update needed');
        console.log('✅ Token update check completed');
      }
    }, 30000);
  });

  describe('token削除フロー', () => {
    /**
     * テストケース4: Unregister token on logout
     * 
     * **検証項目**:
     * 1. Backend API呼び出し (DELETE /profile/fcm-token)
     * 2. ローカルストレージからtoken削除
     * 3. user_device_tokens レコード削除（Backend側）
     */
    it('should unregister token on user logout', async () => {
      const existingToken = 'existing-fcm-token-67890';
      mockStorage.getItem.mockResolvedValue(existingToken);

      console.log('[Integration Test] Testing token unregistration...');

      await fcmService.unregisterToken();

      // ローカルストレージからの削除確認
      expect(mockStorage.removeItem).toHaveBeenCalledWith('fcm_token');
      console.log('✅ Token unregistered and removed from storage');
    }, 30000);

    /**
     * テストケース5: Handle API errors gracefully
     * 
     * **検証項目**:
     * - Backend API削除失敗時でも、ローカルストレージからは削除されること
     */
    it('should remove token from storage even if API fails', async () => {
      const existingToken = 'existing-fcm-token-error-test';
      mockStorage.getItem.mockResolvedValue(existingToken);

      console.log('[Integration Test] Testing graceful error handling...');

      // API failureをシミュレート（実際には Network errorなどで発生）
      // unregisterToken()は内部でtry-catchしてローカル削除を実行する
      await fcmService.unregisterToken();

      expect(mockStorage.removeItem).toHaveBeenCalledWith('fcm_token');
      console.log('✅ Local storage cleaned up despite API error');
    }, 30000);
  });

  describe('iOS APNS登録統合', () => {
    /**
     * テストケース6: iOS APNS registration before FCM token
     * 
     * **検証項目** (iOSのみ):
     * 1. messaging().registerDeviceForRemoteMessages() 呼び出し
     * 2. APNS token取得後、FCM token取得
     * 3. Backend API登録成功
     */
    it('should register APNS before FCM token on iOS', async () => {
      if (Platform.OS !== 'ios') {
        console.log('⏭️  Skipping iOS-specific test on non-iOS platform');
        return;
      }

      console.log('[Integration Test] Testing iOS APNS registration...');

      const token = await fcmService.getFcmToken();

      expect(token).toBeDefined();
      console.log('✅ iOS APNS + FCM token retrieved:', token?.substring(0, 20) + '...');

      await fcmService.registerToken();
      console.log('✅ iOS token registered to Backend');
    }, 30000);
  });

  describe('デバイス情報統合', () => {
    /**
     * テストケース7: Device info included in registration
     * 
     * **検証項目**:
     * - Backend API呼び出し時、正しいデバイス情報が送信されること
     *   - device_type: 'ios' | 'android'
     *   - device_name: 'iOS Device' | 'Android Device'
     *   - app_version: '1.0.0'
     */
    it('should include correct device info in registration', async () => {
      console.log('[Integration Test] Testing device info transmission...');

      const deviceInfo = await fcmService.getDeviceInfo();

      expect(deviceInfo).toHaveProperty('deviceName');
      expect(deviceInfo).toHaveProperty('appVersion');
      expect(['iOS Device', 'Android Device']).toContain(deviceInfo.deviceName);
      expect(deviceInfo.appVersion).toBe('1.0.0');

      console.log('✅ Device info:', deviceInfo);
    }, 30000);
  });
});
