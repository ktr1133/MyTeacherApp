/**
 * アバター管理カスタムフック
 * 
 * アバター表示・非表示の制御、イベント処理を提供
 * Context APIベースのグローバルステート管理にリダイレクト
 * 
 * @deprecated このフックは後方互換性のため残されています。
 * 新規コードでは useAvatarContext を直接使用してください。
 */
import { useAvatarContext } from '../contexts/AvatarContext';
import { AvatarWidgetConfig } from '../types/avatar.types';

/**
 * useAvatar フック
 * 
 * @param _config - アバターウィジェット設定（現在は未使用、Context Providerで設定）
 * @returns アバター状態と制御関数
 */
export const useAvatar = (_config: AvatarWidgetConfig = {}) => {
  // Context APIベースのグローバルステートを使用
  return useAvatarContext();
};

export default useAvatar;
