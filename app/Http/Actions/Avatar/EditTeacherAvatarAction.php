<?php

namespace App\Http\Actions\Avatar;

use App\Models\User;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use App\Responders\Avatar\TeacherAvatarResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();
        $avatar = $user->teacherAvatar;

        if (!$avatar) {
            return $this->responder->create();
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
        // ちびキャラの場合は全身画像を使用
        if ($avatar->is_chibi) {
            $expressions = [
                [
                    'type' => 'full_body',
                    'expression' => 'normal',
                    'label' => '通常',
                    'image' => $avatar->images()
                        ->where('image_type', 'full_body')
                        ->where('expression_type', 'normal')
                        ->first(),
                ],
                [
                    'type' => 'full_body',
                    'expression' => 'happy',
                    'label' => '喜び',
                    'image' => $avatar->images()
                        ->where('image_type', 'full_body')
                        ->where('expression_type', 'happy')
                        ->first(),
                ],
                [
                    'type' => 'full_body',
                    'expression' => 'sad',
                    'label' => '悲しみ',
                    'image' => $avatar->images()
                        ->where('image_type', 'full_body')
                        ->where('expression_type', 'sad')
                        ->first(),
                ],
                [
                    'type' => 'full_body',
                    'expression' => 'angry',
                    'label' => '怒り',
                    'image' => $avatar->images()
                        ->where('image_type', 'full_body')
                        ->where('expression_type', 'angry')
                        ->first(),
                ],
                [
                    'type' => 'full_body',
                    'expression' => 'surprised',
                    'label' => '驚き',
                    'image' => $avatar->images()
                        ->where('image_type', 'full_body')
                        ->where('expression_type', 'surprised')
                        ->first(),
                ],
            ];
        } else {
            // 通常キャラの場合はバストアップ画像を使用
            $expressions = [
                [
                    'type' => 'full_body',
                    'expression' => 'normal',
                    'label' => '全身',
                    'image' => $avatar->fullBodyImage,
                ],
                [
                    'type' => 'bust',
                    'expression' => 'normal',
                    'label' => '通常',
                    'image' => $avatar->bustImage,
                ],
                [
                    'type' => 'bust',
                    'expression' => 'happy',
                    'label' => '喜び',
                    'image' => $avatar->bustImageHappy,
                ],
                [
                    'type' => 'bust',
                    'expression' => 'sad',
                    'label' => '悲しみ',
                    'image' => $avatar->bustImageSad,
                ],
                [
                    'type' => 'bust',
                    'expression' => 'angry',
                    'label' => '怒り',
                    'image' => $avatar->bustImageAngry,
                ],
                [
                    'type' => 'bust',
                    'expression' => 'surprised',
                    'label' => '驚き',
                    'image' => $avatar->bustImageSurprised,
                ],
            ];
        }

        return $expressions;
    }
}