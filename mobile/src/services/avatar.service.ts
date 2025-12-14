/**
 * アバターAPI通信サービス
 * 
 * 教師アバターのCRUD + コメント取得機能を提供
 * Phase 2.B-5 Step 3: コメント取得機能実装済み
 * Phase 2.B-7: CRUD機能追加
 */
import api from './api';
import { 
  AvatarEventType, 
  AvatarCommentResponse,
  Avatar,
  CreateAvatarRequest,
  UpdateAvatarRequest,
  AvatarApiResponse,
  DeleteAvatarApiResponse,
} from '../types/avatar.types';

/**
 * アバター取得
 * 
 * @returns アバター情報
 * @throws {Error} API通信エラーまたはアバター未作成
 */
const getAvatar = async (): Promise<Avatar> => {
  try {
    const response = await api.get<AvatarApiResponse>('/avatar');
    
    return response.data.data.avatar;
  } catch (error: any) {
    throw error;
  }
};

/**
 * アバター作成
 * 
 * @param data - アバター作成データ
 * @returns 作成されたアバター情報（generation_status: 'pending'）
 * @throws {Error} API通信エラーまたはトークン不足
 */
const createAvatar = async (data: CreateAvatarRequest): Promise<Avatar> => {
  try {
    // アバター作成は時間がかかる可能性があるため、タイムアウトを30秒に延長
    const response = await api.post<AvatarApiResponse>('/avatar', data, {
      timeout: 30000, // 30秒
    });
    
    return response.data.data.avatar;
  } catch (error: any) {
    throw error;
  }
};

/**
 * アバター更新
 * 
 * @param data - アバター更新データ
 * @returns 更新されたアバター情報
 * @throws {Error} API通信エラー
 */
const updateAvatar = async (data: UpdateAvatarRequest): Promise<Avatar> => {
  try {
    const response = await api.put<AvatarApiResponse>('/avatar', data);
    
    return response.data.data.avatar;
  } catch (error: any) {
    throw error;
  }
};

/**
 * アバター削除
 * 
 * @returns 削除成功メッセージ
 * @throws {Error} API通信エラー
 */
const deleteAvatar = async (): Promise<void> => {
  try {
    const response = await api.delete<DeleteAvatarApiResponse>('/avatar');
  } catch (error: any) {
    throw error;
  }
};

/**
 * アバター画像再生成
 * 
 * @returns 再生成開始後のアバター情報（generation_status: 'pending'）
 * @throws {Error} API通信エラーまたはトークン不足
 */
const regenerateImages = async (): Promise<Avatar> => {
  try {
    // 画像再生成は時間がかかる可能性があるため、タイムアウトを30秒に延長
    const response = await api.post<AvatarApiResponse>('/avatar/regenerate', {}, {
      timeout: 30000, // 30秒
    });
    
    return response.data.data.avatar;
  } catch (error: any) {
    throw error;
  }
};

/**
 * アバター表示設定切替
 * 
 * @param isVisible - 表示/非表示フラグ
 * @returns 切替後のアバター情報
 * @throws {Error} API通信エラー
 */
const toggleVisibility = async (isVisible: boolean): Promise<Avatar> => {
  try {
    const response = await api.patch<AvatarApiResponse>('/avatar/visibility', { is_visible: isVisible });
    
    return response.data.data.avatar;
  } catch (error: any) {
    throw error;
  }
};

/**
 * 指定イベントのアバターコメントを取得
 * 
 * Phase 2.B-5 Step 3実装済み
 * 
 * @param eventType - アバターイベント種別
 * @returns アバターコメントデータ（画像URL、コメント、アニメーション）
 * @throws {Error} API通信エラーまたは無効なイベントタイプ
 */
const getCommentForEvent = async (
  eventType: AvatarEventType
): Promise<AvatarCommentResponse> => {
  try {
    const response = await api.get<{
      success: boolean;
      data: {
        comment: string;
        image_url: string;
        animation: string;
      };
    }>(`/avatar/comment/${eventType}`);
    
    // snake_case → camelCase 変換
    const result: AvatarCommentResponse = {
      comment: response.data.data.comment,
      imageUrl: response.data.data.image_url,
      animation: response.data.data.animation as any,
    };
    
    return result;
  } catch (error: any) {
    throw error;
  }
};

/**
 * アバターサービス
 */
export const avatarService = {
  getAvatar,
  createAvatar,
  updateAvatar,
  deleteAvatar,
  regenerateImages,
  toggleVisibility,
  getCommentForEvent,
};

export default avatarService;
