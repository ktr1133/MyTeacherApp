<?php

namespace App\Responders\Profile; // Namespace変更

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;

class ProfileResponder
{
    /**
     * プロフィール編集ビューを返す。
     *
     * @param User $user
     * @return View
     */
    public function respondView(User $user): View
    {
        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * プロフィール更新後のリダイレクトレスポンスを返す。
     *
     * @return RedirectResponse
     */
    public function respondUpdateSuccess(): RedirectResponse
    {
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * アカウント削除後のリダイレクトレスポンスを返す。
     *
     * @return RedirectResponse
     */
    public function respondAccountDeleted(): RedirectResponse
    {
        return Redirect::to('/');
    }
}
