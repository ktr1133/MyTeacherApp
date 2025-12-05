<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Responders\Api\Token\TokenApiResponder;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API: トークンパッケージ一覧取得アクション
 * 
 * GET /api/v1/tokens/packages
 * 
 * @package App\Http\Actions\Api\Token
 */
class GetTokenPackagesApiAction
{
    /**
     * コンストラクタ
     *
     * @param TokenServiceInterface $tokenService
     * @param TokenApiResponder $responder
     */
    public function __construct(
        protected TokenServiceInterface $tokenService,
        protected TokenApiResponder $responder
    ) {}

    /**
     * トークンパッケージ一覧取得処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 利用可能なパッケージ一覧を取得
            $packages = $this->tokenService->getAvailablePackages();

            return $this->responder->packages($packages);

        } catch (\Exception $e) {
            Log::error('トークンパッケージ一覧取得エラー', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('トークンパッケージ一覧の取得に失敗しました。', 500);
        }
    }
}
