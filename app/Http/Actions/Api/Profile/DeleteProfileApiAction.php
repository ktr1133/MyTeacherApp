<?php

namespace App\Http\Actions\Api\Profile;

use App\Services\User\UserDeletionServiceInterface;
use App\Http\Requests\Api\Profile\DeleteProfileApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

/**
 * API: アカウント削除アクション
 * 
 * ユーザーアカウントを削除（グループマスターの場合はグループ全体も削除）
 * Cognito認証を前提（middleware: cognito）
 */
class DeleteProfileApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected UserDeletionServiceInterface $userDeletionService
    ) {}

    /**
     * アカウントを削除
     *
     * @param DeleteProfileApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(DeleteProfileApiRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザー認証に失敗しました。',
                ], 401);
            }

            $validated = $request->validated();

            // パスワード確認（Breeze認証の場合のみ）
            if ($user->auth_provider !== 'cognito' && !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'パスワードが正しくありません。',
                ], 422);
            }

            $isGroupMaster = $this->userDeletionService->isGroupMaster($user);
            $message = '';

            if ($isGroupMaster && ($validated['delete_group'] ?? false)) {
                // グループマスターとしてグループ全体を削除
                $status = $this->userDeletionService->getGroupMasterStatus($user);
                Log::info('グループマスターがグループ削除を実行', [
                    'user_id' => $user->id,
                    'group_id' => $user->group->id,
                    'members_count' => $status['members_count'],
                    'has_subscription' => $status['has_subscription'],
                ]);

                $this->userDeletionService->deleteGroupMasterAndGroup($user);
                $message = 'グループを削除しました。全メンバーのアカウントも削除されました。';

                if ($status['has_subscription']) {
                    $message .= 'サブスクリプションは即時解約されました。';
                }
            } else {
                // 通常ユーザー削除
                Log::info('ユーザー削除を実行', [
                    'user_id' => $user->id,
                    'is_group_master' => $isGroupMaster,
                ]);

                $this->userDeletionService->deleteUser($user);
                $message = 'アカウントを削除しました。';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ], 200);

        } catch (\RuntimeException $e) {
            Log::error('アカウント削除失敗', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('アカウント削除中に予期しないエラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'アカウント削除に失敗しました。',
            ], 500);
        }
    }
}
