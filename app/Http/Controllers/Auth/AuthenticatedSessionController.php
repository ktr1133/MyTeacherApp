<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Carbon\Carbon;

class AuthenticatedSessionController extends Controller
{
    /**
     * ログイン後のリダイレクト先
     */
    public const HOME = '/dashboard';

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        
        // ログイン空白期間チェック
        $lastLoginAt = $user->last_login_at;
        $eventType = config('const.avatar_events.login');

        if ($lastLoginAt && Carbon::parse($lastLoginAt)->diffInDays(now()) >= 3) {
            $eventType = config('const.avatar_events.login_gap');
        }
        
        // 最終ログイン日時更新
        $user->update(['last_login_at' => now()]);

        // アバターがない場合はアバター作成画面へ（intended()より優先）
        if (!$user->teacherAvatar()->exists()) {
            // intended URLをクリア（アバター作成を優先）
            $request->session()->forget('url.intended');
            
            return redirect()->route('avatars.create');
        }

        return redirect()->intended(self::HOME)
            ->with('avatar_event', $eventType);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}