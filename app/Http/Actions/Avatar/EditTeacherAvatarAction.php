<?php

namespace App\Http\Actions\Avatar;

use App\Models\User;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use App\Responders\Avatar\TeacherAvatarResponder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 教師アバター画像再生成アクション
 */
class EditTeacherAvatarAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $service,
        private TeacherAvatarResponder $responder
    ) {}

    /**
     * アバター編集画面を表示
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $avatar = $user->teacherAvatar;

        if (!$avatar) {
            abort(404, 'アバターが見つかりません');
        }

        // 表情画像の取得
        $expressionImages = $this->buildExpressionImages($avatar);

        return $this->responder->edit([
            'avatar' => $avatar,
            'expressionImages' => $expressionImages,
        ]);
    }

    /**
     * 表情画像データを構築
     * 
     * @param \App\Models\TeacherAvatar $avatar
     * @return array
     */
    private function buildExpressionImages($avatar): array
    {
        $expressions = [
            [
                'type' => 'full_body',
                'label' => '全身',
                'image' => $avatar->fullBodyImage,
            ],
            [
                'type' => 'normal',
                'label' => '通常',
                'image' => $avatar->bustImage,
            ],
            [
                'type' => 'happy',
                'label' => '喜び',
                'image' => $avatar->bustImageHappy,
            ],
            [
                'type' => 'sad',
                'label' => '悲しみ',
                'image' => $avatar->bustImageSad,
            ],
            [
                'type' => 'angry',
                'label' => '怒り',
                'image' => $avatar->bustImageAngry,
            ],
            [
                'type' => 'surprised',
                'label' => '驚き',
                'image' => $avatar->bustImageSurprised,
            ],
        ];

        return $expressions;
    }
}