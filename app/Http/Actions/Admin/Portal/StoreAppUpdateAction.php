<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Http\Requests\Admin\Portal\StoreAppUpdateRequest;
use App\Services\Portal\AppUpdateServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * アプリ更新履歴登録アクション
 */
final class StoreAppUpdateAction
{
    public function __construct(
        private readonly AppUpdateServiceInterface $appUpdateService
    ) {}

    /**
     * アプリ更新履歴を登録
     *
     * @param StoreAppUpdateRequest $request
     * @return RedirectResponse
     */
    public function __invoke(StoreAppUpdateRequest $request): RedirectResponse
    {
        try {
            $this->appUpdateService->create($request->validated());

            return redirect()
                ->route('admin.portal.updates.index')
                ->with('success', 'アプリ更新履歴を登録しました。');
        } catch (\Exception $e) {
            Log::error('アプリ更新履歴登録エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'アプリ更新履歴の登録に失敗しました。'])
                ->withInput();
        }
    }
}
