<?php

namespace App\Repositories\Avatar;

use App\Models\TeacherAvatar;
use App\Models\User;

interface TeacherAvatarRepositoryInterface
{
    /**
     * ユーザーに紐づくTeacherAvatarを取得
     *
     * @param User $user
     * @return TeacherAvatar|null
     */
    public function findByUser(User $user): ?TeacherAvatar;

    /**
     * TeacherAvatarを作成
     *
     * @param User $user
     * @param array $data
     * @return TeacherAvatar
     */
    public function create(User $user, array $data): TeacherAvatar;

    /**
     * TeacherAvatarを更新
     *
     * @param TeacherAvatar $avatar
     * @param array $data
     * @return bool
     */
    public function update(TeacherAvatar $avatar, array $data): bool;

    /**
     * TeacherAvatarを削除
     *
     * @param TeacherAvatar $avatar
     * @return bool
     */
    public function delete(TeacherAvatar $avatar): bool;

    /**
     * TeacherAvatarの表示/非表示を切り替え
     *
     * @param TeacherAvatar $avatar
     * @return bool
     */
    public function toggleVisibility(TeacherAvatar $avatar): bool;
}