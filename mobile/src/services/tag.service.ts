/**
 * タグAPI通信サービス
 * 
 * Laravel API（/api/tags）との通信を担当
 * Sanctum認証（JWT）を使用
 */
import api from './api';
import type { ApiResponse } from '../types/api.types';
import type {
  TagsResponse,
  TagApiResponse,
  CreateTagRequest,
  UpdateTagRequest,
  DeleteTagResponse,
} from '../types/tag.types';

/**
 * タグ一覧を取得（ユーザーに紐づくタグとタスク）
 * 
 * @returns タグ一覧とタスク一覧
 */
export const getTagsWithTasks = async (): Promise<TagsResponse> => {
  const response = await api.get<ApiResponse<TagsResponse>>('/tags');
  return response.data.data;
};

/**
 * タグを作成
 * 
 * @param data タグ名と色（オプション）
 * @returns 作成されたタグとアバターイベント
 */
export const createTag = async (
  data: CreateTagRequest
): Promise<TagApiResponse> => {
  const response = await api.post<ApiResponse<TagApiResponse>>('/tags', data);
  return response.data.data;
};

/**
 * タグを更新
 * 
 * @param id タグID
 * @param data タグ名と色
 * @returns 更新されたタグとアバターイベント
 */
export const updateTag = async (
  id: number,
  data: UpdateTagRequest
): Promise<TagApiResponse> => {
  const response = await api.put<ApiResponse<TagApiResponse>>(
    `/tags/${id}`,
    data
  );
  return response.data.data;
};

/**
 * タグを削除
 * 
 * @param id タグID
 * @returns 削除されたタグIDとアバターイベント
 */
export const deleteTag = async (
  id: number
): Promise<DeleteTagResponse> => {
  const response = await api.delete<ApiResponse<DeleteTagResponse>>(
    `/tags/${id}`
  );
  return response.data.data;
};
