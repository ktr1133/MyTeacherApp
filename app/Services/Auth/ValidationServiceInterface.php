<?php

namespace App\Services\Auth;

interface ValidationServiceInterface
{
    /**
     * ユーザー名の重複チェック
     *
     * @param string $username
     * @return array{valid: bool, message: string|null}
     */
    public function validateUsername(string $username): array;

    /**
     * パスワードのバリデーション
     *
     * @param string $password
     * @param string|null $passwordConfirmation
     * @return array{valid: bool, message: string|null}
     */
    public function validatePassword(string $password, ?string $passwordConfirmation = null): array;
}