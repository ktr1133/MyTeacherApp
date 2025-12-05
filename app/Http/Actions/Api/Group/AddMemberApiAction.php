<?php

namespace App\Http\Actions\Api\Group;

use App\Services\Profile\GroupServiceInterface;
use App\Http\Requests\Api\Group\AddMemberApiRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API: グループメンバー追加アクション
 * 
 * グループに新規メンバーを追加
 * Cognito認証を前提（middleware: cognito）
 */
class AddMemberApiAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        protected GroupServiceInterface $groupService
    ) {}

    /**
     * グループにメンバーを追加
     *
     * @param AddMemberApiRequest $request
     * @return JsonResponse
     */
    public function __invoke(AddMemberApiRequest $request): JsonResponse
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

            $newMember = $this->groupService->addMember(
                $user, 
                $validated['username'], 
                $validated['email'],
                $validated['password'], 
                $validated['name'] ?? null,
                (bool)($validated['group_edit_flg'] ?? false)
            );

            return response()->json([
                'success' => true,
                'message' => 'メンバーを追加しました。',
                'data' => [
                    'member' => [
                        'id' => $newMember->id,
                        'username' => $newMember->username,
                        'name' => $newMember->name,
                        'email' => $newMember->email,
                        'group_edit_flg' => (bool) $newMember->group_edit_flg,
                    ],
                    'avatar_event' => config('const.avatar_events.group_edited'),
                ],
            ], 201);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('メンバー追加エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'メンバーの追加に失敗しました。',
            ], 500);
        }
    }
}
