<?php

namespace App\Services\Auth;

use App\Repositories\Profile\ProfileUserRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ValidationService implements ValidationServiceInterface
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private ProfileUserRepositoryInterface $userRepository
    ) {}

    /**
     * {@inheritdoc}
     */
    public function validateUsername(string $username): array
    {
        // 基本バリデーション
        $validator = Validator::make(
            ['username' => $username],
            [
                'username' => ['required', 'string', 'max:255', 'alpha_dash'],
            ],
            [
                'username.required' => 'ユーザー名は必須です',
                'username.max' => 'ユーザー名は255文字以内で入力してください',
                'username.alpha_dash' => 'ユーザー名は英数字、ダッシュ、アンダースコアのみ使用できます',
            ]
        );

        if ($validator->fails()) {
            return [
                'valid' => false,
                'message' => $validator->errors()->first('username'),
            ];
        }

        // ユニークチェック
        $existingUser = \App\Models\User::where('username', $username)->first();
        
        if ($existingUser) {
            return [
                'valid' => false,
                'message' => 'このユーザー名は既に使用されています',
            ];
        }

        return [
            'valid' => true,
            'message' => 'このユーザー名は使用可能です',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validatePassword(string $password, ?string $passwordConfirmation = null): array
    {
        $rules = [
            'password' => ['required', 'string', Password::defaults()],
        ];

        $messages = [
            'password.required' => 'パスワードは必須です',
        ];

        // パスワード確認がある場合
        if ($passwordConfirmation !== null) {
            $rules['password'][] = 'confirmed';
            $messages['password.confirmed'] = 'パスワードが一致しません';
        }

        $validator = Validator::make(
            [
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            $rules,
            $messages
        );

        if ($validator->fails()) {
            return [
                'valid' => false,
                'message' => $validator->errors()->first('password'),
            ];
        }

        // パスワード強度のカスタムチェック
        $strength = $this->checkPasswordStrength($password);
        
        if ($strength['level'] === 'weak') {
            return [
                'valid' => false,
                'message' => 'パスワードが弱すぎます。8文字以上で、英字と数字を含めてください',
            ];
        }

        return [
            'valid' => true,
            'message' => $strength['message'],
        ];
    }

    /**
     * パスワード強度チェック
     *
     * @param string $password
     * @return array{level: string, message: string}
     */
    private function checkPasswordStrength(string $password): array
    {
        $length = strlen($password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

        $score = 0;
        
        if ($length >= 8) $score++;
        if ($length >= 12) $score++;
        if ($hasLower) $score++;
        if ($hasUpper) $score++;
        if ($hasNumber) $score++;
        if ($hasSpecial) $score++;

        if ($score <= 2) {
            return [
                'level' => 'weak',
                'message' => 'パスワードが弱すぎます',
            ];
        } elseif ($score <= 4) {
            return [
                'level' => 'medium',
                'message' => 'パスワードの強度: 中',
            ];
        } else {
            return [
                'level' => 'strong',
                'message' => 'パスワードの強度: 強',
            ];
        }
    }
}