<?php

use Illuminate\Support\Facades\Schedule;

// El día 1 de cada mes se generan las cuotas del período (requiere `php artisan schedule:work` o cron)
Schedule::command('cuotas:generar')->monthlyOn(1, '03:00');
