<?php

namespace App\Repositories\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理者用ユーザーリポジトリインターフェース
 */
interface UserRepositoryInterface
{
    /**
     * ユーザー一覧を取得（ページネーション付き）
     *
     * @param string|null $search
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(?string $search = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * ユーザーをIDで取得
     *
     * @param int $userId
     * @return User
     */
    public function findById(int $userId): User;

    /**
     * ユーザー統計を取得
     *
     * @return array
     */
    public function getUserStats(): array;

    /**
     * ユーザー情報を更新
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function update(User $user, array $data): User;

    /**
     * ユーザーを削除
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool;

    /**
     * ユーザー名の重複チェック
     *
     * @param string $username
     * @param int|null $excludeUserId
     * @return bool
     */
    public function isUsernameTaken(string $username, ?int $excludeUserId = null): bool;
}