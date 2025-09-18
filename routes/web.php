<?php

use Illuminate\Support\Facades\Route;
use Arrowpay\ArrowBaze\ArrowBaze;

/*
|--------------------------------------------------------------------------
| ArrowBaze Routes
|--------------------------------------------------------------------------
| All ArrowPay routes are prefixed with "arrowbaze".
| 
*/

Route::prefix('arrowbaze')->group(function () {

    // Initialize a payment
    Route::post('initialize', [ArrowBaze::class, 'initializePayment'])
        ->name('arrowbaze.initialize');

    // Payment return callback
    Route::get('return/{orderId}', function (string $orderId) {
        $service = app(ArrowBaze::class);
        return $service->paymentReturn($orderId);
    })->name('arrowbaze.return');

    // Payment cancel callback
    Route::get('cancel', function () {
        $service = app(ArrowBaze::class);
        return $service->paymentCancel();
    })->name('arrowbaze.cancel');

    // Payment notification callback
    Route::post('notify', function (Illuminate\Http\Request $request) {
        $service = app(ArrowBaze::class);
        return $service->notify($request);
    })->name('arrowbaze.notify');
});

