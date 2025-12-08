/**
 * 月次レポート画面
 * 
 * グループメンバーの月次タスク実績を表示
 * Web版Performance.mdの要件定義に基づく
 */

import { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Picker } from '@react-native-picker/picker';
import { MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { useMonthlyReport } from '../../hooks/usePerformance';
import { BarChart } from 'react-native-chart-kit';
import { Dimensions } from 'react-native';

export default function MonthlyReportScreen() {
  const navigation = useNavigation();
  const {
    report,
    isLoading,
    error,
    availableMonths,
    selectedYear,
    selectedMonth,
    changeMonth,
    generateMemberSummary,
    refresh,
  } = useMonthlyReport();

  const [refreshing, setRefreshing] = useState(false);
  const [generatingSummary, setGeneratingSummary] = useState<number | null>(null);

  const screenWidth = Dimensions.get('window').width;

  /**
   * Pull to Refresh
   */
  const onRefresh = async () => {
    setRefreshing(true);
    await refresh();
    setRefreshing(false);
  };

  /**
   * 年月変更
   */
  const handleMonthChange = (value: string) => {
    const [year, month] = value.split('-');
    changeMonth(year, month);
  };

  /**
   * メンバーサマリー生成 → 画面遷移
   */
  const handleGenerateSummary = async (userId: number, userName: string) => {
    if (!report?.has_subscription) {
      Alert.alert(
        'プレミアム機能',
        'AI生成サマリーはサブスクリプションプランでご利用いただけます'
      );
      return;
    }

    Alert.alert(
      'AI生成サマリー',
      `${userName}さんの月次サマリーを生成しますか？\n（トークンを消費します）`,
      [
        { text: 'キャンセル', style: 'cancel' },
        {
          text: '生成',
          onPress: async () => {
            setGeneratingSummary(userId);
            try {
              // データ検証済みのサマリーデータを取得
              const summaryData = await generateMemberSummary(userId, userName);
              
              if (summaryData) {
                // 検証済みデータを持って専用画面に遷移
                navigation.navigate('MemberSummary', { data: summaryData });
              } else {
                throw new Error('サマリーデータの取得に失敗しました');
              }
            } catch (error: any) {
              console.error('[MonthlyReportScreen] サマリー生成エラー:', error);
              Alert.alert(
                'エラー',
                error.message || 'サマリーの生成に失敗しました'
              );
            } finally {
              setGeneratingSummary(null);
            }
          },
        },
      ]
    );
  };

  // トレンドグラフ設定
  const trendChartConfig = {
    backgroundGradientFrom: '#ffffff',
    backgroundGradientFromOpacity: 0,
    backgroundGradientTo: '#ffffff',
    backgroundGradientToOpacity: 0,
    color: (opacity = 1) => `rgba(89, 185, 198, ${opacity})`,
    strokeWidth: 2,
    barPercentage: 0.7,
    decimalPlaces: 0,
  };

  if (isLoading && !report) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#59B9C6" />
          <Text style={styles.loadingText}>読み込み中...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.errorContainer}>
          <MaterialIcons name="error-outline" size={48} color="#ef4444" />
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={refresh}>
            <Text style={styles.retryButtonText}>再試行</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  // アクセス制限チェック
  if (report && !report.can_access) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.lockContainer}>
          <MaterialIcons name="lock" size={64} color="#8B5CF6" />
          <Text style={styles.lockTitle}>プレミアム機能</Text>
          <Text style={styles.lockMessage}>
            過去のレポートを見るにはサブスクリプションが必要です
          </Text>
          {report.accessible_until && (
            <Text style={styles.lockNote}>
              無料プランでは{report.accessible_until}までのレポートを閲覧できます
            </Text>
          )}
          <TouchableOpacity style={styles.subscribeButton}>
            <Text style={styles.subscribeButtonText}>プランを見る</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {/* ヘッダー */}
        <View style={styles.header}>
          <Text style={styles.headerTitle}>月次レポート</Text>
          {report?.group_name && (
            <Text style={styles.groupName}>{report.group_name}</Text>
          )}
        </View>

        {/* 年月選択 */}
        <View style={styles.monthSelector}>
          <Text style={styles.monthSelectorLabel}>対象月</Text>
          <Picker
            selectedValue={`${selectedYear}-${selectedMonth}`}
            onValueChange={handleMonthChange}
            style={styles.picker}
          >
            {availableMonths.map((month) => (
              <Picker.Item
                key={`${month.year}-${month.month}`}
                label={month.label}
                value={`${month.year}-${month.month}`}
              />
            ))}
          </Picker>
        </View>

        {/* 全体サマリー */}
        {report && (
          <>
            <View style={styles.summarySection}>
              <Text style={styles.sectionTitle}>{report.month_label} 実績</Text>
              <View style={styles.summaryCards}>
                <View style={styles.summaryCard}>
                  <MaterialIcons name="check-circle" size={32} color="#10b981" />
                  <Text style={styles.summaryCardValue}>
                    {report.summary.total_completed}
                  </Text>
                  <Text style={styles.summaryCardLabel}>完了タスク</Text>
                </View>
                <View style={styles.summaryCard}>
                  <MaterialIcons name="stars" size={32} color="#8B5CF6" />
                  <Text style={styles.summaryCardValue}>
                    {report.summary.total_reward.toLocaleString()}
                  </Text>
                  <Text style={styles.summaryCardLabel}>獲得報酬</Text>
                </View>
              </View>
              <View style={styles.summaryDetailCards}>
                <View style={styles.summaryDetailCard}>
                  <Text style={styles.summaryDetailLabel}>通常タスク</Text>
                  <Text style={styles.summaryDetailValue}>
                    {report.summary.normal_tasks_count}件
                  </Text>
                </View>
                <View style={styles.summaryDetailCard}>
                  <Text style={styles.summaryDetailLabel}>グループタスク</Text>
                  <Text style={styles.summaryDetailValue}>
                    {report.summary.group_tasks_count}件
                  </Text>
                </View>
              </View>
            </View>

            {/* トレンドグラフ */}
            {report.trend_data && report.trend_data.labels.length > 0 && (
              <View style={styles.trendSection}>
                <Text style={styles.sectionTitle}>直近6ヶ月のトレンド</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                  <BarChart
                    data={{
                      labels: report.trend_data.labels,
                      datasets: [
                        {
                          data: report.trend_data.normal_tasks,
                        },
                      ],
                    }}
                    width={Math.max(screenWidth - 40, report.trend_data.labels.length * 80)}
                    height={220}
                    yAxisLabel=""
                    chartConfig={trendChartConfig}
                    style={styles.chart}
                    yAxisSuffix=""
                    fromZero
                    showValuesOnTopOfBars
                  />
                </ScrollView>
              </View>
            )}

            {/* メンバー別統計 */}
            <View style={styles.memberSection}>
              <Text style={styles.sectionTitle}>メンバー別実績</Text>
              {report.member_stats.map((member) => (
                <View key={member.user_id} style={styles.memberCard}>
                  <View style={styles.memberHeader}>
                    <Text style={styles.memberName}>{member.user_name}</Text>
                    <TouchableOpacity
                      style={styles.summaryButton}
                      onPress={() =>
                        handleGenerateSummary(member.user_id, member.user_name)
                      }
                      disabled={generatingSummary === member.user_id}
                    >
                      {generatingSummary === member.user_id ? (
                        <ActivityIndicator size="small" color="#8B5CF6" />
                      ) : (
                        <>
                          <MaterialIcons name="auto-awesome" size={16} color="#8B5CF6" />
                          <Text style={styles.summaryButtonText}>AIサマリー</Text>
                        </>
                      )}
                    </TouchableOpacity>
                  </View>
                  <View style={styles.memberStatsContainer}>
                    {/* 1行目: 完了、未完了、報酬 */}
                    <View style={styles.memberStatsRow}>
                      <View style={styles.memberStat}>
                        <Text style={styles.memberStatLabel}>完了</Text>
                        <Text style={styles.memberStatValue}>{member.completed}件</Text>
                      </View>
                      <View style={styles.memberStat}>
                        <Text style={styles.memberStatLabel}>未完了</Text>
                        <Text style={styles.memberStatValue}>{member.incomplete}件</Text>
                      </View>
                      <View style={styles.memberStat}>
                        <Text style={styles.memberStatLabel}>報酬</Text>
                        <Text style={styles.memberStatValue}>
                          {member.reward.toLocaleString()}
                        </Text>
                      </View>
                    </View>
                    {/* 2行目: タスク内訳 */}
                    <View style={styles.memberStatsRow}>
                      <View style={styles.memberStatWide}>
                        <Text style={styles.memberStatLabel}>📝 通常タスク</Text>
                        <Text style={styles.memberStatValue}>{member.normal_tasks_completed}件</Text>
                      </View>
                      <View style={styles.memberStatWide}>
                        <Text style={styles.memberStatLabel}>👥 グループタスク</Text>
                        <Text style={styles.memberStatValue}>{member.group_tasks_completed}件</Text>
                      </View>
                    </View>
                  </View>
                </View>
              ))}
            </View>

            {/* AI生成サマリー */}
            {report.ai_summary && (
              <View style={styles.aiSummarySection}>
                <View style={styles.aiSummaryHeader}>
                  <MaterialIcons name="auto-awesome" size={24} color="#8B5CF6" />
                  <Text style={styles.aiSummaryTitle}>AI生成レポート</Text>
                </View>
                <Text style={styles.aiSummaryContent}>{report.ai_summary.content}</Text>
                <Text style={styles.aiSummaryMeta}>
                  生成日時: {report.ai_summary.generated_at} | トークン: {report.ai_summary.tokens_used}
                </Text>
              </View>
            )}
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  scrollContent: {
    paddingBottom: 24,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: '#6b7280',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  errorText: {
    marginTop: 12,
    fontSize: 16,
    color: '#ef4444',
    textAlign: 'center',
  },
  retryButton: {
    marginTop: 16,
    paddingHorizontal: 24,
    paddingVertical: 12,
    backgroundColor: '#59B9C6',
    borderRadius: 8,
  },
  retryButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  lockContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  lockTitle: {
    marginTop: 16,
    fontSize: 24,
    fontWeight: '700',
    color: '#1f2937',
  },
  lockMessage: {
    marginTop: 8,
    fontSize: 16,
    color: '#6b7280',
    textAlign: 'center',
  },
  lockNote: {
    marginTop: 16,
    fontSize: 14,
    color: '#9ca3af',
    textAlign: 'center',
  },
  subscribeButton: {
    marginTop: 24,
    paddingHorizontal: 32,
    paddingVertical: 12,
    backgroundColor: '#8B5CF6',
    borderRadius: 8,
  },
  subscribeButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  header: {
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: '700',
    color: '#1f2937',
  },
  groupName: {
    marginTop: 4,
    fontSize: 14,
    color: '#6b7280',
  },
  monthSelector: {
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: '#fff',
    marginBottom: 8,
  },
  monthSelectorLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1f2937',
    marginBottom: 8,
  },
  picker: {
    backgroundColor: '#f3f4f6',
    borderRadius: 8,
  },
  summarySection: {
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: '#fff',
    marginBottom: 8,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#1f2937',
    marginBottom: 16,
  },
  summaryCards: {
    flexDirection: 'row',
    gap: 12,
  },
  summaryCard: {
    flex: 1,
    padding: 16,
    backgroundColor: '#f9fafb',
    borderRadius: 12,
    alignItems: 'center',
  },
  summaryCardValue: {
    marginTop: 8,
    fontSize: 24,
    fontWeight: '700',
    color: '#1f2937',
  },
  summaryCardLabel: {
    marginTop: 4,
    fontSize: 12,
    color: '#6b7280',
  },
  summaryDetailCards: {
    flexDirection: 'row',
    marginTop: 12,
    gap: 12,
  },
  summaryDetailCard: {
    flex: 1,
    padding: 12,
    backgroundColor: '#f9fafb',
    borderRadius: 8,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  summaryDetailLabel: {
    fontSize: 14,
    color: '#6b7280',
  },
  summaryDetailValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
  },
  trendSection: {
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: '#fff',
    marginBottom: 8,
  },
  chart: {
    marginVertical: 8,
    borderRadius: 16,
  },
  memberSection: {
    paddingHorizontal: 16,
    paddingVertical: 16,
    backgroundColor: '#fff',
    marginBottom: 8,
  },
  memberCard: {
    marginBottom: 12,
    padding: 16,
    backgroundColor: '#f9fafb',
    borderRadius: 12,
  },
  memberHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  memberName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
  },
  summaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 6,
    backgroundColor: '#f3e8ff',
    borderRadius: 8,
    gap: 4,
  },
  summaryButtonText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#8B5CF6',
  },
  memberStatsContainer: {
    gap: 8,
  },
  memberStatsRow: {
    flexDirection: 'row',
    gap: 12,
  },
  memberStat: {
    flex: 1,
    alignItems: 'center',
  },
  memberStatWide: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 4,
  },
  memberStatLabel: {
    fontSize: 12,
    color: '#6b7280',
  },
  memberStatValue: {
    marginTop: 4,
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
  },
  aiSummarySection: {
    marginHorizontal: 16,
    marginBottom: 8,
    padding: 16,
    backgroundColor: '#f3e8ff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#8B5CF6',
  },
  aiSummaryHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
    gap: 8,
  },
  aiSummaryTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#6b21a8',
  },
  aiSummaryContent: {
    fontSize: 14,
    lineHeight: 22,
    color: '#1f2937',
  },
  aiSummaryMeta: {
    marginTop: 12,
    fontSize: 12,
    color: '#7c3aed',
  },
});
