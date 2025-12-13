<?php

namespace App\Http\Actions\Api\User;

use App\Http\Responders\Api\User\UserApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: 現在のユーザー情報取得アクション
 * 
 * 認証済みユーザーの基本情報（テーマ含む）を取得
 * モバイルアプリのテーマシステムで使用
 * 
 * 責務: プロフィール編集画面とは異なり、最小限のユーザー情報のみ返却
 * パフォーマンス: プロフィール編集APIより軽量（必要な情報のみ取得）
 * 
 * @package App\Http\Actions\Api\User
 */
class GetCurrentUserApiAction
{
    /**
     * コンストラクタ
     *
     * @param UserApiResponder $responder
     */
    public function __construct(
        protected UserApiResponder $responder
    ) {}

    /**
     * 現在のユーザー情報を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return $this->responder->unauthorized();
            }

            // グループ情報をEager Loading
            $user->load('group');

            return $this->responder->currentUser($user);

        } catch (\Exception $e) {
            Log::error('ユーザー情報取得エラー', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('ユーザー情報の取得に失敗しました。');
        }
    }
}
