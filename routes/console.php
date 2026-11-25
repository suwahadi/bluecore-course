<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:expire')->everyTenMinutes()->withoutOverlapping(10);
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping(5);
