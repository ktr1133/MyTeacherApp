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
import { getMostUrgentDeadline } from '../../utils/taskDeadline';
import DeadlineBadge from './DeadlineBadge';
import { useThemedColors } from '../../hooks/useThemedColors';

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
  const { colors, isDark } = useThemedColors();
  
  const previewTasks = tasks.slice(0, 6); // Web版と同じ6件表示
  const remainingCount = Math.max(0, tasks.length - 6);
  const scaleAnim = useRef(new Animated.Value(1)).current;

  // 最も緊急度の高い期限を取得
  const mostUrgentDeadline = useMemo(() => getMostUrgentDeadline(tasks, isChildTheme), [tasks, isChildTheme]);

  /**
   * レスポンシブスタイル生成
   */
  const styles = useMemo(() => createStyles(width, themeType, colors, isDark), [width, themeType, colors, isDark]);

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
        {/* Web版のbento-cardに統一（白背景+シャドウ） */}
        <View style={styles.card}>
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
            <View style={styles.badgeContainer}>
              {/* 期限バッジ */}
              {mostUrgentDeadline && (
                <DeadlineBadge 
                  deadlineInfo={mostUrgentDeadline} 
                  variant="inline" 
                />
              )}
              {/* タスク数バッジ（Web版: tag-badge-gradient） */}
              <LinearGradient
                colors={['#59B9C6', '#9333EA']} // Web版グラデーション
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={styles.badge}
              >
                <Text style={styles.badgeText}>{tasks.length}</Text>
              </LinearGradient>
            </View>
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
        </View>
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
const createStyles = (width: number, theme: 'adult' | 'child', colors: ReturnType<typeof useThemedColors>['colors'], isDark: boolean) => StyleSheet.create({
  cardContainer: {
    marginBottom: getSpacing(16, width), // Web版: gap-4 (lg:gap-6)
  },
  card: {
    // Web版child-theme.cssの.bento-cardに統一（グラデーション背景+太い赤ボーダー）
    // ダークモード: 明るめのグレー (#374151)、ライトモード: 白
    backgroundColor: isDark ? '#374151' : '#FFFFFF',
    borderRadius: getBorderRadius(24, width), // Web版: 1.5rem (24px)
    padding: getSpacing(24, width), // Web版: var(--child-spacing-card) = 1.5rem
    // Web版child-theme.css: 太い赤ボーダー + 大きいシャドウ
    borderWidth: theme === 'child' ? 3 : 0,
    borderColor: theme === 'child' ? '#FF6B6B' : 'transparent',
    ...getShadow(8), // Web版: 0 8px 16px (大きめ)
    overflow: 'hidden', // 下部バーのclip
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
  badgeContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: getSpacing(8, width),
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
    // ダークモード: 白色で視認性確保、ライトモード: プライマリカラー
    color: colors.text.primary,
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
    // ダークモード: 明るめのグレー、ライトモード: 白の半透明
    backgroundColor: isDark ? 'rgba(75, 85, 99, 0.5)' : 'rgba(255, 255, 255, 0.5)',
    borderRadius: getBorderRadius(20, width), // Web版: rounded-full
    paddingHorizontal: getSpacing(12, width), // Web版: px-2 (lg:px-3)
    paddingVertical: getSpacing(4, width), // Web版: py-1 (lg:py-1.5)
    maxWidth: '60%', // Web版: max-w-[60%]
    // Web版: backdrop-blur-sm border border-gray-200/50
    borderWidth: 1,
    borderColor: colors.border.light,
  },
  previewChip: {
    fontSize: getFontSize(12, width, theme), // Web版: text-xs
    color: colors.text.secondary, // ダークモード対応
  },
  remainingContainer: {
    paddingHorizontal: getSpacing(12, width), // Web版: px-2 (lg:px-3)
    paddingVertical: getSpacing(4, width), // Web版: py-1 (lg:py-1.5)
  },
  remainingText: {
    fontSize: getFontSize(12, width, theme), // Web版: text-xs
    color: colors.text.tertiary, // ダークモード対応
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
