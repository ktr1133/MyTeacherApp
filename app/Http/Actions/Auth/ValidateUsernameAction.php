<?php

namespace App\Http\Actions\Auth;

use App\Services\Auth\ValidationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * ゲスト用のユーザ名バリデーションチェック
 */
class ValidateUsernameAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private ValidationServiceInterface $validationService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $username = $request->input('username', '');

        if (empty($username)) {
            return response()->json([
                'valid' => false,
                'message' => 'ユーザー名を入力してください',
            ]);
        }

        $result = $this->validationService->validateUsername($username);

        return response()->json($result);
    }
}