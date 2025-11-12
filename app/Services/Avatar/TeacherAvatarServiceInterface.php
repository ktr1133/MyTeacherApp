<?php

namespace App\Services\Avatar;

use App\Models\TeacherAvatar;
use App\Models\User;

/**
 * 教師アバターサービス インターフェース
 */
interface TeacherAvatarServiceInterface
{
    /**
     * ユーザーの教師アバターを取得
     *
     * @param User $user
     * @return TeacherAvatar|null
     */
    public function getUserAvatar(User $user): ?TeacherAvatar;

    /**
     * 教師アバターを作成
     *
     * @param User $user
     * @param array $data
     * @return TeacherAvatar
     */
    public function createAvatar(User $user, array $data): TeacherAvatar;

    /**
     * 教師アバターを更新
     *
     * @param TeacherAvatar $avatar
     * @param array $data
     * @return bool
     */
    public function updateAvatar(TeacherAvatar $avatar, array $data): bool;

    /**
     * 教師アバターの画像を再生成
     *
     * @param TeacherAvatar $avatar
     * @return void
     */
    public function regenerateImages(TeacherAvatar $avatar): void;

    /**
     * 教師アバターの表示/非表示を切り替え
     *
     * @param TeacherAvatar $avatar
     * @return bool
     */
    public function toggleVisibility(TeacherAvatar $avatar): bool;

    /**
     * 指定イベントタイプのコメントを取得
     *
     * @param User $user
     * @param string $eventType
     * @return array|null
     */
    public function getCommentForEvent(User $user, string $eventType): ?array;
}