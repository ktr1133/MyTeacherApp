<?php

namespace App\Http\Actions\Validation;

use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * グループ名バリデーションアクション
 */
class ValidateGroupNameAction
{
    /**
     * グループ名のバリデーション
     */
    public function __invoke(Request $request): JsonResponse
    {
        $groupName = $request->input('group_name');
        $currentGroupId = $request->input('current_group_id');

        // 空文字チェック
        if (empty($groupName)) {
            return response()->json([
                'valid' => false,
                'message' => 'グループ名を入力してください',
            ]);
        }

        // 最大文字数チェック
        if (mb_strlen($groupName) > 255) {
            return response()->json([
                'valid' => false,
                'message' => 'グループ名は255文字以内で入力してください',
            ]);
        }

        // 重複チェック（更新時は自身のIDを除外）
        $query = Group::where('name', $groupName);

        if ($currentGroupId) {
            $query->where('id', '!=', $currentGroupId);
        }

        $exists = $query->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => '× このグループ名は既に使用されています',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => '✓ 利用可能なグループ名です',
        ]);
    }
}