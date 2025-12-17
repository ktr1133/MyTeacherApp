/**
 * GroupService - グループ管理API通信サービス
 * 
 * 機能:
 * - グループ情報取得
 * - グループ情報更新
 * - メンバー管理（追加・削除・権限変更・テーマ切り替え・マスター譲渡）
 */

import api from './api';
import type {
  GroupEditResponse,
  UpdateGroupRequest,
  AddMemberRequest,
  UpdatePermissionRequest,
  ToggleThemeRequest,
} from '../types/group.types';

/**
 * グループ情報とメンバー一覧を取得
 */
export const getGroupInfo = async (): Promise<GroupEditResponse> => {
  const response = await api.get<GroupEditResponse>('/groups/edit');
  return response.data;
};

/**
 * グループ情報を更新
 */
export const updateGroup = async (data: UpdateGroupRequest): Promise<void> => {
  await api.patch('/groups', data);
};

/**
 * メンバーを追加
 */
export const addMember = async (data: AddMemberRequest): Promise<void> => {
  await api.post('/groups/members', data);
};

/**
 * メンバーの権限を更新
 */
export const updateMemberPermission = async (
  memberId: number,
  data: UpdatePermissionRequest
): Promise<void> => {
  await api.patch(`/groups/members/${memberId}/permission`, data);
};

/**
 * メンバーのテーマを切り替え
 */
export const toggleMemberTheme = async (
  memberId: number,
  data: ToggleThemeRequest
): Promise<void> => {
  await api.patch(`/groups/members/${memberId}/theme`, data);
};

/**
 * マスター権限を譲渡
 */
export const transferMaster = async (newMasterId: number): Promise<void> => {
  await api.post(`/groups/transfer/${newMasterId}`);
};

/**
 * メンバーを削除
 */
export const removeMember = async (memberId: number): Promise<void> => {
  await api.delete(`/groups/members/${memberId}`);
};

/**
 * 未紐付け子アカウントを検索（Phase 6）
 */
export const searchUnlinkedChildren = async (parentEmail: string): Promise<{
  success: boolean;
  message: string;
  data: {
    children: Array<{
      id: number;
      username: string;
      name: string | null;
      email: string;
      created_at: string;
      is_minor: boolean;
    }>;
    count: number;
    parent_email: string;
  };
}> => {
  const response = await api.post('/profile/group/search-children', {
    parent_email: parentEmail,
  });
  return response.data;
};

/**
 * 紐付けリクエストを送信（Phase 6）
 */
export const sendLinkRequest = async (childUserId: number): Promise<{
  success: boolean;
  message: string;
  data: {
    notification_id: number;
    child_user: {
      id: number;
      username: string;
      name: string | null;
    };
  };
}> => {
  const response = await api.post('/profile/group/send-link-request', {
    child_user_id: childUserId,
  });
  return response.data;
};
