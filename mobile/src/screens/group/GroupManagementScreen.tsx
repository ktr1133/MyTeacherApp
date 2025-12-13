/**
 * GroupManagementScreen - グループ管理画面
 * 
 * 機能（Web版完全同等）:
 * - グループ基本情報編集
 * - グループタスク作成状況表示
 * - タスク自動作成設定への導線
 * - メンバー一覧表示
 * - メンバー追加
 * - メンバー権限管理（編集権限付与/解除）
 * - 子どもテーマ切り替え
 * - マスター譲渡
 * - メンバー削除
 * 
 * ナビゲーション階層:
 * Profile → GroupManagement → ScheduledTaskList
 */

import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  RefreshControl,
  Alert,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import { useTheme } from '../../contexts/ThemeContext';
import { useAuth } from '../../contexts/AuthContext';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { ConfirmDialog } from '../../components/common/ConfirmDialog';
import { GroupTaskUsageComponent } from '../../components/group/GroupTaskUsage';
import * as GroupService from '../../services/group.service';
import type { Group, GroupMember, GroupTaskUsage } from '../../types/group.types';

/**
 * GroupManagementScreen コンポーネント
 */
export const GroupManagementScreen: React.FC = () => {
  const navigation = useNavigation();
  const { theme } = useTheme();
  const { user } = useAuth();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';

  // 状態管理
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [group, setGroup] = useState<Group | null>(null);
  const [members, setMembers] = useState<GroupMember[]>([]);
  const [taskUsage, setTaskUsage] = useState<GroupTaskUsage | null>(null);

  // グループ名編集
  const [groupName, setGroupName] = useState('');
  const [isEditingName, setIsEditingName] = useState(false);

  // メンバー追加
  const [newMemberUsername, setNewMemberUsername] = useState('');
  const [newMemberEditFlg, setNewMemberEditFlg] = useState(false);
  const [isAddingMember, setIsAddingMember] = useState(false);

  // 確認ダイアログ
  const [confirmDialog, setConfirmDialog] = useState<{
    visible: boolean;
    title: string;
    message: string;
    onConfirm: () => void;
    isDangerous?: boolean;
  }>({
    visible: false,
    title: '',
    message: '',
    onConfirm: () => {},
    isDangerous: false,
  });

  // 権限判定 - groupステートオブジェクトを使用（user.groupにはmaster_user_idが含まれていない）
  const isGroupMaster = React.useMemo(
    () => group?.master_user_id === user?.id,
    [group?.master_user_id, user?.id]
  );
  const canEditGroup = React.useMemo(
    () => isGroupMaster || (user?.group_edit_flg ?? false),
    [isGroupMaster, user?.group_edit_flg]
  );

  // スタイル生成
  const styles = React.useMemo(() => createStyles(width, themeType), [width, themeType]);

  /**
   * グループ情報取得
   */
  const fetchGroupInfo = useCallback(async () => {
    try {
      const response = await GroupService.getGroupInfo();
      setGroup(response.data.group);
      setMembers(response.data.members);
      setTaskUsage(response.data.task_usage);
      setGroupName(response.data.group.name);
    } catch (error: any) {
      console.error('[GroupManagementScreen] Fetch error:', error);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'グループじょうほうをとれなかったよ'
          : 'グループ情報の取得に失敗しました'
      );
    } finally {
      setLoading(false);
    }
  }, [theme]);

  /**
   * 画面フォーカス時にデータ再取得
   */
  useFocusEffect(
    useCallback(() => {
      fetchGroupInfo();
    }, [fetchGroupInfo])
  );

  /**
   * Pull-to-Refresh
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchGroupInfo();
    setRefreshing(false);
  }, [fetchGroupInfo]);

  /**
   * グループ名更新
   */
  const handleUpdateGroupName = async () => {
    if (!groupName || groupName.trim() === '' || groupName === group?.name) {
      return;
    }

    setIsEditingName(true);
    try {
      await GroupService.updateGroup({ name: groupName.trim() });
      Alert.alert(
        theme === 'child' ? 'せいこう' : '成功',
        theme === 'child'
          ? 'グループめいをかえたよ'
          : 'グループ名を更新しました'
      );
      await fetchGroupInfo();
    } catch (error: any) {
      console.error('[GroupManagementScreen] Update name error:', error);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        error.message || (theme === 'child'
          ? 'グループめいをかえられなかったよ'
          : 'グループ名の更新に失敗しました')
      );
    } finally {
      setIsEditingName(false);
    }
  };

  /**
   * メンバー追加
   */
  const handleAddMember = async () => {
    if (!newMemberUsername || newMemberUsername.trim() === '') {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'ユーザーめいをいれてね'
          : 'ユーザー名を入力してください'
      );
      return;
    }

    setIsAddingMember(true);
    try {
      await GroupService.addMember({
        username: newMemberUsername.trim(),
        group_edit_flg: newMemberEditFlg,
      });
      Alert.alert(
        theme === 'child' ? 'せいこう' : '成功',
        theme === 'child'
          ? 'メンバーをついかしたよ'
          : 'メンバーを追加しました'
      );
      setNewMemberUsername('');
      setNewMemberEditFlg(false);
      await fetchGroupInfo();
    } catch (error: any) {
      console.error('[GroupManagementScreen] Add member error:', error);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        error.message || (theme === 'child'
          ? 'メンバーをついかできなかったよ'
          : 'メンバーの追加に失敗しました')
      );
    } finally {
      setIsAddingMember(false);
    }
  };

  /**
   * 権限変更
   */
  const handleTogglePermission = (member: GroupMember) => {
    setConfirmDialog({
      visible: true,
      title: theme === 'child' ? 'けんげんへんこう' : '権限変更',
      message: member.group_edit_flg
        ? theme === 'child'
          ? 'へんしゅうけんげんをはずしますか？'
          : '編集権限を外しますか？'
        : theme === 'child'
        ? 'へんしゅうけんげんをつけますか？'
        : '編集権限を付与しますか？',
      onConfirm: async () => {
        try {
          await GroupService.updateMemberPermission(member.id, {
            group_edit_flg: !member.group_edit_flg,
          });
          Alert.alert(
            theme === 'child' ? 'せいこう' : '成功',
            theme === 'child'
              ? 'けんげんをかえたよ'
              : '権限を更新しました'
          );
          await fetchGroupInfo();
        } catch (error: any) {
          console.error('[GroupManagementScreen] Toggle permission error:', error);
          Alert.alert(
            theme === 'child' ? 'エラー' : 'エラー',
            error.message || (theme === 'child'
              ? 'けんげんをかえられなかったよ'
              : '権限の更新に失敗しました')
          );
        } finally {
          setConfirmDialog({ ...confirmDialog, visible: false });
        }
      },
      isDangerous: false,
    });
  };

  /**
   * テーマ切り替え
   */
  const handleToggleTheme = async (member: GroupMember) => {
    try {
      await GroupService.toggleMemberTheme(member.id, {
        theme: member.theme === 'child' ? 'adult' : 'child',
      });
      Alert.alert(
        theme === 'child' ? 'せいこう' : '成功',
        theme === 'child'
          ? 'テーマをかえたよ'
          : 'テーマを変更しました'
      );
      await fetchGroupInfo();
    } catch (error: any) {
      console.error('[GroupManagementScreen] Toggle theme error:', error);
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        error.message || (theme === 'child'
          ? 'テーマをかえられなかったよ'
          : 'テーマの変更に失敗しました')
      );
    }
  };

  /**
   * マスター譲渡
   */
  const handleTransferMaster = (member: GroupMember) => {
    setConfirmDialog({
      visible: true,
      title: theme === 'child' ? 'マスターじょうと' : 'マスター譲渡',
      message: theme === 'child'
        ? 'マスターをゆずりますか？もどせないよ。'
        : 'マスター権限を譲渡しますか？この操作は取り消せません。',
      onConfirm: async () => {
        try {
          await GroupService.transferMaster(member.id);
          Alert.alert(
            theme === 'child' ? 'せいこう' : '成功',
            theme === 'child'
              ? 'マスターをゆずったよ'
              : 'マスター権限を譲渡しました'
          );
          await fetchGroupInfo();
        } catch (error: any) {
          console.error('[GroupManagementScreen] Transfer master error:', error);
          Alert.alert(
            theme === 'child' ? 'エラー' : 'エラー',
            error.message || (theme === 'child'
              ? 'マスターをゆずれなかったよ'
              : 'マスター譲渡に失敗しました')
          );
        } finally {
          setConfirmDialog({ ...confirmDialog, visible: false });
        }
      },
      isDangerous: true,
    });
  };

  /**
   * メンバー削除
   */
  const handleRemoveMember = (member: GroupMember) => {
    setConfirmDialog({
      visible: true,
      title: theme === 'child' ? 'メンバーけす' : 'メンバー削除',
      message: theme === 'child'
        ? 'このメンバーをグループからはずしますか？'
        : 'このメンバーをグループから外しますか？',
      onConfirm: async () => {
        try {
          await GroupService.removeMember(member.id);
          Alert.alert(
            theme === 'child' ? 'せいこう' : '成功',
            theme === 'child'
              ? 'メンバーをはずしたよ'
              : 'メンバーを削除しました'
          );
          await fetchGroupInfo();
        } catch (error: any) {
          console.error('[GroupManagementScreen] Remove member error:', error);
          Alert.alert(
            theme === 'child' ? 'エラー' : 'エラー',
            error.message || (theme === 'child'
              ? 'メンバーをはずせなかったよ'
              : 'メンバーの削除に失敗しました')
          );
        } finally {
          setConfirmDialog({ ...confirmDialog, visible: false });
        }
      },
      isDangerous: true,
    });
  };

  /**
   * スケジュールタスク管理画面へ遷移
   */
  const navigateToScheduledTasks = () => {
    if (!group?.id) {
      return;
    }
    (navigation as any).navigate('ScheduledTaskList', { groupId: group.id });
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#6366f1" />
      </View>
    );
  }

  return (
    <>
      <ScrollView
        style={styles.container}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={['#6366f1']}
            tintColor="#6366f1"
          />
        }
      >
        <View style={styles.content}>
          {/* グループ基本情報編集 - Web版同様に全員表示 */}
          {group && (
            <View style={styles.card}>
              <LinearGradient
                colors={['#9333ea', '#db2777']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.cardHeader}
              >
                <Text style={styles.cardTitle}>
                  {theme === 'child' ? 'グループじょうほう' : 'グループ情報'}
                </Text>
              </LinearGradient>
              <View style={styles.cardContent}>
                <Text style={styles.label}>
                  {theme === 'child' ? 'グループめい' : 'グループ名'}
                </Text>
                {canEditGroup ? (
                  <>
                    <TextInput
                      style={styles.input}
                      value={groupName}
                      onChangeText={setGroupName}
                      placeholder={theme === 'child' ? 'グループめい' : 'グループ名'}
                      placeholderTextColor="#94a3b8"
                      editable={!isEditingName}
                    />
                    <TouchableOpacity
                      style={[
                        styles.saveButton,
                        (groupName === group.name || !groupName.trim()) && styles.saveButtonDisabled,
                      ]}
                      onPress={handleUpdateGroupName}
                      disabled={groupName === group.name || !groupName.trim() || isEditingName}
                    >
                      {isEditingName ? (
                        <ActivityIndicator size="small" color="#ffffff" />
                      ) : (
                        <Text style={styles.saveButtonText}>
                          {theme === 'child' ? 'ほぞん' : '保存'}
                        </Text>
                      )}
                    </TouchableOpacity>
                  </>
                ) : (
                  <Text style={styles.readOnlyText}>{group.name}</Text>
                )}
              </View>
            </View>
          )}

          {/* グループタスク作成状況 - Web版同様に全員表示 */}
          {group && taskUsage && (
            <GroupTaskUsageComponent group={group} taskUsage={taskUsage} />
          )}

          {/* タスク自動作成設定 */}
          {canEditGroup && (
            <TouchableOpacity
              onPress={navigateToScheduledTasks}
              disabled={!group?.id}
            >
              <LinearGradient
                colors={['#4f46e5', '#2563eb', '#9333ea']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.scheduleCard}
              >
                <View style={styles.scheduleContent}>
                  <View style={styles.scheduleLeft}>
                    <Text style={styles.scheduleIcon}>📅</Text>
                    <View>
                      <Text style={styles.scheduleTitle}>
                        {theme === 'child'
                          ? 'タスクスケジュールかんり'
                          : 'タスクスケジュール管理'}
                      </Text>
                      <Text style={styles.scheduleDescription}>
                        {theme === 'child'
                          ? 'ていきてきなタスクをせっていするよ'
                          : '定期的に実行するタスクを設定'}
                      </Text>
                    </View>
                  </View>
                  <Text style={styles.scheduleArrow}>›</Text>
                </View>
              </LinearGradient>
            </TouchableOpacity>
          )}

          {/* メンバー一覧 */}
          {members.length > 0 && (
            <View style={styles.card}>
              <LinearGradient
                colors={['#2563eb', '#9333ea']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.cardHeader}
              >
                <Text style={styles.cardTitle}>
                  {theme === 'child' ? 'メンバーいちらん' : 'メンバー一覧'}
                </Text>
              </LinearGradient>
              <View style={styles.cardContent}>
                {members.map((member) => (
                  <View key={member.id} style={styles.memberCard}>
                    {/* メンバー情報 */}
                    <View style={styles.memberHeader}>
                      <View style={styles.memberInfo}>
                        <Text style={styles.memberName}>
                          {member.name || member.username}
                        </Text>
                        {member.name && (
                          <Text style={styles.memberUsername}>@{member.username}</Text>
                        )}
                      </View>
                      <View style={styles.memberBadges}>
                        {member.is_master ? (
                          <View style={styles.badgeMaster}>
                            <Text style={styles.badgeText}>
                              {theme === 'child' ? 'マスター' : 'マスター'}
                            </Text>
                          </View>
                        ) : member.group_edit_flg ? (
                          <View style={styles.badgeEdit}>
                            <Text style={styles.badgeText}>
                              {theme === 'child' ? 'へんしゅう' : '編集権限'}
                            </Text>
                          </View>
                        ) : (
                          <View style={styles.badgeNormal}>
                            <Text style={styles.badgeTextNormal}>
                              {theme === 'child' ? 'いっぱん' : '一般'}
                            </Text>
                          </View>
                        )}
                        {member.theme === 'child' && (
                          <View style={styles.badgeChild}>
                            <Text style={styles.badgeText}>
                              {theme === 'child' ? 'こども' : '子ども'}
                            </Text>
                          </View>
                        )}
                      </View>
                    </View>

                    {/* アクションボタン */}
                    <View style={styles.memberActions}>
                      {/* テーマ切り替え - 編集権限に関係なく常に表示（Web版と同じ） */}
                      <TouchableOpacity
                        style={[
                          styles.actionButton,
                          member.theme === 'child'
                            ? styles.actionButtonChild
                            : styles.actionButtonTheme,
                        ]}
                        onPress={() => handleToggleTheme(member)}
                      >
                        <Text style={[
                          styles.actionButtonText,
                          member.theme === 'child' && styles.actionButtonTextChild
                        ]}>
                          {member.theme === 'child'
                            ? theme === 'child'
                              ? 'おとな'
                              : '大人用'
                            : theme === 'child'
                            ? 'こども'
                            : '子ども用'}
                        </Text>
                      </TouchableOpacity>

                      {/* 以下は編集権限者のみ表示 */}
                      {canEditGroup && (
                        <>
                          {/* 権限変更（マスター以外） */}
                          {!member.is_master && (
                          <TouchableOpacity
                            style={[
                              styles.actionButton,
                              member.group_edit_flg
                                ? styles.actionButtonNormal
                                : styles.actionButtonPermission,
                            ]}
                            onPress={() => handleTogglePermission(member)}
                          >
                            <Text style={styles.actionButtonText}>
                              {member.group_edit_flg
                                ? theme === 'child'
                                  ? 'けんげんはずす'
                                  : '権限解除'
                                : theme === 'child'
                                ? 'けんげんつける'
                                : '権限付与'}
                            </Text>
                          </TouchableOpacity>
                        )}

                        {/* マスター譲渡（マスターのみ、自分以外） */}
                        {isGroupMaster && member.id !== user?.id && (
                          <TouchableOpacity
                            style={[styles.actionButton, styles.actionButtonTransfer]}
                            onPress={() => handleTransferMaster(member)}
                          >
                            <Text style={styles.actionButtonText}>
                              {theme === 'child' ? 'マスターゆずる' : 'マスター譲渡'}
                            </Text>
                          </TouchableOpacity>
                        )}

                        {/* メンバー削除（マスター以外） */}
                        {!member.is_master && (
                          <TouchableOpacity
                            style={[styles.actionButton, styles.actionButtonRemove]}
                            onPress={() => handleRemoveMember(member)}
                          >
                            <Text style={styles.actionButtonText}>
                              {theme === 'child' ? 'はずす' : '削除'}
                            </Text>
                          </TouchableOpacity>
                        )}
                      </>
                      )}
                    </View>
                  </View>
                ))}
              </View>
            </View>
          )}

          {/* メンバー追加 */}
          {canEditGroup && (
            <View style={styles.card}>
              <LinearGradient
                colors={['#10b981', '#059669']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.cardHeader}
              >
                <Text style={styles.cardTitle}>
                  {theme === 'child' ? 'メンバーついか' : 'メンバー追加'}
                </Text>
              </LinearGradient>
              <View style={styles.cardContent}>
                <Text style={styles.label}>
                  {theme === 'child' ? 'ユーザーめい' : 'ユーザー名'}
                </Text>
                <TextInput
                  style={styles.input}
                  value={newMemberUsername}
                  onChangeText={setNewMemberUsername}
                  placeholder={theme === 'child' ? 'ユーザーめい' : 'ユーザー名'}
                  placeholderTextColor="#94a3b8"
                  autoCapitalize="none"
                  editable={!isAddingMember}
                />
                <TouchableOpacity
                  style={styles.checkboxContainer}
                  onPress={() => setNewMemberEditFlg(!newMemberEditFlg)}
                >
                  <View style={[styles.checkbox, newMemberEditFlg && styles.checkboxChecked]}>
                    {newMemberEditFlg && <Text style={styles.checkmark}>✓</Text>}
                  </View>
                  <Text style={styles.checkboxLabel}>
                    {theme === 'child'
                      ? 'へんしゅうけんげんをつける'
                      : '編集権限を付与'}
                  </Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[
                    styles.addButton,
                    (!newMemberUsername.trim() || isAddingMember) && styles.addButtonDisabled,
                  ]}
                  onPress={handleAddMember}
                  disabled={!newMemberUsername.trim() || isAddingMember}
                >
                  {isAddingMember ? (
                    <ActivityIndicator size="small" color="#ffffff" />
                  ) : (
                    <Text style={styles.addButtonText}>
                      {theme === 'child' ? 'ついか' : '追加'}
                    </Text>
                  )}
                </TouchableOpacity>
              </View>
            </View>
          )}
        </View>
      </ScrollView>

      {/* 確認ダイアログ */}
      <ConfirmDialog
        visible={confirmDialog.visible}
        title={confirmDialog.title}
        message={confirmDialog.message}
        confirmText={theme === 'child' ? 'OK' : 'OK'}
        cancelText={theme === 'child' ? 'やめる' : 'キャンセル'}
        onConfirm={confirmDialog.onConfirm}
        onCancel={() => setConfirmDialog({ ...confirmDialog, visible: false })}
        isDangerous={confirmDialog.isDangerous}
      />
    </>
  );
};
/**
 * レスポンシブスタイル生成関数
 */
