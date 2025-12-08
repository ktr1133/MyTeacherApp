<?php

namespace App\Http\Responders\Api\ScheduledTask;

use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;

/**
 * スケジュールタスクAPI用レスポンダー
 * 
 * スケジュールタスク関連のAPIレスポンスを整形
 */
class ScheduledTaskApiResponder
{
    /**
     * スケジュールタスク一覧レスポンス
     * 
     * @param Collection $scheduledTasks
     * @return JsonResponse
     */
    public function index(Collection $scheduledTasks): JsonResponse
    {
        return response()->json([
            'message' => 'スケジュールタスク一覧を取得しました。',
            'data' => [
                'scheduled_tasks' => $scheduledTasks,
            ],
        ], 200);
    }

    /**
     * 作成画面情報レスポンス
     * 
     * @param array $data
     * @return JsonResponse
     */
    public function create(array $data): JsonResponse
    {
        return response()->json([
            'message' => 'スケジュールタスク作成情報を取得しました。',
            'data' => $data,
        ], 200);
    }

    /**
     * スケジュールタスク作成成功レスポンス
     * 
     * @param mixed $scheduledTask
     * @return JsonResponse
     */
    public function store($scheduledTask): JsonResponse
    {
        return response()->json([
            'message' => 'スケジュールタスクを作成しました。',
            'data' => [
                'scheduled_task' => $scheduledTask,
            ],
        ], 201);
    }

    /**
     * 編集画面情報レスポンス
     * 
     * @param array $data
     * @return JsonResponse
     */
    public function edit(array $data): JsonResponse
    {
        return response()->json([
            'message' => 'スケジュールタスク編集情報を取得しました。',
            'data' => $data,
        ], 200);
    }

    /**
     * スケジュールタスク更新成功レスポンス
     * 
     * @param mixed $scheduledTask
     * @return JsonResponse
     */
    public function update($scheduledTask): JsonResponse
    {
        return response()->json([
            'message' => 'スケジュールタスクを更新しました。',
            'data' => [
                'scheduled_task' => $scheduledTask,
            ],
        ], 200);
    }

    /**
     * スケジュールタスク削除成功レスポンス
     * 
     * @return JsonResponse
     */
    public function delete(): JsonResponse
    {
        return response()->json([
            'message' => 'スケジュールタスクを削除しました。',
        ], 200);
    }

    /**
     * スケジュールタスク一時停止成功レスポンス
     * 
     * @param mixed $scheduledTask
     * @return JsonResponse
     */
    public function pause($scheduledTask): JsonResponse
    {
        return response()->json([
            'message' => 'スケジュールタスクを一時停止しました。',
            'data' => [
                'scheduled_task' => $scheduledTask,
            ],
        ], 200);
    }

    /**
     * スケジュールタスク再開成功レスポンス
     * 
     * @param mixed $scheduledTask
     * @return JsonResponse
     */
    public function resume($scheduledTask): JsonResponse
    {
        return response()->json([
            'message' => 'スケジュールタスクを再開しました。',
            'data' => [
                'scheduled_task' => $scheduledTask,
            ],
        ], 200);
    }

    /**
     * 実行履歴レスポンス
     * 
     * @param mixed $scheduledTask
     * @param mixed $executions
     * @return JsonResponse
     */
    public function history($scheduledTask, $executions): JsonResponse
    {
        return response()->json([
            'message' => '実行履歴を取得しました。',
            'data' => [
                'scheduled_task' => [
                    'id' => $scheduledTask->id,
                    'title' => $scheduledTask->title,
                ],
                'executions' => $executions,
            ],
        ], 200);
    }

    /**
     * エラーレスポンス
     * 
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public function error(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $code);
    }
}
