<?php
// filepath: /home/ktr/mtdev/laravel/app/Repositories/Avatar/TeacherAvatarRepository.php

namespace App\Repositories\Avatar;

use App\Models\TeacherAvatar;
use App\Models\User;

class TeacherAvatarRepository implements TeacherAvatarRepositoryInterface
{
    /**
     * ユーザーに紐づくTeacherAvatarを取得
     *
     * @param User $user
     * @return TeacherAvatar|null
     */
    public function findByUser(User $user): ?TeacherAvatar
    {
        return TeacherAvatar::where('user_id', $user->id)
            ->with(['images', 'comments'])
            ->first();
    }

    /**
     * TeacherAvatarを作成
     *
     * @param User $user
     * @param array $data
     * @return TeacherAvatar
     */
    public function create(User $user, array $data): TeacherAvatar
    {
        return TeacherAvatar::create([
            'user_id' => $user->id,
            'seed' => $data['seed'],
            'sex' => $data['sex'],
            'hair_color' => $data['hair_color'],
            'eye_color' => $data['eye_color'],
            'clothing' => $data['clothing'],
            'accessory' => $data['accessory'] ?? null,
            'body_type' => $data['body_type'],
            'tone' => $data['tone'],
            'enthusiasm' => $data['enthusiasm'],
            'formality' => $data['formality'],
            'humor' => $data['humor'],
            'generation_status' => 'pending',
        ]);
    }

    /**
     * TeacherAvatarを更新
     *
     * @param TeacherAvatar $avatar
     * @param array $data
     * @return bool
     */
    public function update(TeacherAvatar $avatar, array $data): bool
    {
        return $avatar->update($data);
    }

    /**
     * TeacherAvatarを削除
     *
     * @param TeacherAvatar $avatar
     * @return bool
     */
    public function delete(TeacherAvatar $avatar): bool
    {
        return $avatar->delete();
    }

    /**
     * TeacherAvatarの表示/非表示を切り替え
     *
     * @param TeacherAvatar $avatar
     * @return bool
     */
    public function toggleVisibility(TeacherAvatar $avatar): bool
    {
        return $avatar->update(['is_visible' => !$avatar->is_visible]);
    }

    /**
     * 指定イベントタイプのコメントを取得
     *
     * @param TeacherAvatar $avatar
     * @param string $eventType
     * @return string|null
     */
    public function getCommentForEvent(TeacherAvatar $avatar, string $eventType): ?string
    {
        return $avatar->getCommentForEvent($eventType);
    }
}