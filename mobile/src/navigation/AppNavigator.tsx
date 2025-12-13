/**
 * ナビゲーション設定
 * 
 * NavigationFlow.mdに基づく認証フロー:
 * - 未認証: LoginScreen → RegisterScreen → ForgotPasswordScreen
 * - 認証済み: DrawerNavigator（デフォルト: TaskList）
 * 
 * @see /home/ktr/mtdev/definitions/mobile/NavigationFlow.md
 */
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../contexts/AuthContext';
import { useAvatarContext } from '../contexts/AvatarContext';
import { ActivityIndicator, View, StyleSheet } from 'react-native';
import AvatarWidget from '../components/common/AvatarWidget';
import { navigationRef } from '../utils/navigationRef';
import { usePushNotifications } from '../hooks/usePushNotifications';
import { useThemedColors } from '../hooks/useThemedColors';

// 認証画面インポート
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
import ForgotPasswordScreen from '../screens/auth/ForgotPasswordScreen';

// Drawerナビゲーター（Development Build対応）
import DrawerNavigator from './DrawerNavigator';

const Stack = createNativeStackNavigator();

/**
 * Push通知統合コンポーネント
 * 
 * NavigationContainer内で実行する必要があります。
 */
function PushNotificationHandler() {
  usePushNotifications();
  return null;
}

export default function AppNavigator() {
  const authData = useAuth();
  const { isVisible, currentData, hideAvatar } = useAvatarContext();
  const { colors, accent } = useThemedColors();
  const loading = authData.loading;
  const isAuthenticated = authData.isAuthenticated;

  console.log('[AppNavigator] loading:', loading, typeof loading);
  console.log('[AppNavigator] isAuthenticated:', isAuthenticated, typeof isAuthenticated);

  if (loading) {
    return (
      <View style={[styles.loadingContainer, { backgroundColor: colors.background }]}>
        <ActivityIndicator size="large" color={accent.primary} />
      </View>
    );
  }

  // 未認証時のナビゲーション
  if (!isAuthenticated) {
    return (
      <NavigationContainer ref={navigationRef} key="guest">
        <Stack.Navigator>
          <Stack.Screen
            name="Login"
            component={LoginScreen}
            options={{
              headerShown: false,
            }}
          />
          <Stack.Screen
            name="Register"
            component={RegisterScreen}
            options={{
              title: '新規登録',
            }}
          />
          <Stack.Screen
            name="ForgotPassword"
            component={ForgotPasswordScreen}
            options={{
              headerShown: false,
            }}
          />
        </Stack.Navigator>
      </NavigationContainer>
    );
  }

  // 認証済み時のナビゲーション（Development Build: Drawer navigation使用）
  return (
    <>
      <NavigationContainer ref={navigationRef} key="authenticated">
        <DrawerNavigator />
        {/* Push通知ハンドラー: NavigationContainer内に配置 */}
        <PushNotificationHandler />
      </NavigationContainer>
      
      {/* アバターウィジェット（全画面共通） */}
      <AvatarWidget
        visible={isVisible}
        data={currentData}
        onClose={hideAvatar}
      />
    </>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f3f4f6',
  },
});
