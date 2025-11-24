<?php

declare(strict_types=1);

namespace App\Http\Actions\Admin\Portal;

use App\Models\AppUpdate;
use App\Services\Portal\AppUpdateServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * アプリ更新履歴削除アクション
 */
final class DeleteAppUpdateAction
{
    public function __construct(
        private readonly AppUpdateServiceInterface $appUpdateService
    ) {}

    /**
     * アプリ更新履歴を削除
     *
     * @param AppUpdate $update
     * @return RedirectResponse
     */
    public function __invoke(AppUpdate $update): RedirectResponse
    {
        try {
            $this->appUpdateService->delete($update);

            return redirect()
                ->route('admin.portal.updates.index')
                ->with('success', 'アプリ更新履歴を削除しました。');
        } catch (\Exception $e) {
            Log::error('アプリ更新履歴削除エラー', [
                'update_id' => $update->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'アプリ更新履歴の削除に失敗しました。']);
        }
    }
}
