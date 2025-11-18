<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SetUserTheme
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $isChildTheme = $request->user()->useChildTheme();
            
            // ビュー全体でテーマを共有
            View::share('isChildTheme', $isChildTheme);
            View::share('userTheme', $isChildTheme ? 'child' : 'adult');
        }

        return $next($request);
    }
}