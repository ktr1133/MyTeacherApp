/**
 * FCMサービステスト
 * 
 * Phase 2.B-7.5: Push通知機能のユニットテスト
 * - fcmService.requestPermission()
 * - fcmService.getFcmToken()
 * - fcmService.registerToken()
 * - fcmService.unregisterToken()
 * - fcmService.getDeviceInfo()
 */

import { fcmService } from '../fcm.service';
import * as storage from '../../utils/storage';
import api from '../api';
import messaging from '@react-native-firebase/messaging';
import { Platform } from 'react-native';

// AuthorizationStatus定数（@react-native-firebase/messagingから）
const AuthorizationStatus = {
  NOT_DETERMINED: -1,
  DENIED: 0,
  AUTHORIZED: 1,
  PROVISIONAL: 2,
};

// モック設定
jest.mock('../api');
jest.mock('../../utils/storage');
jest.mock('@react-native-firebase/messaging', () => {
  const mockMessaging: any = jest.fn(() => ({
    requestPermission: jest.fn(),
    getToken: jest.fn(),
  }));
  
  // messaging関数自体にAuthorizationStatusを設定
  mockMessaging.AuthorizationStatus = {
    NOT_DETERMINED: -1,
    DENIED: 0,
    AUTHORIZED: 1,
    PROVISIONAL: 2,
  };
  
  return {
    __esModule: true,
    default: mockMessaging,
  };
});

// Platform.OSをモック可能にする
jest.mock('react-native', () => ({
  Platform: {
    OS: 'ios',
    select: jest.fn((obj) => obj.ios),
  },
}));

const mockedApi = api as jest.Mocked<typeof api>;
const mockedStorage = storage as jest.Mocked<typeof storage>;
const mockedMessaging = messaging as jest.MockedFunction<typeof messaging>;

