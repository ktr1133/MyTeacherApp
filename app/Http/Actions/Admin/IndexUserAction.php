<?php

namespace App\Http\Actions\Admin;

use App\Responders\Admin\User\IndexUserResponder;
use App\Services\Admin\UserServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IndexUserAction
{
    public function __construct(
        private UserServiceInterface $userService,
        private IndexUserResponder $responder
    ) {}

    /**
     * ユーザー一覧を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $search = $request->input('search');
        
        $data = $this->userService->getUserListData($search);
        
        return $this->responder->respond($data);
    }
}