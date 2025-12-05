<?php

namespace App\Http\Actions\Api\Task;

use App\Models\Task;
use App\Models\TaskImage;
use App\Services\Security\VirusScanServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * API: タスク画像アップロードアクション
 * 
 * モバイルアプリからのタスク証拠画像アップロードリクエストを処理
 * Cognito認証を前提（middleware: cognito）
 */
class UploadTaskImageApiAction
{
    protected VirusScanServiceInterface $virusScanService;

    public function __construct(VirusScanServiceInterface $virusScanService)
    {
        $this->virusScanService = $virusScanService;
    }

    /**
     * タスク画像をアップロード
     *
     * @param Request $request
     * @param Task $task ルートモデルバインディング
     * @return JsonResponse
     */
    public function __invoke(Request $request, Task $task): JsonResponse
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

            // 所有権チェック
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'このタスクに画像をアップロードする権限がありません。',
                ], 403);
            }

            // バリデーション
            $validated = $request->validate([
                'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'], // 10MB
            ]);

            // ウイルススキャン
            $scanEnabled = config('security.upload.virus_scan_enabled', true);
            if ($scanEnabled && $this->virusScanService->isAvailable()) {
                $isClean = $this->virusScanService->scan($validated['image']);
                
                if (!$isClean) {
                    $scanResult = $this->virusScanService->getScanResult();
                    
                    Log::warning('Virus detected in uploaded file (API)', [
                        'user_id' => $user->id,
                        'task_id' => $task->id,
                        'filename' => $validated['image']->getClientOriginalName(),
                        'scan_result' => $scanResult,
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'アップロードされたファイルにウイルスが検出されました。',
                    ], 422);
                }
                
                Log::info('File passed virus scan (API)', [
                    'user_id' => $user->id,
                    'task_id' => $task->id,
                    'filename' => $validated['image']->getClientOriginalName(),
                ]);
            }

            // 画像をS3/MinIOにアップロード
            $path = Storage::disk('s3')->putFile('task_approvals', $validated['image'], 'public');

            if (!$path) {
                return response()->json([
                    'success' => false,
                    'message' => '画像のアップロードに失敗しました。',
                ], 500);
            }

            // TaskImageレコード作成
            $taskImage = TaskImage::create([
                'task_id' => $task->id,
                'file_path' => $path,
            ]);

            // レスポンス
            return response()->json([
                'success' => true,
                'message' => '画像をアップロードしました。',
                'data' => [
                    'image' => [
                        'id' => $taskImage->id,
                        'task_id' => $taskImage->task_id,
                        'file_path' => $taskImage->file_path,
                        'url' => Storage::disk('s3')->url($taskImage->file_path),
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('API: タスク画像アップロードエラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'task_id' => $task->id,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'サーバーエラーが発生しました。',
            ], 500);
        }
    }
}
