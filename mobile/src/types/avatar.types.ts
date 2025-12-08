/**
 * アバター型定義
 */

/**
 * アバターイベント種別
 * config/const.php の avatar_events に対応
 */
export type AvatarEventType =
  | 'task_created'
  | 'task_updated'
  | 'task_deleted'
  | 'task_completed'
  | 'task_breakdown'
  | 'task_breakdown_refine'
  | 'group_task_created'
  | 'group_task_updated'
  | 'group_edited'
  | 'login'
  | 'logout'
  | 'login_gap'
  | 'token_purchased'
  | 'performance_personal_viewed'
  | 'performance_group_viewed'
  | 'tag_created'
  | 'tag_updated'
  | 'tag_deleted'
  | 'group_created'
  | 'group_deleted'
  | 'notification_created'
  | 'notification_updated';

/**
 * アバター表情種別
 */
export type AvatarExpressionType = 'normal' | 'happy' | 'sad' | 'angry' | 'surprised';

/**
 * アバター画像タイプ
 */
export type AvatarImageType = 'bust' | 'full_body';

/**
 * アバターアニメーション種別
 */
export type AvatarAnimationType =
  | 'avatar-idle'
  | 'avatar-joy'
  | 'avatar-worry'
  | 'avatar-cheer'
  | 'avatar-question'
  | 'avatar-secretary'
  | 'avatar-wave'
  | 'avatar-goodbye'
  | 'avatar-thanks'
  | 'avatar-applause'
  | 'avatar-nod'
  | 'avatar-shake'
  | 'avatar-bless'
  | 'avatar-confirm';

/**
 * アバターコメントレスポンス
 */
export interface AvatarCommentResponse {
  comment: string;
  imageUrl: string;
  animation: AvatarAnimationType;
}

/**
 * アバター表示データ
 */
export interface AvatarDisplayData {
  comment: string;
  imageUrl: string;
  animation: AvatarAnimationType;
  eventType: AvatarEventType;
  timestamp: number;
}

/**
 * アバターウィジェット設定
 */
export interface AvatarWidgetConfig {
  autoHideDelay?: number; // 自動非表示までの遅延時間（ミリ秒）、デフォルト: 20000
  position?: 'top' | 'center' | 'bottom'; // 表示位置、デフォルト: 'center'
  enableAnimation?: boolean; // アニメーション有効化、デフォルト: true
}

/**
 * アバター表示状態
 */
export interface AvatarState {
  isVisible: boolean;
  currentData: AvatarDisplayData | null;
  isLoading: boolean;
}

// ============================================================
// アバター管理機能（Phase 2.B-7）
// ============================================================

/**
 * 生成ステータス
 */
export type AvatarGenerationStatus = 'pending' | 'processing' | 'completed' | 'failed';

/**
 * 性別
 */
export type AvatarSex = 'male' | 'female' | 'other';

/**
 * 髪型
 */
export type AvatarHairStyle = 'short' | 'middle' | 'long';

/**
 * 髪の色
 */
export type AvatarHairColor = 'black' | 'brown' | 'blonde' | 'silver' | 'red';

/**
 * 目の色
 */
export type AvatarEyeColor = 'black' | 'brown' | 'blue' | 'green' | 'gray' | 'purple';

/**
 * 服装
 */
export type AvatarClothing = 'suit' | 'casual' | 'kimono' | 'robe' | 'dress';

/**
 * アクセサリー
 */
export type AvatarAccessory = 'nothing' | 'glasses' | 'hat' | 'necklace' | 'cheer';

/**
 * 体型
 */
export type AvatarBodyType = 'slim' | 'average' | 'sturdy' | 'chubby';

/**
 * 口調
 */
export type AvatarTone = 'gentle' | 'friendly' | 'strict' | 'intellectual';

/**
 * 熱意
 */
export type AvatarEnthusiasm = 'modest' | 'normal' | 'high';

/**
 * 丁寧さ
 */
export type AvatarFormality = 'polite' | 'casual' | 'formal';

/**
 * ユーモア
 */
export type AvatarHumor = 'high' | 'normal' | 'low';

/**
 * 描画モデルバージョン
 */
export type AvatarDrawModelVersion = 'anything-v4.0' | 'animagine-xl-3.1' | 'stable-diffusion-3.5-medium';

/**
 * アバター画像
 */
export interface AvatarImage {
  id: number;
  image_type: 'full_body' | 'bust';
  emotion: 'neutral' | 'happy' | 'sad' | 'angry';
  image_url: string | null;
  created_at: string;
}

/**
 * アバター基本情報
 */
export interface Avatar {
  id: number;
  user_id: number;
  seed: number;
  sex: AvatarSex;
  hair_style: AvatarHairStyle | null;
  hair_color: AvatarHairColor;
  eye_color: AvatarEyeColor;
  clothing: AvatarClothing;
  accessory: AvatarAccessory | null;
  body_type: AvatarBodyType;
  tone: AvatarTone;
  enthusiasm: AvatarEnthusiasm;
  formality: AvatarFormality;
  humor: AvatarHumor;
  draw_model_version: AvatarDrawModelVersion;
  is_transparent: boolean;
  is_chibi: boolean;
  estimated_token_usage: number;
  generation_status: AvatarGenerationStatus;
  last_generated_at: string | null;
  is_visible: boolean;
  created_at: string;
  updated_at: string;
  images: AvatarImage[];
}

/**
 * アバター作成リクエスト
 */
export interface CreateAvatarRequest {
  sex: AvatarSex;
  hair_style: AvatarHairStyle;
  hair_color: AvatarHairColor;
  eye_color: AvatarEyeColor;
  clothing: AvatarClothing;
  accessory?: AvatarAccessory;
  body_type: AvatarBodyType;
  tone: AvatarTone;
  enthusiasm: AvatarEnthusiasm;
  formality: AvatarFormality;
  humor: AvatarHumor;
  draw_model_version: AvatarDrawModelVersion;
  is_transparent: boolean;
  is_chibi: boolean;
}

/**
 * アバター更新リクエスト
 */
export interface UpdateAvatarRequest extends CreateAvatarRequest {}

/**
 * アバターAPIレスポンス
 */
export interface AvatarApiResponse {
  success: boolean;
  message?: string;
  data: {
    avatar: Avatar;
  };
}

/**
 * アバター削除APIレスポンス
 */
export interface DeleteAvatarApiResponse {
  success: boolean;
  message: string;
}
