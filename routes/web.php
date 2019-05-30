<?php

use Ichynul\LaADuo\Http\Controllers\LaADuoController;

Route::get('la-a-duo', LaADuoController::class.'@index');

Route::post('la-a-duo/run', LaADuoController::class.'@run');