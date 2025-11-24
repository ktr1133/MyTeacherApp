<?php

namespace App\Http\Actions\Profile;

use App\Services\Timezone\TimezoneServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowTimezoneSettingAction
{
    public function __construct(
        protected TimezoneServiceInterface $timezoneService
    ) {}

    /**
     * タイムゾーン設定画面を表示
     *
     * @param Request $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        
        return view('profile.timezone', [
            'currentTimezone' => $this->timezoneService->getTimezoneName($user->timezone ?? 'Asia/Tokyo'),
            'timezonesGrouped' => $this->timezoneService->getTimezonesGroupedByRegion(),
        ]);
    }
}
