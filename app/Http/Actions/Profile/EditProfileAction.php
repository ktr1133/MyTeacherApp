<?php

namespace App\Http\Actions\Profile;

use App\Responders\Profile\ProfileResponder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditProfileAction
{
    /**
     * ProfileResponderを依存性注入 (DI) する。
     *
     * @param ProfileResponder $responder
     */
    public function __construct(
        private ProfileResponder $responder
    ) {}

    /**
     * プロフィール編集画面を表示する。
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        // リクエストから認証済みユーザーのモデルを取得する
        $user = $request->user();

        // レスポンダにユーザーデータを渡し、ビューを生成させる
        return $this->responder->respondView($user);
    }
}
