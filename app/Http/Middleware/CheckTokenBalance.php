<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Token\TokenServiceInterface;

/**
 * トークン残高チェックミドルウェア
 * 
 * API呼び出し前にトークン残高を確認し、不足時はリダイレクトします。
 */
class CheckTokenBalance
{
    public function __construct(
        private TokenServiceInterface $tokenService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @param int $requiredTokens 必要なトークン数（省略可）
     */
    public function handle(Request $request, Closure $next, int $requiredTokens = 0): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // トークン残高取得
        $balance = $user->getOrCreateTokenBalance();

        // 残高が完全に枯渇している場合
        if ($balance->isDepleted()) {
            return redirect()
                ->route('tokens.purchase')
                ->with('error', 'トークンが不足しています。追加購入が必要です。');
        }

        // 必要量が指定されている場合はチェック
        if ($requiredTokens > 0 && !$this->tokenService->checkBalance($user, $requiredTokens)) {
            return redirect()
                ->route('tokens.purchase')
                ->with('error', "この機能を使用するには{$requiredTokens}トークンが必要ですが、残高が不足しています。");
        }

        return $next($request);
    }
}