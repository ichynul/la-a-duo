<?php

use Ichynul\LaADuo\Http\Controllers\LaADuoController;

Route::get('la-a-duo', LaADuoController::class.'@index');
Route::get('la-a-duo-ruote-tips', LaADuoController::class.'@ruoteTips');