<?php

namespace App\Services\Profile;

use App\Repositories\Profile\ProfileUserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class ProfileManagementService implements ProfileManagementServiceInterface
{
    protected ProfileUserRepositoryInterface $userRepository;

    public function __construct(ProfileUserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function updateProfile(User $user, array $data): bool
    {
        // ここにビジネスロジック（例：変更ログの記録など）を記述可能
        return $this->userRepository->update($user, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAccount(User $user): bool
    {
        return $this->userRepository->delete($user);
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupMembers(int $groupId): Collection
    {
        return $this->userRepository->getGroupMembersByGroupId($groupId);
    }

    /**
     * {@inheritdoc}
     */
    public function findUserById(int $userId): ?User
    {
        return $this->userRepository->findById($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function createUser(array $data): User
    {
        return $this->userRepository->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function updatePassword(User $user, string $newPassword): bool
    {
        // パスワードハッシュ化（Serviceの責務: データ整形）
        $hashedPassword = Hash::make($newPassword);
        
        // Repository経由でDB更新
        return $this->userRepository->update($user, [
            'password' => $hashedPassword,
        ]);
    }
}