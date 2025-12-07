/**
 * タグ関連の型定義
 * 
 * バックエンドAPI（/api/tags）のレスポンス形式に対応
 */

export interface Tag {
  id: number;
  name: string;
  color: string; // HEX形式（例: "#3B82F6"）
  tasks_count: number; // 関連タスク件数
  created_at: string; // ISO 8601形式
  updated_at: string; // ISO 8601形式
}

export interface TagsResponse {
  tags: Tag[];
  tasks?: Task[]; // タグ一覧取得時にタスクも含まれる場合
}

export interface Task {
  id: number;
  title: string;
  is_completed: boolean;
  tag_id?: number | null;
}

export interface CreateTagRequest {
  name: string;
  color?: string; // オプション（デフォルト: #3B82F6）
}

export interface UpdateTagRequest {
  name: string;
  color?: string;
}

export interface TagApiResponse {
  tag: Tag;
  avatar_event?: string; // アバターイベント（tag_created, tag_updated, tag_deleted）
}

export interface DeleteTagResponse {
  deleted_tag_id: number;
  avatar_event?: string;
}
