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
  console.log('🎭 [avatarService] getAvatar called');
  
  try {
    const response = await api.get<AvatarApiResponse>('/avatar');
    console.log('🎭 [avatarService] Get avatar response:', JSON.stringify(response.data, null, 2));
    console.log('🎭 [avatarService] Avatar images:', response.data.data.avatar.images);
    
    return response.data.data.avatar;
  } catch (error: any) {
    console.error('🎭 [avatarService] Get avatar error:', error);
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
  console.log('🎭 [avatarService] createAvatar called:', data);
  
  try {
    const response = await api.post<AvatarApiResponse>('/avatar', data);
    console.log('🎭 [avatarService] Create avatar response:', response.data);
    
    return response.data.data.avatar;
  } catch (error: any) {
    console.error('🎭 [avatarService] Create avatar error:', error);
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
  console.log('🎭 [avatarService] updateAvatar called:', data);
  
  try {
    const response = await api.put<AvatarApiResponse>('/avatar', data);
    console.log('🎭 [avatarService] Update avatar response:', response.data);
    
    return response.data.data.avatar;
  } catch (error: any) {
    console.error('🎭 [avatarService] Update avatar error:', error);
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
  console.log('🎭 [avatarService] deleteAvatar called');
  
  try {
    const response = await api.delete<DeleteAvatarApiResponse>('/avatar');
    console.log('🎭 [avatarService] Delete avatar response:', response.data);
  } catch (error: any) {
    console.error('🎭 [avatarService] Delete avatar error:', error);
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
  console.log('🎭 [avatarService] regenerateImages called');
  
  try {
    const response = await api.post<AvatarApiResponse>('/avatar/regenerate');
    console.log('🎭 [avatarService] Regenerate images response:', response.data);
    
    return response.data.data.avatar;
  } catch (error: any) {
    console.error('🎭 [avatarService] Regenerate images error:', error);
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
  console.log('🎭 [avatarService] toggleVisibility called:', isVisible);
  
  try {
    const response = await api.patch<AvatarApiResponse>('/avatar/visibility', { is_visible: isVisible });
    console.log('🎭 [avatarService] Toggle visibility response:', response.data);
    
    return response.data.data.avatar;
  } catch (error: any) {
    console.error('🎭 [avatarService] Toggle visibility error:', error);
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
  console.log('🎭 [avatarService] getCommentForEvent called:', { eventType });
  console.log('🎭 [avatarService] API endpoint:', `/avatar/comment/${eventType}`);
  
  try {
    const response = await api.get<{
      success: boolean;
      data: {
        comment: string;
        image_url: string;
        animation: string;
      };
    }>(`/avatar/comment/${eventType}`);
    
    console.log('🎭 [avatarService] API response:', response);
    console.log('🎭 [avatarService] Response data:', response.data);
    
    // snake_case → camelCase 変換
    const result: AvatarCommentResponse = {
      comment: response.data.data.comment,
      imageUrl: response.data.data.image_url,
      animation: response.data.data.animation as any,
    };
    
    console.log('🎭 [avatarService] Converted response:', result);
    return result;
  } catch (error: any) {
    console.error('🎭 [avatarService] API error:', error);
    console.error('🎭 [avatarService] Error response:', error.response);
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
