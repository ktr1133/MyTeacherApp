/**
 * useAvatarManagement Hook テスト
 * 
 * Phase 2.B-7: アバター管理機能実装
 * 
 * テスト対象:
 * - fetchAvatar: アバター情報取得
 * - createAvatar: アバター作成
 * - updateAvatar: アバター更新
 * - deleteAvatar: アバター削除
 * - regenerateImages: 画像再生成
 * - toggleVisibility: 表示/非表示切り替え
 * - clearError: エラークリア
 * - 状態管理（avatar, isLoading, error）
 */

import { renderHook, act, waitFor } from '@testing-library/react-native';
import { useAvatarManagement } from '../useAvatarManagement';
import { avatarService } from '../../services/avatar.service';
import {
  Avatar,
  CreateAvatarRequest,
  UpdateAvatarRequest,
} from '../../types/avatar.types';

// avatarService をモック
jest.mock('../../services/avatar.service');

describe('useAvatarManagement', () => {
  const mockAvatar: Avatar = {
    id: 1,
    user_id: 1,
    seed: 12345,
    sex: 'female',
    hair_style: 'long',
    hair_color: 'black',
    eye_color: 'brown',
    clothing: 'suit',
    accessory: 'nothing',
    body_type: 'average',
    tone: 'gentle',
    enthusiasm: 'normal',
    formality: 'polite',
    humor: 'normal',
    draw_model_version: 'anything-v4.0',
    is_transparent: true,
    is_chibi: false,
    estimated_token_usage: 5000,
    is_visible: true,
    generation_status: 'completed',
    last_generated_at: '2025-01-15T10:00:00Z',
    created_at: '2025-01-15T10:00:00Z',
    updated_at: '2025-01-15T10:00:00Z',
    images: [],
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ========== fetchAvatar ==========
  describe('fetchAvatar', () => {
    it('アバター情報を正常に取得できる', async () => {
      (avatarService.getAvatar as jest.Mock).mockResolvedValue(mockAvatar);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        await result.current.fetchAvatar();
      });

      await waitFor(() => {
        expect(result.current.avatar).toEqual(mockAvatar);
        expect(result.current.isLoading).toBe(false);
        expect(result.current.error).toBeNull();
      });
    });

    it('アバター未作成時（null）、エラーをセットしない', async () => {
      (avatarService.getAvatar as jest.Mock).mockResolvedValue(null);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        await result.current.fetchAvatar();
      });

      await waitFor(() => {
        expect(result.current.avatar).toBeNull();
        expect(result.current.error).toBeNull(); // 404はエラーではない
        expect(result.current.isLoading).toBe(false);
      });
    });

    it('API通信エラー時、エラーメッセージをセットする', async () => {
      const error = {
        response: {
          data: {
            message: 'Network Error',
          },
        },
      };
      (avatarService.getAvatar as jest.Mock).mockRejectedValue(error);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        try {
          await result.current.fetchAvatar();
        } catch (e) {
          // エラーが再スローされるのでキャッチ
        }
      });

      await waitFor(() => {
        expect(result.current.error).toBe('Network Error');
        expect(result.current.isLoading).toBe(false);
      });
    });
  });

  // ========== createAvatar ==========
  describe('createAvatar', () => {
    it('アバターを正常に作成できる', async () => {
      const requestData: CreateAvatarRequest = {
        sex: 'male',
        hair_style: 'short',
        hair_color: 'black',
        eye_color: 'black',
        clothing: 'suit',
        accessory: 'nothing',
        body_type: 'average',
        tone: 'gentle',
        enthusiasm: 'normal',
        formality: 'polite',
        humor: 'normal',
        draw_model_version: 'anything-v4.0',
        is_transparent: true,
        is_chibi: false,
      };

      const createdAvatar: Avatar = {
        ...mockAvatar,
        sex: 'male',
        hair_style: 'short',
        generation_status: 'pending',
      };

      (avatarService.createAvatar as jest.Mock).mockResolvedValue(createdAvatar);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        await result.current.createAvatar(requestData);
      });

      await waitFor(() => {
        expect(result.current.avatar).toEqual(createdAvatar);
        expect(result.current.isLoading).toBe(false);
        expect(result.current.error).toBeNull();
      });
    });

    it('バリデーションエラー時、適切なエラーメッセージをセットする', async () => {
      const requestData: CreateAvatarRequest = {
        sex: 'male',
        hair_style: 'short',
        hair_color: 'black',
        eye_color: 'black',
        clothing: 'suit',
        accessory: 'nothing',
        body_type: 'average',
        tone: 'gentle',
        enthusiasm: 'normal',
        formality: 'polite',
        humor: 'normal',
        draw_model_version: 'anything-v4.0',
        is_transparent: true,
        is_chibi: false,
      };

      const mockError = {
        response: {
          data: {
            message: 'Validation error',
            errors: { sex: ['Invalid value'] },
          },
        },
      };

      (avatarService.createAvatar as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        try {
          await result.current.createAvatar(requestData);
        } catch (e) {
          // エラーが再スローされるのでキャッチ
        }
      });

      await waitFor(() => {
        expect(result.current.error).toContain('Validation error');
        expect(result.current.isLoading).toBe(false);
      });
    });
  });

  // ========== updateAvatar ==========
  describe('updateAvatar', () => {
    it('アバター設定を正常に更新できる', async () => {
      const requestData: UpdateAvatarRequest = {
        sex: 'female',
        hair_style: 'long',
        hair_color: 'brown',
        eye_color: 'blue',
        clothing: 'casual',
        accessory: 'glasses',
        body_type: 'slim',
        tone: 'friendly',
        enthusiasm: 'high',
        formality: 'casual',
        humor: 'high',
        draw_model_version: 'anything-v4.0',
        is_transparent: true,
        is_chibi: false,
      };

      const updatedAvatar: Avatar = {
        ...mockAvatar,
        hair_color: 'brown',
        eye_color: 'blue',
      };

      (avatarService.updateAvatar as jest.Mock).mockResolvedValue(updatedAvatar);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        await result.current.updateAvatar(requestData);
      });

      await waitFor(() => {
        expect(result.current.avatar).toEqual(updatedAvatar);
        expect(result.current.isLoading).toBe(false);
      });
    });
  });

  // ========== deleteAvatar ==========
  describe('deleteAvatar', () => {
    it('アバターを正常に削除できる', async () => {
      (avatarService.deleteAvatar as jest.Mock).mockResolvedValue(undefined);

      const { result } = renderHook(() => useAvatarManagement());

      // 初期状態にアバターをセット
      await act(async () => {
        (avatarService.getAvatar as jest.Mock).mockResolvedValue(mockAvatar);
        await result.current.fetchAvatar();
      });

      // 削除実行
      await act(async () => {
        await result.current.deleteAvatar();
      });

      await waitFor(() => {
        expect(result.current.avatar).toBeNull(); // 削除後はnull
        expect(result.current.isLoading).toBe(false);
      });
    });

    it('削除失敗時、エラーメッセージをセットする', async () => {
      const error = {
        response: {
          data: {
            message: 'Delete failed',
          },
        },
      };
      (avatarService.deleteAvatar as jest.Mock).mockRejectedValue(error);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        try {
          await result.current.deleteAvatar();
        } catch (e) {
          // エラーが再スローされるのでキャッチ
        }
      });

      await waitFor(() => {
        expect(result.current.error).toBe('Delete failed');
        expect(result.current.isLoading).toBe(false);
      });
    });
  });

  // ========== regenerateImages ==========
  describe('regenerateImages', () => {
    it('画像を正常に再生成できる', async () => {
      const regeneratedAvatar: Avatar = {
        ...mockAvatar,
        generation_status: 'pending',
      };

      (avatarService.regenerateImages as jest.Mock).mockResolvedValue(regeneratedAvatar);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        await result.current.regenerateImages();
      });

      await waitFor(() => {
        expect(result.current.avatar).toEqual(regeneratedAvatar);
        expect(result.current.avatar?.generation_status).toBe('pending');
      });
    });
  });

  // ========== toggleVisibility ==========
  describe('toggleVisibility', () => {
    it('表示状態を正常に切り替えられる', async () => {
      const toggledAvatar: Avatar = {
        ...mockAvatar,
        is_visible: false,
      };

      (avatarService.toggleVisibility as jest.Mock).mockResolvedValue(toggledAvatar);

      const { result } = renderHook(() => useAvatarManagement());

      await act(async () => {
        await result.current.toggleVisibility(false);
      });

      await waitFor(() => {
        expect(result.current.avatar?.is_visible).toBe(false);
      });
    });
  });

  // ========== clearError ==========
  describe('clearError', () => {
    it('エラーメッセージをクリアできる', async () => {
      const error = {
        response: {
          data: {
            message: 'Test Error',
          },
        },
      };
      (avatarService.getAvatar as jest.Mock).mockRejectedValue(error);

      const { result } = renderHook(() => useAvatarManagement());

      // エラーをセット
      await act(async () => {
        try {
          await result.current.fetchAvatar();
        } catch (e) {
          // エラーが再スローされるのでキャッチ
        }
      });

      await waitFor(() => {
        expect(result.current.error).toBe('Test Error');
      });

      // エラークリア
      act(() => {
        result.current.clearError();
      });

      expect(result.current.error).toBeNull();
    });
  });

  // ========== isLoading状態テスト ==========
  describe('isLoading state', () => {
    it('API呼び出し中はisLoadingがtrueになる', async () => {
      let resolveGetAvatar: (value: Avatar) => void;
      const getAvatarPromise = new Promise<Avatar>((resolve) => {
        resolveGetAvatar = resolve;
      });

      (avatarService.getAvatar as jest.Mock).mockReturnValue(getAvatarPromise);

      const { result } = renderHook(() => useAvatarManagement());

      // 非同期処理開始
      act(() => {
        result.current.fetchAvatar();
      });

      // isLoading確認（処理中）
      expect(result.current.isLoading).toBe(true);

      // 処理完了
      await act(async () => {
        resolveGetAvatar!(mockAvatar);
        await getAvatarPromise;
      });

      // isLoading確認（完了後）
      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });
    });
  });
});
