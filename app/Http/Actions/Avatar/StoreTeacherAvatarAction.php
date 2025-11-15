<?php

namespace App\Http\Actions\Avatar;

use App\Http\Requests\Avatar\StoreTeacherAvatarRequest as Request;
use App\Services\Avatar\TeacherAvatarServiceInterface;
use App\Responders\Avatar\TeacherAvatarResponder;
use Illuminate\Http\RedirectResponse;

/**
 * 教師アバター保存アクション
 */
class StoreTeacherAvatarAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $teacherAvatarService,
        private TeacherAvatarResponder $responder
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        try {
            $this->teacherAvatarService->createAvatar($request->user(), $validated);

            return redirect()
                ->route('dashboard')
                ->with('success', '教師アバターの生成を開始しました。完成まで1〜2分お待ちください。');
                
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
                
        } catch (\Exception $e) {
            \Log::error('Avatar creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'アバター作成に失敗しました。しばらく時間をおいて再度お試しください。');
        }
    }
}