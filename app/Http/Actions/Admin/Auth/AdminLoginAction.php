<?php

namespace App\Http\Actions\Admin\Auth;

use App\Http\Requests\Admin\Auth\AdminLoginRequest;
use App\Services\Auth\LoginAttemptServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * 管理者ログインAction
 * 
 * Stripe要件対応:
 * - アカウントロック機能
 * - 5回のログイン失敗でロック
 * - ログイン試行履歴の記録
 */
class AdminLoginAction
{
    public function __construct(
        protected LoginAttemptServiceInterface $loginAttemptService
    ) {}

    /**
     * ログインフォーム表示
     */
    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * ログイン処理
     */
    public function __invoke(AdminLoginRequest $request): RedirectResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember');
        $ipAddress = $request->ip();

        // アカウントロック確認
        if ($this->loginAttemptService->isLocked($email)) {
            return back()->withErrors([
                'email' => 'このアカウントはロックされています。管理者に連絡してください。',
            ])->onlyInput('email');
        }

        // 疑わしいIPをブロック
        if ($this->loginAttemptService->isSuspiciousIp($ipAddress)) {
            Log::warning('Login blocked: Suspicious IP', [
                'email' => $email,
                'ip' => $ipAddress,
            ]);

            return back()->withErrors([
                'email' => 'アクセスが制限されています。しばらく経ってから再試行してください。',
            ])->onlyInput('email');
        }

        // 認証試行
        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $user = Auth::user();

            // 管理者権限チェック
            if (!$user->is_admin) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $this->loginAttemptService->recordAttempt(
                    $email,
                    $ipAddress,
                    false,
                    '管理者権限なし'
                );

                return back()->withErrors([
                    'email' => '管理者権限がありません。',
                ])->onlyInput('email');
            }

            // ログイン成功処理
            $request->session()->regenerate();
            $this->loginAttemptService->handleSuccessfulLogin($user);

            Log::info('Admin login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $ipAddress,
            ]);

            return redirect()->intended('/admin/dashboard');
        }

        // ログイン失敗
        $this->loginAttemptService->recordAttempt(
            $email,
            $ipAddress,
            false,
            '認証情報が正しくありません'
        );

        $failedAttempts = $this->loginAttemptService->getFailedAttempts($email);
        $remainingAttempts = config('admin.max_login_attempts', 5) - $failedAttempts;

        if ($remainingAttempts > 0) {
            return back()->withErrors([
                'email' => sprintf(
                    'メールアドレスまたはパスワードが正しくありません。（残り試行回数: %d回）',
                    $remainingAttempts
                ),
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'アカウントがロックされました。管理者に連絡してください。',
        ])->onlyInput('email');
    }
}
