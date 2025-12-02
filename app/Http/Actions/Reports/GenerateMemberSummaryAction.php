<?php

namespace App\Http\Actions\Reports;

use App\Services\Report\MonthlyReportServiceInterface;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * メンバー別概況レポート生成アクション
 */
class GenerateMemberSummaryAction
{
    /**
     * コンストラクタ
     * 
     * @param MonthlyReportServiceInterface $reportService
     * @param TokenServiceInterface $tokenService
     */
    public function __construct(
        protected MonthlyReportServiceInterface $reportService,
        protected TokenServiceInterface $tokenService
    ) {}
    
    /**
     * メンバー別概況レポートを生成
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'year_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            ]);
            
            $userId = (int) $validated['user_id'];
            $yearMonth = $validated['year_month'];
            
            // 認証ユーザー取得
            $currentUser = $request->user();
            $group = $currentUser->group;
            
            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'グループが見つかりません。',
                ], 404);
            }
            
            // 権限チェック: 自分自身または同じグループのメンバー
            $targetUser = \App\Models\User::find($userId);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザーが見つかりません。',
                ], 404);
            }
            
            if ($targetUser->group_id !== $group->id) {
                return response()->json([
                    'success' => false,
                    'message' => '他のグループのメンバーの概況は生成できません。',
                ], 403);
            }
            
            // トークン消費量を取得（タスク件数に応じて動的に計算）
            $tokenCost = $this->calculateTokenCost($userId, $group->id, $yearMonth);
            
            // トークン残高チェック
            if (!$this->tokenService->checkBalance($currentUser, $tokenCost)) {
                return response()->json([
                    'success' => false,
                    'message' => 'トークン残高が不足しています。',
                    'token_cost' => $tokenCost,
                ], 402); // 402 Payment Required
            }
            
            // レポート生成（Serviceに委譲）
            $result = $this->reportService->generateMemberSummary($userId, $group->id, $yearMonth);
            
            // トークン消費
            $this->tokenService->consumeTokens(
                $currentUser,
                $tokenCost,
                "メンバー別概況生成: {$targetUser->name} ({$yearMonth})",
                $targetUser
            );
            
            // 成功レスポンス
            return response()->json([
                'success' => true,
                'data' => [
                    'comment' => $result['comment'],
                    'task_classification' => $result['task_classification'],
                    'reward_trend' => $result['reward_trend'],
                    'tokens_used' => $result['tokens_used'],
                    'user_name' => $targetUser->name,
                    'year_month' => $yearMonth,
                ],
                'message' => 'メンバー別概況レポートを生成しました。',
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\RuntimeException $e) {
            Log::error('Member summary generation failed', [
                'user_id' => $request->input('user_id'),
                'year_month' => $request->input('year_month'),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'レポート生成に失敗しました: ' . $e->getMessage(),
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Unexpected error in member summary generation', [
                'user_id' => $request->input('user_id'),
                'year_month' => $request->input('year_month'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '予期しないエラーが発生しました。',
            ], 500);
        }
    }
    
    /**
     * タスク件数に応じたトークン消費量を計算
     * 
     * @param int $userId ユーザーID
     * @param int $groupId グループID
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @return int トークン消費量
     */
    protected function calculateTokenCost(int $userId, int $groupId, string $yearMonth): int
    {
        // 対象月のタスク件数を取得
        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $taskCount = \Illuminate\Support\Facades\DB::table('tasks')
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();
        
        // 帯域別のトークン消費量設定を取得
        $costs = config('const.token.consumption.member_summary_generation');
        
        // タスク件数に応じて適切な値を返す
        if ($taskCount === 0) {
            return $costs['zero_tasks'];
        } elseif ($taskCount <= 50) {
            return $costs['low'];
        } elseif ($taskCount <= 200) {
            return $costs['medium'];
        } else {
            return $costs['high'];
        }
    }
}
