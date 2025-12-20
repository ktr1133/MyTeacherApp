<?php

namespace App\Http\Actions\Profile\Group;

use App\Http\Requests\Profile\Group\SearchUnlinkedChildrenRequest;
use App\Responders\Profile\Group\GroupResponder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * 未紐付け子アカウント検索アクション
 * 
 * 保護者のメールアドレスで未紐付けの子アカウントを検索します。
 * Phase 5-2拡張: 招待トークン失効後のフォールバック機能。
 */
class SearchUnlinkedChildrenAction
{
    /**
     * コンストラクタ
     * 
     * @param GroupResponder $responder レスポンダー
     */
    public function __construct(
        private GroupResponder $responder
    ) {}

    /**
     * 未紐付け子アカウント検索を実行
     * 
     * @param SearchUnlinkedChildrenRequest $request HTTPリクエスト
     * @return RedirectResponse|JsonResponse リダイレクトレスポンスまたはJSONレスポンス
     */
    public function __invoke(SearchUnlinkedChildrenRequest $request): RedirectResponse|JsonResponse
    {
        $parentUser = $request->user();
        $parentEmail = $request->input('parent_email');

        // 検索実行: parent_emailが一致し、parent_user_idが未設定、is_minorがtrueの子アカウント
        // group_idもNULLで未所属の子のみ（リクエスト送信時のエラーを防ぐ）
        $children = User::where('parent_email', $parentEmail)
            ->where('is_minor', true)
            ->whereNull('parent_user_id')
            ->whereNull('group_id')
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('Searched for unlinked child accounts', [
            'parent_user_id' => $parentUser->id,
            'parent_email' => $parentEmail,
            'found_count' => $children->count(),
        ]);

        // AJAXリクエストの場合はJSONレスポンス
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $children->count() > 0 
                    ? sprintf('%d人の子アカウントが見つかりました。', $children->count())
                    : '該当する子アカウントが見つかりませんでした。',
                'data' => [
                    'children' => $children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'username' => $child->username,
                            'name' => $child->name,
                            'email' => $child->email,
                            'created_at' => $child->created_at->toIso8601String(),
                            'is_minor' => $child->is_minor,
                        ];
                    })->values(),
                ],
            ]);
        }

        // 通常リクエストの場合はグループ管理画面にリダイレクト（検索結果を含む）
        return redirect()->route('group.edit')
            ->with('children', $children);
    }
}
