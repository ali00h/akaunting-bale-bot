<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'bale-bot' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('bale-bot', function () {
    Route::get('/', 'Main@index');
});
