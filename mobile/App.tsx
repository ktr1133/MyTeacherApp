import { GestureHandlerRootView } from 'react-native-gesture-handler';
import AppNavigator from './src/navigation/AppNavigator';
import { ThemeProvider } from './src/contexts/ThemeContext';
import { AuthProvider } from './src/contexts/AuthContext';
import { AvatarProvider } from './src/contexts/AvatarContext';
import { FCMProvider } from './src/contexts/FCMContext';
import { usePushNotifications } from './src/hooks/usePushNotifications';

/**
 * Push通知統合コンポーネント
 * 
 * AppNavigator初期化後にPush通知フックを呼び出します。
 * useNavigationフックを使用するため、NavigationContainerの内部で実行する必要があります。
 */
function PushNotificationHandler() {
  usePushNotifications();
  return null;
}

export default function App() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <AuthProvider>
        <FCMProvider>
          <ThemeProvider>
            <AvatarProvider>
              <AppNavigator />
              {/* 
                Push通知ハンドラー: ナビゲーション初期化後に配置
                usePushNotifications内でuseNavigationを使用するため、
                AppNavigatorより下に配置する必要があります
              */}
              <PushNotificationHandler />
            </AvatarProvider>
          </ThemeProvider>
        </FCMProvider>
      </AuthProvider>
    </GestureHandlerRootView>
  );
}
