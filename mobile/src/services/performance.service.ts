/**
 * パフォーマンス（実績）サービス
 * 
 * 実績データの取得、月次レポートの取得を担当
 * API: /api/reports/performance, /api/reports/monthly
 */

import api from './api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  PerformanceData,
  PerformanceParams,
  MonthlyReport,
  MonthlyReportParams,
  GenerateMemberSummaryRequest,
  MemberSummaryResponse,
  MemberSummaryData,
  AvailableMonth,
  RawPerformanceData,
  ChartData,
} from '../types/performance.types';
import { ApiResponse } from '../types/api.types';

const MEMBER_SUMMARY_CACHE_KEY_PREFIX = 'member_summary_';

/**
 * Laravel PerformanceServiceの生データをモバイル用ChartDataに変換
 */
const convertToChartData = (rawData: RawPerformanceData, taskType: 'normal' | 'group'): ChartData => {
  const isNormal = taskType === 'normal';
  
  const datasets = [
    {
      label: '完了',
      data: isNormal ? rawData.nDone : rawData.gDone,
      type: 'bar' as const,
      backgroundColor: isNormal ? 'rgba(89, 185, 198, 0.8)' : 'rgba(139, 92, 246, 0.8)',
    },
    {
      label: '未完了',
      data: isNormal ? rawData.nTodo : rawData.gTodo,
      type: 'bar' as const,
      backgroundColor: isNormal ? 'rgba(89, 185, 198, 0.4)' : 'rgba(139, 92, 246, 0.4)',
    },
    {
      label: '累積完了',
      data: isNormal ? rawData.nCum : rawData.gCum,
      type: 'line' as const,
      borderColor: isNormal ? 'rgba(89, 185, 198, 1)' : 'rgba(139, 92, 246, 1)',
    },
  ];

  // グループタスクの場合、報酬データセットを追加
  if (!isNormal && rawData.gReward && rawData.gRewardCum) {
    datasets.push({
      label: '報酬累積',
      data: rawData.gRewardCum,
      type: 'line' as const,
      borderColor: 'rgba(251, 191, 36, 1)', // 黄色系
    });
  }
  
  return {
    labels: rawData.labels,
    datasets,
  };
};

/**
 * 集計データを計算
 */
const calculateSummary = (rawData: RawPerformanceData, taskType: 'normal' | 'group') => {
  const isNormal = taskType === 'normal';
  const completed = isNormal ? rawData.nDone : rawData.gDone;
  const incomplete = isNormal ? rawData.nTodo : rawData.gTodo;
  
  const totalCompleted = completed.reduce((sum, val) => sum + val, 0);
  const totalIncomplete = incomplete.reduce((sum, val) => sum + val, 0);
  const totalReward = isNormal ? 0 : rawData.gReward.reduce((sum, val) => sum + val, 0);
  
  return {
    total_completed: totalCompleted,
    total_incomplete: totalIncomplete,
    total_reward: totalReward,
    average_per_day: rawData.labels.length > 0 ? totalCompleted / rawData.labels.length : 0,
  };
};

/**
 * パフォーマンスデータ取得
 * 
 * @param params リクエストパラメータ
 * @returns パフォーマンスデータ
 */
export const getPerformanceData = async (
  params: PerformanceParams
): Promise<PerformanceData> => {
  const response = await api.get<ApiResponse<any>>(
    '/reports/performance',
    { params }
  );
  
  const apiData = response.data.data;
  const tab = params.tab || 'normal';
  
  // tabに応じてnormal_dataまたはgroup_dataを取得
  const rawData: RawPerformanceData = tab === 'normal' ? apiData.normal_data : apiData.group_data;
  
  if (!rawData || !rawData.labels) {
    throw new Error('実績データが取得できませんでした');
  }
  
  // Laravel生データをモバイル用に変換
  const chartData = convertToChartData(rawData, tab);
  const summary = calculateSummary(rawData, tab);
  
  return {
    period_label: rawData.periodInfo?.displayText || '',
    task_type: tab,
    chart_data: chartData,
    summary: summary,
    can_navigate_prev: rawData.periodInfo?.canGoPrevious || false,
    can_navigate_next: rawData.periodInfo?.canGoNext || false,
    has_subscription: apiData.has_subscription || false,
    restrictions: {
      period_restricted: !apiData.has_subscription,
      navigation_restricted: !apiData.has_subscription,
      member_restricted: !apiData.has_subscription,
    },
    members: apiData.members || [],
    selected_user_id: apiData.selected_user_id || 0,
    is_group_whole: apiData.is_group_whole || false,
  };
};

/**
 * 月次レポート取得
 * 
 * @param params リクエストパラメータ
 * @returns 月次レポートデータ
 */
