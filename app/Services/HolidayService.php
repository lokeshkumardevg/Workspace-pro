<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class HolidayService
{
    /**
     * Check if a date is a holiday.
     * Includes 2nd and 4th Saturdays and days in the holidays table.
     */
    public function isHoliday(Carbon $date): bool
    {
        // 1. Every Sunday is off
        if ($date->isSunday()) {
            return true;
        }

        // 2. 2nd and 4th Saturday is off
        if ($date->isSaturday()) {
            $saturdayNumber = ceil($date->day / 7);
            if ($saturdayNumber == 2 || $saturdayNumber == 4) {
                return true;
            }
        }

        // 3. Check official holidays table
        return Holiday::where('date', $date->toDateString())->exists();
    }

    /**
     * Get number of working days in a month.
     */
    public function getWorkingDaysCount(int $month, int $year): int
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;

        for ($date = $startDate; $date <= $endDate; $date->addDay()) {
            if (!$this->isHoliday($date)) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
}
