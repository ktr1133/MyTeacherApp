<?php

namespace App\Services\Admin;

use App\Models\User;

/**
 * 管理者用ユーザーサービスインターフェース
 */
interface UserServiceInterface
{
    /**
     * ユーザー一覧データを取得
     *
     * @param string|null $search
     * @return array
     */
    public function getUserListData(?string $search = null): array;

    /**
     * ユーザー編集データを取得
     *
     * @param int $userId
     * @return array
     */
    public function getUserEditData(int $userId): array;

    /**
     * ユーザー情報を更新
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateUser(User $user, array $data): User;

    /**
     * ユーザーを削除
     *
     * @param User $user
     * @param int $currentUserId
     * @return array ステータスとメッセージ
     */
    public function deleteUser(User $user, int $currentUserId): array;

    /**
     * ユーザー名の重複チェック
     *
     * @param string $username
     * @param int|null $excludeUserId
     * @return bool
     */
    public function isUsernameTaken(string $username, ?int $excludeUserId = null): bool;
}