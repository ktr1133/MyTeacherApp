<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * 本人同意チェックミドルウェア
 * 
 * 13歳に到達した子ユーザーが本人同意を行っているかチェックし、
 * 未同意の場合は本人同意画面にリダイレクトします。
 * 
 * Phase 6D: 13歳到達時の本人再同意実装
 */
class CheckSelfConsentRequired
{
    /**
     * 本人同意チェックをスキップするルート
     * 
     * @var array<string>
     */
    protected array $exceptRoutes = [
        'legal.self-consent',           // 本人同意画面
        'legal.self-consent.submit',    // 本人同意送信
        'privacy-policy',               // プライバシーポリシー閲覧
        'terms-of-service',             // 利用規約閲覧
        'logout',                       // ログアウト
        'api.self-consent-status',      // API: 本人同意状態確認
        'api.self-consent',             // API: 本人同意送信
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 未認証ユーザーはスキップ
        if (!$user) {
            return $next($request);
        }

        // 除外ルートはスキップ
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // 本人同意が必要かチェック
        if ($user->needsSelfConsent()) {
            Log::info('Self consent required (13th birthday)', [
                'user_id' => $user->id,
                'birthdate' => $user->birthdate?->toDateString(),
                'age' => $user->birthdate?->age,
                'created_by' => $user->created_by_user_id,
                'consent_given_by' => $user->consent_given_by_user_id,
                'self_consented_at' => $user->self_consented_at,
            ]);

            // API リクエストの場合
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Self consent required',
                    'message' => 'あなたは13歳になりました。本人同意が必要です。',
                    'requires_self_consent' => true,
                    'age' => $user->birthdate?->age,
                ], 403);
            }

            // Web リクエストの場合
            return redirect()->route('legal.self-consent')
                ->with('info', 'あなたは13歳になりました。本人同意をお願いします。');
        }

        return $next($request);
    }

    /**
     * このリクエストでチェックをスキップすべきか判定
     * 
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request): bool
    {
        $currentRoute = $request->route()?->getName();
        
        if (!$currentRoute) {
            return false;
        }

        return in_array($currentRoute, $this->exceptRoutes, true);
    }
}
