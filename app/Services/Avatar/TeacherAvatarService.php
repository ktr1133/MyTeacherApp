<?php

namespace App\Services\Avatar;

use App\Models\TeacherAvatar;
use App\Models\User;
use App\Repositories\Avatar\TeacherAvatarRepositoryInterface;
use App\Jobs\GenerateAvatarImagesJob;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Support\Str;

/**
 * 教師アバターサービス
 */
class TeacherAvatarService implements TeacherAvatarServiceInterface
{
    public function __construct(
        private TeacherAvatarRepositoryInterface $repository,
        private TokenServiceInterface $tokenService
    ) {}

    /**
     * ユーザーの教師アバターを取得
     */
    public function getUserAvatar(User $user): ?TeacherAvatar
    {
        return $this->repository->findByUser($user);
    }

    /**
     * 教師アバターを作成
     */
    public function createAvatar(User $user, array $data): TeacherAvatar
    {
        // シード値を生成してデータに追加
        $data['seed'] = random_int(1, 2147483647);
        $data['is_transparent'] = isset($data['is_transparent']) ? true : false;
        
        // アバター作成
        $avatar = $this->repository->create($user, $data);

        // トークン消費
        $consumed = $this->tokenService->consumeTokens(
            $user,
            config('const.estimate_token'),
            'アバター作成',
            $avatar
        );
        
        if (!$consumed) {
            logger()->error('Failed to consume tokens for avatar creation', [
                'user_id' => $user->id,
                'avatar_id' => $avatar->id,
            ]);
            $avatar->delete();
            throw new \RuntimeException('トークンが不足しています。');
        }

        // 画像生成ジョブをディスパッチ
        GenerateAvatarImagesJob::dispatch($avatar->id);

        return $avatar;
    }

    /**
     * 教師アバターを更新
     */
    public function updateAvatar(TeacherAvatar $avatar, array $data): bool
    {
        $data['is_transparent'] = isset($data['is_transparent']) ? true : false;

        return $this->repository->update($avatar, $data);
    }

    /**
     * 教師アバターの画像を再生成
     */
    public function regenerateImages(TeacherAvatar $avatar): void
    {
        // トークン消費
        $consumed = $this->tokenService->consumeTokens(
            $avatar->user,
            config('const.estimate_token'),
            'アバター画像再生成',
            $avatar
        );

        if (!$consumed) {
            throw new \RuntimeException('トークンが不足しています。画像再生成には' . number_format(config('const.estimate_token')) . 'トークンが必要です。');
        }

        // 既存画像削除
        foreach ($avatar->images as $image) {
            \Storage::disk('s3')->delete($image->s3_path);
            $image->delete();
        }

        // 既存コメント削除
        $avatar->comments()->delete();

        // ステータス更新
        $avatar->update(['generation_status' => 'pending']);

        // 再生成ジョブをディスパッチ
        GenerateAvatarImagesJob::dispatch($avatar->id);
    }

    /**
     * 教師アバターの表示/非表示を切り替え
     */
    public function toggleVisibility(TeacherAvatar $avatar): bool
    {
        if (!$avatar) {
            return false;
        }

        return $this->repository->toggleVisibility($avatar);
    }

    /**
     * 指定イベントタイプのコメントを取得
     */
    public function getCommentForEvent(User $user, string $eventType): ?array
    {
        $avatar = $this->getUserAvatar($user);

        if (!$avatar || !$avatar->isCompleted() || !$avatar->is_visible) {
            return null;
        }

        $comment = $this->repository->getCommentForEvent($avatar, $eventType);

        if (!$comment) {
            return null;
        }

        // イベントタイプに応じた画像・表情・アニメーションを決定
        $imageType = $this->determineImageType($eventType);
        $expressionType = $this->determineExpressionType($eventType);
        $animation = $this->determineAnimation($eventType);
        // 該当する画像を取得
        $image = $avatar->images()
            ->where('image_type', $imageType)
            ->where('expression_type', $expressionType)
            ->first();

        return [
            'comment' => $comment,
            'imageUrl' => $image?->s3_url,
            'animation' => $animation,
        ];
    }

    /**
     * イベントタイプに応じた画像タイプを決定
     */
    private function determineImageType(string $eventType): string
    {
        // 実績閲覧のみ全身、それ以外はバストアップ
        return in_array($eventType, [
            config('const.avatar_events.performance_personal_viewed'),
            config('const.avatar_events.performance_group_viewed')
        ]) ? 'full_body' : 'bust';
    }

    /**
     * イベントタイプに応じた表情を決定
     */
    private function determineExpressionType(string $eventType): string
    {
        $avatarEventsExpressionMap = config('const.avatar_event_expression_types');

        return $avatarEventsExpressionMap[$eventType] ?? 'normal';
    }

    /**
     * イベントタイプに応じたアニメーションを決定
     */
    private function determineAnimation(string $eventType): string
    {
        return match($eventType) {
            config('const.avatar_events.task_completed') => 'avatar-joy',
            config('const.avatar_events.task_updated') => 'avatar-cheer',
            config('const.avatar_events.task_created') => 'avatar-cheer',
            config('const.avatar_events.task_breakdown'), 
            config('const.avatar_events.group_task_created'), 
            config('const.avatar_events.group_edited') => 'avatar-secretary',
            config('const.avatar_events.task_breakdown_refine') => 'avatar-question',
            config('const.avatar_events.login') => 'avatar-wave',
            config('const.avatar_events.logout') => 'avatar-goodbye',
            config('const.avatar_events.login_gap') => 'avatar-worry',
            config('const.avatar_events.token_purchased') => 'avatar-thanks',
            config('const.avatar_events.performance_viewed') => 'avatar-applause',
            config('const.avatar_events.tag_created') => 'avatar-nod',
            config('const.avatar_events.tag_deleted') => 'avatar-shake',
            config('const.avatar_events.group_created') => 'avatar-bless',
            config('const.avatar_events.group_deleted') => 'avatar-confirm',
            default => 'avatar-idle',
        };
    }
}