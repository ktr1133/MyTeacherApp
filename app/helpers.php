<?php

use App\Models\User;
use App\Helpers\AuthHelper;

if (!function_exists('current_user')) {
    /**
     * 現在の認証済みユーザーを取得
     * 
     * Breeze認証とCognito認証の両方に対応
     * 
     * @return User|null 認証済みユーザー、未認証の場合はnull
     */
    function current_user(): ?User
    {
        return request()->user();
    }
}

if (!function_exists('auth_provider')) {
    /**
     * 現在のユーザーの認証プロバイダーを取得
     * 
     * @return string 'breeze', 'cognito', または 'unknown'
     */
    function auth_provider(): string
    {
        return AuthHelper::getAuthProvider(current_user());
    }
}

if (!function_exists('is_cognito_auth')) {
    /**
     * 現在の認証がCognitoかどうかを判定
     * 
     * @return bool Cognito認証の場合true
     */
    function is_cognito_auth(): bool
    {
        return auth_provider() === 'cognito';
    }
}
