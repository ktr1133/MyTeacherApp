/**
 * バケットカードコンポーネント
 * 
 * タグ別にグループ化されたタスクをカード形式で表示
 * Web版dashboard.cssのデザインを踏襲（グラデーション、影効果）
 * 
 * @see /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 */
import { View, Text, TouchableOpacity, StyleSheet, Animated } from 'react-native';
import { useRef, useMemo } from 'react';
import { Task } from '../../types/task.types';
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';

interface BucketCardProps {
  tagId: number;
  tagName: string;
  tasks: Task[];
  onPress: () => void;
  theme: 'adult' | 'child';
}

/**
 * バケットカードコンポーネント
 */
export default function BucketCard({ tagName, tasks, onPress, theme }: BucketCardProps) {
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  
  const previewTasks = tasks.slice(0, 6); // Web版と同じ6件表示
  const remainingCount = Math.max(0, tasks.length - 6);
  const scaleAnim = useRef(new Animated.Value(1)).current;

  /**
   * レスポンシブスタイル生成
   */
  const styles = useMemo(() => createStyles(width, themeType), [width, themeType]);

  /**
   * タップ時のアニメーション（Web版のtranslateY効果を再現）
   */
  const handlePressIn = () => {
    Animated.spring(scaleAnim, {
      toValue: 0.97,
      useNativeDriver: true,
    }).start();
  };

  const handlePressOut = () => {
    Animated.spring(scaleAnim, {
      toValue: 1,
      friction: 3,
      useNativeDriver: true,
    }).start();
  };

  return (
    <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
      <TouchableOpacity
        style={styles.card}
        onPress={onPress}
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        activeOpacity={1} // アニメーションで制御するため1に設定
      >
      {/* ヘッダー */}
      <View style={styles.header}>
        <View style={styles.titleContainer}>
          <Text style={styles.tagIcon}>🏷️</Text>
          <Text style={styles.tagName} numberOfLines={1}>
            {tagName}
          </Text>
        </View>
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{tasks.length}</Text>
        </View>
      </View>

      {/* タスクプレビュー */}
      <View style={styles.taskPreview}>
        {previewTasks.map((task) => (
          <View key={task.id} style={styles.previewItem}>
            <Text style={styles.checkBox}>{task.is_completed ? '✓' : '□'}</Text>
            <Text
              style={[
                styles.taskTitle,
                task.is_completed && styles.taskTitleCompleted,
              ]}
              numberOfLines={1}
            >
              {task.title}
            </Text>
          </View>
        ))}
        {remainingCount > 0 && (
          <View style={styles.remainingContainer}>
            <Text style={styles.remainingText}>+ あと{remainingCount}件</Text>
          </View>
        )}
      </View>
      </TouchableOpacity>
    </Animated.View>
  );
}

/**
 * レスポンシブスタイル生成関数
 * 
 * @param width - 画面幅
 * @param theme - テーマタイプ
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: getBorderRadius(16, width), // Web版: rounded-2xl
    padding: getSpacing(16, width), // Web版: p-4 (lg:p-6)
    marginBottom: getSpacing(16, width), // Web版: gap-4 (lg:gap-6)
    // Web版dashboard.cssの影効果を再現
    ...getShadow(6), // Web版: shadow-lg相当
    borderWidth: 1,
    borderColor: 'rgba(229, 231, 235, 0.5)', // Web版: border-gray-200/50
  },
  header: {
    flexDirection: 'row' as const,
    justifyContent: 'space-between' as const,
    alignItems: 'center' as const,
    marginBottom: getSpacing(12, width),
  },
  titleContainer: {
    flexDirection: 'row' as const,
    alignItems: 'center' as const,
    flex: 1,
    marginRight: getSpacing(8, width),
  },
  tagIcon: {
    width: getSpacing(40, width), // Web版: w-10 (lg基準)
    height: getSpacing(40, width),
    fontSize: getFontSize(24, width, theme),
    textAlign: 'center' as const,
    lineHeight: getSpacing(40, width),
    marginRight: getSpacing(12, width),
    backgroundColor: '#59B9C6', // Web版グラデーション開始色
    borderRadius: getBorderRadius(12, width), // Web版: rounded-xl
    overflow: 'hidden' as const,
  },
  tagName: {
    fontSize: getFontSize(18, width, theme), // Web版: text-lg
    fontWeight: 'bold' as const, // Web版: font-bold
    color: '#111827', // Web版: text-gray-900
    flex: 1,
  },
  badge: {
    backgroundColor: '#8B5CF6', // Web版グラデーション中間色
    borderRadius: getBorderRadius(14, width), // Web版: rounded-full
    paddingHorizontal: getSpacing(12, width), // Web版: px-3
    paddingVertical: getSpacing(6, width), // Web版: py-1.5
    minWidth: getSpacing(40, width), // Web版: min-w-[2.5rem]
    height: getSpacing(28, width), // Web版: h-7
    justifyContent: 'center' as const,
    alignItems: 'center' as const,
  },
  badgeText: {
    color: '#FFFFFF',
    fontSize: getFontSize(14, width, theme),
    fontWeight: '600' as const,
  },
  taskPreview: {
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    paddingTop: getSpacing(12, width),
  },
  previewItem: {
    flexDirection: 'row' as const,
    alignItems: 'center' as const,
    marginBottom: getSpacing(8, width),
  },
  checkBox: {
    fontSize: getFontSize(16, width, theme),
    marginRight: getSpacing(8, width),
    color: '#6B7280',
  },
  taskTitle: {
    fontSize: getFontSize(14, width, theme),
    color: '#374151',
    flex: 1,
  },
  taskTitleCompleted: {
    textDecorationLine: 'line-through' as const,
    color: '#9CA3AF',
  },
  remainingContainer: {
    marginTop: getSpacing(4, width),
  },
  remainingText: {
    fontSize: getFontSize(12, width, theme),
    color: '#6B7280',
    fontStyle: 'italic' as const,
  },
});
