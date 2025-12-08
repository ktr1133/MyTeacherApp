/**
 * performance.service.ts テスト
 * 
 * パフォーマンスサービス（データ変換、API通信、キャッシュ）の動作を検証
 */

import MockAdapter from 'axios-mock-adapter';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../../src/services/api';
import * as performanceService from '../../src/services/performance.service';
import {
  PerformanceParams,
  MonthlyReportParams,
  GenerateMemberSummaryRequest,
} from '../../src/types/performance.types';

// Axiosモックインスタンス
const mockAxios = new MockAdapter(api);

describe('performance.service', () => {
  beforeEach(() => {
    mockAxios.reset();
    AsyncStorage.clear();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  describe('getPerformanceData()', () => {
    describe('正常系', () => {
      it('通常タスクのパフォーマンスデータを取得できる', async () => {
        const params: PerformanceParams = {
          tab: 'normal',
          period: 'week',
          offset: 0,
        };

        const mockApiResponse = {
          success: true,
          data: {
            normal_data: {
              labels: ['月', '火', '水', '木', '金', '土', '日'],
              nDone: [2, 3, 1, 4, 2, 0, 1],
              nTodo: [1, 0, 2, 0, 1, 3, 2],
              nCum: [2, 5, 6, 10, 12, 12, 13],
              periodInfo: {
                displayText: '2025年1月第1週',
                canGoPrevious: true,
                canGoNext: false,
              },
            },
            group_data: {
              labels: [],
              gDone: [],
              gTodo: [],
              gCum: [],
              gReward: [],
              gRewardCum: [],
              periodInfo: null,
            },
            has_subscription: false,
            members: [],
            selected_user_id: 0,
            is_group_whole: true,
          },
        };

        mockAxios.onGet('/reports/performance').reply(200, mockApiResponse);

        const result = await performanceService.getPerformanceData(params);

        expect(result).toBeDefined();
        expect(result.period_label).toBe('2025年1月第1週');
        expect(result.task_type).toBe('normal');
        expect(result.chart_data.labels).toEqual(['月', '火', '水', '木', '金', '土', '日']);
        expect(result.chart_data.datasets).toHaveLength(3);
        expect(result.chart_data.datasets[0].label).toBe('完了');
        expect(result.chart_data.datasets[0].data).toEqual([2, 3, 1, 4, 2, 0, 1]);
        expect(result.summary.total_completed).toBe(13);
        expect(result.summary.total_incomplete).toBe(9);
        expect(result.can_navigate_prev).toBe(true);
        expect(result.can_navigate_next).toBe(false);
        expect(result.has_subscription).toBe(false);
        expect(result.restrictions.period_restricted).toBe(true);
      });

      it('グループタスクのパフォーマンスデータを取得できる', async () => {
        const params: PerformanceParams = {
          tab: 'group',
          period: 'month',
          offset: 0,
        };

        const mockApiResponse = {
          success: true,
          data: {
            normal_data: {
              labels: [],
              nDone: [],
              nTodo: [],
              nCum: [],
              periodInfo: null,
            },
            group_data: {
              labels: ['1週', '2週', '3週', '4週'],
              gDone: [5, 8, 6, 7],
              gTodo: [2, 1, 3, 2],
              gCum: [5, 13, 19, 26],
              gReward: [500, 800, 600, 700],
              gRewardCum: [500, 1300, 1900, 2600],
              periodInfo: {
                displayText: '2025年1月',
                canGoPrevious: true,
                canGoNext: false,
              },
            },
            has_subscription: true,
            members: [
              { id: 1, name: 'テストユーザー1' },
              { id: 2, name: 'テストユーザー2' },
            ],
            selected_user_id: 1,
            is_group_whole: false,
          },
        };

        mockAxios.onGet('/reports/performance').reply(200, mockApiResponse);

        const result = await performanceService.getPerformanceData(params);

        expect(result).toBeDefined();
        expect(result.period_label).toBe('2025年1月');
        expect(result.task_type).toBe('group');
        expect(result.chart_data.labels).toEqual(['1週', '2週', '3週', '4週']);
        expect(result.chart_data.datasets).toHaveLength(4); // グループタスクは報酬累積も含む
        expect(result.chart_data.datasets[0].label).toBe('完了');
        expect(result.chart_data.datasets[0].data).toEqual([5, 8, 6, 7]);
        expect(result.chart_data.datasets[3].label).toBe('報酬累積');
        expect(result.summary.total_completed).toBe(26);
        expect(result.summary.total_reward).toBe(2600);
        expect(result.has_subscription).toBe(true);
        expect(result.restrictions.period_restricted).toBe(false);
        expect(result.members).toHaveLength(2);
        expect(result.selected_user_id).toBe(1);
      });

      it('サブスクリプション制限が正しく設定される', async () => {
        const params: PerformanceParams = {
          tab: 'normal',
          period: 'year',
          offset: 0,
        };

        const mockApiResponse = {
          success: true,
          data: {
            normal_data: {
              labels: ['1月', '2月', '3月'],
              nDone: [10, 15, 12],
              nTodo: [5, 3, 8],
              nCum: [10, 25, 37],
              periodInfo: {
                displayText: '2025年',
                canGoPrevious: false,
                canGoNext: false,
              },
            },
            group_data: {
              labels: [],
              gDone: [],
              gTodo: [],
              gCum: [],
              gReward: [],
              gRewardCum: [],
              periodInfo: null,
            },
            has_subscription: false,
            members: [],
            selected_user_id: 0,
            is_group_whole: true,
          },
        };

        mockAxios.onGet('/reports/performance').reply(200, mockApiResponse);

        const result = await performanceService.getPerformanceData(params);

        expect(result.restrictions.period_restricted).toBe(true);
        expect(result.restrictions.navigation_restricted).toBe(true);
        expect(result.restrictions.member_restricted).toBe(true);
      });
    });

    describe('異常系', () => {
      it('APIエラー時に例外をスローする', async () => {
        const params: PerformanceParams = {
          tab: 'normal',
          period: 'week',
          offset: 0,
        };

        mockAxios.onGet('/reports/performance').reply(500, {
          success: false,
          message: 'Internal Server Error',
        });

        await expect(performanceService.getPerformanceData(params)).rejects.toThrow();
      });

      it('データが不正な場合にエラーをスローする', async () => {
        const params: PerformanceParams = {
          tab: 'normal',
          period: 'week',
          offset: 0,
        };

        const mockApiResponse = {
          success: true,
          data: {
            normal_data: {
              // labelsが欠落
              nDone: [1, 2, 3],
            },
          },
        };

        mockAxios.onGet('/reports/performance').reply(200, mockApiResponse);

        await expect(performanceService.getPerformanceData(params)).rejects.toThrow(
          '実績データが取得できませんでした'
        );
      });
    });
  });

  describe('getMonthlyReport()', () => {
    describe('正常系', () => {
      it('指定月の月次レポートを取得できる', async () => {
        const params: MonthlyReportParams = {
          year: '2025',
          month: '01',
        };

        const mockApiResponse = {
          success: true,
          data: {
            year_month: '2025-01',
            report: {
              group: {
                name: 'テストグループ',
              },
            },
            formatted: {
              report_month: '2025年1月',
              summary: {
                normal_tasks: { count: 15 },
                group_tasks: { count: 10 },
                rewards: { total: 5000 },
              },
              member_details: {
                '1': {
                  user_name: 'テストユーザー1',
                  completed_count: 10,
                },
                '2': {
                  user_name: 'テストユーザー2',
                  completed_count: 5,
                },
              },
              group_task_summary: {
                '1': {
                  reward: 3000,
                  completed_count: 7,
                },
                '2': {
                  reward: 2000,
                  completed_count: 3,
                },
              },
            },
            has_subscription: true,
            can_access: true,
          },
        };

        mockAxios.onGet('/reports/monthly/2025/01').reply(200, mockApiResponse);

        const result = await performanceService.getMonthlyReport(params);

        expect(result).toBeDefined();
        expect(result.month_label).toBe('2025年1月');
        expect(result.year_month).toBe('2025-01');
        expect(result.group_name).toBe('テストグループ');
        expect(result.summary.total_completed).toBe(25); // 15 + 10
        expect(result.summary.total_reward).toBe(5000);
        expect(result.summary.normal_tasks_count).toBe(15);
        expect(result.summary.group_tasks_count).toBe(10);
        expect(result.member_stats).toHaveLength(2);
        expect(result.member_stats[0].user_name).toBe('テストユーザー1');
        expect(result.member_stats[0].completed).toBe(17); // 10 + 7
        expect(result.member_stats[0].reward).toBe(3000);
        expect(result.member_stats[0].normal_tasks_completed).toBe(10);
        expect(result.member_stats[0].group_tasks_completed).toBe(7);
        expect(result.has_subscription).toBe(true);
        expect(result.can_access).toBe(true);
      });

      it('最新月の月次レポートを取得できる（年月指定なし）', async () => {
        const params: MonthlyReportParams = {};

        const mockApiResponse = {
          success: true,
          data: {
            year_month: '2025-01',
            report: {
              group: {
                name: 'デフォルトグループ',
              },
            },
            formatted: {
              report_month: '2025年1月',
              summary: {
                normal_tasks: { count: 5 },
                group_tasks: { count: 3 },
                rewards: { total: 1500 },
              },
              member_details: {},
              group_task_summary: {},
            },
            has_subscription: false,
            can_access: true,
          },
        };

        mockAxios.onGet('/reports/monthly').reply(200, mockApiResponse);

        const result = await performanceService.getMonthlyReport(params);

        expect(result).toBeDefined();
        expect(result.month_label).toBe('2025年1月');
        expect(result.group_name).toBe('デフォルトグループ');
        expect(result.summary.total_completed).toBe(8); // 5 + 3
        expect(result.member_stats).toHaveLength(0);
        expect(result.has_subscription).toBe(false);
      });

      it('グループタスクデータが存在しない場合も正しく処理される', async () => {
        const params: MonthlyReportParams = {
          year: '2025',
          month: '01',
        };

        const mockApiResponse = {
          success: true,
          data: {
            year_month: '2025-01',
            report: {
              group: null,
            },
            formatted: {
              report_month: '2025年1月',
              summary: {
                normal_tasks: { count: 10 },
                group_tasks: null,
                rewards: null,
              },
              member_details: {
                '1': {
                  user_name: 'ソロユーザー',
                  completed_count: 10,
                },
              },
              group_task_summary: null,
            },
            has_subscription: true,
            can_access: true,
          },
        };

        mockAxios.onGet('/reports/monthly/2025/01').reply(200, mockApiResponse);

        const result = await performanceService.getMonthlyReport(params);

        expect(result).toBeDefined();
        expect(result.group_name).toBe('グループ'); // デフォルト値
        expect(result.summary.total_completed).toBe(10);
        expect(result.summary.total_reward).toBe(0);
        expect(result.member_stats[0].reward).toBe(0);
        expect(result.member_stats[0].group_tasks_completed).toBe(0);
      });
    });

    describe('異常系', () => {
      it('APIエラー時に例外をスローする', async () => {
        const params: MonthlyReportParams = {
          year: '2025',
          month: '01',
        };

        mockAxios.onGet('/reports/monthly/2025/01').reply(404, {
          success: false,
          message: 'Report not found',
        });

        await expect(performanceService.getMonthlyReport(params)).rejects.toThrow();
      });
    });
  });

  describe('generateMemberSummary()', () => {
    describe('正常系', () => {
      it('メンバーサマリーを生成できる', async () => {
        const request: GenerateMemberSummaryRequest = {
          user_id: 1,
          year_month: '2025-01',
        };

        const mockApiResponse = {
          success: true,
          data: {
            user_id: 1,
            year_month: '2025-01',
            summary: {
              comment: 'テストコメント',
              task_classification: {
                labels: ['家事', '勉強', '運動'],
                data: [10, 5, 3],
              },
              reward_trend: {
                labels: ['1週', '2週', '3週', '4週'],
                data: [500, 800, 600, 1100],
              },
              tokens_used: 1000,
            },
          },
        };

        mockAxios.onPost('/reports/monthly/member-summary').reply(200, mockApiResponse);

        const result = await performanceService.generateMemberSummary(request, 'テストユーザー');

        expect(result).toBeDefined();
        expect(result.user_id).toBe(1);
        expect(result.user_name).toBe('テストユーザー');
        expect(result.year_month).toBe('2025-01');
        expect(result.comment).toBe('テストコメント');
        expect(result.task_classification.labels).toEqual(['家事', '勉強', '運動']);
        expect(result.task_classification.data).toEqual([10, 5, 3]);
        expect(result.reward_trend.labels).toEqual(['1週', '2週', '3週', '4週']);
        expect(result.reward_trend.data).toEqual([500, 800, 600, 1100]);
        expect(result.tokens_used).toBe(1000);
        expect(result.generated_at).toBeDefined();
      });

      it('キャッシュから取得できる（2回目の呼び出し）', async () => {
        const request: GenerateMemberSummaryRequest = {
          user_id: 1,
          year_month: '2025-01',
        };

        const mockApiResponse = {
          success: true,
          data: {
            user_id: 1,
            year_month: '2025-01',
            summary: {
              comment: 'テストコメント',
              task_classification: {
                labels: ['家事'],
                data: [10],
              },
              reward_trend: {
                labels: ['1週'],
                data: [500],
              },
              tokens_used: 1000,
            },
          },
        };

        mockAxios.onPost('/reports/monthly/member-summary').reply(200, mockApiResponse);

        // 1回目: API呼び出し + キャッシュ保存
        const result1 = await performanceService.generateMemberSummary(request, 'テストユーザー');
        expect(result1).toBeDefined();

        // 2回目: キャッシュから取得（API呼び出しなし）
        const result2 = await performanceService.generateMemberSummary(request, 'テストユーザー');
        expect(result2).toEqual(result1);

        // API呼び出しは1回のみ
        expect(mockAxios.history.post.length).toBe(1);
      });

      it('月が異なるとキャッシュが無効化される', async () => {
        const request1: GenerateMemberSummaryRequest = {
          user_id: 1,
          year_month: '2025-01',
        };

        const request2: GenerateMemberSummaryRequest = {
          user_id: 1,
          year_month: '2025-02',
        };

        const mockApiResponse1 = {
          success: true,
          data: {
            user_id: 1,
            year_month: '2025-01',
            summary: {
              comment: '1月コメント',
              task_classification: { labels: [], data: [] },
              reward_trend: { labels: [], data: [] },
              tokens_used: 1000,
            },
          },
        };

        const mockApiResponse2 = {
          success: true,
          data: {
            user_id: 1,
            year_month: '2025-02',
            summary: {
              comment: '2月コメント',
              task_classification: { labels: [], data: [] },
              reward_trend: { labels: [], data: [] },
              tokens_used: 1500,
            },
          },
        };

        mockAxios
          .onPost('/reports/monthly/member-summary')
          .replyOnce(200, mockApiResponse1)
          .onPost('/reports/monthly/member-summary')
          .replyOnce(200, mockApiResponse2);

        // 1月のサマリー
        const result1 = await performanceService.generateMemberSummary(request1, 'テストユーザー');
        expect(result1.comment).toBe('1月コメント');

        // 2月のサマリー（異なるキャッシュキー）
        const result2 = await performanceService.generateMemberSummary(request2, 'テストユーザー');
        expect(result2.comment).toBe('2月コメント');

        // API呼び出しは2回
        expect(mockAxios.history.post.length).toBe(2);
      });
    });

    describe('異常系', () => {
      it('APIエラー時に例外をスローする', async () => {
        const request: GenerateMemberSummaryRequest = {
          user_id: 1,
          year_month: '2025-01',
        };

        mockAxios.onPost('/reports/monthly/member-summary').reply(402, {
          success: false,
          message: 'Insufficient tokens',
        });

        await expect(
          performanceService.generateMemberSummary(request, 'テストユーザー')
        ).rejects.toThrow();
      });

      it('キャッシュ読み取りエラーでもAPI呼び出しは成功する', async () => {
        const request: GenerateMemberSummaryRequest = {
          user_id: 1,
          year_month: '2025-01',
        };

        const mockApiResponse = {
          success: true,
          data: {
            user_id: 1,
            year_month: '2025-01',
            summary: {
              comment: 'テストコメント',
              task_classification: { labels: [], data: [] },
              reward_trend: { labels: [], data: [] },
              tokens_used: 1000,
            },
          },
        };

        mockAxios.onPost('/reports/monthly/member-summary').reply(200, mockApiResponse);

        // AsyncStorageのgetItemをモックしてエラーを発生させる
        const getItemSpy = jest.spyOn(AsyncStorage, 'getItem').mockRejectedValue(new Error('Cache error'));

        const result = await performanceService.generateMemberSummary(request, 'テストユーザー');

        expect(result).toBeDefined();
        expect(result.comment).toBe('テストコメント');

        getItemSpy.mockRestore();
      });
    });
  });

  describe('getAvailableMonths()', () => {
    describe('正常系', () => {
      it('利用可能な月リストを取得できる', async () => {
        const mockApiResponse = {
          success: true,
          data: [
            { year: '2025', month: '01', label: '2025年1月' },
            { year: '2024', month: '12', label: '2024年12月' },
            { year: '2024', month: '11', label: '2024年11月' },
          ],
        };

        mockAxios.onGet('/reports/monthly/available-months').reply(200, mockApiResponse);

        const result = await performanceService.getAvailableMonths();

        expect(result).toBeDefined();
        expect(result).toHaveLength(3);
        expect(result[0].year).toBe('2025');
        expect(result[0].month).toBe('01');
        expect(result[0].label).toBe('2025年1月');
      });
    });

    describe('異常系', () => {
      it('APIエラー時はフォールバック（過去12ヶ月）を返す', async () => {
        mockAxios.onGet('/reports/monthly/available-months').reply(500, {
          success: false,
          message: 'Internal Server Error',
        });

        const result = await performanceService.getAvailableMonths();

        expect(result).toBeDefined();
        expect(result).toHaveLength(12); // 過去12ヶ月
        expect(result[0].label).toMatch(/^\d{4}年\d{1,2}月$/);
      });
    });
  });
});
