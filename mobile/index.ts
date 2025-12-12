import 'react-native-gesture-handler';
import { registerRootComponent } from 'expo';
import messaging, { FirebaseMessagingTypes } from '@react-native-firebase/messaging';

import App from './App';

/**
 * バックグラウンド・終了状態でのPush通知ハンドラー
 * 
 * アプリがバックグラウンドまたは完全に終了した状態でPush通知を受信した際に呼び出されます。
 * この関数は、アプリの起動前に実行されます。
 * 
 * **重要**:
 * - この関数はindex.tsでmessaging().setBackgroundMessageHandler()として登録する必要があります
 * - 非同期処理はPromiseを返す必要があります
 * - 30秒以内に処理を完了しないとOSにkillされる可能性があります
 * - データのみの通知（notification プロパティなし）でも呼び出されます
 * 
 * **React Native Firebase公式ドキュメント**:
 * - https://rnfirebase.io/messaging/usage#background-&-quit-state-messages
 * - https://rnfirebase.io/reference/messaging#setBackgroundMessageHandler
 * 
 * @param remoteMessage Firebase RemoteMessageオブジェクト
 * @returns Promise<void>
 */
messaging().setBackgroundMessageHandler(
  async (remoteMessage: FirebaseMessagingTypes.RemoteMessage): Promise<void> => {
    console.log('[Background Handler] Message received in background:', remoteMessage);

    // バックグラウンドでのデータ処理（例: ローカルDBに保存、キャッシュ更新など）
    try {
      // 通知データを取得
      const data = remoteMessage.data;

      if (data) {
        console.log('[Background Handler] Notification data:', data);

        // TODO: 必要に応じてローカルストレージにデータを保存
        // 例: AsyncStorageに通知を保存、未読カウント更新など
        // await AsyncStorage.setItem(`notification_${data.notification_id}`, JSON.stringify(data));
      }

      // 通知内容をログに記録
      if (remoteMessage.notification) {
        console.log(
          '[Background Handler] Notification title:',
          remoteMessage.notification.title
        );
        console.log(
          '[Background Handler] Notification body:',
          remoteMessage.notification.body
        );
      }

      console.log('[Background Handler] Processing completed successfully');
    } catch (error) {
      console.error('[Background Handler] Error processing background message:', error);
      // エラーが発生してもクラッシュさせない
    }

    // Promise<void>を返す（必須）
    return Promise.resolve();
  }
);

// registerRootComponent calls AppRegistry.registerComponent('main', () => App);
// It also ensures that whether you load the app in Expo Go or in a native build,
// the environment is set up appropriately
registerRootComponent(App);
