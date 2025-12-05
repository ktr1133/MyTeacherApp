<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
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

    /**
     * API版ログイン（モバイルアプリ用）
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function apiLogin(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        
        $user = Auth::user();
        
        // 最終ログイン日時更新
        $user->update(['last_login_at' => now()]);
        
        // Sanctumトークン発行（モバイルアプリ用）
        $token = $user->createToken('mobile-app', ['*'], now()->addDays(30))->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    /**
     * API版ログアウト（モバイルアプリ用）
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function apiLogout(Request $request): JsonResponse
    {
        // 現在のトークンを削除
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'ログアウトしました']);
    }
}