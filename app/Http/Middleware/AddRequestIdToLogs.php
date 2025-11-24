<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * リクエストIDをログコンテキストに追加するミドルウェア
 * 
 * 冗長構成でのトレーシングを容易にするため、
 * 各リクエストに一意のIDを付与し、すべてのログに含めます。
 */
class AddRequestIdToLogs
{
    /**
     * リクエスト処理
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // リクエストIDを生成（またはヘッダーから取得）
        $requestId = $request->header('X-Request-ID') ?? (string) Str::uuid();
        
        // リクエストIDをログコンテキストに追加
        Log::withContext([
            'request_id' => $requestId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
        
        // レスポンスヘッダーにリクエストIDを含める
        $response = $next($request);
        
        if ($response instanceof Response) {
            $response->headers->set('X-Request-ID', $requestId);
        }
        
        return $response;
    }
}
