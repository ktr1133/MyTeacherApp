/**
 * タグ-タスク紐付け管理サービス
 * 
 * Web版API統合:
 * - GET /tags/{tag}/tasks: タグに紐づくタスク一覧と未紐付けタスク一覧取得
 * - POST /tags/{tag}/tasks/attach: タスクをタグに紐付ける
 * - DELETE /tags/{tag}/tasks/detach: タスクからタグを解除
 * 
 * @see /home/ktr/mtdev/app/Http/Actions/Tags/TagTaskAction.php (Web版)
 * @see /home/ktr/mtdev/routes/web.php (ルート定義)
 */

import api from './api';

/**
 * タグに紐づくタスク一覧と未紐付けタスク一覧のレスポンス
 */
export interface TagTasksResponse {
  linked: Array<{
    id: number;
    title: string;
  }>;
  available: Array<{
    id: number;
    title: string;
  }>;
}

/**
 * タグ-タスク紐付け管理サービス
 */
class TagTaskService {
  /**
   * タグに紐づくタスク一覧と未紐付けタスク一覧を取得
   * 
   * @param tagId タグID
   * @returns タグに紐づくタスクと未紐付けタスクのリスト
   */
  async getTagTasks(tagId: number): Promise<TagTasksResponse> {
    const response = await api.get<TagTasksResponse>(`/tags/${tagId}/tasks`);
    return response.data;
  }

  /**
   * タスクをタグに紐付ける
   * 
   * @param tagId タグID
   * @param taskId タスクID
   * @returns 成功メッセージ
   */
  async attachTask(tagId: number, taskId: number): Promise<{ message: string }> {
    const response = await api.post<{ message: string }>(
      `/tags/${tagId}/tasks/attach`,
      { task_id: taskId }
    );
    return response.data;
  }

  /**
   * タスクからタグを解除
   * 
   * @param tagId タグID
   * @param taskId タスクID
   * @returns 成功メッセージ
   */
  async detachTask(tagId: number, taskId: number): Promise<{ message: string }> {
    const response = await api.delete<{ message: string }>(
      `/tags/${tagId}/tasks/detach`,
      { data: { task_id: taskId } }
    );
    return response.data;
  }
}

export const tagTaskService = new TagTaskService();
