<?php

namespace App\Http\Actions\Api\Profile\Group;

use App\Http\Requests\Api\Profile\Group\SearchUnlinkedChildrenApiRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * 未紐付け子アカウント検索API
 * 
 * モバイルアプリ用: 保護者のメールアドレスで未紐付けの子アカウントを検索します。
 * Phase 6: 親子紐付け機能 - Mobile API実装。
 */
class SearchUnlinkedChildrenApiAction
{
    /**
     * 未紐付け子アカウント検索を実行
     * 
     * @param SearchUnlinkedChildrenApiRequest $request HTTPリクエスト
     * @return JsonResponse JSONレスポンス
     */
    public function __invoke(SearchUnlinkedChildrenApiRequest $request): JsonResponse
    {
        try {
            $parentUser = $request->user();
            $parentEmail = $request->input('parent_email');

            // 検索実行: parent_emailが一致し、parent_user_idが未設定、is_minorがtrueの子アカウント
            $children = User::where('parent_email', $parentEmail)
                ->where('is_minor', true)
                ->whereNull('parent_user_id')
                ->whereNull('group_id')
                ->orderBy('created_at', 'desc')
                ->get();
            
            Log::info('API: Searched for unlinked child accounts', [
                'parent_user_id' => $parentUser->id,
                'parent_email' => $parentEmail,
                'found_count' => $children->count(),
                'child_user_ids' => $children->pluck('id')->toArray(),
            ]);

            // レスポンス整形: モバイルアプリ向けに必要な情報のみ返却
            $childrenData = $children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'username' => $child->username,
                    'name' => $child->name,
                    'email' => $child->email,
                    'created_at' => $child->created_at->format('Y-m-d H:i:s'),
                    'is_minor' => $child->is_minor,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => count($childrenData) > 0 
                    ? '未紐付けの子アカウントが見つかりました。' 
                    : '該当する子アカウントが見つかりませんでした。',
                'data' => [
                    'children' => $childrenData,
                    'count' => count($childrenData),
                    'parent_email' => $parentEmail,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('API: Failed to search unlinked child accounts', [
                'parent_user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '子アカウントの検索中にエラーが発生しました。',
                'errors' => [
                    'server' => ['サーバーエラーが発生しました。もう一度お試しください。']
                ],
            ], 500);
        }
    }
}
