<?php

namespace App\Services\Timezone;

class TimezoneService implements TimezoneServiceInterface
{
    /**
     * タイムゾーン一覧を取得（地域別グループ化）
     *
     * @return array
     */
    public function getTimezonesGroupedByRegion(): array
    {
        $timezones = config('const.timezones');
        $grouped = [];

        foreach ($timezones as $identifier => $data) {
            $region = $data['region'];
            
            if (!isset($grouped[$region])) {
                $grouped[$region] = [];
            }
            
            $grouped[$region][$identifier] = $data['name'] . ' (UTC' . $data['offset'] . ')';
        }

        return $grouped;
    }

    /**
     * タイムゾーン一覧を取得（フラット）
     *
     * @return array
     */
    public function getTimezones(): array
    {
        $timezones = config('const.timezones');
        $flat = [];

        foreach ($timezones as $identifier => $data) {
            $flat[$identifier] = $data['name'] . ' (UTC' . $data['offset'] . ')';
        }

        return $flat;
    }

    /**
     * タイムゾーンが有効かチェック
     *
     * @param string $timezone
     * @return bool
     */
    public function isValidTimezone(string $timezone): bool
    {
        return array_key_exists($timezone, config('const.timezones'));
    }

    /**
     * タイムゾーンの表示名を取得
     *
     * @param string $timezone
     * @return string
     */
    public function getTimezoneName(string $timezone): string
    {
        $timezones = config('const.timezones');
        
        if (!isset($timezones[$timezone])) {
            return $timezone;
        }
        
        $data = $timezones[$timezone];
        return $data['name'] . ' (UTC' . $data['offset'] . ')';
    }

    /**
     * ユーザーの現在時刻を取得（ローカライズ）
     *
     * @param \App\Models\User $user
     * @param string|null $format
     * @return string
     */
    public function getUserCurrentTime($user, ?string $format = null): string
    {
        $format = $format ?? 'Y-m-d H:i:s';
        return now()->timezone($user->timezone ?? 'Asia/Tokyo')->format($format);
    }
}
