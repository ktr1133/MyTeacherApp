/**
 * パフォーマンス（実績）カスタムフック
 * 
 * 実績データの取得・管理、月次レポートの取得を担当
 */

import { useState, useEffect, useCallback } from 'react';
import {
  PerformanceData,
  PeriodType,
  TaskType,
  MonthlyReport,
  MemberSummaryData,
} from '../types/performance.types';
import * as performanceService from '../services/performance.service';
import * as pdfService from '../services/pdf.service';
import { useAuth } from '../contexts/AuthContext';

/**
 * パフォーマンスデータ管理フック
 */
export const usePerformance = () => {
  const { user } = useAuth();
  const [data, setData] = useState<PerformanceData | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // 現在の選択状態
  const [period, setPeriod] = useState<PeriodType>('week');
  const [taskType, setTaskType] = useState<TaskType>('normal');
  const [offset, setOffset] = useState(0);
  const [selectedUserId, setSelectedUserId] = useState<number>(0);

  /**
   * パフォーマンスデータ取得
   */
  const fetchPerformance = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const result = await performanceService.getPerformanceData({
        tab: taskType,
        period,
        offset,
        user_id: selectedUserId,
      });
      setData(result);
    } catch (err: any) {
      console.error('[usePerformance] データ取得エラー:', err);
      setError(err.response?.data?.message || 'データの取得に失敗しました');
    } finally {
      setIsLoading(false);
    }
  }, [period, taskType, offset, selectedUserId]);

  /**
   * 期間変更
   */
  const changePeriod = useCallback((newPeriod: PeriodType) => {
    setPeriod(newPeriod);
    setOffset(0); // 期間変更時はオフセットをリセット
  }, []);

  /**
   * タスク種別変更
   */
  const changeTaskType = useCallback((newTaskType: TaskType) => {
    setTaskType(newTaskType);
    setOffset(0); // タスク種別変更時はオフセットをリセット
  }, []);

  /**
   * 期間ナビゲーション（前へ）
   */
  const navigatePrev = useCallback(() => {
    if (data?.can_navigate_prev) {
      setOffset((prev) => prev - 1);
    }
  }, [data]);

  /**
   * 期間ナビゲーション（次へ）
   */
  const navigateNext = useCallback(() => {
    if (data?.can_navigate_next) {
      setOffset((prev) => prev + 1);
    }
  }, [data]);

  /**
   * メンバー選択変更
   */
  const changeSelectedUser = useCallback((userId: number) => {
    setSelectedUserId(userId);
  }, []);

  /**
   * データリフレッシュ
   */
  const refresh = useCallback(() => {
    fetchPerformance();
  }, [fetchPerformance]);

  // 初回読み込み & パラメータ変更時に自動取得
  useEffect(() => {
    if (user) {
      fetchPerformance();
    }
  }, [user, fetchPerformance]);

  return {
    data,
    isLoading,
    error,
    period,
    taskType,
    offset,
    selectedUserId,
    changePeriod,
    changeTaskType,
    navigatePrev,
    navigateNext,
    changeSelectedUser,
    refresh,
  };
};

/**
 * 月次レポート管理フック
 */
