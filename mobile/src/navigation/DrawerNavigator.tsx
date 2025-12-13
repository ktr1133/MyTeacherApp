/**
 * Drawerナビゲーター
 * 
 * ハンバーガーメニュー（ドロワー）を使用したナビゲーション
 * 
 * @see /home/ktr/mtdev/definitions/mobile/NavigationFlow.md - Section 3
 * @see /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 */

import React from 'react';
import { Text, TouchableOpacity } from 'react-native';
import { createDrawerNavigator } from '@react-navigation/drawer';
import DrawerContent from '../components/common/DrawerContent';
import HeaderNotificationIcon from '../components/common/HeaderNotificationIcon';

// 画面インポート
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
import { NotificationSettingsScreen } from '../screens/settings/NotificationSettingsScreen';
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
import SubscriptionWebViewScreen from '../screens/subscriptions/SubscriptionWebViewScreen';
import PerformanceScreen from '../screens/reports/PerformanceScreen';
import MonthlyReportScreen from '../screens/reports/MonthlyReportScreen';
import MemberSummaryScreen from '../screens/reports/MemberSummaryScreen';
import { AvatarCreateScreen } from '../screens/avatars/AvatarCreateScreen';
import { AvatarManageScreen } from '../screens/avatars/AvatarManageScreen';
import { AvatarEditScreen } from '../screens/avatars/AvatarEditScreen';
import PendingApprovalsScreen from '../screens/approvals/PendingApprovalsScreen';

const Drawer = createDrawerNavigator();

/**
 * Drawerナビゲーター
 * 
 * NavigationFlow.mdに基づく画面遷移:
 * - デフォルト画面: TaskList（タスク一覧）
 * - ハンバーガーメニュー: DrawerContentで定義
 * - ヘッダー: 全画面共通（左:メニュー、中央:タイトル、右:通知）
 * 
 * @returns JSX.Element
 */
