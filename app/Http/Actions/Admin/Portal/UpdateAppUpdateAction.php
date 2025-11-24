<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Http\Requests\Admin\Portal\UpdateAppUpdateRequest;
use App\Models\AppUpdate;
use App\Services\Portal\AppUpdateServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * アプリ更新履歴更新アクション
 */
final class UpdateAppUpdateAction
{
    public function __construct(
        private readonly AppUpdateServiceInterface $appUpdateService
    ) {}

    /**
     * アプリ更新履歴を更新
     *
     * @param UpdateAppUpdateRequest $request
     * @param AppUpdate $update
     * @return RedirectResponse
     */
    public function __invoke(UpdateAppUpdateRequest $request, AppUpdate $update): RedirectResponse
    {
        try {
            $this->appUpdateService->update($update, $request->validated());

            return redirect()
                ->route('admin.portal.updates.index')
                ->with('success', 'アプリ更新履歴を更新しました。');
        } catch (\Exception $e) {
            Log::error('アプリ更新履歴更新エラー', [
                'update_id' => $update->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'アプリ更新履歴の更新に失敗しました。'])
                ->withInput();
        }
    }
}
