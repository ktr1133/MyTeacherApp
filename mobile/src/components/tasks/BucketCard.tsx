/**
 * バケットカードコンポーネント
 * 
 * タグ別にグループ化されたタスクをカード形式で表示
 * Web版dashboard.cssのデザインを踏襲（グラデーション、影効果）
 * 
 * @see /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 * @see /home/ktr/mtdev/resources/views/dashboard/partials/task-bento-layout.blade.php
 * @see /home/ktr/mtdev/resources/css/dashboard.css
 */
import { View, Text, TouchableOpacity, StyleSheet, Animated } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
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
        style={styles.cardContainer}
        onPress={onPress}
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        activeOpacity={1} // アニメーションで制御するため1に設定
      >
        {/* グラデーション背景（Web版: bg-gradient-to-br from-blue-50 to-purple-50） */}
        <LinearGradient
          colors={['#EFF6FF', '#FAF5FF']} // Web版: blue-50 (#EFF6FF) → purple-50 (#FAF5FF)
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }} // 右下方向のグラデーション
          style={styles.card}
        >
          {/* ヘッダー */}
          <View style={styles.header}>
            <View style={styles.titleContainer}>
              {/* アイコン（Web版: bg-gradient-to-br from-[#59B9C6] to-purple-600） */}
              <LinearGradient
                colors={['#59B9C6', '#9333EA']} // Web版: from-[#59B9C6] to-purple-600
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.iconGradient}
              >
                <Text style={styles.tagIcon}>🏷️</Text>
              </LinearGradient>
              <Text style={styles.tagName} numberOfLines={1}>
                {tagName}
              </Text>
            </View>
            {/* バッジ（Web版: tag-badge-gradient） */}
            <LinearGradient
              colors={['#59B9C6', '#9333EA']} // Web版グラデーション
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.badge}
            >
              <Text style={styles.badgeText}>{tasks.length}</Text>
            </LinearGradient>
          </View>

          {/* タスクプレビュー */}
          <View style={styles.taskPreview}>
            {previewTasks.map((task) => (
              <View key={task.id} style={styles.previewItem}>
                <Text style={styles.previewChip} numberOfLines={1}>
                  {task.title}
                </Text>
              </View>
            ))}
            {remainingCount > 0 && (
              <View style={styles.remainingContainer}>
                <Text style={styles.remainingText}>他 {remainingCount} 件</Text>
              </View>
            )}
          </View>

          {/* 下部グラデーションバー（Web版: group-hover:opacity-100） */}
          <LinearGradient
            colors={['#59B9C6', '#9333EA']} // Web版: from-[#59B9C6] to-purple-600
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.bottomBar}
          />
        </LinearGradient>
      </TouchableOpacity>
    </Animated.View>
  );
}

/**
 * レスポンシブスタイル生成関数
 * 
 * Web版スタイル参照:
 * - Blade: /home/ktr/mtdev/resources/views/dashboard/partials/task-bento-layout.blade.php
 * - CSS: /home/ktr/mtdev/resources/css/dashboard.css (.bento-card)
 * 
 * @param width - 画面幅
 * @param theme - テーマタイプ
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  cardContainer: {
    marginBottom: getSpacing(16, width), // Web版: gap-4 (lg:gap-6)
  },
  card: {
    borderRadius: getBorderRadius(16, width), // Web版: rounded-2xl
    padding: getSpacing(16, width), // Web版: p-4 (lg:p-6)
    // Web版dashboard.cssの影効果（shadow-lg hover:shadow-2xl）
    ...getShadow(6), // Web版: shadow-lg相当
    overflow: 'hidden', // グラデーション背景のclip
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: getSpacing(12, width), // Web版: mb-3 (lg:mb-4)
  },
  titleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    marginRight: getSpacing(8, width), // Web版: gap-2 (lg:gap-3)
    overflow: 'hidden',
  },
  iconGradient: {
    width: getSpacing(40, width), // Web版: w-8 (lg:w-10)
    height: getSpacing(40, width), // Web版: h-8 (lg:h-10)
    borderRadius: getBorderRadius(12, width), // Web版: rounded-lg (lg:rounded-xl)
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: getSpacing(12, width), // Web版: gap-2 (lg:gap-3)
    // Web版: shadow-lg
    ...getShadow(6),
  },
  tagIcon: {
    fontSize: getFontSize(20, width, theme), // Web版: w-4 h-4 (lg:w-5 lg:h-5)
    color: '#FFFFFF', // Web版: text-white
  },
  tagName: {
    fontSize: getFontSize(18, width, theme), // Web版: text-base (lg:text-lg)
    fontWeight: 'bold', // Web版: font-bold
    color: '#111827', // Web版: text-gray-900
    flex: 1,
  },
  badge: {
    borderRadius: getBorderRadius(20, width), // Web版: rounded-full
    paddingHorizontal: getSpacing(12, width), // Web版: px-2 (lg:px-3)
    paddingVertical: getSpacing(4, width), // Web版: min-w-[2rem] h-6 (lg:min-w-[2.5rem] h-7)
    minWidth: getSpacing(32, width), // Web版: min-w-[2rem] (lg:min-w-[2.5rem])
    height: getSpacing(28, width), // Web版: h-6 (lg:h-7)
    justifyContent: 'center',
    alignItems: 'center',
    // Web版: shadow-md
    ...getShadow(4),
  },
  badgeText: {
    color: '#FFFFFF', // Web版: text-white (tag-badge-gradient適用)
    fontSize: getFontSize(12, width, theme), // Web版: text-xs
    fontWeight: 'bold', // Web版: font-bold
  },
  taskPreview: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: getSpacing(6, width), // Web版: gap-1.5 (lg:gap-2)
  },
  previewItem: {
    backgroundColor: 'rgba(255, 255, 255, 0.5)', // Web版: bg-white/50
    borderRadius: getBorderRadius(20, width), // Web版: rounded-full
    paddingHorizontal: getSpacing(12, width), // Web版: px-2 (lg:px-3)
    paddingVertical: getSpacing(4, width), // Web版: py-1 (lg:py-1.5)
    maxWidth: '60%', // Web版: max-w-[60%]
    // Web版: backdrop-blur-sm border border-gray-200/50
    borderWidth: 1,
    borderColor: 'rgba(229, 231, 235, 0.5)', // Web版: border-gray-200/50
  },
  previewChip: {
    fontSize: getFontSize(12, width, theme), // Web版: text-xs
    color: '#374151', // Web版: text-gray-700
  },
  remainingContainer: {
    paddingHorizontal: getSpacing(12, width), // Web版: px-2 (lg:px-3)
    paddingVertical: getSpacing(4, width), // Web版: py-1 (lg:py-1.5)
  },
  remainingText: {
    fontSize: getFontSize(12, width, theme), // Web版: text-xs
    color: '#9CA3AF', // Web版: text-gray-400
  },
  bottomBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 4, // Web版: h-1
    borderBottomLeftRadius: getBorderRadius(16, width), // Web版: rounded-b-2xl
    borderBottomRightRadius: getBorderRadius(16, width),
    // Web版: opacity-0 group-hover:opacity-100（常に表示に変更）
    opacity: 1,
  },
});
