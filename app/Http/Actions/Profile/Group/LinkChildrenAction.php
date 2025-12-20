<?php

namespace App\Http\Actions\Profile\Group;

use App\Http\Requests\Profile\Group\LinkChildrenRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 子アカウント一括紐づけ（Web）
 * 
 * Web版: 親が検索した子アカウントを同意なしで一括紐づけします。
 * Phase 6: 親子紐づけ機能 - 同意プロセス簡略化版。
 * 
 * AJAX対応: wantsJson()の場合はJSON応答を返します。
 */
class LinkChildrenAction
{
    /**
     * 子アカウントを一括紐づけ
     * 
     * @param LinkChildrenRequest $request HTTPリクエスト
     * @return RedirectResponse|JsonResponse リダイレクトまたはJSON応答
     */
    public function __invoke(LinkChildrenRequest $request): RedirectResponse|JsonResponse
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
                            'username' => $childUser->username ?? '不明',
                            'reason' => $limitMessage,
                        ];
                        continue;
                    }

                    $childUser = User::find($childUserId);

                    if (!$childUser) {
                        $skippedChildren[] = [
                            'username' => 'ID: ' . $childUserId,
                            'reason' => '子アカウントが見つかりませんでした。',
                        ];
                        continue;
                    }

                    // 既にグループに所属している場合はスキップ
                    if ($childUser->group_id !== null) {
                        $skippedChildren[] = [
                            'username' => $childUser->username,
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

                    $linkedChildren[] = $childUser->username;

                    Log::info('Web: Child account linked', [
                        'parent_user_id' => $parentUser->id,
                        'child_user_id' => $childUser->id,
                        'group_id' => $parentUser->group_id,
                        'current_member_count' => $currentMemberCount,
                        'max_members' => $maxMembers,
                    ]);
                }
            });

            // 成功メッセージ
            if (!empty($linkedChildren)) {
                $successMessage = sprintf('%d人の子アカウントを紐づけました。', count($linkedChildren));
                $request->session()->flash('status', 'children-linked');
                $request->session()->flash('success_message', $successMessage);
            }

            // スキップしたアカウントがある場合はwarningメッセージ
            if (!empty($skippedChildren)) {
                $warningMessage = '以下のアカウントは紐づけできませんでした：<br>';
                foreach ($skippedChildren as $skipped) {
                    $warningMessage .= sprintf('• %s: %s<br>', $skipped['username'], $skipped['reason']);
                }
                $request->session()->flash('warning_message', $warningMessage);
            }

            // 紐づけできたアカウントがない場合はエラー
            if (empty($linkedChildren)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => '紐づけできる子アカウントがありませんでした。',
                        'data' => [
                            'linked_children' => [],
                            'skipped_children' => $skippedChildren,
                            'summary' => [
                                'total_requested' => count($childUserIds),
                                'linked' => 0,
                                'skipped' => count($skippedChildren),
                            ],
                        ],
                    ], 400);
                }
                return redirect()
                    ->route('profile.group.edit')
                    ->withErrors(['link_children' => '紐づけできる子アカウントがありませんでした。']);
            }

            // AJAX/JSON応答の場合
            if ($request->wantsJson() || $request->ajax()) {
                // 全て成功した場合
                if (empty($skippedChildren)) {
                    return response()->json([
                        'success' => true,
                        'message' => sprintf('%d人の子アカウントを紐づけました。', count($linkedChildren)),
                        'data' => [
                            'linked_children' => $linkedChildren,
                            'skipped_children' => [],
                            'summary' => [
                                'total_requested' => count($childUserIds),
                                'linked' => count($linkedChildren),
                                'skipped' => 0,
                            ],
                        ],
                    ], 200);
                }

                // 一部成功・一部スキップの場合
                return response()->json([
                    'success' => true,
                    'message' => sprintf('%d人を紐づけました。%d人はスキップされました。', count($linkedChildren), count($skippedChildren)),
                    'data' => [
                        'linked_children' => $linkedChildren,
                        'skipped_children' => $skippedChildren,
                        'summary' => [
                            'total_requested' => count($childUserIds),
                            'linked' => count($linkedChildren),
                            'skipped' => count($skippedChildren),
                        ],
                    ],
                ], 206);
            }

            return redirect()->route('profile.group.edit');

        } catch (\Exception $e) {
            Log::error('Web: Failed to link children', [
                'parent_user_id' => $request->user()->id ?? null,
                'child_user_ids' => $request->input('child_user_ids'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // AJAX/JSON応答の場合
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '子アカウントの紐づけ中にエラーが発生しました。',
                    'errors' => [
                        'error' => [$e->getMessage()],
                    ],
                ], 500);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => '子アカウントの紐づけ中にエラーが発生しました。'])
                ->withInput();
        }
    }
}
