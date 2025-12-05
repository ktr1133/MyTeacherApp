<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Responders\Api\Token\TokenApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: トークンモード切替アクション
 * 
 * PATCH /api/v1/tokens/toggle-mode
 * 
 * @package App\Http\Actions\Api\Token
 */
class ToggleTokenModeApiAction
{
    /**
     * コンストラクタ
     *
     * @param TokenApiResponder $responder
     */
    public function __construct(
        protected TokenApiResponder $responder
    ) {}

    /**
     * トークンモード切替処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // グループ未所属の場合はエラー
            if (!$user->group_id) {
                return $this->responder->error('グループに所属していないため、モードを切り替えられません。', 400);
            }

            // モード切替
            $newMode = $user->token_mode === 'individual' ? 'group' : 'individual';
            $user->token_mode = $newMode;
            $user->save();

            return $this->responder->modeToggled($newMode);

        } catch (\Exception $e) {
            Log::error('トークンモード切替エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('トークンモードの切替に失敗しました。', 500);
        }
    }
}
