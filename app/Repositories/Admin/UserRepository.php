<?php

namespace App\Repositories\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理者用ユーザーリポジトリ
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPaginatedUsers(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->with('group')
            ->when($search, function ($query, $search) {
                $query->where('username', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $userId): User
    {
        return User::with('group', 'tokenBalance')->findOrFail($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserStats(): array
    {
        return [
            'total_count' => User::count(),
            'admin_count' => User::where('is_admin', true)->count(),
            'master_count' => User::whereHas('masterGroup')->count(),
            'normal_count' => User::where('is_admin', false)
                ->whereDoesntHave('masterGroup')
                ->count(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function isUsernameTaken(string $username, ?int $excludeUserId = null): bool
    {
        $query = User::where('username', $username);
        
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        
        return $query->exists();
    }
}