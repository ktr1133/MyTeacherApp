/**
 * AvatarService テスト
 */
import { avatarService } from '../avatar.service';
import api from '../api';
import { AvatarEventType } from '../../types/avatar.types';

// apiモジュールをモック
jest.mock('../api');
const mockedApi = api as jest.Mocked<typeof api>;

describe('avatarService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getCommentForEvent', () => {
    it('指定イベントのアバターコメントを取得できる', async () => {
      // Arrange
      const eventType: AvatarEventType = 'task_created';
      const mockResponse = {
        data: {
          comment: 'タスクを作成しました！頑張りましょう！',
          imageUrl: 'https://example.com/avatar/bust_happy.png',
          animation: 'avatar-cheer',
        },
      };
      mockedApi.get.mockResolvedValue(mockResponse);

      // Act
      const result = await avatarService.getCommentForEvent(eventType);

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/avatar/comment/task_created');
      expect(result).toEqual(mockResponse.data);
    });

    it('タスク完了イベントでアバターコメントを取得できる', async () => {
      // Arrange
      const eventType: AvatarEventType = 'task_completed';
      const mockResponse = {
        data: {
          comment: 'やりましたね！素晴らしい成果です！',
          imageUrl: 'https://example.com/avatar/bust_happy.png',
          animation: 'avatar-joy',
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
          comment: 'おかえりなさい！今日も頑張りましょう！',
          imageUrl: 'https://example.com/avatar/bust_happy.png',
          animation: 'avatar-wave',
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
          comment: '',
          imageUrl: '',
          animation: 'avatar-idle',
        },
      };
      mockedApi.get.mockResolvedValue(mockResponse);

      // Act
      const result = await avatarService.getCommentForEvent(eventType);

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/avatar/comment/invalid_event');
      expect(result).toEqual(mockResponse.data);
    });
  });
});
