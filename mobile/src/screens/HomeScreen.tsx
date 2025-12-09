/**
 * ホーム画面（認証後）
 */
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  RefreshControl,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuth } from '../contexts/AuthContext';

export default function HomeScreen() {
  const navigation = useNavigation();
  const { user, logout } = useAuth();

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>ようこそ</Text>
        <Text style={styles.userName}>{user?.name}さん</Text>
        
        <View style={styles.infoBox}>
          <Text style={styles.infoLabel}>メールアドレス</Text>
          <Text style={styles.infoValue}>{user?.email}</Text>
        </View>

        <View style={styles.statusBox}>
          <Text style={styles.statusText}>✅ 認証機能実装完了</Text>
          <Text style={styles.statusSubtext}>Phase 2.B-2</Text>
        </View>

        <TouchableOpacity
          style={styles.taskButton}
          onPress={() => navigation.navigate('TaskList' as never)}
        >
          <Text style={styles.taskButtonText}>📋 タスク一覧</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.avatarButton}
          onPress={() => navigation.navigate('AvatarManage' as never)}
        >
          <Text style={styles.avatarButtonText}>👤 アバター管理</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.notificationButton}
          onPress={() => navigation.navigate('NotificationList' as never)}
        >
          <Text style={styles.notificationButtonText}>🔔 通知一覧</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.tagButton}
          onPress={() => navigation.navigate('TagManagement' as never)}
        >
          <Text style={styles.tagButtonText}>🏷️ タグ管理</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.performanceButton}
          onPress={() => navigation.navigate('Performance' as never)}
        >
          <Text style={styles.performanceButtonText}>📊 実績</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.tokenButton}
          onPress={() => navigation.navigate('TokenBalance' as never)}
        >
          <Text style={styles.tokenButtonText}>💰 トークン購入</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.subscriptionButton}
          onPress={() => navigation.navigate('SubscriptionManage' as never)}
        >
          <Text style={styles.subscriptionButtonText}>💳 サブスクリプション管理</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.profileButton}
          onPress={() => navigation.navigate('Profile' as never)}
        >
          <Text style={styles.profileButtonText}>プロフィール</Text>
        </TouchableOpacity>

        <TouchableOpacity style={styles.logoutButton} onPress={logout}>
          <Text style={styles.logoutButtonText}>ログアウト</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  content: {
    flex: 1,
    padding: 24,
    justifyContent: 'center',
  },
  title: {
    fontSize: 24,
    fontWeight: '600',
    color: '#1f2937',
    marginBottom: 8,
  },
  userName: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#3b82f6',
    marginBottom: 32,
  },
  infoBox: {
    backgroundColor: '#fff',
    padding: 16,
    borderRadius: 8,
    marginBottom: 24,
  },
  infoLabel: {
    fontSize: 12,
    color: '#6b7280',
    marginBottom: 4,
  },
  infoValue: {
    fontSize: 16,
    color: '#1f2937',
  },
  statusBox: {
    backgroundColor: '#d1fae5',
    padding: 16,
    borderRadius: 8,
    marginBottom: 32,
    alignItems: 'center',
  },
  statusText: {
    fontSize: 18,
    fontWeight: '600',
    color: '#059669',
    marginBottom: 4,
  },
  statusSubtext: {
    fontSize: 14,
    color: '#059669',
  },
  taskButton: {
    backgroundColor: '#10b981',
    paddingVertical: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  taskButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  avatarButton: {
    backgroundColor: '#EC4899',
    paddingVertical: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  avatarButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  notificationButton: {
    backgroundColor: '#59B9C6',
    paddingVertical: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  notificationButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  tagButton: {
    backgroundColor: '#8B5CF6',
    paddingVertical: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  tagButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  performanceButton: {
    backgroundColor: '#06b6d4',
    paddingVertical: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  performanceButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  tokenButton: {
    backgroundColor: '#f97316',
    paddingVertical: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  tokenButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  subscriptionButton: {
    backgroundColor: '#6366f1',
    paddingVertical: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  subscriptionButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  profileButton: {
    backgroundColor: '#3b82f6',
    borderRadius: 8,
    padding: 16,
    alignItems: 'center',
    marginBottom: 12,
  },
  profileButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#fff',
  },
  logoutButton: {
    backgroundColor: '#ef4444',
    borderRadius: 8,
    padding: 16,
    alignItems: 'center',
  },
  logoutButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});
