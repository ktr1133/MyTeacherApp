<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum認証デバッグミドルウェア
 * 
 * モバイルアプリの401エラー原因調査のため、
 * Sanctum認証プロセスを詳細にログ出力します。
 * 
 * @see Phase 2.B-5 Step 2: 通知機能実装
 */
class SanctumDebugMiddleware
{
    /**
     * リクエストを処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // デバッグログを無効化（必要に応じてコメントを外してください）
        /*
        $token = $request->bearerToken();
        
        // トークン情報をログ出力
        Log::info('[SanctumDebug] Request received', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'has_bearer_token' => !is_null($token),
            'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Sanctum認証チェック（auth:sanctumミドルウェアの前に実行）
        if ($token) {
            // トークンIDを抽出（例: "24|SC1zjDEIOAuRk9gYs..." → 24）
            $tokenId = explode('|', $token, 2)[0] ?? null;
            
            if ($tokenId && is_numeric($tokenId)) {
                // personal_access_tokensテーブルからトークン情報を取得
                $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
                
                if ($personalAccessToken) {
                    Log::info('[SanctumDebug] Token found in database', [
                        'token_id' => $tokenId,
                        'tokenable_id' => $personalAccessToken->tokenable_id,
                        'tokenable_type' => $personalAccessToken->tokenable_type,
                        'name' => $personalAccessToken->name,
                        'abilities' => $personalAccessToken->abilities,
                        'expires_at' => $personalAccessToken->expires_at,
                        'created_at' => $personalAccessToken->created_at,
                        'last_used_at' => $personalAccessToken->last_used_at,
                    ]);
                    
                    // ハッシュ検証
                    $tokenHash = hash('sha256', explode('|', $token, 2)[1] ?? '');
                    $isHashValid = hash_equals($personalAccessToken->token, $tokenHash);
                    
                    Log::info('[SanctumDebug] Token hash validation', [
                        'token_id' => $tokenId,
                        'is_hash_valid' => $isHashValid,
                    ]);
                } else {
                    Log::warning('[SanctumDebug] Token NOT found in database', [
                        'token_id' => $tokenId,
                        'url' => $request->fullUrl(),
                    ]);
                }
            }
        }
        */

        $response = $next($request);

        // 認証後のユーザー情報をログ出力（デバッグログ無効化）
        /*
        $user = $request->user();
        if ($user) {
            Log::info('[SanctumDebug] User authenticated', [
                'user_id' => $user->id,
                'username' => $user->username,
                'auth_provider' => $user->auth_provider ?? 'unknown',
            ]);
        } else {
            Log::warning('[SanctumDebug] User NOT authenticated', [
                'url' => $request->fullUrl(),
                'response_status' => $response->getStatusCode(),
            ]);
        }
        */

        return $response;
    }
}
