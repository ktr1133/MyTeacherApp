<?php

namespace App\Http\Actions\Admin\Portal;

use App\Models\ContactSubmission;
use App\Services\Portal\ContactServiceInterface;
use Illuminate\Contracts\View\View;

/**
 * お問い合わせ詳細表示アクション
 */
class ShowContactAction
{
    public function __construct(
        private ContactServiceInterface $contactService
    ) {}

    /**
     * お問い合わせ詳細を表示
     *
     * @param ContactSubmission $contact
     * @return View
     */
    public function __invoke(ContactSubmission $contact): View
    {
        return view('admin.portal.contacts.show', [
            'contact' => $contact,
        ]);
    }
}
