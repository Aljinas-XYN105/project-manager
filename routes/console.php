<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send standup reminders at 9:30 AM on weekdays to members who haven't submitted
Schedule::command('standups:send-reminders')->weekdays()->at('09:30');
