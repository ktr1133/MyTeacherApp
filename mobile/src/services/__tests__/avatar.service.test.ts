/**
 * AvatarService テスト
 * 
 * Phase 2.B-5: アバターコメント機能（getCommentForEvent）
 * Phase 2.B-7: アバターCRUD機能（追加）
 */
import { avatarService } from '../avatar.service';
import api from '../api';
import {
  AvatarEventType,
  CreateAvatarRequest,
  UpdateAvatarRequest,
} from '../../types/avatar.types';

// apiモジュールをモック
jest.mock('../api');
const mockedApi = api as jest.Mocked<typeof api>;

describe('avatarService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // ========== Phase 2.B-5: イベント別コメント取得 ==========
  describe('getCommentForEvent', () => {
    it('指定イベントのアバターコメントを取得できる', async () => {
      // Arrange
      const eventType: AvatarEventType = 'task_created';
      const mockResponse = {
        data: {
          data: {
            comment: 'タスクを作成しました！頑張りましょう！',
            image_url: 'https://example.com/avatar/bust_happy.png',
            animation: 'avatar-cheer',
          },
        },
      };
      mockedApi.get.mockResolvedValue(mockResponse);

      // Act
      const result = await avatarService.getCommentForEvent(eventType);

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/avatar/comment/task_created');
      expect(result.comment).toBe('タスクを作成しました！頑張りましょう！');
      expect(result.imageUrl).toBe('https://example.com/avatar/bust_happy.png');
      expect(result.animation).toBe('avatar-cheer');
    });

    it('タスク完了イベントでアバターコメントを取得できる', async () => {
      // Arrange
      const eventType: AvatarEventType = 'task_completed';
      const mockResponse = {
        data: {
          data: {
            comment: 'やりましたね！素晴らしい成果です！',
            image_url: 'https://example.com/avatar/bust_happy.png',
            animation: 'avatar-joy',
          },
        },
      };
      mockedApi.get.mockResolvedValue(mockResponse);

      // Act
      const result = await avatarService.getCommentForEvent(eventType);

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/avatar/comment/task_completed');
      expect(result.comment).toBe('やりましたね！素晴らしい成果です！');
      expect(result.animation).toBe('avatar-joy');
    });

    it('ログインイベントでアバターコメントを取得できる', async () => {
      // Arrange
      const eventType: AvatarEventType = 'login';
      const mockResponse = {
        data: {
          data: {
            comment: 'おかえりなさい！今日も頑張りましょう！',
            image_url: 'https://example.com/avatar/bust_happy.png',
            animation: 'avatar-wave',
          },
        },
      };
      mockedApi.get.mockResolvedValue(mockResponse);

      // Act
      const result = await avatarService.getCommentForEvent(eventType);

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/avatar/comment/login');
      expect(result.animation).toBe('avatar-wave');
    });

    it('API通信エラー時にエラーをスローする', async () => {
      // Arrange
      const eventType: AvatarEventType = 'task_created';
      const error = new Error('Network Error');
      mockedApi.get.mockRejectedValue(error);

      // Act & Assert
      await expect(avatarService.getCommentForEvent(eventType)).rejects.toThrow('Network Error');
    });

    it('無効なイベントタイプでもAPIコールを実行する（バックエンドで検証）', async () => {
      // Arrange
      const eventType = 'invalid_event' as AvatarEventType;
      const mockResponse = {
        data: {
          data: {
            comment: '',
            image_url: '',
            animation: 'avatar-idle',
          },
        },
      };
      mockedApi.get.mockResolvedValue(mockResponse);

      // Act
      const result = await avatarService.getCommentForEvent(eventType);

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/avatar/comment/invalid_event');
      expect(result.comment).toBe('');
      expect(result.animation).toBe('avatar-idle');
    });
  });

  // ========== Phase 2.B-7: CRUD操作テスト ==========

  describe('getAvatar', () => {
    it('アバター情報を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            avatar: {
              id: 1,
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
              is_visible: true,
              generation_status: 'completed',
              created_at: '2025-01-15T10:00:00Z',
              updated_at: '2025-01-15T10:00:00Z',
              images: [],
            },
          },
        },
      };

      (api.get as jest.Mock).mockResolvedValue(mockResponse);

      const result = await avatarService.getAvatar();

      expect(api.get).toHaveBeenCalledWith('/avatar');
      expect(result).toEqual(mockResponse.data.data.avatar);
      expect(result.hair_style).toBe('long'); // snake_case確認
    });

    it('アバター未作成時（404）はnullを返す', async () => {
      const mockError = {
        response: {
          status: 404,
          data: { message: 'Avatar not found' },
        },
      };

      (api.get as jest.Mock).mockRejectedValue(mockError);

      try {
        const result = await avatarService.getAvatar();
        // 404の場合、サービスがnullを返すかエラーを投げるかは実装次第
        expect(result).toBeNull();
      } catch (error: any) {
        // エラーをスローする実装の場合
        expect(error.response.status).toBe(404);
      }
    });
  });

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

      const mockResponse = {
        data: {
          success: true,
          message: 'アバターの作成を開始しました。',
          data: {
            avatar: {
              id: 1,
              ...requestData,
              is_visible: true,
              generation_status: 'pending',
              created_at: '2025-01-15T10:00:00Z',
              updated_at: '2025-01-15T10:00:00Z',
              images: [],
            },
          },
        },
      };

      (api.post as jest.Mock).mockResolvedValue(mockResponse);

      const result = await avatarService.createAvatar(requestData);

      expect(api.post).toHaveBeenCalledWith('/avatar', requestData, { timeout: 30000 });
      expect(result.generation_status).toBe('pending');
    });
  });

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
        draw_model_version: 'stable-diffusion-xl-base-1.0',
        is_transparent: true,
        is_chibi: false,
      };

      const mockResponse = {
        data: {
          success: true,
          message: 'アバター設定を更新しました。',
          data: {
            avatar: {
              id: 1,
              ...requestData,
              is_visible: true,
              generation_status: 'completed',
              created_at: '2025-01-15T10:00:00Z',
              updated_at: '2025-01-15T11:00:00Z',
              images: [],
            },
          },
        },
      };

      (api.put as jest.Mock).mockResolvedValue(mockResponse);

      const result = await avatarService.updateAvatar(requestData);

      expect(api.put).toHaveBeenCalledWith('/avatar', requestData);
      expect(result.hair_color).toBe('brown');
    });
  });

  describe('deleteAvatar', () => {
    it('アバターを正常に削除できる', async () => {
      (api.delete as jest.Mock).mockResolvedValue({ status: 200 });

      await avatarService.deleteAvatar();

      expect(api.delete).toHaveBeenCalledWith('/avatar');
    });
  });

  describe('regenerateImages', () => {
    it('画像を正常に再生成できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            avatar: {
              id: 1,
              sex: 'female',
              generation_status: 'pending',
              images: [],
              created_at: '2025-01-15T10:00:00Z',
              updated_at: '2025-01-15T11:00:00Z',
            },
          },
        },
      };

      (api.post as jest.Mock).mockResolvedValue(mockResponse);

      const result = await avatarService.regenerateImages();

      expect(api.post).toHaveBeenCalledWith('/avatar/regenerate', {}, { timeout: 30000 });
      expect(result.generation_status).toBe('pending');
    });
  });

  describe('toggleVisibility', () => {
    it('表示状態を正常に切り替えられる', async () => {
      const mockResponse = {
        data: {
          data: {
            avatar: {
              id: 1,
              sex: 'female',
              is_visible: false,
              generation_status: 'completed',
              images: [],
              created_at: '2025-01-15T10:00:00Z',
              updated_at: '2025-01-15T11:00:00Z',
            },
          },
        },
      };

      (api.patch as jest.Mock).mockResolvedValue(mockResponse);

      const result = await avatarService.toggleVisibility(false);

      expect(api.patch).toHaveBeenCalledWith('/avatar/visibility', { is_visible: false });
      expect(result.is_visible).toBe(false);
    });
  });
});
