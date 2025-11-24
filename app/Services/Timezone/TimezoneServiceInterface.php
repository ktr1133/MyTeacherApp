<?php

namespace App\Services\Timezone;

interface TimezoneServiceInterface
{
    /**
     * タイムゾーン一覧を取得（地域別グループ化）
     *
     * @return array
     */
    public function getTimezonesGroupedByRegion(): array;

    /**
     * タイムゾーン一覧を取得（フラット）
     *
     * @return array
     */
    public function getTimezones(): array;

    /**
     * タイムゾーンが有効かチェック
     *
     * @param string $timezone
     * @return bool
     */
    public function isValidTimezone(string $timezone): bool;

    /**
     * タイムゾーンの表示名を取得
     *
     * @param string $timezone
     * @return string
     */
    public function getTimezoneName(string $timezone): string;
}
