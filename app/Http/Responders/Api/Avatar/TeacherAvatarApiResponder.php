<?php

namespace App\Http\Responders\Api\Avatar;

use App\Models\TeacherAvatar;
use Illuminate\Http\JsonResponse;

/**
 * API: 教師アバターレスポンダ
 * 
 * アバター関連APIのJSONレスポンスを生成。
 * 
 * @package App\Http\Responders\Api\Avatar
 */
class TeacherAvatarApiResponder
{
    /**
     * アバター情報取得成功レスポンス
     *
     * @param TeacherAvatar $avatar
     * @return JsonResponse
     */
    public function show(TeacherAvatar $avatar): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'avatar' => $this->formatAvatarData($avatar),
            ],
        ], 200);
    }

    /**
     * アバター作成成功レスポンス
     *
     * @param TeacherAvatar $avatar
     * @return JsonResponse
     */
    public function created(TeacherAvatar $avatar): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'アバターの作成を開始しました。画像生成には数分かかります。',
            'data' => [
                'avatar' => $this->formatAvatarData($avatar),
            ],
        ], 201);
    }

    /**
     * アバター更新成功レスポンス
     *
     * @param TeacherAvatar $avatar
     * @return JsonResponse
     */
    public function updated(TeacherAvatar $avatar): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'アバター設定を更新しました。',
            'data' => [
                'avatar' => $this->formatAvatarData($avatar),
            ],
        ], 200);
    }

    /**
     * アバター再生成成功レスポンス
     *
     * @param TeacherAvatar $avatar
     * @return JsonResponse
     */
    public function regenerated(TeacherAvatar $avatar): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'アバター画像の再生成を開始しました。完了には数分かかります。',
            'data' => [
                'avatar' => $this->formatAvatarData($avatar),
            ],
        ], 200);
    }

    /**
     * アバター削除成功レスポンス
     *
     * @return JsonResponse
     */
    public function deleted(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'アバターを削除しました。',
        ], 200);
    }

    /**
     * アバター表示設定切替成功レスポンス
     *
     * @param TeacherAvatar $avatar
     * @return JsonResponse
     */
    public function visibilityToggled(TeacherAvatar $avatar): JsonResponse
    {
        $message = $avatar->is_visible 
            ? 'アバター表示をONにしました。'
            : 'アバター表示をOFFにしました。';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'avatar' => $this->formatAvatarData($avatar),
            ],
        ], 200);
    }

    /**
     * イベント向けコメント取得成功レスポンス
     *
     * @param string $comment
     * @param string|null $imageUrl
     * @return JsonResponse
     */
    public function comment(string $comment, ?string $imageUrl): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'comment' => $comment,
                'image_url' => $imageUrl,
            ],
        ], 200);
    }

    /**
     * エラーレスポンス
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function error(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * アバターデータをフォーマット
     *
     * @param TeacherAvatar $avatar
     * @return array
     */
    private function formatAvatarData(TeacherAvatar $avatar): array
    {
        return [
            'id' => $avatar->id,
            'user_id' => $avatar->user_id,
            'seed' => $avatar->seed,
            'sex' => $avatar->sex,
            'hair_color' => $avatar->hair_color,
            'hair_style' => $avatar->hair_style,
            'eye_color' => $avatar->eye_color,
            'clothing' => $avatar->clothing,
            'accessory' => $avatar->accessory,
            'body_type' => $avatar->body_type,
            'tone' => $avatar->tone,
            'enthusiasm' => $avatar->enthusiasm,
            'formality' => $avatar->formality,
            'humor' => $avatar->humor,
            'draw_model_version' => $avatar->draw_model_version,
            'is_transparent' => $avatar->is_transparent,
            'is_chibi' => $avatar->is_chibi,
            'estimated_token_usage' => $avatar->estimated_token_usage,
            'generation_status' => $avatar->generation_status,
            'last_generated_at' => $avatar->last_generated_at?->toIso8601String(),
            'is_visible' => $avatar->is_visible,
            'created_at' => $avatar->created_at->toIso8601String(),
            'updated_at' => $avatar->updated_at->toIso8601String(),
            'images' => $avatar->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_type' => $image->image_type,
                    'emotion' => $image->emotion,
                    'image_url' => $image->image_url,
                    'created_at' => $image->created_at->toIso8601String(),
                ];
            })->toArray(),
        ];
    }
}
