<?php

namespace App\Http\Actions\Task;

use App\Http\Requests\Task\RequestApprovalRequest;
use App\Models\Task;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Task\TaskApprovalServiceInterface;
use App\Services\Security\VirusScanServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * タスクの完了申請を行うアクション
 */
class RequestApprovalAction
{
    protected NotificationServiceInterface $notificationService;
    protected TaskApprovalServiceInterface $taskApprovalService;
    protected VirusScanServiceInterface $virusScanService;

    public function __construct(
        NotificationServiceInterface $notificationService,
        TaskApprovalServiceInterface $taskApprovalService,
        VirusScanServiceInterface $virusScanService
    ) {
        $this->notificationService = $notificationService;
        $this->taskApprovalService = $taskApprovalService;
        $this->virusScanService = $virusScanService;
    }

    /**
     * 完了申請を実行
     *
     * @param RequestApprovalRequest $request
     * @param Task $task
     * @return RedirectResponse
     */
    public function __invoke(RequestApprovalRequest $request, Task $task): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $task) {
                // 画像をアップロード（最大3枚）
                if ($request->hasFile('images')) {
                    $this->uploadImages($request, $task);
                }
                // 承認が必要な場合
                if ($task->requires_approval) {
                    // 完了申請する
                    $task = $this->taskApprovalService->requestApproval($task, $request->user());
                // 承認が不要な場合
                } else {
                    $task = $this->taskApprovalService->completeWithoutApproval($task, $request->user());
                }
            });

            return redirect()
                ->route('dashboard')
                ->with('success', 'タスクの完了を申請しました。')
                ->with('avatar_event', config('const.avatar_events.task_completed'));

        } catch (\Exception $e) {
            Log::error('タスク完了申請処理でエラーが発生しました。', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'タスクの完了申請に失敗しました。'])
                ->withInput();
        }
    }

    /**
     * 画像をS3にアップロード
     *
     * @param RequestApprovalRequest $request
     * @param Task $task
     * @return void
     * @throws \Exception ウイルス検出時
     */
    protected function uploadImages(RequestApprovalRequest $request, Task $task): void
    {
        $images = $request->file('images');
        $scanEnabled = config('security.upload.virus_scan_enabled', true);
        
        foreach ($images as $image) {
            // ウイルススキャン（有効な場合）
            if ($scanEnabled && $this->virusScanService->isAvailable()) {
                $isClean = $this->virusScanService->scan($image);
                
                if (!$isClean) {
                    $scanResult = $this->virusScanService->getScanResult();
                    
                    Log::warning('Virus detected in uploaded file', [
                        'user_id' => $request->user()->id,
                        'task_id' => $task->id,
                        'filename' => $image->getClientOriginalName(),
                        'scan_result' => $scanResult,
                    ]);
                    
                    throw new \Exception('アップロードされたファイルにウイルスが検出されました。ファイル: ' . $image->getClientOriginalName());
                }
                
                Log::info('File passed virus scan', [
                    'user_id' => $request->user()->id,
                    'task_id' => $task->id,
                    'filename' => $image->getClientOriginalName(),
                ]);
            }
            
            // S3にアップロード
            $path = Storage::disk('s3')->putFile('task_approvals', $image, 'public');
            
            // DBに保存
            $task->images()->create([
                'task_id'   => $task->id,
                'file_path' => $path,
            ]);
        }
    }
}