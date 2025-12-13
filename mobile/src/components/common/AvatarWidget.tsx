/**
 * アバターウィジェットコンポーネント
 * 
 * 教師アバターの画像とコメントを表示するモーダルウィジェット
 * Web版の avatar-widget.blade.php + avatar.css アニメーションを移植
 */
import React, { useEffect, useRef } from 'react';
import {
  Modal,
  View,
  Text,
  Image,
  TouchableOpacity,
  StyleSheet,
  Animated,
  Dimensions,
  Platform,
} from 'react-native';
import { AvatarDisplayData, AvatarAnimationType } from '../../types/avatar.types';
import { useThemedColors } from '../../hooks/useThemedColors';

interface AvatarWidgetProps {
  visible: boolean;
  data: AvatarDisplayData | null;
  onClose: () => void;
  position?: 'top' | 'center' | 'bottom';
  enableAnimation?: boolean;
}

const { width: SCREEN_WIDTH } = Dimensions.get('window');

/**
 * アバターウィジェット
 */
export const AvatarWidget: React.FC<AvatarWidgetProps> = ({
  visible,
  data,
  onClose,
  position = 'center',
  enableAnimation = true,
}) => {
  const { colors } = useThemedColors();
  
  // アニメーション値
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(0.8)).current;
  const avatarAnim = useRef(new Animated.Value(0)).current;

  /**
   * 表示アニメーション
   */
  useEffect(() => {
    if (visible && enableAnimation) {
      // フェードイン + スケールアップ
      Animated.parallel([
        Animated.timing(fadeAnim, {
          toValue: 1,
          duration: 300,
          useNativeDriver: true,
        }),
        Animated.spring(scaleAnim, {
          toValue: 1,
          friction: 5,
          useNativeDriver: true,
        }),
      ]).start();

      // アバター固有のアニメーション
      if (data?.animation) {
        playAvatarAnimation(data.animation);
      }
    } else if (!visible) {
      // フェードアウト
      Animated.timing(fadeAnim, {
        toValue: 0,
        duration: 200,
        useNativeDriver: true,
      }).start();
    }
  }, [visible, enableAnimation, data?.animation]);

  /**
   * アバター固有のアニメーションを再生
   */
  const playAvatarAnimation = (animation: AvatarAnimationType) => {
    avatarAnim.setValue(0);

    switch (animation) {
      case 'avatar-joy': // 喜び - ジャンプ + 回転
        Animated.sequence([
          Animated.timing(avatarAnim, {
            toValue: -30,
            duration: 250,
            useNativeDriver: true,
          }),
          Animated.timing(avatarAnim, {
            toValue: 0,
            duration: 250,
            useNativeDriver: true,
          }),
        ]).start();
        break;

      case 'avatar-cheer': // 応援 - 上下バウンス
        Animated.sequence([
          Animated.timing(avatarAnim, {
            toValue: -15,
            duration: 400,
            useNativeDriver: true,
          }),
          Animated.timing(avatarAnim, {
            toValue: 0,
            duration: 400,
            useNativeDriver: true,
          }),
        ]).start();
        break;

      case 'avatar-wave': // 手を振る - 左右揺れ
        Animated.sequence([
          Animated.timing(avatarAnim, {
            toValue: 10,
            duration: 250,
            useNativeDriver: true,
          }),
          Animated.timing(avatarAnim, {
            toValue: -10,
            duration: 500,
            useNativeDriver: true,
          }),
          Animated.timing(avatarAnim, {
            toValue: 0,
            duration: 250,
            useNativeDriver: true,
          }),
        ]).start();
        break;

      case 'avatar-worry': // 心配 - 小刻みな揺れ
        Animated.loop(
          Animated.sequence([
            Animated.timing(avatarAnim, {
              toValue: -3,
              duration: 125,
              useNativeDriver: true,
            }),
            Animated.timing(avatarAnim, {
              toValue: 3,
              duration: 250,
              useNativeDriver: true,
            }),
            Animated.timing(avatarAnim, {
              toValue: 0,
              duration: 125,
              useNativeDriver: true,
            }),
          ]),
          { iterations: 3 }
        ).start();
        break;

      case 'avatar-idle': // 待機 - ゆっくり上下
        Animated.loop(
          Animated.sequence([
            Animated.timing(avatarAnim, {
              toValue: -5,
              duration: 1500,
              useNativeDriver: true,
            }),
            Animated.timing(avatarAnim, {
              toValue: 0,
              duration: 1500,
              useNativeDriver: true,
            }),
          ])
        ).start();
        break;

      default:
        // その他のアニメーションはデフォルト（フェードインのみ）
        break;
    }
  };

  /**
   * 表示位置を計算
   */
  const getModalPosition = () => {
    switch (position) {
      case 'top':
        return { justifyContent: 'flex-start' as const, paddingTop: 80 };
      case 'bottom':
        return { justifyContent: 'flex-end' as const, paddingBottom: 80 };
      case 'center':
      default:
        return { justifyContent: 'center' as const };
    }
  };

  if (!data) {
    return null;
  }

  // visible=falseの時はモーダルをレンダリングしない（完全に削除）
  if (!visible) {
    return null;
  }
  
  return (
    <Modal
      visible={visible}
      transparent
      animationType="none"
      onRequestClose={onClose}
      testID="avatar-modal"
    >
      <TouchableOpacity
        style={[styles.overlay, getModalPosition()]}
        activeOpacity={1}
        onPress={onClose}
      >
        <TouchableOpacity
          activeOpacity={1}
          onPress={(e) => e.stopPropagation()}
        >
          <Animated.View
            style={[
              styles.container,
              {
                opacity: fadeAnim,
                transform: [
                  { scale: scaleAnim },
                  { translateY: avatarAnim },
                ],
              },
            ]}
          >
            {/* 吹き出し */}
            <View style={styles.bubble}>
              <Text style={styles.bubbleText}>{data.comment}</Text>
              <View style={styles.bubbleArrow} />
            </View>

            {/* アバター画像 */}
            <Image
              source={{ uri: data.imageUrl }}
              style={styles.avatarImage}
              resizeMode="contain"
            />

            {/* 閉じるボタン */}
            <TouchableOpacity
              style={styles.closeButton}
              onPress={onClose}
              activeOpacity={0.8}
            >
              <Text style={styles.closeButtonText}>✕</Text>
            </TouchableOpacity>
          </Animated.View>
        </TouchableOpacity>
      </TouchableOpacity>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    alignItems: 'center',
  },
  container: {
    alignItems: 'center',
    maxWidth: SCREEN_WIDTH * 0.85,
  },
  bubble: {
    maxWidth: 300,
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.15,
    shadowRadius: 25,
    elevation: 10,
    position: 'relative',
  },
  bubbleText: {
    fontSize: 14,
    color: '#1F2937',
    lineHeight: 20,
    textAlign: 'center',
  },
  bubbleArrow: {
    position: 'absolute',
    bottom: -8,
    left: '50%',
    marginLeft: -8,
    width: 0,
    height: 0,
    borderLeftWidth: 8,
    borderRightWidth: 8,
    borderTopWidth: 8,
    borderLeftColor: 'transparent',
    borderRightColor: 'transparent',
    borderTopColor: '#FFFFFF',
  },
  avatarImage: {
    width: Platform.OS === 'ios' ? 250 : 220,
    height: Platform.OS === 'ios' ? 300 : 270,
    marginBottom: 20,
  },
  closeButton: {
    position: 'absolute',
    top: -10,
    right: -10,
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#EF4444',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#EF4444',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 5,
  },
  closeButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
});

export default AvatarWidget;
