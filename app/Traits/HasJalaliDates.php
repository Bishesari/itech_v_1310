<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasJalaliDates
{
    /**
     * گرفتن تاریخ شمسی از فیلد دلخواه مدل
     */
    public function getJalaliDate($field, $format = 'Y/m/d H:i l')
    {
        $date = $this->{$field};
        if (! $date) {
            return null;
        }
        // اطمینان از اینکه تاریخ Carbon است
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // تبدیل به timestamp
        $timestamp = $carbon->timestamp;

        // تبدیل timestamp میلادی به تاریخ شمسی
        return jdate($format, $timestamp, '', '', 'en');
    }

    // تاریخ شمسی برای created_at
    public function getJalaliCreatedAtAttribute()
    {
        return $this->getJalaliDate('created_at');
    }

    // تاریخ شمسی برای updated_at
    public function getJalaliUpdatedAtAttribute()
    {
        return $this->getJalaliDate('updated_at');
    }

    public function getJalaliStartDateTimeAttribute()
    {
        return $this->getJalaliDate('start_date_time');
    }

    public function getJalaliEndDateAttribute()
    {
        return $this->getJalaliDate('end_date');
    }

    public function getJalaliActiveUntilAttribute()
    {
        return $this->getJalaliDate('active_until');
    }
}
