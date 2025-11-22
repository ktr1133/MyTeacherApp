<?php

namespace App\Http\Actions\Admin\Portal;

use App\Services\Portal\ContactServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * お問い合わせ一覧表示アクション
 */
class IndexContactAction
{
    public function __construct(
        private ContactServiceInterface $contactService
    ) {}

    /**
     * お問い合わせ一覧を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $filters = [
            'status' => $request->input('status'),
            'app_name' => $request->input('app_name'),
        ];

        $contacts = $this->contactService->paginateWithFilters($filters, 15);

        return view('admin.portal.contacts.index', [
            'contacts' => $contacts,
            'filters' => $filters,
        ]);
    }
}
