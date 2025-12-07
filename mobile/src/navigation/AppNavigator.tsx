/**
 * ナビゲーション設定
 */
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../contexts/AuthContext';
import { ActivityIndicator, View, StyleSheet } from 'react-native';

// 画面インポート
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
import HomeScreen from '../screens/HomeScreen';
import TaskListScreen from '../screens/tasks/TaskListScreen';
import TaskDetailScreen from '../screens/tasks/TaskDetailScreen';
import TaskEditScreen from '../screens/tasks/TaskEditScreen';
import CreateTaskScreen from '../screens/tasks/CreateTaskScreen';
import NotificationListScreen from '../screens/notifications/NotificationListScreen';
import NotificationDetailScreen from '../screens/notifications/NotificationDetailScreen';
import { ProfileScreen } from '../screens/profile/ProfileScreen';
import PasswordChangeScreen from '../screens/profile/PasswordChangeScreen';
import { SettingsScreen } from '../screens/settings/SettingsScreen';
import TagManagementScreen from '../screens/tags/TagManagementScreen';
import { TagDetailScreen } from '../screens/tags/TagDetailScreen';
import TagTasksScreen from '../screens/tasks/TagTasksScreen';

const Stack = createNativeStackNavigator();

export default function AppNavigator() {
  const authData = useAuth();
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

  // 認証済み時のナビゲーション
  return (
    <NavigationContainer key="authenticated">
      <Stack.Navigator>
        <Stack.Screen
          name="Home"
          component={HomeScreen}
          options={{
            title: 'MyTeacher',
          }}
        />
        <Stack.Screen
          name="TaskList"
          component={TaskListScreen}
          options={{
            title: 'タスク一覧',
          }}
        />
        <Stack.Screen
          name="TaskDetail"
          component={TaskDetailScreen}
          options={{
            title: 'タスク詳細',
          }}
        />
        <Stack.Screen
          name="TaskEdit"
          component={TaskEditScreen}
          options={{
            title: 'タスク編集',
          }}
        />
        <Stack.Screen
          name="TagTasks"
          component={TagTasksScreen}
          options={{
            headerShown: false,
          }}
        />
        <Stack.Screen
          name="CreateTask"
          component={CreateTaskScreen}
          options={{
            title: 'タスク作成',
          }}
        />
        <Stack.Screen
          name="NotificationList"
          component={NotificationListScreen}
          options={{
            title: '通知一覧',
          }}
        />
        <Stack.Screen
          name="NotificationDetail"
          component={NotificationDetailScreen}
          options={{
            title: '通知詳細',
          }}
        />
        <Stack.Screen
          name="Profile"
          component={ProfileScreen}
          options={{
            title: 'プロフィール',
          }}
        />
        <Stack.Screen
          name="PasswordChange"
          component={PasswordChangeScreen}
          options={{
            title: 'パスワード変更',
          }}
        />
        <Stack.Screen
          name="Settings"
          component={SettingsScreen}
          options={{
            title: '設定',
          }}
        />
        <Stack.Screen
          name="TagManagement"
          component={TagManagementScreen as React.ComponentType<any>}
          options={{
            title: 'タグ管理',
          }}
        />
        <Stack.Screen
          name="TagDetail"
          component={TagDetailScreen as React.ComponentType<any>}
          options={{
            title: 'タグ詳細',
          }}
        />
      </Stack.Navigator>
    </NavigationContainer>
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