const createStyles = (width: number, theme: 'adult' | 'child') =>
  StyleSheet.create({
    loadingContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: '#f8fafc',
    },
    container: {
      flex: 1,
      backgroundColor: '#f8fafc',
    },
    content: {
      padding: getSpacing(16, width),
    },
    card: {
      backgroundColor: '#ffffff',
      borderRadius: getBorderRadius(16, width),
      marginBottom: getSpacing(16, width),
      ...getShadow(2),
      overflow: 'hidden',
    },
    cardHeader: {
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(16, width),
    },
    cardTitle: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: '#ffffff',
    },
    cardContent: {
      padding: getSpacing(16, width),
    },
    label: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '500',
      color: '#475569',
      marginBottom: getSpacing(8, width),
    },
    input: {
      backgroundColor: '#f8fafc',
      borderWidth: 1,
      borderColor: '#e2e8f0',
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(16, width),
      fontSize: getFontSize(16, width, theme),
      color: '#1e293b',
      marginBottom: getSpacing(12, width),
    },
    readOnlyText: {
      backgroundColor: '#f8fafc',
      borderWidth: 1,
      borderColor: '#e2e8f0',
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(16, width),
      fontSize: getFontSize(16, width, theme),
      color: '#64748b',
    },
    saveButton: {
      backgroundColor: '#6366f1',
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: 48,
    },
    saveButtonDisabled: {
      backgroundColor: '#cbd5e1',
      opacity: 0.6,
    },
    saveButtonText: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: '#ffffff',
    },
    scheduleCard: {
      borderRadius: getBorderRadius(16, width),
      padding: getSpacing(16, width),
      marginBottom: getSpacing(16, width),
      ...getShadow(2),
      overflow: 'hidden',
    },
    scheduleContent: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
    },
    scheduleLeft: {
      flexDirection: 'row',
      alignItems: 'center',
      flex: 1,
    },
    scheduleIcon: {
      fontSize: getFontSize(24, width, theme),
      marginRight: getSpacing(12, width),
    },
    scheduleTitle: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: '#ffffff',
      marginBottom: getSpacing(4, width),
    },
    scheduleDescription: {
      fontSize: getFontSize(13, width, theme),
      color: 'rgba(255, 255, 255, 0.9)',
    },
    scheduleArrow: {
      fontSize: getFontSize(24, width, theme),
      color: 'rgba(255, 255, 255, 0.7)',
      fontWeight: '300',
    },
    memberCard: {
      borderBottomWidth: 1,
      borderBottomColor: '#f1f5f9',
      paddingVertical: getSpacing(12, width),
    },
    memberHeader: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
      marginBottom: getSpacing(12, width),
    },
    memberInfo: {
      flex: 1,
      marginRight: getSpacing(12, width),
    },
    memberName: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: '#1e293b',
      marginBottom: getSpacing(2, width),
    },
    memberUsername: {
      fontSize: getFontSize(13, width, theme),
      color: '#64748b',
    },
    memberBadges: {
      flexDirection: 'row',
      gap: getSpacing(6, width),
      flexWrap: 'wrap',
    },
    badgeMaster: {
      backgroundColor: '#fef3c7',
      paddingVertical: getSpacing(4, width),
      paddingHorizontal: getSpacing(8, width),
      borderRadius: getBorderRadius(12, width),
    },
    badgeEdit: {
      backgroundColor: '#dcfce7',
      paddingVertical: getSpacing(4, width),
      paddingHorizontal: getSpacing(8, width),
      borderRadius: getBorderRadius(12, width),
    },
    badgeNormal: {
      backgroundColor: '#f1f5f9',
      paddingVertical: getSpacing(4, width),
      paddingHorizontal: getSpacing(8, width),
      borderRadius: getBorderRadius(12, width),
    },
    badgeChild: {
      backgroundColor: '#ffedd5',
      paddingVertical: getSpacing(4, width),
      paddingHorizontal: getSpacing(8, width),
      borderRadius: getBorderRadius(12, width),
    },
    badgeText: {
      fontSize: getFontSize(11, width, theme),
      fontWeight: 'bold',
      color: '#1e293b',
    },
    badgeTextNormal: {
      fontSize: getFontSize(11, width, theme),
      fontWeight: '500',
      color: '#475569',
    },
    memberActions: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: getSpacing(8, width),
    },
    actionButton: {
      paddingVertical: getSpacing(8, width),
      paddingHorizontal: getSpacing(12, width),
      borderRadius: getBorderRadius(8, width),
      borderWidth: 1,
      minHeight: 36,
      justifyContent: 'center',
    },
    actionButtonTheme: {
      backgroundColor: '#f1f5f9',
      borderColor: '#e2e8f0',
    },
    actionButtonChild: {
      backgroundColor: '#fed7aa',
      borderColor: '#fdba74',
    },
    actionButtonPermission: {
      backgroundColor: '#dcfce7',
      borderColor: '#bbf7d0',
    },
    actionButtonNormal: {
      backgroundColor: '#f1f5f9',
      borderColor: '#e2e8f0',
    },
    actionButtonTransfer: {
      backgroundColor: '#fef3c7',
      borderColor: '#fde68a',
    },
    actionButtonRemove: {
      backgroundColor: '#fee2e2',
      borderColor: '#fecaca',
    },
    actionButtonText: {
      fontSize: getFontSize(13, width, theme),
      fontWeight: '600',
      color: '#1e293b',
    },
    actionButtonTextChild: {
      color: '#9a3412',
    },
    checkboxContainer: {
      flexDirection: 'row',
      alignItems: 'center',
      marginBottom: getSpacing(16, width),
    },
    checkbox: {
      width: 20,
      height: 20,
      borderWidth: 2,
      borderColor: '#cbd5e1',
      borderRadius: getBorderRadius(4, width),
      marginRight: getSpacing(8, width),
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: '#ffffff',
    },
    checkboxChecked: {
      backgroundColor: '#6366f1',
      borderColor: '#6366f1',
    },
    checkmark: {
      color: '#ffffff',
      fontSize: getFontSize(12, width, theme),
      fontWeight: 'bold',
    },
    checkboxLabel: {
      fontSize: getFontSize(14, width, theme),
      color: '#475569',
    },
    addButton: {
      backgroundColor: '#10b981',
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: 48,
    },
    addButtonDisabled: {
      backgroundColor: '#cbd5e1',
      opacity: 0.6,
    },
    addButtonText: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: '#ffffff',
    },
  });

export default GroupManagementScreen;

