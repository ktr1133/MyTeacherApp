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
import { useThemedColors } from '../../hooks/useThemedColors';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { ConfirmDialog } from '../../components/common/ConfirmDialog';
import { GroupTaskUsageComponent } from '../../components/group/GroupTaskUsage';
import { SearchChildrenModal } from '../../components/group/SearchChildrenModal';
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
  const { colors, accent } = useThemedColors();
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
  const [newMemberEmail, setNewMemberEmail] = useState('');
  const [newMemberName, setNewMemberName] = useState('');
  const [newMemberPassword, setNewMemberPassword] = useState('');
  const [newMemberEditFlg, setNewMemberEditFlg] = useState(false);
  const [privacyConsent, setPrivacyConsent] = useState(false);
  const [termsConsent, setTermsConsent] = useState(false);
  const [isAddingMember, setIsAddingMember] = useState(false);

  // 未紐付け子検索モーダル
  const [showSearchChildrenModal, setShowSearchChildrenModal] = useState(false);

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
  const styles = React.useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

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
    // 必須フィールドバリデーション
    if (!newMemberUsername || newMemberUsername.trim() === '') {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'ユーザーめいをいれてね'
          : 'ユーザー名を入力してください'
      );
      return;
    }
    if (!newMemberEmail || newMemberEmail.trim() === '') {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'メールアドレスをいれてね'
          : 'メールアドレスを入力してください'
      );
      return;
    }
    if (!newMemberPassword || newMemberPassword.trim() === '') {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'パスワードをいれてね'
          : 'パスワードを入力してください'
      );
      return;
    }
    if (newMemberPassword.length < 8) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'パスワードは8もじいじょうだよ'
          : 'パスワードは8文字以上で入力してください'
      );
      return;
    }
    
    // 同意チェックバリデーション
    if (!privacyConsent || !termsConsent) {
      Alert.alert(
        theme === 'child' ? 'エラー' : 'エラー',
        theme === 'child'
          ? 'プライバシーポリシーとりようきやくにどういしてね'
          : 'プライバシーポリシーおよび利用規約への同意が必要です'
      );
      return;
    }

    setIsAddingMember(true);
    try {
      await GroupService.addMember({
        username: newMemberUsername.trim(),
        email: newMemberEmail.trim(),
        password: newMemberPassword,
        name: newMemberName.trim() || undefined,
        group_edit_flg: newMemberEditFlg,
        privacy_policy_consent: privacyConsent,
        terms_consent: termsConsent,
      });
      Alert.alert(
        theme === 'child' ? 'せいこう' : '成功',
        theme === 'child'
          ? 'メンバーをついかしたよ'
          : 'メンバーを追加しました'
      );
      // フォームクリア
      setNewMemberUsername('');
      setNewMemberEmail('');
      setNewMemberName('');
      setNewMemberPassword('');
      setNewMemberEditFlg(false);
      setPrivacyConsent(false);
      setTermsConsent(false);
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
        <ActivityIndicator size="large" color={accent.primary} />
      </View>
    );
  }

  return (
    <>
      {/* 未紐付け子検索モーダル */}
      <SearchChildrenModal
        visible={showSearchChildrenModal}
        onClose={() => setShowSearchChildrenModal(false)}
        onSuccess={() => {
          setShowSearchChildrenModal(false);
          fetchGroupInfo();
        }}
      />

      <ScrollView
        style={styles.container}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={[accent.primary]}
            tintColor={accent.primary}
          />
        }
      >
        <View style={styles.content}>
          {/* グループ基本情報編集 - Web版同様に全員表示 */}
          {group && (
            <View style={styles.card}>
              <LinearGradient
                colors={[accent.primary, accent.primary] as const}
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
                      placeholderTextColor={colors.text.disabled}
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
                        <ActivityIndicator size="small" color={colors.background} />
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
                colors={[accent.primary, accent.primary] as const}
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
                colors={[accent.primary, accent.primary] as const}
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
                colors={[colors.status.success, colors.status.success] as const}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.cardHeader}
              >
                <Text style={styles.cardTitle}>
                  {theme === 'child' ? 'メンバーついか' : 'メンバー追加'}
                </Text>
              </LinearGradient>
              <View style={styles.cardContent}>
                {/* 未紐付け子検索ボタン */}
                <TouchableOpacity
                  style={styles.searchChildrenButton}
                  onPress={() => setShowSearchChildrenModal(true)}
                >
                  <LinearGradient
                    colors={accent.gradient as any}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 1 }}
                    style={styles.searchChildrenButtonGradient}
                  >
                    <Text style={styles.searchChildrenButtonText}>
                      {theme === 'child' 
                        ? '🔍 こどもを さがして ついか' 
                        : '🔍 未紐付け子検索'}
                    </Text>
                  </LinearGradient>
                </TouchableOpacity>

                {/* 区切り線 */}
                <View style={styles.divider}>
                  <Text style={styles.dividerText}>
                    {theme === 'child' ? 'または' : 'または'}
                  </Text>
                </View>

                {/* ユーザー名 */}
                <Text style={styles.label}>
                  {theme === 'child' ? 'ユーザーめい' : 'ユーザー名'}
                </Text>
                <TextInput
                  style={styles.input}
                  value={newMemberUsername}
                  onChangeText={setNewMemberUsername}
                  placeholder={theme === 'child' ? 'ユーザーめい' : 'ユーザー名'}
                  placeholderTextColor={colors.text.disabled}
                  autoCapitalize="none"
                  editable={!isAddingMember}
                />

                {/* メールアドレス */}
                <Text style={[styles.label, { marginTop: 16 }]}>
                  {theme === 'child' ? 'メールアドレス' : 'メールアドレス'}
                </Text>
                <TextInput
                  style={styles.input}
                  value={newMemberEmail}
                  onChangeText={setNewMemberEmail}
                  placeholder={theme === 'child' ? 'メールアドレス' : 'メールアドレス'}
                  placeholderTextColor={colors.text.disabled}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  editable={!isAddingMember}
                />

                {/* 表示名（任意） */}
                <Text style={[styles.label, { marginTop: 16 }]}>
                  {theme === 'child' ? 'ひょうじめい（なくてもOK）' : '表示名（任意）'}
                </Text>
                <TextInput
                  style={styles.input}
                  value={newMemberName}
                  onChangeText={setNewMemberName}
                  placeholder={theme === 'child' ? 'ひょうじめい' : '表示名'}
                  placeholderTextColor={colors.text.disabled}
                  editable={!isAddingMember}
                />

                {/* パスワード */}
                <Text style={[styles.label, { marginTop: 16 }]}>
                  {theme === 'child' ? 'パスワード（8もじいじょう）' : 'パスワード（8文字以上）'}
                </Text>
                <TextInput
                  style={styles.input}
                  value={newMemberPassword}
                  onChangeText={setNewMemberPassword}
                  placeholder={theme === 'child' ? 'パスワード' : 'パスワード'}
                  placeholderTextColor={colors.text.disabled}
                  secureTextEntry
                  autoCapitalize="none"
                  editable={!isAddingMember}
                />

                {/* 編集権限チェックボックス */}
                <TouchableOpacity
                  style={[styles.checkboxContainer, { marginTop: 16 }]}
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

                {/* 保護者による同意（代理同意） */}
                <View style={[styles.consentSection, { marginTop: 20 }]}>
                  <Text style={styles.consentTitle}>
                    {theme === 'child'
                      ? 'ほごしゃのどうい'
                      : '保護者による同意（代理同意）'}
                  </Text>
                  <Text style={styles.consentDescription}>
                    {theme === 'child'
                      ? 'おこさまのアカウントをつくるときは、ほごしゃとしてどういしてね'
                      : 'お子様のアカウントを作成する場合、保護者としてプライバシーポリシーおよび利用規約に同意する必要があります。'}
                  </Text>

                  {/* プライバシーポリシーへの同意 */}
                  <TouchableOpacity
                    style={[styles.checkboxContainer, { marginTop: 12 }]}
                    onPress={() => setPrivacyConsent(!privacyConsent)}
                  >
                    <View style={[styles.checkbox, privacyConsent && styles.checkboxChecked]}>
                      {privacyConsent && <Text style={styles.checkmark}>✓</Text>}
                    </View>
                    <Text style={styles.checkboxLabel}>
                      {theme === 'child'
                        ? 'プライバシーポリシーにどういする'
                        : 'プライバシーポリシーに保護者として同意します'}
                      <Text style={styles.required}> *</Text>
                    </Text>
                  </TouchableOpacity>

                  {/* 利用規約への同意 */}
                  <TouchableOpacity
                    style={[styles.checkboxContainer, { marginTop: 8 }]}
                    onPress={() => setTermsConsent(!termsConsent)}
                  >
                    <View style={[styles.checkbox, termsConsent && styles.checkboxChecked]}>
                      {termsConsent && <Text style={styles.checkmark}>✓</Text>}
                    </View>
                    <Text style={styles.checkboxLabel}>
                      {theme === 'child'
                        ? 'りようきやくにどういする'
                        : '利用規約に保護者として同意します'}
                      <Text style={styles.required}> *</Text>
                    </Text>
                  </TouchableOpacity>
                </View>

                {/* 追加ボタン */}
                <TouchableOpacity
                  style={[
                    styles.addButton,
                    (!newMemberUsername.trim() || !newMemberEmail.trim() || !newMemberPassword.trim() || !privacyConsent || !termsConsent || isAddingMember) && styles.addButtonDisabled,
                  ]}
                  onPress={handleAddMember}
                  disabled={!newMemberUsername.trim() || !newMemberEmail.trim() || !newMemberPassword.trim() || !privacyConsent || !termsConsent || isAddingMember}
                >
                  {isAddingMember ? (
                    <ActivityIndicator size="small" color={colors.background} />
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
const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) =>
  StyleSheet.create({
    loadingContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: theme === 'child' ? '#FFF8E1' : colors.background,
    },
    container: {
      flex: 1,
      backgroundColor: theme === 'child' ? '#FFF8E1' : colors.background,
    },
    content: {
      padding: getSpacing(16, width),
    },
    card: {
      backgroundColor: colors.card,
      borderRadius: getBorderRadius(16, width),
      marginBottom: getSpacing(16, width),
      borderWidth: theme === 'child' ? 3 : 0,
      borderColor: theme === 'child' ? '#FF6B6B' : 'transparent',
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
      color: colors.background,
    },
    cardContent: {
      padding: getSpacing(16, width),
    },
    label: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '500',
      color: colors.text.secondary,
      marginBottom: getSpacing(8, width),
    },
    input: {
      backgroundColor: colors.background,
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(16, width),
      fontSize: getFontSize(16, width, theme),
      color: colors.text.primary,
      marginBottom: getSpacing(12, width),
    },
    readOnlyText: {
      backgroundColor: colors.background,
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(16, width),
      fontSize: getFontSize(16, width, theme),
      color: colors.text.secondary,
    },
    saveButton: {
      backgroundColor: accent.primary,
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: 48,
    },
    saveButtonDisabled: {
      backgroundColor: colors.border,
      opacity: 0.6,
    },
    saveButtonText: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: colors.background,
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
      color: colors.background,
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
      borderBottomColor: colors.border,
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
      color: colors.text.primary,
      marginBottom: getSpacing(2, width),
    },
    memberUsername: {
      fontSize: getFontSize(13, width, theme),
      color: colors.text.secondary,
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
      backgroundColor: colors.border,
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
      color: colors.text.primary,
    },
    badgeTextNormal: {
      fontSize: getFontSize(11, width, theme),
      fontWeight: '500',
      color: colors.text.secondary,
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
      backgroundColor: colors.background,
      borderColor: colors.border,
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
      backgroundColor: colors.background,
      borderColor: colors.border,
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
      color: colors.text.primary,
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
      borderColor: colors.border,
      borderRadius: getBorderRadius(4, width),
      marginRight: getSpacing(8, width),
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: colors.card,
    },
    checkboxChecked: {
      backgroundColor: accent.primary,
      borderColor: accent.primary,
    },
    checkmark: {
      color: colors.background,
      fontSize: getFontSize(12, width, theme),
      fontWeight: 'bold',
    },
    checkboxLabel: {
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
    },
    addButton: {
      backgroundColor: colors.status.success,
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: 48,
    },
    addButtonDisabled: {
      backgroundColor: colors.border,
      opacity: 0.6,
    },
    addButtonText: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: colors.background,
    },
    consentSection: {
      backgroundColor: theme === 'child' ? '#E3F2FD' : colors.card,
      borderRadius: getBorderRadius(12, width),
      padding: getSpacing(16, width),
      borderWidth: theme === 'child' ? 2 : 1,
      borderColor: theme === 'child' ? '#2196F3' : colors.border,
    },
    consentTitle: {
      fontSize: getFontSize(14, width, theme),
      fontWeight: '600',
      color: colors.text.primary,
      marginBottom: getSpacing(8, width),
    },
    consentDescription: {
      fontSize: getFontSize(13, width, theme),
      color: colors.text.secondary,
      lineHeight: getFontSize(18, width, theme),
      marginBottom: getSpacing(8, width),
    },
    required: {
      color: '#EF4444',
      fontWeight: 'bold',
    },
    searchChildrenButton: {
      marginBottom: getSpacing(16, width),
    },
    searchChildrenButtonGradient: {
      paddingVertical: getSpacing(14, width),
      paddingHorizontal: getSpacing(24, width),
      borderRadius: getBorderRadius(8, width),
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: 48,
    },
    searchChildrenButtonText: {
      color: '#FFFFFF',
      fontSize: getFontSize(16, width, theme),
      fontWeight: '700',
    },
    divider: {
      flexDirection: 'row',
      alignItems: 'center',
      marginVertical: getSpacing(16, width),
    },
    dividerText: {
      flex: 1,
      textAlign: 'center',
      fontSize: getFontSize(14, width, theme),
      color: colors.text.tertiary,
      fontWeight: '600',
    },
  });

export default GroupManagementScreen;

