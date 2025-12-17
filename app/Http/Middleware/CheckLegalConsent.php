<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * 法的同意チェックミドルウェア
 * 
 * ユーザーが最新のプライバシーポリシー・利用規約に同意しているか確認し、
 * 未同意の場合は再同意画面にリダイレクトします。
 * 
 * Phase 6C: 再同意プロセス実装
 */
class CheckLegalConsent
{
    /**
     * 再同意チェックをスキップするルート
     * 
     * @var array<string>
     */
    protected array $exceptRoutes = [
        'legal.reconsent',           // 再同意画面
        'legal.reconsent.submit',    // 再同意送信
        'privacy-policy',            // プライバシーポリシー閲覧
        'terms-of-service',          // 利用規約閲覧
        'logout',                    // ログアウト
        'api.consent-status',        // API: 同意状態確認
        'api.reconsent',             // API: 再同意送信
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

        // 再同意が必要かチェック
        if ($user->needsAnyLegalReconsent()) {
            Log::info('Legal reconsent required', [
                'user_id' => $user->id,
                'current_privacy_version' => $user->privacy_policy_version,
                'current_terms_version' => $user->terms_version,
                'required_privacy_version' => config('legal.current_versions.privacy_policy'),
                'required_terms_version' => config('legal.current_versions.terms_of_service'),
            ]);

            // API リクエストの場合は JSON レスポンス
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '最新のプライバシーポリシー・利用規約への同意が必要です。',
                    'requires_reconsent' => true,
                    'privacy_policy_version' => config('legal.current_versions.privacy_policy'),
                    'terms_version' => config('legal.current_versions.terms_of_service'),
                ], 403);
            }

            // Web リクエストの場合はリダイレクト
            return redirect()
                ->route('legal.reconsent')
                ->with('warning', '最新のプライバシーポリシー・利用規約への同意が必要です。');
        }

        return $next($request);
    }

    /**
     * ミドルウェアをスキップすべきかどうか判定
     * 
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function shouldSkip(Request $request): bool
    {
        // 現在のルート名を取得
        $currentRoute = $request->route()?->getName();

        if (!$currentRoute) {
            return false;
        }

        // 除外ルートに含まれるか確認
        return in_array($currentRoute, $this->exceptRoutes, true);
    }
}
