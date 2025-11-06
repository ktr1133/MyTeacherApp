<?php

namespace App\Responders\Tags;

use Illuminate\Http\JsonResponse;

class TagTaskResponder
{
    /**
     * タグのタスク一覧レスポンス
     *
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
        return response()->json($data);
    }

    /**
     * タスク紐付け成功レスポンス
     *
     * @return JsonResponse
     */
    public function attached(): JsonResponse
    {
        return response()->json([
            'message' => 'タスクを紐付けました。',
        ]);
    }

    /**
     * タスク解除成功レスポンス
     *
     * @return JsonResponse
     */
    public function detached(): JsonResponse
    {
        return response()->json([
            'message' => 'タスクを解除しました。',
        ]);
    }

    /**
     * 権限エラーレスポンス
     *
     * @return JsonResponse
     */
    public function forbidden(): JsonResponse
    {
        return response()->json([
            'message' => 'このタグにアクセスする権限がありません。',
        ], 403);
    }

    /**
     * バリデーションエラーレスポンス
     *
     * @param array $errors
     * @return JsonResponse
     */
    public function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'message' => '入力内容に誤りがあります。',
            'errors'  => $errors,
        ], 422);
    }

    /**
     * サーバーエラーレスポンス
     *
     * @param string $message
     * @return JsonResponse
     */
    public function serverError(string $message = 'サーバーエラーが発生しました。'): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 500);
    }
}