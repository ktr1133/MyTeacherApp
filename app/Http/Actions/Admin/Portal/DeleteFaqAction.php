<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Models\Faq;
use App\Services\Portal\FaqServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * FAQ削除アクション
 */
final class DeleteFaqAction
{
    public function __construct(
        private readonly FaqServiceInterface $faqService
    ) {}

    /**
     * FAQを削除
     *
     * @param Faq $faq
     * @return RedirectResponse
     */
    public function __invoke(Faq $faq): RedirectResponse
    {
        try {
            $this->faqService->delete($faq);

            return redirect()
                ->route('admin.portal.faqs.index')
                ->with('success', 'FAQを削除しました。');
        } catch (\Exception $e) {
            Log::error('FAQ削除エラー', [
                'faq_id' => $faq->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'FAQの削除に失敗しました。']);
        }
    }
}
