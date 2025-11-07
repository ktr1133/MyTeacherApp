<?php

namespace App\Repositories\Batch;

interface HolidayRepositoryInterface
{
    /**
     * 指定日が祝日かチェック
     *
     * @param \DateTime $date
     * @return bool
     */
    public function isHoliday(\DateTime $date): bool;

    /**
     * 次の営業日を取得
     *
     * @param \DateTime $date
     * @return \DateTime
     */
    public function getNextBusinessDay(\DateTime $date): \DateTime;

    /**
     * 年の祝日をキャッシュ
     *
     * @param int $year
     * @return void
     */
    public function cacheYearHolidays(int $year): void;
}