export const useMonthlyReport = () => {
  const { user } = useAuth();
  const [report, setReport] = useState<MonthlyReport | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [availableMonths, setAvailableMonths] = useState<Array<{year: string, month: string, label: string}>>([]);

  // 選択中の年月
  const [selectedYear, setSelectedYear] = useState<string>('');
  const [selectedMonth, setSelectedMonth] = useState<string>('');

  /**
   * 月次レポート取得
   */
  const fetchMonthlyReport = useCallback(async (year?: string, month?: string) => {
    setIsLoading(true);
    setError(null);

    try {
      const result = await performanceService.getMonthlyReport({
        year: year || selectedYear,
        month: month || selectedMonth,
      });
      setReport(result);
      
      // 年月が指定された場合は選択状態を更新
      if (year) setSelectedYear(year);
      if (month) setSelectedMonth(month);
    } catch (err: any) {
      console.error('[useMonthlyReport] レポート取得エラー:', err);
      
      // レポート未生成エラーの場合は、reportをnullにしてエラーメッセージを表示
      if (err.notGenerated) {
        setReport(null);
        setError(`${err.yearMonth || '選択された月'}のレポートは生成されていません。`);
      } else {
        setError(err.response?.data?.message || 'レポートの取得に失敗しました');
      }
    } finally {
      setIsLoading(false);
    }
  }, [selectedYear, selectedMonth]);

  /**
   * メンバーサマリー生成
   * 
   * データ検証 + キャッシュ機能付きで画面表示用データを返却
   */
  const generateMemberSummary = useCallback(
    async (userId: number, userName: string): Promise<MemberSummaryData | null> => {
      if (!selectedYear || !selectedMonth) {
        console.error('[useMonthlyReport] 年月が選択されていません');
        throw new Error('年月が選択されていません');
      }
      
      if (!user?.group_id) {
        console.error('[useMonthlyReport] グループIDが取得できません');
        throw new Error('グループIDが取得できません');
      }

      try {
        // YYYY-MM形式に変換
        const yearMonth = `${selectedYear}-${selectedMonth}`;
        
        // Service層でキャッシュチェック + API呼び出し + データ変換
        const result = await performanceService.generateMemberSummary(
          {
            user_id: userId,
            group_id: user.group_id,
            year_month: yearMonth,
          },
          userName
        );
        
        // データ検証
        if (!result.comment || !result.task_classification || !result.reward_trend) {
          console.error('[useMonthlyReport] 不正なレスポンス構造:', result);
          throw new Error('サマリーデータの形式が不正です');
        }
        
        return result;
      } catch (err: any) {
        console.error('[useMonthlyReport] メンバーサマリー生成エラー:', err);
        throw new Error(err.response?.data?.message || 'サマリーの生成に失敗しました');
      }
    },
    [selectedYear, selectedMonth, user]
  );

  /**
   * 利用可能な月リスト取得
   */
  const fetchAvailableMonths = useCallback(async () => {
    try {
      const months = await performanceService.getAvailableMonths();
      setAvailableMonths(months);
      
      // 初回読み込み時は前月（先月）のレポートを選択
      // サブスク未加入でも初月だけは表示できる仕様
      if (months.length > 0 && !selectedYear && !selectedMonth) {
        const now = new Date();
        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const targetYear = lastMonth.getFullYear().toString();
        const targetMonth = (lastMonth.getMonth() + 1).toString().padStart(2, '0');
        
        // 前月のデータがリストに含まれているか確認
        const lastMonthData = months.find(m => m.year === targetYear && m.month === targetMonth);
        
        if (lastMonthData) {
          // 前月のデータが見つかった場合はそれを選択
          setSelectedYear(lastMonthData.year);
          setSelectedMonth(lastMonthData.month);
        } else {
          // 見つからない場合はリストの最初の月を選択（フォールバック）
          setSelectedYear(months[0].year);
          setSelectedMonth(months[0].month);
        }
      }
    } catch (err: any) {
      console.error('[useMonthlyReport] 利用可能月取得エラー:', err);
    }
  }, [selectedYear, selectedMonth]);

  /**
   * 年月変更
   */
  const changeMonth = useCallback((year: string, month: string) => {
    setSelectedYear(year);
    setSelectedMonth(month);
    fetchMonthlyReport(year, month);
  }, [fetchMonthlyReport]);

  /**
   * データリフレッシュ
   */
  const refresh = useCallback(() => {
    fetchMonthlyReport();
  }, [fetchMonthlyReport]);

  /**
   * メンバーサマリーPDFをダウンロード・共有
   * 
   * @param userId メンバーID
   * @param yearMonth 対象年月（YYYY-MM形式）
   * @param comment AIコメント（オプショナル）
   * @returns ダウンロード結果
   * @throws {Error} グループ情報なし、API通信エラー等
   */
  const downloadMemberSummaryPdf = useCallback(
    async (userId: number, yearMonth: string, comment?: string) => {
      if (!user?.group_id) {
        throw new Error('グループ情報が取得できません');
      }

      try {
        const result = await pdfService.downloadAndShareMemberSummaryPdf({
          user_id: userId,
          group_id: user.group_id,
          year_month: yearMonth,
          comment,
        });
        
        return result;
      } catch (err: any) {
        console.error('[useMonthlyReport] PDF download error:', err);
        throw err;
      }
    },
    [user]
  );

  // 初回読み込み
  useEffect(() => {
    if (user) {
      fetchAvailableMonths();
    }
  }, [user, fetchAvailableMonths]);

  // 年月選択後にレポート取得
  useEffect(() => {
    if (user && selectedYear && selectedMonth) {
      fetchMonthlyReport();
    }
  }, [user, selectedYear, selectedMonth, fetchMonthlyReport]);

  return {
    report,
    isLoading,
    error,
    availableMonths,
    selectedYear,
    selectedMonth,
    changeMonth,
    generateMemberSummary,
    downloadMemberSummaryPdf,
    refresh,
  };
};
