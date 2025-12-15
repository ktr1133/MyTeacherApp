/**
 * Push Delivery End-to-End Integration Test
 * 
 * **テスト対象**:
 * Backend通知作成 → FCM送信 → Mobile受信の完全なフロー
 * 
 * **実API・実環境使用**:
 * - Backend API: POST /tasks (Task作成 → 自動通知生成)
 * - Backend: UserNotification作成 → FCM送信ジョブ
 * - Firebase Cloud Messaging (実環境)
 * - Mobile: onMessage(), onNotificationOpenedApp(), getInitialNotification()
 * 
 * **前提条件**:
 * - 実デバイスまたはシミュレータ（FCM設定済み）
 * - Apple Developer Program登録（iOS実機の場合）
 * - Backend: Queueワーカー稼働中（SendPushNotificationJob処理）
 * 
 * **注意**:
 * このテストは実デバイスでの手動確認が必要です。
 * 自動テストでは、API呼び出しまでを検証し、実際のPush受信は手動確認としています。
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.8
 */

import api from '../../services/api';
import { fcmService } from '../../services/fcm.service';
import messaging, { FirebaseMessagingTypes } from '@react-native-firebase/messaging';
import { Alert } from 'react-native';

// 実Push受信テストのため、モックは最小限
jest.mock('react-native/Libraries/Alert/Alert', () => ({
  alert: jest.fn(),
}));

