/**
 * ナビゲーション設定
 * 
 * NavigationFlow.mdに基づく認証フロー:
 * - 未認証: LoginScreen → RegisterScreen
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

// 認証画面インポート
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';

// Drawerナビゲーター（Development Build対応）
import DrawerNavigator from './DrawerNavigator';

const Stack = createNativeStackNavigator();

export default function AppNavigator() {
  const authData = useAuth();
  const { isVisible, currentData, hideAvatar } = useAvatarContext();
  const loading = authData.loading;
  const isAuthenticated = authData.isAuthenticated;

  console.log('[AppNavigator] loading:', loading, typeof loading);
  console.log('[AppNavigator] isAuthenticated:', isAuthenticated, typeof isAuthenticated);

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3b82f6" />
      </View>
    );
  }

  // 未認証時のナビゲーション
  if (!isAuthenticated) {
    return (
      <NavigationContainer key="guest">
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
        </Stack.Navigator>
      </NavigationContainer>
    );
  }

  // 認証済み時のナビゲーション（Development Build: Drawer navigation使用）
  return (
    <>
      <NavigationContainer key="authenticated">
        <DrawerNavigator />
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
