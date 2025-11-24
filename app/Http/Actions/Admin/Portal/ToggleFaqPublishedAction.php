<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Models\Faq;
use App\Services\Portal\FaqServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * FAQ公開状態切り替えアクション
 */
final class ToggleFaqPublishedAction
{
    public function __construct(
        private readonly FaqServiceInterface $faqService
    ) {}

    /**
     * FAQ公開状態を切り替え
     *
     * @param Faq $faq
     * @return RedirectResponse
     */
    public function __invoke(Faq $faq): RedirectResponse
    {
        try {
            $this->faqService->togglePublished($faq);

            $status = $faq->is_published ? '非公開' : '公開';

            return redirect()
                ->back()
                ->with('success', "FAQを{$status}にしました。");
        } catch (\Exception $e) {
            Log::error('FAQ公開状態切り替えエラー', [
                'faq_id' => $faq->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'FAQ公開状態の切り替えに失敗しました。']);
        }
    }
}
