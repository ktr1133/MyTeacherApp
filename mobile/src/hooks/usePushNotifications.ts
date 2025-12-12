/**
 * Push通知受信カスタムフック
 * フォアグラウンド・バックグラウンド・終了状態での通知受信を処理
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 * @see https://rnfirebase.io/messaging/usage - React Native Firebase公式ドキュメント
 */
import { useEffect } from 'react';
import { Alert } from 'react-native';
import messaging, { FirebaseMessagingTypes } from '@react-native-firebase/messaging';
import { useNavigation } from '@react-navigation/native';

/**
 * Push通知データ型定義
 */
export interface PushNotificationData {
  /** 通知ID */
  notification_id?: string;
  /** タスクID */
  task_id?: string;
  /** グループID */
  group_id?: string;
  /** トークンパッケージID */
  token_package_id?: string;
  /** ナビゲーション先画面 */
  screen?: string;
  /** 追加パラメータ */
  [key: string]: any;
}

/**
 * Push通知フック戻り値の型
 */
export interface UsePushNotificationsReturn {
  /** フック初期化済みフラグ */
  isInitialized: boolean;
}

/**
 * Push通知受信・処理を管理するカスタムフック
 * 
 * **処理対象**:
 * 1. フォアグラウンド: アプリ起動中にPush通知受信 → アラート表示
 * 2. バックグラウンド: アプリ最小化中にPush通知をタップ → 画面遷移
 * 3. 終了状態: アプリ終了状態からPush通知をタップして起動 → 画面遷移
 * 
 * **使用方法**:
 * ```tsx
 * // App.tsxまたはナビゲーション初期化後に呼び出し
 * const { isInitialized } = usePushNotifications();
 * ```
 * 
 * **画面遷移ルール**:
 * - notification_id存在: NotificationDetailScreenへ遷移
 * - task_id存在: TaskDetailScreenへ遷移
 * - group_id存在: GroupDetailScreenへ遷移
 * - それ以外: NotificationListScreenへ遷移
 * 
 * @returns {UsePushNotificationsReturn} Push通知状態
 */
export const usePushNotifications = (): UsePushNotificationsReturn => {
  const navigation = useNavigation<any>();

  /**
   * Push通知データから遷移先画面を決定
   * 
   * @param data Push通知データ
   * @returns 遷移先画面名とパラメータ
   */
  const getNavigationTarget = (
    data?: PushNotificationData
  ): { screen: string; params?: any } => {
    if (!data) {
      return { screen: 'NotificationList' };
    }

    // 通知ID優先: 通知詳細画面へ遷移
    if (data.notification_id) {
      return {
        screen: 'NotificationDetail',
        params: { notificationId: data.notification_id },
      };
    }

    // タスクID: タスク詳細画面へ遷移
    if (data.task_id) {
      return {
        screen: 'TaskDetail',
        params: { taskId: data.task_id },
      };
    }

    // グループID: グループ詳細画面へ遷移
    if (data.group_id) {
      return {
        screen: 'GroupDetail',
        params: { groupId: data.group_id },
      };
    }

    // デフォルト: 通知一覧画面へ遷移
    return { screen: 'NotificationList' };
  };

  /**
   * Push通知タップ時の画面遷移処理
   * 
   * @param remoteMessage Firebase RemoteMessageオブジェクト
   */
  const handleNotificationOpen = (
    remoteMessage: FirebaseMessagingTypes.RemoteMessage
  ) => {
    console.log('[usePushNotifications] Notification opened:', remoteMessage);

    const { screen, params } = getNavigationTarget(
      remoteMessage.data as PushNotificationData
    );

    console.log('[usePushNotifications] Navigating to:', screen, params);

    // React Navigationで画面遷移
    try {
      if (params) {
        navigation.navigate(screen, params);
      } else {
        navigation.navigate(screen);
      }
    } catch (error) {
      console.error('[usePushNotifications] Navigation error:', error);
      // フォールバック: 通知一覧画面へ遷移
      navigation.navigate('NotificationList');
    }
  };

  useEffect(() => {
    /**
     * 1. フォアグラウンド通知ハンドラー
     * 
     * アプリ起動中にPush通知を受信した際に呼び出されます。
     * 通知はシステムトレイに表示されないため、アプリ内でアラート表示します。
     * 
     * **React Native Firebase公式ドキュメント**:
     * - https://rnfirebase.io/messaging/usage#foreground-state-messages
     */
    const unsubscribeForeground = messaging().onMessage(
      async (remoteMessage: FirebaseMessagingTypes.RemoteMessage) => {
        console.log('[usePushNotifications] Foreground message received:', remoteMessage);

        // 通知タイトル・本文を取得
        const title = remoteMessage.notification?.title || '新しい通知';
        const body = remoteMessage.notification?.body || '';

        // アラート表示（フォアグラウンドでは自動表示されない）
        Alert.alert(
          title,
          body,
          [
            {
              text: 'キャンセル',
              style: 'cancel',
            },
            {
              text: '開く',
              onPress: () => {
                // 「開く」をタップした場合、画面遷移
                handleNotificationOpen(remoteMessage);
              },
            },
          ],
          { cancelable: true }
        );

        // TODO: ローカル通知を表示する場合は、Notifeeライブラリを使用
        // https://notifee.app/react-native/docs/integrations/fcm
      }
    );

    /**
     * 2. バックグラウンド通知タップハンドラー
     * 
     * アプリがバックグラウンド状態で、ユーザーが通知をタップした際に呼び出されます。
     * アプリが前面に復帰した後、画面遷移を実行します。
     * 
     * **React Native Firebase公式ドキュメント**:
     * - https://rnfirebase.io/messaging/usage#background-&-quit-state-messages
     * - https://rnfirebase.io/messaging/notifications#handling-interaction
     */
    const unsubscribeBackground = messaging().onNotificationOpenedApp(
      (remoteMessage: FirebaseMessagingTypes.RemoteMessage) => {
        console.log(
          '[usePushNotifications] Background notification opened:',
          remoteMessage
        );

        // 画面遷移
        handleNotificationOpen(remoteMessage);
      }
    );

    /**
     * 3. 終了状態からの起動ハンドラー
     * 
     * アプリが完全に終了した状態で、ユーザーが通知をタップして起動した際に呼び出されます。
     * アプリ起動直後に初回通知を取得し、画面遷移を実行します。
     * 
     * **注意**: この処理は一度だけ実行されます（アプリ起動時のみ）。
     * 
     * **React Native Firebase公式ドキュメント**:
     * - https://rnfirebase.io/messaging/usage#background-&-quit-state-messages
     * - https://rnfirebase.io/reference/messaging#getInitialNotification
     */
    messaging()
      .getInitialNotification()
      .then((remoteMessage: FirebaseMessagingTypes.RemoteMessage | null) => {
        if (remoteMessage) {
          console.log(
            '[usePushNotifications] App opened from quit state by notification:',
            remoteMessage
          );

          // 画面遷移（少し遅延させてナビゲーションスタックが初期化されるのを待つ）
          setTimeout(() => {
            handleNotificationOpen(remoteMessage);
          }, 1000);
        } else {
          console.log('[usePushNotifications] App opened normally (not from notification)');
        }
      })
      .catch((error) => {
        console.error('[usePushNotifications] getInitialNotification error:', error);
      });

    // クリーンアップ: リスナー解除
    return () => {
      console.log('[usePushNotifications] Unsubscribing notification listeners');
      unsubscribeForeground();
      unsubscribeBackground();
    };
  }, [navigation]);

  return {
    isInitialized: true,
  };
};
