<?php


use Illuminate\Support\Facades\Route;
use Arrowpay\ArrowBaze\Http\Controllers\WebpayController;


Route::post(config('arrowbaze.routes.notify'), [WebpayController::class, 'notify'])->name('arrowbaze.notify');
Route::get(config('arrowbaze.routes.return'), [WebpayController::class, 'paymentReturn'])->name('arrowbaze.return');
Route::get(config('arrowbaze.routes.cancel'), [WebpayController::class, 'paymentCancel'])->name('arrowbaze.cancel');
Route::post('/arrowbaze/initialize', [WebpayController::class, 'initialize'])->name('arrowbaze.initialize');
