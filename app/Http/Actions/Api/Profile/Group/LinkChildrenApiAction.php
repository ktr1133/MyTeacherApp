<?php

namespace App\Http\Actions\Api\Profile\Group;

use App\Http\Requests\Api\Profile\Group\LinkChildrenApiRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 子アカウント一括紐づけAPI
 * 
 * モバイルアプリ用: 親が検索した子アカウントを同意なしで一括紐づけします。
 * Phase 6: 親子紐づけ機能 - 同意プロセス簡略化版。
 */
class LinkChildrenApiAction
{
    /**
     * 子アカウントを一括紐づけ
     * 
     * @param LinkChildrenApiRequest $request HTTPリクエスト
     * @return JsonResponse JSONレスポンス
     */
    public function __invoke(LinkChildrenApiRequest $request): JsonResponse
    {
        try {
            $parentUser = $request->user();
            $childUserIds = $request->input('child_user_ids');

            $linkedChildren = [];
            $skippedChildren = [];

            // グループのメンバー数上限を取得
            $group = $parentUser->group;
            $maxMembers = $group->subscription_active ? $group->max_members : 6; // 無料プランは6名
            $currentMemberCount = User::where('group_id', $parentUser->group_id)->count();

            DB::transaction(function () use ($parentUser, $childUserIds, $group, $maxMembers, &$currentMemberCount, &$linkedChildren, &$skippedChildren) {
                foreach ($childUserIds as $childUserId) {
                    // メンバー数上限チェック（各紐づけ前に確認）
                    if ($currentMemberCount >= $maxMembers) {
                        $childUser = User::find($childUserId);
                        $limitMessage = $group->subscription_active
                            ? "グループメンバーの上限（{$maxMembers}名）に達しています。"
                            : "グループメンバーの上限（{$maxMembers}名）に達しています。エンタープライズプランにアップグレードしてください。";
                        
                        $skippedChildren[] = [
                            'user_id' => $childUser->id ?? null,
                            'username' => $childUser->username ?? '不明',
                            'name' => $childUser->name ?? null,
                            'reason' => $limitMessage,
                        ];
                        continue;
                    }

                    $childUser = User::find($childUserId);

                    if (!$childUser) {
                        $skippedChildren[] = [
                            'user_id' => $childUserId,
                            'reason' => '子アカウントが見つかりませんでした。',
                        ];
                        continue;
                    }

                    // 既にグループに所属している場合はスキップ
                    if ($childUser->group_id !== null) {
                        $skippedChildren[] = [
                            'user_id' => $childUser->id,
                            'username' => $childUser->username,
                            'name' => $childUser->name,
                            'reason' => '既に別のグループに所属しています。',
                        ];
                        continue;
                    }

                    // 紐づけ実行
                    $childUser->update([
                        'group_id' => $parentUser->group_id,
                        'group_edit_flg' => false,
                        'parent_user_id' => $parentUser->id,
                    ]);

                    // 紐づけ成功したらカウント増加
                    $currentMemberCount++;

                    $linkedChildren[] = [
                        'user_id' => $childUser->id,
                        'username' => $childUser->username,
                        'name' => $childUser->name,
                        'email' => $childUser->email,
                    ];

                    Log::info('API: Child account linked', [
                        'parent_user_id' => $parentUser->id,
                        'child_user_id' => $childUser->id,
                        'group_id' => $parentUser->group_id,
                        'current_member_count' => $currentMemberCount,
                        'max_members' => $maxMembers,
                    ]);
                }
            });

            // 成功・失敗の両方がある場合は206 Partial Content
            $statusCode = empty($skippedChildren) ? 200 : (empty($linkedChildren) ? 400 : 206);

            return response()->json([
                'success' => !empty($linkedChildren),
                'message' => empty($linkedChildren)
                    ? '紐づけできる子アカウントがありませんでした。'
                    : (empty($skippedChildren)
                        ? sprintf('%d人の子アカウントを紐づけました。', count($linkedChildren))
                        : sprintf('%d人を紐づけしました（%d人スキップ）。', count($linkedChildren), count($skippedChildren))),
                'data' => [
                    'linked_children' => $linkedChildren,
                    'skipped_children' => $skippedChildren,
                    'summary' => [
                        'total_requested' => count($childUserIds),
                        'linked' => count($linkedChildren),
                        'skipped' => count($skippedChildren),
                    ],
                ],
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error('API: Failed to link children', [
                'parent_user_id' => $request->user()->id ?? null,
                'child_user_ids' => $request->input('child_user_ids'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '子アカウントの紐づけ中にエラーが発生しました。',
                'errors' => [
                    'server' => ['サーバーエラーが発生しました。もう一度お試しください。']
                ],
            ], 500);
        }
    }
}