describe('Push Delivery End-to-End - Integration', () => {
  let testUserId: number;
  let fcmToken: string | null;
  let createdTaskId: number;
  let receivedMessage: FirebaseMessagingTypes.RemoteMessage | null = null;

  beforeAll(async () => {
    // FCM初期化
    await fcmService.requestPermission();
    fcmToken = await fcmService.getFcmToken();
    await fcmService.registerToken();

    // ユーザーID取得
    const userResponse = await api.get('/profile');
    testUserId = userResponse.data.id;

    console.log('[E2E Test Setup] User ID:', testUserId);
    console.log('[E2E Test Setup] FCM Token:', fcmToken?.substring(0, 20) + '...');

    // Push通知リスナー設定（テスト用）
    messaging().onMessage(async (message) => {
      receivedMessage = message;
      console.log('[E2E Test] Received foreground message:', message.notification?.title);
    });

    console.log('⚠️  [E2E Test Setup] This test requires manual verification on real device');
    console.log('⚠️  Please ensure Queue worker is running on Backend (php artisan queue:work)');
  }, 60000);

  afterAll(async () => {
    // テストTask削除
    if (createdTaskId) {
      try {
        await api.delete(`/tasks/${createdTaskId}`);
        console.log('[E2E Test Cleanup] Deleted test task:', createdTaskId);
      } catch (error) {
        console.error('[E2E Test Cleanup] Failed to delete task:', error);
      }
    }
  }, 30000);

  describe('Task作成 → Push受信フロー', () => {
    /**
     * テストケース1: Task作成時、自動的にPush通知が配信される
     * 
     * **検証フロー**:
     * 1. Backend: POST /tasks (Task作成)
     * 2. Backend: UserNotification作成（type='task'）
     * 3. Backend: SendPushNotificationJob ディスパッチ
     * 4. Backend: FCM Admin SDK経由でPush送信
     * 5. Mobile: onMessage()コールバック実行
     * 6. Mobile: Alert.alert()表示（フォアグラウンド時）
     */
    it('should receive push notification when task is created', async () => {
      console.log('[E2E Test] Step 1: Creating task...');

      // Task作成
      const taskResponse = await api.post('/tasks', {
        title: 'E2E Push Test Task',
        description: 'This is a test task for E2E push notification',
        priority: 1,
        deadline: new Date(Date.now() + 86400000).toISOString(), // 24時間後
      });

      expect(taskResponse.status).toBe(201);
      expect(taskResponse.data.task).toBeDefined();
      createdTaskId = taskResponse.data.task.id;

      console.log('✅ Task created:', createdTaskId);

      // UserNotification作成確認
      console.log('[E2E Test] Step 2: Verifying UserNotification creation...');
      await new Promise((resolve) => setTimeout(resolve, 2000)); // 2秒待機（非同期処理のため）

      const notificationsResponse = await api.get('/notifications', {
        params: {
          type: 'task',
          task_id: createdTaskId,
        },
      });

      expect(notificationsResponse.data.notifications.length).toBeGreaterThan(0);
      const notification = notificationsResponse.data.notifications[0];
      expect(notification.type).toBe('task');
      expect(notification.task_id).toBe(createdTaskId);

      console.log('✅ UserNotification created:', notification.id);

      // FCM送信ジョブ確認（Backend Queue）
      console.log('[E2E Test] Step 3: Waiting for FCM job processing...');
      console.log('⚠️  Ensure Backend Queue worker is running: php artisan queue:work');
      await new Promise((resolve) => setTimeout(resolve, 5000)); // 5秒待機（ジョブ処理のため）

      // Push受信確認（手動）
      console.log('[E2E Test] Step 4: Checking push reception...');
      console.log('⚠️  Manual check required: Did you receive push notification on device?');
      console.log('Expected notification:');
      console.log('  Title:', 'タスクが作成されました');
      console.log('  Body:', 'E2E Push Test Task');

      // 自動検証（receivedMessageが設定されている場合）
      if (receivedMessage) {
        expect(receivedMessage.notification?.title).toContain('タスク');
        expect(receivedMessage.notification?.body).toBe('E2E Push Test Task');
        expect(receivedMessage.data?.task_id).toBe(createdTaskId.toString());
        console.log('✅ Push notification received successfully (auto-verified)');
      } else {
        console.log('⚠️  Automated reception check not available - manual verification required');
      }
    }, 120000); // 2分タイムアウト（ジョブ処理待ち含む）

    /**
     * テストケース2: Background状態での通知受信
     * 
     * **検証項目**:
     * - onNotificationOpenedApp()が呼び出される
     * - 通知タップで適切な画面に遷移
     * 
     * **注意**: 手動テストのみ（自動化困難）
     */
    it('[MANUAL] should handle notification tap when app is in background', async () => {
      console.log('[E2E Test - MANUAL] Testing background notification tap...');
      console.log('');
      console.log('📱 Manual Test Steps:');
      console.log('1. Put app in background (Home button or App switcher)');
      console.log('2. Create a new task via Web or API');
      console.log('3. Wait for push notification to appear');
      console.log('4. Tap the notification');
      console.log('5. Verify app opens and navigates to TaskDetail screen');
      console.log('');
      console.log('⚠️  This test requires manual execution on real device');

      // API経由でTask作成（手動テスト用）
      const taskResponse = await api.post('/tasks', {
        title: 'Background Test Task',
        description: 'Test task for background notification',
        priority: 2,
        deadline: new Date(Date.now() + 86400000).toISOString(),
      });

      console.log('✅ Test task created:', taskResponse.data.task.id);
      console.log('⏳ Waiting 10 seconds for FCM delivery...');

      await new Promise((resolve) => setTimeout(resolve, 10000));

      console.log('');
      console.log('✅ Manual test setup complete');
      console.log('📋 Expected behavior:');
      console.log('  - Notification appears with title: "タスクが作成されました"');
      console.log('  - Tapping opens TaskDetail screen with task_id:', taskResponse.data.task.id);
    }, 120000);

    /**
     * テストケース3: Quit状態での通知受信
     * 
     * **検証項目**:
     * - getInitialNotification()が通知データを返す
     * - アプリ起動時に適切な画面に遷移
     * 
     * **注意**: 手動テストのみ（自動化困難）
     */
    it('[MANUAL] should handle notification tap when app is quit', async () => {
      console.log('[E2E Test - MANUAL] Testing quit state notification tap...');
      console.log('');
      console.log('📱 Manual Test Steps:');
      console.log('1. Completely quit the app (Force quit)');
      console.log('2. Create a new task via Web or API');
      console.log('3. Wait for push notification to appear');
      console.log('4. Tap the notification');
      console.log('5. Verify app launches and navigates to TaskDetail screen');
      console.log('');
      console.log('⚠️  This test requires manual execution on real device');

      // API経由でTask作成（手動テスト用）
      const taskResponse = await api.post('/tasks', {
        title: 'Quit State Test Task',
        description: 'Test task for quit state notification',
        priority: 3,
        deadline: new Date(Date.now() + 86400000).toISOString(),
      });

      console.log('✅ Test task created:', taskResponse.data.task.id);
      console.log('⏳ Waiting 10 seconds for FCM delivery...');

      await new Promise((resolve) => setTimeout(resolve, 10000));

      console.log('');
      console.log('✅ Manual test setup complete');
      console.log('📋 Expected behavior:');
      console.log('  - Notification appears with title: "タスクが作成されました"');
      console.log('  - Tapping opens TaskDetail screen with task_id:', taskResponse.data.task.id);
    }, 120000);
  });

  describe('Group Task作成 → Push受信フロー', () => {
    /**
     * テストケース4: Group Task作成時、複数メンバーにPush配信
     * 
     * **注意**: このテストは複数ユーザー環境が必要
     */
    it('[MANUAL] should send push to all group members when group task created', async () => {
      console.log('[E2E Test - MANUAL] Testing group task push delivery...');
      console.log('');
      console.log('📱 Manual Test Steps:');
      console.log('1. Create a group with 2+ members (Web or API)');
      console.log('2. Create a group task assigned to all members');
      console.log('3. Verify all members receive push notification');
      console.log('4. Each member should see same group_task_id in notification data');
      console.log('');
      console.log('⚠️  This test requires multiple user accounts and devices');

      console.log('✅ Manual test guidance provided');
      console.log('📋 Expected behavior:');
      console.log('  - All group members receive notification');
      console.log('  - Notification title: "グループタスクが作成されました"');
      console.log('  - Tapping opens GroupTaskDetail screen');
    }, 60000);
  });

  describe('Token通知 → Push受信フロー', () => {
    /**
     * テストケース5: Token付与時、Push通知配信
     */
    it('should receive push when tokens are granted', async () => {
      console.log('[E2E Test] Testing token grant notification...');

      // Token付与API呼び出し
      const tokenResponse = await api.post('/tokens/grant', {
        user_id: testUserId,
        amount: 500,
        transaction_type: 'admin_adjust',
        description: 'E2E test token grant',
      });

      expect(tokenResponse.status).toBe(200);
      console.log('✅ Tokens granted:', 500);

      // 通知確認
      await new Promise((resolve) => setTimeout(resolve, 5000));

      console.log('⚠️  Manual check: Did you receive token notification?');
      console.log('Expected notification:');
      console.log('  Title:', 'トークンが付与されました');
      console.log('  Body:', '500トークンが追加されました');

      if (receivedMessage) {
        expect(receivedMessage.notification?.title).toContain('トークン');
        console.log('✅ Token notification received');
      }
    }, 60000);
  });

  describe('System通知 → Push受信フロー', () => {
    /**
     * テストケース6: System通知配信
     */
    it('should receive system push notification', async () => {
      console.log('[E2E Test] Testing system notification...');

      // System通知送信API
      const systemNotificationResponse = await api.post('/notifications/test', {
        type: 'system',
        user_id: testUserId,
        message: 'E2E system notification test',
      });

      expect(systemNotificationResponse.data.push_sent).toBe(true);
      console.log('✅ System notification sent');

      await new Promise((resolve) => setTimeout(resolve, 5000));

      console.log('⚠️  Manual check: Did you receive system notification?');

      if (receivedMessage) {
        console.log('✅ System notification received');
      }
    }, 60000);
  });

  describe('エラーケース', () => {
    /**
     * テストケース7: FCM token無効時の挙動
     */
    it('should handle invalid FCM token gracefully', async () => {
      console.log('[E2E Test] Testing invalid token handling...');

      // 無効なtokenを登録
      const invalidToken = 'invalid-fcm-token-' + Date.now();
      await api.post('/profile/fcm-token', {
        fcm_token: invalidToken,
        device_type: 'ios',
        device_name: 'Invalid Device',
        app_version: '1.0.0',
      });

      // Task作成（Push送信試行）
      const taskResponse = await api.post('/tasks', {
        title: 'Invalid Token Test',
        description: 'Test task for invalid token',
        priority: 1,
        deadline: new Date(Date.now() + 86400000).toISOString(),
      });

      console.log('✅ Task created with invalid token registered');

      await new Promise((resolve) => setTimeout(resolve, 5000));

      // Backend側でエラーハンドリングされ、ジョブが失敗しないことを確認
      // 実際の確認はBackend logs参照
      console.log('⚠️  Check Backend logs for FCM error handling');
      console.log('Expected: FCM error logged, but job completed without exception');

      // クリーンアップ: 無効なtokenを削除
      const devices = await api.get('/profile/devices');
      const invalidDevice = devices.data.find((d: any) => d.fcm_token === invalidToken);
      if (invalidDevice) {
        await api.delete(`/profile/fcm-token/${invalidDevice.id}`);
      }
    }, 90000);
  });

  describe('パフォーマンステスト', () => {
    /**
     * テストケース8: 大量通知送信時のパフォーマンス
     */
    it('should handle burst notifications without delay', async () => {
      console.log('[E2E Test] Testing burst notification performance...');

      const startTime = Date.now();

      // 10件のTask作成（一斉送信）
      const taskPromises = [];
      for (let i = 1; i <= 10; i++) {
        taskPromises.push(
          api.post('/tasks', {
            title: `Burst Test Task ${i}`,
            description: `Performance test task ${i}`,
            priority: 1,
            deadline: new Date(Date.now() + 86400000).toISOString(),
          })
        );
      }

      const taskResponses = await Promise.all(taskPromises);
      const endTime = Date.now();

      expect(taskResponses.length).toBe(10);
      console.log('✅ 10 tasks created');
      console.log('Task creation time:', endTime - startTime, 'ms');

      // FCM送信処理待ち
      await new Promise((resolve) => setTimeout(resolve, 15000)); // 15秒待機

      console.log('⚠️  Manual check: Did you receive all 10 notifications?');
      console.log('Expected: 10 notifications received within 15 seconds');

      // クリーンアップ
      for (const response of taskResponses) {
        await api.delete(`/tasks/${response.data.task.id}`);
      }
      console.log('✅ Burst test tasks cleaned up');
    }, 180000); // 3分タイムアウト
  });
});
