<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('gold:sync-prices')->hourly()->withoutOverlapping()->onOneServer();
