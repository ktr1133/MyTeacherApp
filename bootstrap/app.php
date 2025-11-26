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
        // HTTPS強制（本番環境）
        // プロキシ信頼設定: CloudFront/ALBからのX-Forwarded-Protoヘッダーを信頼
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
        
        $middleware->alias([
            'check.tokens' => \App\Http\Middleware\CheckTokenBalance::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'cognito' => \App\Http\Middleware\VerifyCognitoToken::class, // Phase 1: Cognito JWT検証
            'dual.auth' => \App\Http\Middleware\DualAuthMiddleware::class, // Phase 1.5: Breeze + Cognito並行運用
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