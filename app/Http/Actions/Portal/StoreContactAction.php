<?php

namespace App\Http\Actions\Portal;

use App\Http\Requests\Portal\StoreContactRequest;
use App\Services\Portal\ContactServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * お問い合わせ送信アクション
 */
class StoreContactAction
{
    public function __construct(
        private ContactServiceInterface $contactService
    ) {}

    /**
     * お問い合わせを送信
     *
     * @param StoreContactRequest $request
     * @return RedirectResponse
     */
    public function __invoke(StoreContactRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        // ログイン済みの場合はuser_idを追加
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        }
        
        $this->contactService->create($data);
        
        return redirect()
            ->route('portal.contact')
            ->with('success', 'お問い合わせを受け付けました。ご連絡ありがとうございます。');
    }
}
