<?php

namespace App\Http\Actions\Api\Task;

use App\Models\TaskImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク画像削除アクション
 * 
 * モバイルアプリからのタスク画像削除リクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
class DeleteTaskImageApiAction
{
    /**
     * タスク画像を削除
     *
     * @param Request $request
     * @param TaskImage $image ルートモデルバインディング
     * @return JsonResponse
     */
    public function __invoke(Request $request, TaskImage $image): JsonResponse
    {
        try {
            // 認証済みユーザーを取得（VerifyCognitoTokenで注入済み）
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            // 所有権チェック（タスクの所有者かどうか）
            if ($image->task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'この画像を削除する権限がありません。',
                ], 403);
            }

            // S3/MinIOから画像を削除
            if ($image->path && Storage::disk('s3')->exists($image->path)) {
                Storage::disk('s3')->delete($image->path);
            }

            // TaskImageレコード削除
            $image->delete();

            // レスポンス
            return response()->json([
                'success' => true,
                'message' => '画像を削除しました。',
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: タスク画像削除エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'image_id' => $image->id,
                'task_id' => $image->task_id,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'サーバーエラーが発生しました。',
            ], 500);
        }
    }
}
