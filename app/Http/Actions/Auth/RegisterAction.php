<?php

namespace App\Http\Actions\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responders\Auth\RegisterResponder;
use App\Services\Profile\ProfileManagementServiceInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * ユーザー登録Action
 */
class RegisterAction
{
    /**
     * コンストラクタ
     */
    public function __construct(
        private ProfileManagementServiceInterface $profileService,
        private RegisterResponder $responder
    ) {}

    /**
     * 登録画面を表示
     *
     * @return View
     */
    public function create(): View
    {
        return $this->responder->create();
    }

    /**
     * ユーザー登録処理
     *
     * @param RegisterRequest $request
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // TODO: 登録一時停止中は404を返す
        // abort(404);
        try {
            // ユーザー作成
            $user = $this->profileService->createUser([
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'name' => $request->input('username'), // 表示名として使用
                'password' => Hash::make($request->input('password')),
                'timezone' => $request->input('timezone', 'Asia/Tokyo'),
            ]);

            // 登録イベント発火
            event(new Registered($user));

            // ログイン
            Auth::login($user);

            Log::info('New user registered', [
                'user_id' => $user->id,
                'username' => $user->username,
                'timezone' => $user->timezone,
            ]);

            return $this->responder->successRedirect();

        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responder->errorRedirect('アカウントの作成に失敗しました。もう一度お試しください。');
        }
    }
}
