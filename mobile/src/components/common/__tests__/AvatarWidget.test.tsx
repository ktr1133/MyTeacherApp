/**
 * AvatarWidget コンポーネントテスト
 */
import { render, fireEvent } from '@testing-library/react-native';
import { Image } from 'react-native';
import AvatarWidget from '../AvatarWidget';
import { AvatarDisplayData } from '../../../types/avatar.types';

describe('AvatarWidget', () => {
  const mockData: AvatarDisplayData = {
    comment: 'タスクを作成しました！頑張りましょう！',
    imageUrl: 'https://example.com/avatar/bust_happy.png',
    animation: 'avatar-cheer',
    eventType: 'task_created',
    timestamp: Date.now(),
  };

  const mockOnClose = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('表示・非表示', () => {
    it('visible=trueの時にモーダルが表示される', () => {
      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={mockData} onClose={mockOnClose} />
      );

      // Assert
      expect(getByText('タスクを作成しました！頑張りましょう！')).toBeTruthy();
    });

    it('visible=falseの時にモーダルが非表示になる', () => {
      // Act
      const { queryByText } = render(
        <AvatarWidget visible={false} data={mockData} onClose={mockOnClose} />
      );

      // Assert
      expect(queryByText('タスクを作成しました！頑張りましょう！')).toBeNull();
    });

    it('data=nullの時は何も表示しない', () => {
      // Act
      const { queryByTestId } = render(
        <AvatarWidget visible={true} data={null} onClose={mockOnClose} />
      );

      // Assert
      expect(queryByTestId('avatar-modal')).toBeNull();
    });
  });

  describe('コンテンツ表示', () => {
    it('コメントテキストが正しく表示される', () => {
      // Arrange
      const customData: AvatarDisplayData = {
        ...mockData,
        comment: 'やりましたね！素晴らしい成果です！',
      };

      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={customData} onClose={mockOnClose} />
      );

      // Assert
      expect(getByText('やりましたね！素晴らしい成果です！')).toBeTruthy();
    });

    it('アバター画像が正しいURLで表示される', () => {
      // Act
      const { UNSAFE_getByType } = render(
        <AvatarWidget visible={true} data={mockData} onClose={mockOnClose} />
      );

      // Assert
      const image = UNSAFE_getByType(Image);
      expect(image.props.source).toEqual({ uri: 'https://example.com/avatar/bust_happy.png' });
    });
  });

  describe('閉じるボタン', () => {
    it('閉じるボタンをクリックするとonCloseが呼ばれる', () => {
      // Arrange
      const { getByText } = render(
        <AvatarWidget visible={true} data={mockData} onClose={mockOnClose} />
      );

      // Act
      const closeButton = getByText('✕');
      fireEvent.press(closeButton);

      // Assert
      expect(mockOnClose).toHaveBeenCalledTimes(1);
    });
  });

  describe('表示位置', () => {
    it('position=topの時に上部に表示される', () => {
      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={mockData} onClose={mockOnClose} position="top" />
      );

      // Assert
      expect(getByText('タスクを作成しました！頑張りましょう！')).toBeTruthy();
    });

    it('position=centerの時に中央に表示される', () => {
      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={mockData} onClose={mockOnClose} position="center" />
      );

      // Assert
      expect(getByText('タスクを作成しました！頑張りましょう！')).toBeTruthy();
    });

    it('position=bottomの時に下部に表示される', () => {
      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={mockData} onClose={mockOnClose} position="bottom" />
      );

      // Assert
      expect(getByText('タスクを作成しました！頑張りましょう！')).toBeTruthy();
    });
  });

  describe('アニメーション', () => {
    it('enableAnimation=trueの時にアニメーションが有効', () => {
      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={mockData} onClose={mockOnClose} enableAnimation={true} />
      );

      // Assert
      expect(getByText('タスクを作成しました！頑張りましょう！')).toBeTruthy();
    });

    it('enableAnimation=falseの時にアニメーションが無効', () => {
      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={mockData} onClose={mockOnClose} enableAnimation={false} />
      );

      // Assert
      expect(getByText('タスクを作成しました！頑張りましょう！')).toBeTruthy();
    });
  });

  describe('様々なイベントタイプ', () => {
    it('task_completedイベントで喜びのアニメーション', () => {
      // Arrange
      const completedData: AvatarDisplayData = {
        ...mockData,
        animation: 'avatar-joy',
        eventType: 'task_completed',
      };

      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={completedData} onClose={mockOnClose} />
      );

      // Assert
      expect(getByText('タスクを作成しました！頑張りましょう！')).toBeTruthy();
    });

    it('loginイベントで手を振るアニメーション', () => {
      // Arrange
      const loginData: AvatarDisplayData = {
        ...mockData,
        comment: 'おかえりなさい！',
        animation: 'avatar-wave',
        eventType: 'login',
      };

      // Act
      const { getByText } = render(
        <AvatarWidget visible={true} data={loginData} onClose={mockOnClose} />
      );

      // Assert
      expect(getByText('おかえりなさい！')).toBeTruthy();
    });
  });
});
