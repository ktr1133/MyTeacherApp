/**
 * ナビゲーション設定
 */
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../contexts/AuthContext';
import { useAvatarContext } from '../contexts/AvatarContext';
import { ActivityIndicator, View, StyleSheet } from 'react-native';
import AvatarWidget from '../components/common/AvatarWidget';

// 画面インポート
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
import HomeScreen from '../screens/HomeScreen';
import TaskListScreen from '../screens/tasks/TaskListScreen';
import TaskDetailScreen from '../screens/tasks/TaskDetailScreen';
import TaskEditScreen from '../screens/tasks/TaskEditScreen';
import CreateTaskScreen from '../screens/tasks/CreateTaskScreen';
import TaskDecompositionScreen from '../screens/tasks/TaskDecompositionScreen';
import NotificationListScreen from '../screens/notifications/NotificationListScreen';
import NotificationDetailScreen from '../screens/notifications/NotificationDetailScreen';
import { ProfileScreen } from '../screens/profile/ProfileScreen';
import PasswordChangeScreen from '../screens/profile/PasswordChangeScreen';
import { SettingsScreen } from '../screens/settings/SettingsScreen';
import GroupManagementScreen from '../screens/group/GroupManagementScreen';
import ScheduledTaskListScreen from '../screens/scheduled-tasks/ScheduledTaskListScreen';
import ScheduledTaskHistoryScreen from '../screens/scheduled-tasks/ScheduledTaskHistoryScreen';
import ScheduledTaskCreateScreen from '../screens/scheduled-tasks/ScheduledTaskCreateScreen';
import ScheduledTaskEditScreen from '../screens/scheduled-tasks/ScheduledTaskEditScreen';
import TagManagementScreen from '../screens/tags/TagManagementScreen';
import { TagDetailScreen } from '../screens/tags/TagDetailScreen';
import TagTasksScreen from '../screens/tasks/TagTasksScreen';
import TokenBalanceScreen from '../screens/tokens/TokenBalanceScreen';
import TokenPackageListScreen from '../screens/tokens/TokenPurchaseWebViewScreen';
import TokenHistoryScreen from '../screens/tokens/TokenHistoryScreen';
import SubscriptionManageScreen from '../screens/subscriptions/SubscriptionManageScreen';
import SubscriptionInvoicesScreen from '../screens/subscriptions/SubscriptionInvoicesScreen';
import PerformanceScreen from '../screens/reports/PerformanceScreen';
import MonthlyReportScreen from '../screens/reports/MonthlyReportScreen';
import MemberSummaryScreen from '../screens/reports/MemberSummaryScreen';
import { AvatarCreateScreen } from '../screens/avatars/AvatarCreateScreen';
import { AvatarManageScreen } from '../screens/avatars/AvatarManageScreen';
import { AvatarEditScreen } from '../screens/avatars/AvatarEditScreen';
import PendingApprovalsScreen from '../screens/approvals/PendingApprovalsScreen';

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

  // 認証済み時のナビゲーション
  return (
    <>
      <NavigationContainer key="authenticated">
        <Stack.Navigator
          screenOptions={{
            headerBackTitleVisible: false, // 戻るボタンのタイトルを非表示
            headerBackTitle: '', // iOS: 戻るボタンのテキストを空文字
            gestureEnabled: true, // スワイプで戻る機能を有効化
          }}
        >
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
          name="AvatarManage"
          component={AvatarManageScreen as React.ComponentType<any>}
          options={{
            title: 'アバター管理',
          }}
        />
        <Stack.Screen
          name="AvatarCreate"
          component={AvatarCreateScreen as React.ComponentType<any>}
          options={{
            title: 'アバター作成',
          }}
        />
        <Stack.Screen
          name="AvatarEdit"
          component={AvatarEditScreen as React.ComponentType<any>}
          options={{
            title: 'アバター編集',
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
          name="TaskDecomposition"
          component={TaskDecompositionScreen}
          options={{
            title: 'AIタスク分解',
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
        <Stack.Screen
          name="TokenBalance"
          component={TokenBalanceScreen}
          options={{
            title: 'トークン残高',
          }}
        />
        <Stack.Screen
          name="TokenPackageList"
          component={TokenPackageListScreen}
          options={{
            headerShown: false,
          }}
        />
        <Stack.Screen
          name="TokenHistory"
          component={TokenHistoryScreen}
          options={{
            headerShown: false,
          }}
        />
        <Stack.Screen
          name="SubscriptionManage"
          component={SubscriptionManageScreen}
          options={{
            title: 'サブスクリプション管理',
          }}
        />
        <Stack.Screen
          name="SubscriptionInvoices"
          component={SubscriptionInvoicesScreen}
          options={{
            title: '請求履歴',
          }}
        />
        <Stack.Screen
          name="Performance"
          component={PerformanceScreen}
          options={{
            title: '実績',
          }}
        />
        <Stack.Screen
          name="MonthlyReport"
          component={MonthlyReportScreen}
          options={{
            title: '月次レポート',
          }}
        />
        <Stack.Screen
          name="MemberSummary"
          component={MemberSummaryScreen}
          options={{
            title: 'メンバー別概況',
          }}
        />
        <Stack.Screen
          name="GroupManagement"
          component={GroupManagementScreen}
          options={{
            title: 'グループ管理',
          }}
        />
        <Stack.Screen
          name="ScheduledTaskList"
          component={ScheduledTaskListScreen}
          options={{
            title: 'タスクスケジュール管理',
          }}
        />
        <Stack.Screen
          name="ScheduledTaskHistory"
          component={ScheduledTaskHistoryScreen}
          options={{
            title: '実行履歴',
          }}
        />
        <Stack.Screen
          name="ScheduledTaskCreate"
          component={ScheduledTaskCreateScreen}
          options={{
            title: 'スケジュール作成',
          }}
        />
        <Stack.Screen
          name="ScheduledTaskEdit"
          component={ScheduledTaskEditScreen}
          options={{
            title: 'スケジュール編集',
          }}
        />
        <Stack.Screen
          name="PendingApprovals"
          component={PendingApprovalsScreen}
          options={{
            title: '承認待ち一覧',
          }}
        />
      </Stack.Navigator>
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
