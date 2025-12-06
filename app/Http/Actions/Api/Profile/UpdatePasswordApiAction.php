<?php

namespace App\Http\Actions\Api\Profile;

use App\Http\Requests\Api\Profile\UpdatePasswordRequest;
use App\Services\Profile\ProfileManagementServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * パスワード更新API（モバイル用）
 * 
 * Action-Service-Repositoryパターン
 * - Action: リクエスト受付、レスポンス返却
 * - Service: パスワード更新ロジック（Hash::make）
 * - Repository: User更新（update()）
 * 
 * Web版: PasswordController::update()
 * Route: PUT /api/v1/profile/password
 */
class UpdatePasswordApiAction
{
    /**
     * @param ProfileManagementServiceInterface $profileService
     */
    public function __construct(
        protected ProfileManagementServiceInterface $profileService
    ) {}

    /**
     * パスワード更新
     * 
     * @param UpdatePasswordRequest $request
     * @return JsonResponse
     */
    public function __invoke(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            // Service経由でパスワード更新
            $this->profileService->updatePassword(
                $user,
                $validated['password']
            );

            Log::info('Password updated successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'パスワードを更新しました',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Password update failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'パスワードの更新に失敗しました',
            ], 500);
        }
    }
}
