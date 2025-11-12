<?php

namespace App\Http\Actions\Avatar;

use App\Services\Avatar\TeacherAvatarServiceInterface;
use App\Responders\Avatar\TeacherAvatarResponder;
use Illuminate\Http\Request;

/**
 * 教師アバター保存アクション
 */
class StoreTeacherAvatarAction
{
    public function __construct(
        private TeacherAvatarServiceInterface $service,
        private TeacherAvatarResponder $responder
    ) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'sex' => 'required|in:male,female,other',
            'hair_color' => 'required|in:black,brown,blonde,silver,red',
            'eye_color' => 'required|in:brown,blue,green,gray,purple',
            'clothing' => 'required|in:suit,casual,kimono,robe,dress',
            'accessory' => 'nullable|in:glasses,hat,tie',
            'body_type' => 'required|in:average,slim,sturdy',
            'tone' => 'required|in:gentle,strict,friendly,intellectual',
            'enthusiasm' => 'required|in:high,normal,modest',
            'formality' => 'required|in:polite,casual,formal',
            'humor' => 'required|in:high,normal,low',
        ]);

        try {
            $this->service->createAvatar($request->user(), $validated);

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
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'アバター作成に失敗しました。しばらく時間をおいて再度お試しください。');
        }
    }
}