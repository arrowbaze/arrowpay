<?php

use Illuminate\Support\Facades\Route;
use Arrowpay\ArrowBaze\ArrowPay;

/*
|--------------------------------------------------------------------------
| ArrowBaze Routes
|--------------------------------------------------------------------------
| All ArrowPay routes are prefixed with "arrowbaze".
| 
*/

Route::prefix('arrowpay')->group(function () {

    // Initialize a payment
    Route::post('initialize', [ArrowPay::class, 'initializePayment'])
        ->name('arrowpay.initialize');

    // Payment return callback
    Route::get('return/{orderId}', function (string $orderId) {
        $service = app(ArrowPay::class);
        return $service->paymentReturn($orderId);
    })->name('arrowpay.return');

    // Payment cancel callback
    Route::get('cancel', function () {
        $service = app(ArrowPay::class);
        return $service->paymentCancel();
    })->name('arrowpay.cancel');

    // Payment notification callback
    Route::post('notify', function (Illuminate\Http\Request $request) {
        $service = app(ArrowPay::class);
        return $service->notify($request);
    })->name('arrowpay.notify');
});

