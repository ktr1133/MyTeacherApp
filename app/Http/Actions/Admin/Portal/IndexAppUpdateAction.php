<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Services\Portal\AppUpdateServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * アプリ更新履歴一覧表示アクション
 */
final class IndexAppUpdateAction
{
    public function __construct(
        private readonly AppUpdateServiceInterface $appUpdateService
    ) {}

    /**
     * アプリ更新履歴一覧画面を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $filters = [
            'app_name' => $request->input('app_name'),
            'is_major' => $request->input('is_major'),
            'released_from' => $request->input('released_from'),
            'released_to' => $request->input('released_to'),
        ];

        $updates = $this->appUpdateService->paginateWithFilters($filters, 20);

        return view('admin.portal.updates.index', compact('updates', 'filters'));
    }
}
