/**
 * アバターコンテキスト
 * 
 * アプリ全体でアバター状態を共有するためのContext API実装
 * 画面遷移時もアバター表示状態を維持
 */
import React, { createContext, useContext, useState, useCallback, useRef, useEffect } from 'react';
import {
  AvatarEventType,
  AvatarDisplayData,
  AvatarWidgetConfig,
  AvatarState,
} from '../types/avatar.types';
import avatarService from '../services/avatar.service';

/**
 * デフォルト設定
 */
const DEFAULT_CONFIG: Required<AvatarWidgetConfig> = {
  autoHideDelay: 20000, // 20秒
  position: 'center',
  enableAnimation: true,
};

/**
 * アバターコンテキストの型定義
 */
interface AvatarContextType {
  // 状態
  isVisible: boolean;
  currentData: AvatarDisplayData | null;
  isLoading: boolean;

  // 制御関数
  dispatchAvatarEvent: (eventType: AvatarEventType) => Promise<void>;
  showAvatarDirect: (
    comment: string,
    imageUrl: string,
    animation: string,
    eventType?: AvatarEventType
  ) => void;
  hideAvatar: () => void;
}

/**
 * アバターコンテキスト
 */
const AvatarContext = createContext<AvatarContextType | undefined>(undefined);

/**
 * アバタープロバイダーのプロパティ
 */
interface AvatarProviderProps {
  children: React.ReactNode;
  config?: AvatarWidgetConfig;
}

/**
 * アバタープロバイダー
 * 
 * アプリのルートレベルで使用し、全画面でアバター状態を共有
 */
export const AvatarProvider: React.FC<AvatarProviderProps> = ({ children, config = {} }) => {
  const mergedConfig = { ...DEFAULT_CONFIG, ...config };

  // 状態管理
  const [state, setState] = useState<AvatarState>({
    isVisible: false,
    currentData: null,
    isLoading: false,
  });

  // 自動非表示タイマー参照
  const autoHideTimerRef = useRef<NodeJS.Timeout | null>(null);

  /**
   * タイマーをクリア
   */
  const clearAutoHideTimer = useCallback(() => {
    if (autoHideTimerRef.current) {
      clearTimeout(autoHideTimerRef.current);
      autoHideTimerRef.current = null;
    }
  }, []);

  /**
   * 自動非表示タイマーを設定
   */
  const setAutoHideTimer = useCallback(() => {
    clearAutoHideTimer();
    autoHideTimerRef.current = setTimeout(() => {
      hideAvatar();
    }, mergedConfig.autoHideDelay);
  }, [mergedConfig.autoHideDelay, clearAutoHideTimer]);

  /**
   * アバターを表示
   * 
   * @param data - 表示データ
   */
  const showAvatar = useCallback(
    (data: AvatarDisplayData) => {
      console.log('🎭 [AvatarContext] showAvatar called with data:', data);
      setState({
        isVisible: true,
        currentData: data,
        isLoading: false,
      });
      console.log('🎭 [AvatarContext] State updated: isVisible=true');

      // 自動非表示タイマーを設定
      setAutoHideTimer();
      console.log('🎭 [AvatarContext] Auto-hide timer set');
    },
    [setAutoHideTimer]
  );

  /**
   * アバターを非表示
   */
  const hideAvatar = useCallback(() => {
    console.log('🎭 [AvatarContext] hideAvatar called');
    setState((prev) => ({
      ...prev,
      isVisible: false,
    }));
    clearAutoHideTimer();
  }, [clearAutoHideTimer]);

  /**
   * イベント発火でアバターコメントを取得・表示
   * 
   * @param eventType - アバターイベント種別
   */
  const dispatchAvatarEvent = useCallback(
    async (eventType: AvatarEventType) => {
      console.log('🎭 [AvatarContext] dispatchAvatarEvent called:', { eventType });
      try {
        setState((prev) => ({ ...prev, isLoading: true }));
        console.log('🎭 [AvatarContext] State set to loading');

        const response = await avatarService.getCommentForEvent(eventType);
        console.log('🎭 [AvatarContext] API response received:', response);

        const displayData: AvatarDisplayData = {
          comment: response.comment,
          imageUrl: response.imageUrl,
          animation: response.animation,
          eventType,
          timestamp: Date.now(),
        };
        console.log('🎭 [AvatarContext] Display data prepared:', displayData);

        showAvatar(displayData);
        console.log('🎭 [AvatarContext] showAvatar called');
      } catch (error) {
        console.error('🎭 [AvatarContext] Failed to fetch avatar comment:', error);
        setState((prev) => ({ ...prev, isLoading: false }));
      }
    },
    [showAvatar]
  );

  /**
   * アバターを直接表示（APIコールなし）
   * 
   * @param comment - コメントテキスト
   * @param imageUrl - 画像URL
   * @param animation - アニメーション種別
   * @param eventType - イベントタイプ（オプション）
   */
  const showAvatarDirect = useCallback(
    (
      comment: string,
      imageUrl: string,
      animation: string,
      eventType: AvatarEventType = 'task_created'
    ) => {
      console.log('🎭 [AvatarContext] showAvatarDirect called:', {
        comment,
        imageUrl,
        animation,
        eventType,
      });
      const displayData: AvatarDisplayData = {
        comment,
        imageUrl,
        animation: animation as any,
        eventType,
        timestamp: Date.now(),
      };

      showAvatar(displayData);
    },
    [showAvatar]
  );

  /**
   * クリーンアップ（アンマウント時）
   */
  useEffect(() => {
    return () => {
      clearAutoHideTimer();
    };
  }, [clearAutoHideTimer]);

  const value: AvatarContextType = {
    // 状態
    isVisible: state.isVisible,
    currentData: state.currentData,
    isLoading: state.isLoading,

    // 制御関数
    dispatchAvatarEvent,
    showAvatarDirect,
    hideAvatar,
  };

  return <AvatarContext.Provider value={value}>{children}</AvatarContext.Provider>;
};

/**
 * アバターコンテキストフック
 * 
 * @returns アバターコンテキスト
 * @throws AvatarProviderでラップされていない場合
 */
export const useAvatarContext = (): AvatarContextType => {
  const context = useContext(AvatarContext);
  if (!context) {
    throw new Error('useAvatarContext must be used within AvatarProvider');
  }
  return context;
};
