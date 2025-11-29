<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ALB/CloudFront経由のHTTPSリクエストを正しく認識
        // 本番環境のみプロキシを信頼（ローカル環境では無効化）
        if (env('TRUST_PROXIES', env('APP_ENV') === 'production')) {
            $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
        }
        
        $middleware->alias([
            'check.tokens' => \App\Http\Middleware\CheckTokenBalance::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.ip' => \App\Http\Middleware\AdminIpRestriction::class,
            'cognito' => \App\Http\Middleware\VerifyCognitoToken::class,
        ]);
        // ★ Web ミドルウェアグループに追加
        $middleware->web(append: [
            \App\Http\Middleware\AddRequestIdToLogs::class, // 冗長構成対応: リクエストトレーシング
            \App\Http\Middleware\SetUserTheme::class, // 子供むけ画面対応
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();