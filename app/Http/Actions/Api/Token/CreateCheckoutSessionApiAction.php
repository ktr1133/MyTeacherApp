<?php

namespace App\Http\Actions\Api\Token;

use App\Http\Responders\Api\Token\TokenApiResponder;
use App\Services\Token\TokenPurchaseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API: Stripe Checkout Session作成アクション
 * 
 * POST /api/v1/tokens/create-checkout-session
 * 
 * @package App\Http\Actions\Api\Token
 */
class CreateCheckoutSessionApiAction
{
    /**
     * コンストラクタ
     *
     * @param TokenPurchaseServiceInterface $purchaseService
     * @param TokenApiResponder $responder
     */
    public function __construct(
        protected TokenPurchaseServiceInterface $purchaseService,
        protected TokenApiResponder $responder
    ) {}

    /**
     * Stripe Checkout Session作成処理
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validator = Validator::make($request->all(), [
                'package_id' => 'required|integer|exists:token_packages,id',
            ], [
                'package_id.required' => 'パッケージIDは必須です。',
                'package_id.integer' => 'パッケージIDは整数である必要があります。',
                'package_id.exists' => '指定されたパッケージが見つかりません。',
            ]);

            if ($validator->fails()) {
                return $this->responder->error(
                    $validator->errors()->first(),
                    422
                );
            }

            $user = $request->user();
            $packageId = $request->input('package_id');
            
            // TokenPackageモデルを取得
            $package = \App\Models\TokenPackage::find($packageId);
            
            if (!$package) {
                return $this->responder->error('指定されたトークンパッケージが見つかりません。', 422);
            }

            // Checkout Session作成
            $session = $this->purchaseService->createCheckoutSession($user, $package);

            Log::info('Checkout Session created successfully', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'session_id' => $session->id,
                'session_url' => $session->url,
            ]);

            return $this->responder->checkoutSession($session->id, $session->url);

        } catch (\RuntimeException $e) {
            Log::error('Checkout Session作成エラー', [
                'user_id' => $request->user()->id,
                'package_id' => $request->input('package_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error($e->getMessage(), 400);

        } catch (\Exception $e) {
            Log::error('Checkout Sessionシステムエラー', [
                'user_id' => $request->user()->id,
                'package_id' => $request->input('package_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->error('Checkout Sessionの作成に失敗しました。', 500);
        }
    }
}