export const getMonthlyReport = async (
  params: MonthlyReportParams
): Promise<MonthlyReport> => {
  const { year, month } = params;
  const url = year && month
    ? `/reports/monthly/${year}/${month}`
    : '/reports/monthly';
  
  console.log('[performanceService] getMonthlyReport request:', { url, year, month });
  
  try {
    const response = await api.get<ApiResponse<any>>(url);
    console.log('[performanceService] getMonthlyReport response:', {
      status: response.status,
      hasData: !!response.data.data,
      dataKeys: response.data.data ? Object.keys(response.data.data) : [],
    });
    
    // APIレスポンスをモバイルアプリの型に変換
    const apiData = response.data.data;
    const formatted = apiData.formatted;
    
    // グループタスクサマリーから報酬とグループタスク件数を取得（オブジェクト形式）
    const groupTaskData: { [userId: string]: { reward: number, count: number } } = {};
    if (formatted.group_task_summary) {
      Object.entries(formatted.group_task_summary).forEach(([userId, summary]: [string, any]) => {
        groupTaskData[userId] = {
          reward: summary.reward || 0,
          count: summary.completed_count || 0,
        };
      });
    }
    
    // メンバー統計データを変換（member_detailsはオブジェクト形式）
    const memberStats = formatted.member_details 
      ? Object.entries(formatted.member_details).map(([userId, member]: [string, any]) => {
          const normalTasksCount = member.completed_count || 0;
          const groupTasksCount = groupTaskData[userId]?.count || 0;
          
          return {
            user_id: parseInt(userId),
            user_name: member.user_name,
            completed: normalTasksCount + groupTasksCount, // 合計タスク数
            incomplete: 0, // APIにはincompleteがないため0
            reward: groupTaskData[userId]?.reward || 0, // グループタスクの報酬を取得
            normal_tasks_completed: normalTasksCount, // 通常タスク件数
            group_tasks_completed: groupTasksCount, // グループタスク件数
          };
        })
      : [];
    
    return {
      month_label: formatted.report_month,
      year_month: apiData.year_month,
      group_name: apiData.report.group?.name || 'グループ',
      summary: {
        total_completed: (formatted.summary.normal_tasks?.count || 0) + (formatted.summary.group_tasks?.count || 0),
        total_incomplete: 0, // APIにはincompleteがない
        total_reward: formatted.summary.rewards?.total || 0,
        normal_tasks_count: formatted.summary.normal_tasks?.count || 0,
        group_tasks_count: formatted.summary.group_tasks?.count || 0,
      },
      member_stats: memberStats,
      trend_data: {
        labels: [],
        normal_tasks: [],
        group_tasks: [],
      }, // トレンドデータは別途処理が必要
      has_subscription: apiData.has_subscription || false,
      can_access: apiData.can_access || false,
    };
  } catch (error: any) {
    console.error('[performanceService] getMonthlyReport error:', {
      status: error.response?.status,
      message: error.response?.data?.message,
      errorData: error.response?.data,
    });
    throw error;
  }
};

/**
 * メンバーサマリー生成（AI）
 * 
 * キャッシュ機能付き:
 * - キャッシュキー: `member_summary_{user_id}_{year_month}`
 * - 対象月が異なる場合は異なるキャッシュキー → 自動的にキャッシュ無効化
 * - キャッシュヒット時はAPIコールせずキャッシュから返却
 * 
 * @param request リクエストパラメータ
 * @param userName ユーザー名（キャッシュ保存用）
 * @returns メンバーサマリーデータ
 */
export const generateMemberSummary = async (
  request: GenerateMemberSummaryRequest,
  userName: string
): Promise<MemberSummaryData> => {
  // キャッシュキー生成（user_id + year_month で一意）
  const cacheKey = `${MEMBER_SUMMARY_CACHE_KEY_PREFIX}${request.user_id}_${request.year_month}`;
  
  // キャッシュチェック
  try {
    const cached = await AsyncStorage.getItem(cacheKey);
    if (cached) {
      console.log('[performanceService] Member summary cache hit:', cacheKey);
      return JSON.parse(cached);
    }
  } catch (error) {
    console.warn('[performanceService] Cache read error:', error);
  }
  
  // API呼び出し
  const response = await api.post<ApiResponse<MemberSummaryResponse>>(
    '/reports/monthly/member-summary',
    request
  );
  
  const apiData = response.data.data;
  
  // API生データを画面表示用データに変換
  const summaryData: MemberSummaryData = {
    user_id: apiData.user_id,
    user_name: userName,
    year_month: apiData.year_month,
    comment: apiData.summary.comment,
    task_classification: apiData.summary.task_classification,
    reward_trend: apiData.summary.reward_trend,
    tokens_used: apiData.summary.tokens_used,
    generated_at: new Date().toISOString(),
  };
  
  // キャッシュ保存
  try {
    await AsyncStorage.setItem(cacheKey, JSON.stringify(summaryData));
    console.log('[performanceService] Member summary cached:', cacheKey);
  } catch (error) {
    console.warn('[performanceService] Cache write error:', error);
  }
  
  return summaryData;
};

/**
 * 利用可能な月リスト取得
 * 
 * バックエンドAPIから生成済みのレポート月リストを取得
 * Web版と同じく、実際に存在するレポートのみを返す
 * 
 * @returns 利用可能な月のリスト
 */
export const getAvailableMonths = async (): Promise<AvailableMonth[]> => {
  try {
    const response = await api.get<ApiResponse<AvailableMonth[]>>(
      '/reports/monthly/available-months'
    );
    return response.data.data;
  } catch (error) {
    console.error('[performanceService] getAvailableMonths error:', error);
    // エラー時はフォールバック: 過去12ヶ月を生成
    const months: AvailableMonth[] = [];
    const now = new Date();
    
    for (let i = 0; i < 12; i++) {
      const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
      const year = date.getFullYear().toString();
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const label = `${year}年${parseInt(month)}月`;
      
      months.push({ year, month, label });
    }
    
    return months;
  }
};

export default {
  getPerformanceData,
  getMonthlyReport,
  generateMemberSummary,
  getAvailableMonths,
};