describe('fcmService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    console.log = jest.fn();
    console.warn = jest.fn();
    console.error = jest.fn();
  });

  describe('requestPermission', () => {
    it('パーミッション許可時はtrueを返す (AUTHORIZED)', async () => {
      // Arrange
      const mockMessagingInstance = {
        requestPermission: jest.fn().mockResolvedValue(AuthorizationStatus.AUTHORIZED),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);

      const consoleSpy = jest.spyOn(console, 'log').mockImplementation();

      // Act
      const result = await fcmService.requestPermission();

      // Assert
      expect(result).toBe(true);
      expect(mockMessagingInstance.requestPermission).toHaveBeenCalled();
      expect(console.log).toHaveBeenCalledWith('[FCM] Permission status:', 1);
      expect(console.log).toHaveBeenCalledWith('[FCM] Permission granted');

      consoleSpy.mockRestore();
    });

    it('パーミッション許可時はtrueを返す (PROVISIONAL)', async () => {
      // Arrange
      const mockMessagingInstance = {
        requestPermission: jest.fn().mockResolvedValue(AuthorizationStatus.PROVISIONAL),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);

      const consoleSpy = jest.spyOn(console, 'log').mockImplementation();

      // Act
      const result = await fcmService.requestPermission();

      // Assert
      expect(result).toBe(true);
      expect(console.log).toHaveBeenCalledWith('[FCM] Permission status:', 2);
      expect(console.log).toHaveBeenCalledWith('[FCM] Permission granted');

      consoleSpy.mockRestore();
    });

    it('パーミッション拒否時はfalseを返す', async () => {
      // Arrange
      const mockMessagingInstance = {
        requestPermission: jest.fn().mockResolvedValue(AuthorizationStatus.DENIED),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);

      const consoleSpy = jest.spyOn(console, 'log').mockImplementation();

      // Act
      const result = await fcmService.requestPermission();

      // Assert
      expect(result).toBe(false);
      expect(console.log).toHaveBeenCalledWith('[FCM] Permission status:', 0);
      expect(console.log).toHaveBeenCalledWith('[FCM] Permission denied');

      consoleSpy.mockRestore();
    });

    it('エラー時はfalseを返す', async () => {
      // Arrange
      const mockMessagingInstance = {
        requestPermission: jest.fn().mockRejectedValue(new Error('Permission error')),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);

      // Act
      const result = await fcmService.requestPermission();

      // Assert
      expect(result).toBe(false);
      expect(console.error).toHaveBeenCalledWith(
        '[FCM] Permission request failed:',
        expect.any(Error)
      );
    });
  });

  describe('getFcmToken', () => {
    it('トークン取得成功時はトークンを返す', async () => {
      // Arrange
      const mockToken = 'fcm-token-test-12345';
      const mockMessagingInstance = {
        getToken: jest.fn().mockResolvedValue(mockToken),
        isDeviceRegisteredForRemoteMessages: true,
        registerDeviceForRemoteMessages: jest.fn().mockResolvedValue(undefined),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);
      mockedStorage.setItem.mockResolvedValue();
      Platform.OS = 'ios';

      // Act
      const result = await fcmService.getFcmToken();

      // Assert
      expect(result).toBe(mockToken);
      expect(mockMessagingInstance.getToken).toHaveBeenCalled();
      expect(mockedStorage.setItem).toHaveBeenCalledWith('fcm_token', mockToken);
    });

    it('トークンが空の場合はnullを返す', async () => {
      // Arrange
      const mockMessagingInstance = {
        getToken: jest.fn().mockResolvedValue(''),
        isDeviceRegisteredForRemoteMessages: true,
        registerDeviceForRemoteMessages: jest.fn().mockResolvedValue(undefined),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);
      Platform.OS = 'ios';

      // Act
      const result = await fcmService.getFcmToken();

      // Assert
      expect(result).toBeNull();
      expect(console.warn).toHaveBeenCalledWith('[FCM] Token is empty');
    });

    it('エラー時はnullを返す', async () => {
      // Arrange
      const mockMessagingInstance = {
        getToken: jest.fn().mockRejectedValue(new Error('Token fetch error')),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);

      // Act
      const result = await fcmService.getFcmToken();

      // Assert
      expect(result).toBeNull();
      expect(console.error).toHaveBeenCalledWith(
        '[FCM] Token fetch failed:',
        expect.any(Error)
      );
    });
  });

  describe('getDeviceInfo', () => {
    it('デバイス情報を返す（iOS）', async () => {
      // Arrange
      Platform.OS = 'ios';

      // Act
      const result = await fcmService.getDeviceInfo();

      // Assert
      expect(result.deviceName).toBe('iOS Device');
      expect(result.appVersion).toBe('1.0.0');
    });

    it('デバイス情報を返す(Android)', async () => {
      // Arrange
      // Platform.OSをandroidに設定
      jest.spyOn(Platform, 'select').mockImplementation((obj: any) => obj.android);

      // Act
      const result = await fcmService.getDeviceInfo();

      // Assert
      expect(result.deviceName).toBe('Android Device');
      expect(result.appVersion).toBe('1.0.0');

      // モックをクリア
      jest.restoreAllMocks();
    });
  });

  describe('registerToken', () => {
    it('トークンを正常に登録する', async () => {
      // Arrange
      const mockToken = 'fcm-token-register-test';
      const mockMessagingInstance = {
        requestPermission: jest.fn().mockResolvedValue(AuthorizationStatus.AUTHORIZED),
        getToken: jest.fn().mockResolvedValue(mockToken),
        isDeviceRegisteredForRemoteMessages: true,
        registerDeviceForRemoteMessages: jest.fn().mockResolvedValue(undefined),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);
      mockedStorage.setItem.mockResolvedValue();
      mockedApi.post.mockResolvedValue({ data: { success: true } });
      Platform.OS = 'ios';
      
      // Platform.selectをiOSに設定
      jest.spyOn(Platform, 'select').mockImplementation((obj: any) => obj.ios);

      const consoleSpy = jest.spyOn(console, 'log').mockImplementation();

      // Act
      await fcmService.registerToken();

      // Assert
      expect(mockMessagingInstance.requestPermission).toHaveBeenCalled();
      expect(mockMessagingInstance.getToken).toHaveBeenCalled();
      expect(mockedApi.post).toHaveBeenCalledWith('/profile/fcm-token', {
        device_token: mockToken,
        device_type: 'ios',
        device_name: 'iOS Device',
        app_version: '1.0.0',
      });

      consoleSpy.mockRestore();
      jest.restoreAllMocks();
    });

    it('パーミッション拒否時は登録をスキップする', async () => {
      // Arrange
      const mockMessagingInstance = {
        requestPermission: jest.fn().mockResolvedValue(AuthorizationStatus.DENIED),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);

      const consoleLogSpy = jest.spyOn(console, 'log').mockImplementation();
      const consoleWarnSpy = jest.spyOn(console, 'warn').mockImplementation();

      // Act
      await fcmService.registerToken();

      // Assert
      expect(console.warn).toHaveBeenCalledWith('[FCM] Registration skipped: permission denied');
      expect(mockedApi.post).not.toHaveBeenCalled();

      consoleLogSpy.mockRestore();
      consoleWarnSpy.mockRestore();
    });

    it('トークン取得失敗時は登録をスキップする', async () => {
      // Arrange
      const mockMessagingInstance = {
        requestPermission: jest.fn().mockResolvedValue(AuthorizationStatus.AUTHORIZED),
        getToken: jest.fn().mockResolvedValue(null),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);

      const consoleLogSpy = jest.spyOn(console, 'log').mockImplementation();
      const consoleWarnSpy = jest.spyOn(console, 'warn').mockImplementation();

      // Act
      await fcmService.registerToken();

      // Assert
      expect(console.warn).toHaveBeenCalledWith('[FCM] Registration skipped: token not available');
      expect(mockedApi.post).not.toHaveBeenCalled();

      consoleLogSpy.mockRestore();
      consoleWarnSpy.mockRestore();
    });

    it('API呼び出し失敗時は例外をスローする', async () => {
      // Arrange
      const mockToken = 'fcm-token-error-test';
      const mockMessagingInstance = {
        requestPermission: jest.fn().mockResolvedValue(AuthorizationStatus.AUTHORIZED),
        getToken: jest.fn().mockResolvedValue(mockToken),
        isDeviceRegisteredForRemoteMessages: true,
        registerDeviceForRemoteMessages: jest.fn().mockResolvedValue(undefined),
      };
      mockedMessaging.mockReturnValue(mockMessagingInstance as any);
      mockedStorage.setItem.mockResolvedValue();
      mockedApi.post.mockRejectedValue(new Error('API error'));
      Platform.OS = 'ios';

      const consoleLogSpy = jest.spyOn(console, 'log').mockImplementation();

      // Act & Assert
      await expect(fcmService.registerToken()).rejects.toThrow('API error');

      consoleLogSpy.mockRestore();
    });
  });

  describe('unregisterToken', () => {
    it('トークンを正常に削除する', async () => {
      // Arrange
      const mockToken = 'fcm-token-unregister-test';
      mockedStorage.getItem.mockResolvedValue(mockToken);
      mockedApi.delete.mockResolvedValue({ data: { success: true } });
      mockedStorage.removeItem.mockResolvedValue();

      // Act
      await fcmService.unregisterToken();

      // Assert
      expect(mockedStorage.getItem).toHaveBeenCalledWith('fcm_token');
      expect(mockedApi.delete).toHaveBeenCalledWith('/profile/fcm-token', {
        data: {
          device_token: mockToken,
        },
      });
      expect(mockedStorage.removeItem).toHaveBeenCalledWith('fcm_token');
    });

    it('トークンが存在しない場合はスキップする', async () => {
      // Arrange
      mockedStorage.getItem.mockResolvedValue(null);

      // Act
      await fcmService.unregisterToken();

      // Assert
      expect(console.warn).toHaveBeenCalledWith('[FCM] Unregister skipped: token not found in storage');
      expect(mockedApi.delete).not.toHaveBeenCalled();
    });

    it('API呼び出し失敗時もローカルストレージから削除する', async () => {
      // Arrange
      const mockToken = 'fcm-token-delete-error-test';
      mockedStorage.getItem.mockResolvedValue(mockToken);
      mockedApi.delete.mockRejectedValue(new Error('API error'));
      mockedStorage.removeItem.mockResolvedValue();

      // Act
      await fcmService.unregisterToken();

      // Assert
      expect(console.error).toHaveBeenCalledWith('[FCM] Backend unregister failed:', expect.any(Error));
      expect(mockedStorage.removeItem).toHaveBeenCalledWith('fcm_token');
    });
  });
});
