<?php

use Illuminate\Support\Facades\Route;

/**
 * 'guest' middleware applied to all routes
 *
 * @see \App\Providers\Route::mapGuestRoutes
 * @see \modules\PaypalStandard\Routes\guest.php for module example
 */


Route::group(['prefix' => 'bale-bot'], function () {
    Route::post('webhook/send-message', 'Modules\BaleBot\Http\Controllers\WebHook@send_message')->name('bale-bot-webhook-send-message');
    //Route::post('login', 'Auth\Login@store');
});
