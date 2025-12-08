/**
 * アバターAPI通信サービス
 * 
 * 教師アバターのコメント取得機能を提供
 * Web版の GetAvatarCommentApiAction に対応
 */
import api from './api';
import { AvatarEventType, AvatarCommentResponse } from '../types/avatar.types';

/**
 * 指定イベントのアバターコメントを取得
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
  getCommentForEvent,
};

export default avatarService;