export default function DrawerNavigator() {
  return (
    <Drawer.Navigator
      initialRouteName="TaskList"
      drawerContent={(props) => <DrawerContent {...props} />}
      screenOptions={{
        headerShown: true,
        drawerType: 'front', // ドロワーをオーバーレイ表示
        swipeEnabled: false, // ドロワーメニューのスワイプジェスチャーを無効化
        drawerStyle: {
          width: '80%', // 画面幅の80%
        },
        headerStyle: {
          backgroundColor: '#FFFFFF',
          elevation: 2, // Android
          shadowOpacity: 0.1, // iOS
        },
        headerTitleStyle: {
          fontWeight: '600',
          fontSize: 18,
        },
        // Section 3.3: 全画面共通でヘッダー右側に通知アイコンを表示
        headerRight: () => <HeaderNotificationIcon />,
      }}
    >
      {/* タスク管理 */}
      <Drawer.Screen
        name="TaskList"
        component={TaskListScreen}
        options={{
          title: 'タスク一覧',
        }}
      />
      <Drawer.Screen
        name="TaskDetail"
        component={TaskDetailScreen}
        options={{
          title: 'タスク詳細',
        }}
      />
      <Drawer.Screen
        name="TaskEdit"
        component={TaskEditScreen}
        options={({ navigation }) => ({
          title: 'タスク編集',
          headerLeft: () => (
            <TouchableOpacity
              onPress={() => navigation.navigate('TaskList')}
              style={{ marginLeft: 15 }}
            >
              <Text style={{ fontSize: 18, color: '#4F46E5' }}>←</Text>
            </TouchableOpacity>
          ),
        })}
      />
      <Drawer.Screen
        name="CreateTask"
        component={CreateTaskScreen}
        options={{
          title: 'タスク作成',
        }}
      />
      <Drawer.Screen
        name="TaskDecomposition"
        component={TaskDecompositionScreen}
        options={{
          title: 'AIタスク分解',
        }}
      />
      <Drawer.Screen
        name="TagTasks"
        component={TagTasksScreen}
        options={{
          headerShown: false,
        }}
      />

      {/* 承認待ち */}
      <Drawer.Screen
        name="PendingApprovals"
        component={PendingApprovalsScreen}
        options={{
          title: '承認待ち一覧',
        }}
      />

      {/* タグ管理 */}
      <Drawer.Screen
        name="TagManagement"
        component={TagManagementScreen as React.ComponentType<any>}
        options={{
          title: 'タグ管理',
        }}
      />
      <Drawer.Screen
        name="TagDetail"
        component={TagDetailScreen as React.ComponentType<any>}
        options={({ navigation }) => ({
          title: 'タグ詳細',
          headerLeft: () => (
            <TouchableOpacity
              onPress={() => navigation.navigate('TagManagement')}
              style={{ marginLeft: 15 }}
            >
              <Text style={{ fontSize: 18, color: '#4F46E5' }}>←</Text>
            </TouchableOpacity>
          ),
        })}
      />

      {/* アバター管理 */}
      <Drawer.Screen
        name="AvatarManage"
        component={AvatarManageScreen as React.ComponentType<any>}
        options={{
          title: 'アバター管理',
        }}
      />
      <Drawer.Screen
        name="AvatarCreate"
        component={AvatarCreateScreen as React.ComponentType<any>}
        options={{
          title: 'アバター作成',
        }}
      />
      <Drawer.Screen
        name="AvatarEdit"
        component={AvatarEditScreen as React.ComponentType<any>}
        options={{
          title: 'アバター編集',
        }}
      />

      {/* 実績レポート */}
      <Drawer.Screen
        name="Performance"
        component={PerformanceScreen}
        options={{
          title: '実績',
        }}
      />
      <Drawer.Screen
        name="MonthlyReport"
        component={MonthlyReportScreen}
        options={{
          title: '月次レポート',
        }}
      />
      <Drawer.Screen
        name="MemberSummary"
        component={MemberSummaryScreen}
        options={{
          title: 'メンバー別概況',
        }}
      />

      {/* トークン・決済 */}
      <Drawer.Screen
        name="TokenBalance"
        component={TokenBalanceScreen}
        options={{
          title: 'トークン残高',
        }}
      />
      <Drawer.Screen
        name="TokenPackageList"
        component={TokenPackageListScreen}
        options={{
          headerShown: false,
        }}
      />
      <Drawer.Screen
        name="TokenHistory"
        component={TokenHistoryScreen}
        options={{
          headerShown: false,
        }}
      />

      {/* サブスクリプション */}
      <Drawer.Screen
        name="SubscriptionManage"
        component={SubscriptionManageScreen}
        options={{
          title: 'サブスクリプション管理',
        }}
      />
      <Drawer.Screen
        name="SubscriptionInvoices"
        component={SubscriptionInvoicesScreen}
        options={{
          title: '請求履歴',
        }}
      />
      <Drawer.Screen
        name="SubscriptionWebView"
        component={SubscriptionWebViewScreen}
        options={{
          title: 'サブスクリプション購入',
        }}
      />

      {/* 通知 */}
      <Drawer.Screen
        name="NotificationList"
        component={NotificationListScreen}
        options={{
          title: '通知一覧',
        }}
      />
      <Drawer.Screen
        name="NotificationDetail"
        component={NotificationDetailScreen}
        options={{
          title: '通知詳細',
        }}
      />

      {/* プロフィール・設定 */}
      <Drawer.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          title: 'プロフィール',
        }}
      />
      <Drawer.Screen
        name="PasswordChange"
        component={PasswordChangeScreen}
        options={{
          title: 'パスワード変更',
        }}
      />
      <Drawer.Screen
        name="Settings"
        component={SettingsScreen}
        options={{
          title: '設定',
        }}
      />
      <Drawer.Screen
        name="NotificationSettings"
        component={NotificationSettingsScreen}
        options={{
          title: '通知設定',
        }}
      />

      {/* グループ管理 */}
      <Drawer.Screen
        name="GroupManagement"
        component={GroupManagementScreen}
        options={{
          title: 'グループ管理',
        }}
      />

      {/* スケジュールタスク */}
      <Drawer.Screen
        name="ScheduledTaskList"
        component={ScheduledTaskListScreen}
        options={{
          title: 'タスクスケジュール管理',
        }}
      />
      <Drawer.Screen
        name="ScheduledTaskHistory"
        component={ScheduledTaskHistoryScreen}
        options={{
          title: '実行履歴',
        }}
      />
      <Drawer.Screen
        name="ScheduledTaskCreate"
        component={ScheduledTaskCreateScreen}
        options={{
          title: 'クエスト自動作成設定',
        }}
      />
      <Drawer.Screen
        name="ScheduledTaskEdit"
        component={ScheduledTaskEditScreen}
        options={{
          title: 'スケジュール編集',
        }}
      />
    </Drawer.Navigator>
  );
}
