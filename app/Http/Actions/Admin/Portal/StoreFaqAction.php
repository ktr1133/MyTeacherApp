<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Http\Requests\Admin\Portal\StoreFaqRequest;
use App\Services\Portal\FaqServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * FAQ登録アクション
 */
final class StoreFaqAction
{
    public function __construct(
        private readonly FaqServiceInterface $faqService
    ) {}

    /**
     * FAQを登録
     *
     * @param StoreFaqRequest $request
     * @return RedirectResponse
     */
    public function __invoke(StoreFaqRequest $request): RedirectResponse
    {
        try {
            $this->faqService->create($request->validated());

            return redirect()
                ->route('admin.portal.faqs.index')
                ->with('success', 'FAQを登録しました。');
        } catch (\Exception $e) {
            Log::error('FAQ登録エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'FAQの登録に失敗しました。'])
                ->withInput();
        }
    }
}
