/**
 * 承認待ち一覧画面
 * 
 * タスク承認とトークン購入申請を統合表示（日付順ソート）
 * 親ユーザー専用画面
 * 
 * 参照:
 * - 要件定義: /home/ktr/mtdev/definitions/mobile/PendingApprovalsScreen.md
 * - レスポンシブ設計: /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 * - モバイル規約: /home/ktr/mtdev/docs/mobile/mobile-rules.md
 */

import { useEffect, useState, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  FlatList,
  StyleSheet,
  RefreshControl,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { usePendingApprovals } from '../../hooks/usePendingApprovals';
import { ApprovalItem } from '../../types/approval.types';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
import { useChildTheme } from '../../hooks/useChildTheme';
import { useThemedColors } from '../../hooks/useThemedColors';
import TaskApprovalCard from '../../components/approvals/TaskApprovalCard';
import TokenApprovalCard from '../../components/approvals/TokenApprovalCard';
import RejectReasonModal from '../../components/approvals/RejectReasonModal';

/**
 * ナビゲーションスタック型定義
 */
type RootStackParamList = {
  PendingApprovals: undefined;
  TaskDetail: { taskId: number };
};

type NavigationProp = NativeStackNavigationProp<RootStackParamList>;

/**
 * 承認待ち一覧画面コンポーネント
 */
export default function PendingApprovalsScreen() {
  const navigation = useNavigation<NavigationProp>();
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';
  const { colors, accent } = useThemedColors();

  const {
    approvals,
    isLoading,
    isLoadingMore,
    hasMore,
    error,
    fetchApprovals,
    loadMoreApprovals,
    refreshApprovals,
    approveTaskItem,
    rejectTaskItem,
    approveTokenItem,
    rejectTokenItem,
    clearError,
  } = usePendingApprovals();

  const [refreshing, setRefreshing] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectTarget, setRejectTarget] = useState<{
    id: number;
    type: 'task' | 'token';
    title: string;
  } | null>(null);

  // レスポンシブスタイル生成
  const styles = useMemo(() => createStyles(width, themeType, colors, accent), [width, themeType, colors, accent]);

  /**
   * 初回データ取得
   */
  useEffect(() => {
    fetchApprovals();
  }, [fetchApprovals]);

  /**
   * エラー表示
   */
  useEffect(() => {
    if (error) {
      Alert.alert('エラー', error, [
        { text: 'OK', onPress: clearError },
      ]);
    }
  }, [error, clearError]);

  /**
   * Pull-to-Refresh
   */
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await refreshApprovals();
    setRefreshing(false);
  }, [refreshApprovals]);

  /**
   * 無限スクロール: リスト末尾到達時の処理
   */
  const handleLoadMore = useCallback(() => {
    if (hasMore && !isLoadingMore && !isLoading) {
      loadMoreApprovals();
    }
  }, [hasMore, isLoadingMore, isLoading, loadMoreApprovals]);

  /**
   * タスク承認
   */
  const handleApproveTask = useCallback(async (taskId: number) => {
    Alert.alert(
      '承認確認',
      'このタスクを承認しますか?',
      [
        { text: 'キャンセル', style: 'cancel' },
        {
          text: '承認する',
          onPress: async () => {
            setIsProcessing(true);
            const success = await approveTaskItem(taskId);
            setIsProcessing(false);

            if (success) {
              Alert.alert('成功', 'タスクを承認しました');
            }
          },
        },
      ]
    );
  }, [approveTaskItem]);

  /**
   * タスク却下（却下理由モーダルを表示）
   */
  const handleRejectTask = useCallback((taskId: number, title: string) => {
    setRejectTarget({ id: taskId, type: 'task', title });
    setShowRejectModal(true);
  }, []);

  /**
   * トークン購入承認
   */
  const handleApproveToken = useCallback(async (purchaseRequestId: number) => {
    Alert.alert(
      '承認確認',
      'このトークン購入申請を承認しますか?',
      [
        { text: 'キャンセル', style: 'cancel' },
        {
          text: '承認する',
          onPress: async () => {
            setIsProcessing(true);
            const success = await approveTokenItem(purchaseRequestId);
            setIsProcessing(false);

            if (success) {
              Alert.alert('成功', 'トークン購入申請を承認しました');
            }
          },
        },
      ]
    );
  }, [approveTokenItem]);

  /**
   * トークン購入却下（却下理由モーダルを表示）
   */
  const handleRejectToken = useCallback((purchaseRequestId: number, packageName: string) => {
    setRejectTarget({ id: purchaseRequestId, type: 'token', title: packageName });
    setShowRejectModal(true);
  }, []);

  /**
   * 却下実行（モーダルから呼ばれる）
   */
  const handleRejectConfirm = useCallback(async (reason?: string) => {
    if (!rejectTarget) return;

    setShowRejectModal(false);
    setIsProcessing(true);

    let success = false;
    if (rejectTarget.type === 'task') {
      success = await rejectTaskItem(rejectTarget.id, reason);
    } else {
      success = await rejectTokenItem(rejectTarget.id, reason);
    }

    setIsProcessing(false);
    setRejectTarget(null);

    if (success) {
      const targetType = rejectTarget.type === 'task' ? 'タスク' : 'トークン購入申請';
      Alert.alert('成功', `${targetType}を却下しました`);
    }
  }, [rejectTarget, rejectTaskItem, rejectTokenItem]);

  /**
   * 却下キャンセル
   */
  const handleRejectCancel = useCallback(() => {
    setShowRejectModal(false);
    setRejectTarget(null);
  }, []);

  /**
   * タスク詳細画面へ遷移
   */
  const handleViewTaskDetail = useCallback((taskId: number) => {
    navigation.navigate('TaskDetail', { taskId });
  }, [navigation]);

  /**
   * リストアイテム描画
   */
  const renderApprovalItem = useCallback(
    ({ item }: { item: ApprovalItem }) => {
      if (item.type === 'task') {
        return (
          <TaskApprovalCard
            item={item}
            onApprove={handleApproveTask}
            onReject={(id) => handleRejectTask(id, item.title)}
            onViewDetail={handleViewTaskDetail}
            isProcessing={isProcessing}
          />
        );
      } else {
        return (
          <TokenApprovalCard
            item={item}
            onApprove={handleApproveToken}
            onReject={(id) => handleRejectToken(id, item.package_name)}
            isProcessing={isProcessing}
          />
        );
      }
    },
    [
      handleApproveTask,
      handleRejectTask,
      handleApproveToken,
      handleRejectToken,
      handleViewTaskDetail,
      isProcessing,
    ]
  );

  /**
   * 空状態表示
   */
  const renderEmptyComponent = useCallback(() => {
    if (isLoading) {
      return null;
    }

    return (
      <View style={styles.emptyContainer}>
        <Text style={styles.emptyIcon}>✓</Text>
        <Text style={styles.emptyTitle}>承認待ちの項目がありません</Text>
        <Text style={styles.emptySubtitle}>すべての申請を処理しました</Text>
      </View>
    );
  }, [isLoading, styles]);

  /**
   * フッター表示（無限スクロールローディング）
   */
  const renderFooter = useCallback(() => {
    if (!isLoadingMore) return null;

    return (
      <View style={styles.loadingFooter}>
        <ActivityIndicator size="small" color={accent.primary} />
      </View>
    );
  }, [isLoadingMore, styles]);

  /**
   * アイテム区切り線
   */
  const renderSeparator = useCallback(() => {
    return <View style={styles.separator} />;
  }, [styles]);

  /**
   * キー抽出
   */
  const keyExtractor = useCallback((item: ApprovalItem) => {
    return `${item.type}-${item.id}`;
  }, []);

  return (
    <View style={styles.container}>
      {/* ローディング表示 */}
      {isLoading && approvals.length === 0 && (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={accent.primary} />
          <Text style={styles.loadingText}>読み込み中...</Text>
        </View>
      )}

      {/* 承認待ち一覧 */}
      {(!isLoading || approvals.length > 0) && (
        <FlatList
          testID="approvals-list"
          data={approvals}
          renderItem={renderApprovalItem}
          keyExtractor={keyExtractor}
          ListEmptyComponent={renderEmptyComponent}
          ListFooterComponent={renderFooter}
          ItemSeparatorComponent={renderSeparator}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              colors={[accent.primary]}
              tintColor={accent.primary}
            />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.5}
          contentContainerStyle={[
            styles.listContent,
            approvals.length === 0 && styles.listContentEmpty,
          ]}
        />
      )}

      {/* 却下理由入力モーダル */}
      {rejectTarget && (
        <RejectReasonModal
          visible={showRejectModal}
          targetTitle={rejectTarget.title}
          onReject={handleRejectConfirm}
          onCancel={handleRejectCancel}
          isSubmitting={isProcessing}
        />
      )}
    </View>
  );
}

/**
 * レスポンシブスタイル生成関数
 * 
 * @param width - 画面幅
 * @param theme - テーマ種別
 * @returns StyleSheet
 */
const createStyles = (width: number, theme: 'adult' | 'child', colors: any, accent: any) => {
  const spacing = getSpacing(8, width);

  return StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    loadingContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
    },
    loadingText: {
      marginTop: spacing * 2,
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
    },
    listContent: {
      padding: spacing * 2,
    },
    listContentEmpty: {
      flexGrow: 1,
    },
    emptyContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      paddingVertical: spacing * 6,
    },
    emptyIcon: {
      fontSize: getFontSize(48, width, theme),
      color: colors.status.success,
      marginBottom: spacing * 2,
    },
    emptyTitle: {
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
      color: colors.text.primary,
      marginBottom: spacing,
    },
    emptySubtitle: {
      fontSize: getFontSize(14, width, theme),
      color: colors.text.secondary,
    },
    loadingFooter: {
      paddingVertical: spacing * 2,
      alignItems: 'center',
    },
    separator: {
      height: 0, // カード間の余白はカード自体のmarginBottomで確保
    },
  });
};
