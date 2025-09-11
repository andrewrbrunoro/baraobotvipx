<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('chat-member:listen')
    ->everyFifteenMinutes();

Schedule::command('app:remarketing-15-minutes')
    ->everyMinute();

Schedule::command('campaigns:execute')
    ->everyMinute();

Schedule::command('remarketings:execute')
    ->everyMinute();

// Schedule::command('app:before-time-out-command 3600')
//     ->everyFiveMinutes();

// Schedule::command('app:before-time-out-command 86400')
//     ->everyFiveMinutes();

// Schedule::command('bots:check-status')
//     ->everyFiveMinutes();

Schedule::command('orders:check-pending')
    ->everyMinute();
