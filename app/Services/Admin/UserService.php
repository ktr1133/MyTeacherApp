<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Admin\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

/**
 * 管理者用ユーザーサービス
 */
class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getUserListData(?string $search = null): array
    {
        $users = $this->userRepository->getPaginatedUsers($search);
        $stats = $this->userRepository->getUserStats();

        return [
            'users' => $users,
            'stats' => $stats,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEditData(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        return [
            'user' => $user,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateUser(User $user, array $data): User
    {
        // パスワードのハッシュ化
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // チェックボックスの値を正規化
        $data['is_admin'] = $data['is_admin'] ?? false;
        $data['group_edit_flg'] = $data['group_edit_flg'] ?? false;

        return $this->userRepository->update($user, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUser(User $user, int $currentUserId): array
    {
        // 自分自身は削除できない
        if ($user->id === $currentUserId) {
            return [
                'success' => false,
                'message' => '自分自身を削除することはできません',
            ];
        }

        // グループマスターの場合は削除不可
        if ($user->isGroupMaster()) {
            return [
                'success' => false,
                'message' => 'グループマスターを削除する場合は、先にマスター権限を移譲してください',
            ];
        }

        $this->userRepository->delete($user);

        return [
            'success' => true,
            'message' => 'ユーザーを削除しました',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsernameTaken(string $username, ?int $excludeUserId = null): bool
    {
        return $this->userRepository->isUsernameTaken($username, $excludeUserId);
    }
}