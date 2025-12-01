<?php

namespace App\Http\Actions\Profile;

use App\Services\User\UserDeletionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * プロフィール（ユーザーアカウント）削除アクション
 * 
 * - グループマスターの場合: グループ全体とサブスクリプションを削除
 * - 通常ユーザーの場合: 自分のアカウントのみ削除
 * - パスワード確認必須
 * - ログアウトしてセッション無効化
 */
class DeleteProfileAction
{
    /**
     * コンストラクタ
     * 
     * @param UserDeletionServiceInterface $userDeletionService ユーザー削除サービス
     */
    public function __construct(
        protected UserDeletionServiceInterface $userDeletionService
    ) {}

    /**
     * アカウント削除実行
     * 
     * @param Request $request リクエスト
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        // パスワード確認
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        try {
            $isGroupMaster = $this->userDeletionService->isGroupMaster($user);

            if ($isGroupMaster && $request->has('delete_group')) {
                // グループマスターとしてグループ全体を削除
                $status = $this->userDeletionService->getGroupMasterStatus($user);
                Log::info('Group master initiating group deletion', [
                    'user_id' => $user->id,
                    'group_id' => $user->group->id,
                    'members_count' => $status['members_count'],
                    'has_subscription' => $status['has_subscription'],
                    'plan' => $status['plan'],
                ]);

                $this->userDeletionService->deleteGroupMasterAndGroup($user);
                $message = 'グループを削除しました。全メンバーのアカウントも削除されました。';

                if ($status['has_subscription']) {
                    $message .= 'サブスクリプションは即時解約されました。';
                }
            } else {
                // 通常ユーザー削除
                Log::info('User deletion initiated', [
                    'user_id' => $user->id,
                    'is_group_master' => $isGroupMaster,
                ]);

                $this->userDeletionService->deleteUser($user);
                $message = 'アカウントを削除しました。';
            }

            // セッション終了
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('success', $message);

        } catch (\RuntimeException $e) {
            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Unexpected error during user deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'error' => 'アカウント削除中にエラーが発生しました。',
            ]);
        }
    }
}