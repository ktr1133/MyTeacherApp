<?php

namespace App\Http\Actions\Profile;

use App\Http\Requests\Profile\UpdateTimezoneRequest;
use Illuminate\Http\RedirectResponse;

class UpdateTimezoneAction
{
    /**
     * ユーザーのタイムゾーンを更新
     *
     * @param UpdateTimezoneRequest $request
     * @return RedirectResponse
     */
    public function __invoke(UpdateTimezoneRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        $user->update([
            'timezone' => $request->validated('timezone'),
        ]);
        
        return redirect()->back()->with('success', 'タイムゾーンを更新しました。');
    }
}
