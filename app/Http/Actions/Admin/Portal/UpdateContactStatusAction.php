<?php

namespace App\Http\Actions\Admin\Portal;

use App\Http\Requests\Admin\Portal\UpdateContactStatusRequest;
use App\Models\ContactSubmission;
use App\Services\Portal\ContactServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * お問い合わせステータス更新アクション
 */
class UpdateContactStatusAction
{
    public function __construct(
        private ContactServiceInterface $contactService
    ) {}

    /**
     * お問い合わせのステータスを更新
     *
     * @param UpdateContactStatusRequest $request
     * @param ContactSubmission $contact
     * @return RedirectResponse
     */
    public function __invoke(UpdateContactStatusRequest $request, ContactSubmission $contact): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            $this->contactService->updateStatusWithNote(
                $contact,
                $validated['status'],
                $validated['admin_note'] ?? null
            );

            return redirect()
                ->route('admin.portal.contacts.show', $contact)
                ->with('success', 'ステータスを更新しました。');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'ステータスの更新に失敗しました。']);
        }
    }
}
