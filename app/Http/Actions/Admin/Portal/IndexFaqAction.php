<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Services\Portal\FaqServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FAQ一覧表示アクション
 */
final class IndexFaqAction
{
    public function __construct(
        private readonly FaqServiceInterface $faqService
    ) {}

    /**
     * FAQ一覧画面を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $filters = [
            'category' => $request->input('category'),
            'app_name' => $request->input('app_name'),
            'is_published' => $request->input('is_published'),
        ];

        $faqs = $this->faqService->paginateWithFilters($filters, 20);

        return view('admin.portal.faqs.index', compact('faqs', 'filters'));
    }
}
