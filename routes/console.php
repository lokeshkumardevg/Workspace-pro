<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('payroll:generate')->monthlyOn(28, '23:50');
// In Laravel, to run strictly on the very last day of the month dynamically, we rely on the monthly() macro? No, monthlyOn expects day 1-31.
// A safe way for End Of Month is monthlyOn(28) or triggering a daily custom check:
Schedule::command('payroll:generate')->dailyAt('23:50')->when(function () {
    return \Carbon\Carbon::now()->endOfMonth()->isToday();
});
