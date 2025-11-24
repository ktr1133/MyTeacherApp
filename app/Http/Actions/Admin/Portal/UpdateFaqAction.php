<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Http\Requests\Admin\Portal\UpdateFaqRequest;
use App\Models\Faq;
use App\Services\Portal\FaqServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * FAQ更新アクション
 */
final class UpdateFaqAction
{
    public function __construct(
        private readonly FaqServiceInterface $faqService
    ) {}

    /**
     * FAQを更新
     *
     * @param UpdateFaqRequest $request
     * @param Faq $faq
     * @return RedirectResponse
     */
    public function __invoke(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        try {
            $this->faqService->update($faq, $request->validated());

            return redirect()
                ->route('admin.portal.faqs.index')
                ->with('success', 'FAQを更新しました。');
        } catch (\Exception $e) {
            Log::error('FAQ更新エラー', [
                'faq_id' => $faq->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'FAQの更新に失敗しました。'])
                ->withInput();
        }
    }
}
