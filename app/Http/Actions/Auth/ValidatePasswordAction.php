<?php

namespace App\Http\Actions\Auth;

use App\Services\Auth\ValidationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * ゲスト用のパスワードバリデーションチェック
 */
class ValidatePasswordAction
{
    public function __construct(
        private ValidationServiceInterface $validationService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $password = $request->input('password', '');
        $passwordConfirmation = $request->input('password_confirmation');

        if (empty($password)) {
            return response()->json([
                'valid' => false,
                'message' => 'パスワードを入力してください',
            ]);
        }

        $result = $this->validationService->validatePassword($password, $passwordConfirmation);

        return response()->json($result);
    }
}