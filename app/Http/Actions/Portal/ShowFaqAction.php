<?php

namespace App\Http\Actions\Portal;

use App\Services\Portal\FaqServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FAQ表示アクション
 */
class ShowFaqAction
{
    public function __construct(
        private FaqServiceInterface $faqService
    ) {}

    /**
     * FAQページを表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $category = $request->query('category');
        $appName = $request->query('app');
        $keyword = $request->query('q');
        
        if ($keyword) {
            $faqs = $this->faqService->search($keyword);
        } else {
            $faqs = $this->faqService->getAllPublished($category, $appName);
        }
        
        return view('portal.faq', [
            'faqs' => $faqs,
            'category' => $category,
            'appName' => $appName,
            'keyword' => $keyword,
        ]);
    }
}
